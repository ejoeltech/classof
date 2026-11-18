<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Response;
use App\Models\CoopDividendAllocation;
use App\Models\CoopFine;
use App\Models\CoopLoan;
use App\Models\CoopLoanGuarantor;
use App\Models\CoopMember;
use App\Models\CoopMeeting;
use App\Models\CoopTransaction;
use App\Models\Cooperative;
use App\Services\CoopService;

final class CoopController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->coopContext($slug);

        if ($groupMembership === null || $groupMembership['status'] !== 'active') {
            $this->flashError('Join the alumni group before joining its cooperative.');
            $this->redirect('groups/' . $slug);
        }

        if ($coop === null) {
            $this->view('coop/index', [
                'group' => $group,
                'coop' => null,
                'groupMembership' => $groupMembership,
                'coopMember' => null,
                'canManage' => Guard::canManage($groupMembership),
            ]);

            return;
        }

        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);
        $isMember = $coopMember !== null && $coopMember['status'] === 'active';

        if (!$isOfficer && !$isMember) {
            $this->view('coop/index', [
                'group' => $group,
                'coop' => $coop,
                'groupMembership' => $groupMembership,
                'coopMember' => $coopMember,
                'canManage' => false,
            ]);

            return;
        }

        $summary = Cooperative::summary((int) $coop['id']);
        $activeLoan = $isMember ? CoopLoan::activeForMember((int) $coopMember['id']) : null;
        $nextMeeting = \App\Core\Database::fetch(
            'SELECT * FROM coop_meetings WHERE cooperative_id = ? AND status = "scheduled" ORDER BY scheduled_at ASC LIMIT 1',
            [(int) $coop['id']]
        );

        $this->view('coop/index', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'coopMember' => $coopMember,
            'canManage' => $isOfficer,
            'isMember' => $isMember,
            'summary' => $summary,
            'savingsBalance' => $isMember ? CoopMember::savingsBalance((int) $coop['id'], (int) $coopMember['id']) : 0,
            'shareCapital' => $isMember ? CoopMember::shareCapital((int) $coop['id'], (int) $coopMember['id']) : 0,
            'finesUnpaid' => $isMember ? CoopFine::unpaidTotal((int) $coop['id'], (int) $coopMember['id']) : 0,
            'activeLoan' => $activeLoan,
            'transactions' => CoopTransaction::forCoop((int) $coop['id'], [], 8),
            'nextMeeting' => $nextMeeting,
            'pendingGuarantees' => $isMember ? CoopLoanGuarantor::pendingForMember((int) $coopMember['id']) : [],
            'guarantees' => $isMember ? CoopLoanGuarantor::pendingForMember((int) $coopMember['id']) : [],
            'pendingMembers' => $isOfficer ? (int) \App\Core\Database::value(
                'SELECT COUNT(*) FROM coop_members WHERE cooperative_id = ? AND status = "pending"',
                [(int) $coop['id']],
                0
            ) : 0,
        ]);
    }

    public function enable(string $slug): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);

        if (Cooperative::forGroup((int) $group['id']) !== null) {
            $this->flashSuccess('This group already has a cooperative.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        $data = $this->validate([
            'name' => 'required|min:3|max:160',
            'registration_no' => 'max:60',
            'share_value' => 'required|numeric|gt:0',
            'monthly_savings' => 'required|numeric|gte:0',
            'loan_interest_rate' => 'required|numeric|gte:0',
            'loan_duration_months' => 'required|integer|gte:1',
            'max_loan_multiple' => 'required|numeric|gt:0',
            'guarantors_required' => 'required|integer|gte:0',
            'fine_absent' => 'required|numeric|gte:0',
            'meeting_frequency' => 'max:60',
        ]);

        $coopId = Cooperative::create([
            'group_id' => (int) $group['id'],
            'name' => $data['name'],
            'registration_no' => $data['registration_no'] ?: null,
            'share_value' => (float) $data['share_value'],
            'monthly_savings' => (float) $data['monthly_savings'],
            'loan_interest_rate' => (float) $data['loan_interest_rate'],
            'loan_duration_months' => (int) $data['loan_duration_months'],
            'max_loan_multiple' => (float) $data['max_loan_multiple'],
            'guarantors_required' => (int) $data['guarantors_required'],
            'fine_absent' => (float) $data['fine_absent'],
            'meeting_frequency' => $data['meeting_frequency'] ?: 'Monthly',
            'created_by' => (int) user()['id'],
        ]);

        CoopMember::create([
            'cooperative_id' => $coopId,
            'user_id' => (int) user()['id'],
            'role' => 'coordinator',
            'status' => 'active',
            'approved_by' => (int) user()['id'],
        ]);

        $this->audit((int) $group['id'], 'coop.enabled', 'Enabled the cooperative "' . $data['name'] . '"', 'cooperative', $coopId);
        $this->flashSuccess('Cooperative created. Invite members to enrol.');
        $this->redirect('groups/' . $slug . '/coop');
    }

    public function settings(string $slug): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoopOfficer($slug);

        $this->view('coop/settings', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'coopMember' => $coopMember,
        ]);
    }

    public function updateSettings(string $slug): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);

        $data = $this->validate([
            'name' => 'required|min:3|max:160',
            'registration_no' => 'max:60',
            'share_value' => 'required|numeric|gt:0',
            'monthly_savings' => 'required|numeric|gte:0',
            'loan_interest_rate' => 'required|numeric|gte:0',
            'loan_duration_months' => 'required|integer|gte:1',
            'max_loan_multiple' => 'required|numeric|gt:0',
            'guarantors_required' => 'required|integer|gte:0',
            'fine_absent' => 'required|numeric|gte:0',
            'meeting_frequency' => 'max:60',
            'status' => 'required|in:active,suspended',
        ]);

        Cooperative::update((int) $coop['id'], [
            'name' => $data['name'],
            'registration_no' => $data['registration_no'] ?: null,
            'share_value' => (float) $data['share_value'],
            'monthly_savings' => (float) $data['monthly_savings'],
            'loan_interest_rate' => (float) $data['loan_interest_rate'],
            'loan_duration_months' => (int) $data['loan_duration_months'],
            'max_loan_multiple' => (float) $data['max_loan_multiple'],
            'guarantors_required' => (int) $data['guarantors_required'],
            'fine_absent' => (float) $data['fine_absent'],
            'meeting_frequency' => $data['meeting_frequency'] ?: 'Monthly',
            'status' => $data['status'],
        ]);

        $this->audit((int) $group['id'], 'coop.settings_updated', 'Updated cooperative settings', 'cooperative', (int) $coop['id']);
        $this->flashSuccess('Cooperative settings saved.');
        $this->redirect('groups/' . $slug . '/coop/settings');
    }

    public function members(string $slug): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);
        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);

        if (!$isOfficer && ($coopMember === null || $coopMember['status'] !== 'active')) {
            $this->flashError('Only active co-op members can view the member list.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        $filters = [
            'search' => trim((string) Request::input('q', '')),
            'status' => (string) Request::input('status', ''),
            'role' => (string) Request::input('role', ''),
        ];

        $this->view('coop/members', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'coopMember' => $coopMember,
            'isOfficer' => $isOfficer,
            'members' => CoopMember::list((int) $coop['id'], $filters),
            'filters' => $filters,
            'roles' => Cooperative::ROLE_LABELS,
            'pendingCount' => (int) \App\Core\Database::value(
                'SELECT COUNT(*) FROM coop_members WHERE cooperative_id = ? AND status = "pending"',
                [(int) $coop['id']],
                0
            ),
        ]);
    }

    public function join(string $slug): void
    {
        [$group] = $this->groupContext($slug);
        $coop = Cooperative::forGroup((int) $group['id']);

        if ($coop === null || $coop['status'] !== 'active') {
            $this->flashError('The cooperative is not accepting members right now.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        $userId = (int) user()['id'];
        $coopMember = CoopMember::forUser((int) $coop['id'], $userId);

        if ($coopMember !== null && in_array($coopMember['status'], ['active', 'pending'], true)) {
            $this->flashSuccess('You already have an active or pending co-op membership.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        if ($coopMember !== null && $coopMember['status'] === 'suspended') {
            $this->flashError('Your co-op membership is suspended. Contact a co-op officer.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        if ($coopMember !== null) {
            \App\Core\Database::update('coop_members', [
                'status' => 'pending',
                'role' => 'member',
                'reviewed_at' => null,
                'rejection_reason' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $coopMember['id']]);
        } else {
            CoopMember::create([
                'cooperative_id' => (int) $coop['id'],
                'user_id' => $userId,
                'role' => 'member',
                'status' => 'pending',
            ]);
        }

        $this->audit((int) $group['id'], 'coop.join_requested', 'Requested to join the cooperative', 'cooperative', (int) $coop['id']);
        $this->flashSuccess('Your co-op membership request has been submitted for approval.');
        $this->redirect('groups/' . $slug . '/coop');
    }

    public function approveMember(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $target = $this->findCoopMember((int) $coop['id'], (int) $id);

        CoopMember::approve((int) $target['id'], (int) user()['id']);
        $this->audit((int) $group['id'], 'coop.member_approved', 'Approved ' . $target['name'] . ' into the cooperative', 'coop_member', (int) $target['id']);
        $this->flashSuccess($target['name'] . ' is now an active co-op member.');
        $this->redirect('groups/' . $slug . '/coop/members');
    }

    public function rejectMember(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $target = $this->findCoopMember((int) $coop['id'], (int) $id);
        $reason = Request::string('rejection_reason');

        CoopMember::reject((int) $target['id'], (int) user()['id'], $reason !== '' ? mb_substr($reason, 0, 255) : null);
        $this->audit((int) $group['id'], 'coop.member_rejected', 'Rejected ' . $target['name'] . "'s co-op request", 'coop_member', (int) $target['id']);
        $this->flashSuccess('Co-op membership request rejected.');
        $this->redirect('groups/' . $slug . '/coop/members');
    }

    public function setMemberRole(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $target = $this->findCoopMember((int) $coop['id'], (int) $id);
        $role = Request::string('role');

        if (!isset(Cooperative::ROLE_LABELS[$role])) {
            $this->flashError('Choose a valid co-op role.');
            $this->redirect('groups/' . $slug . '/coop/members');
        }

        if ($target['role'] === 'coordinator' && $role !== 'coordinator' && $this->otherCoordinators((int) $coop['id'], (int) $target['id']) === 0) {
            $this->flashError('Appoint another coordinator before changing the last one.');
            $this->redirect('groups/' . $slug . '/coop/members');
        }

        CoopMember::setRole((int) $target['id'], $role);
        $this->audit((int) $group['id'], 'coop.role_changed', 'Changed ' . $target['name'] . "'s co-op role to " . Cooperative::roleLabel($role), 'coop_member', (int) $target['id']);
        $this->flashSuccess($target['name'] . ' is now ' . strtolower(Cooperative::roleLabel($role)) . '.');
        $this->redirect('groups/' . $slug . '/coop/members');
    }

    public function removeMember(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $target = $this->findCoopMember((int) $coop['id'], (int) $id);

        if ((int) $target['user_id'] === (int) user()['id']) {
            $this->flashError('Use a different officer to remove your own membership.');
            $this->redirect('groups/' . $slug . '/coop/members');
        }

        if ($target['role'] === 'coordinator' && $this->otherCoordinators((int) $coop['id'], (int) $target['id']) === 0) {
            $this->flashError('You cannot remove the last coordinator.');
            $this->redirect('groups/' . $slug . '/coop/members');
        }

        CoopMember::setStatus((int) $target['id'], 'exited');
        $this->audit((int) $group['id'], 'coop.member_removed', 'Removed ' . $target['name'] . ' from the cooperative', 'coop_member', (int) $target['id']);
        $this->flashSuccess($target['name'] . ' has been marked as exited.');
        $this->redirect('groups/' . $slug . '/coop/members');
    }

    public function me(string $slug): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoopMember($slug);

        $this->view('coop/me', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'coopMember' => $coopMember,
            'savings' => CoopMember::savingsBalance((int) $coop['id'], (int) $coopMember['id']),
            'shareCapital' => CoopMember::shareCapital((int) $coop['id'], (int) $coopMember['id']),
            'loans' => CoopLoan::forMember((int) $coopMember['id']),
            'transactions' => CoopTransaction::forMember((int) $coop['id'], (int) $coopMember['id'], 80),
            'fines' => CoopFine::forMember((int) $coopMember['id']),
            'dividends' => CoopDividendAllocation::forMember((int) $coopMember['id']),
            'guarantees' => CoopLoanGuarantor::pendingForMember((int) $coopMember['id']),
            'activeLoan' => CoopLoan::activeForMember((int) $coopMember['id']),
        ]);
    }

    public function exportStatement(string $slug): void
    {
        [$group, $coop, , $coopMember] = $this->requireCoopMember($slug);

        $transactions = CoopTransaction::forMember((int) $coop['id'], (int) $coopMember['id'], 2000);
        $filename = 'classof-coop-statement-' . ($coopMember['member_no'] ?: $coopMember['id']) . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if ($output === false) {
            return;
        }

        fputcsv($output, ['Member', $coopMember['name'] ?? '']);
        fputcsv($output, ['Member number', $coopMember['member_no'] ?? '']);
        fputcsv($output, ['Cooperative', $coop['name']]);
        fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['Date', 'Type', 'Direction', 'Amount', 'Method', 'Reference', 'Note']);

        foreach ($transactions as $transaction) {
            fputcsv($output, [
                $transaction['occurred_at'],
                CoopTransaction::typeLabel($transaction['type']),
                $transaction['direction'] === 'in' ? 'In' : 'Out',
                number_format((float) $transaction['amount'], 2, '.', ''),
                $transaction['method'],
                $transaction['reference'],
                $transaction['note'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    public function transactions(string $slug): void
    {
        [$group, $coop, $groupMembership] = $this->requireCoopOfficer($slug);

        $filters = [
            'type' => (string) Request::input('type', ''),
            'member_id' => (int) Request::input('member_id', 0),
            'search' => trim((string) Request::input('q', '')),
        ];

        $this->view('coop/transactions', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'transactions' => CoopTransaction::forCoop((int) $coop['id'], array_filter($filters), 300),
            'filters' => $filters,
            'types' => CoopTransaction::TYPES,
            'members' => CoopMember::active((int) $coop['id']),
            'summary' => Cooperative::summary((int) $coop['id']),
            'pendingPayments' => \App\Core\Database::fetchAll(
                'SELECT p.*, u.name AS member_name, u.avatar
                 FROM payments p
                 LEFT JOIN users u ON u.id = p.user_id
                 WHERE p.group_id = ? AND p.status = "pending" AND p.purpose LIKE "coop_%"
                 ORDER BY p.created_at DESC',
                [(int) $group['id']]
            ),
        ]);
    }

    public function recordTransaction(string $slug): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);

        $data = $this->validate([
            'member_id' => 'required|integer',
            'type' => 'required|in:savings_deposit,savings_withdrawal,share_purchase,adjustment',
            'amount' => 'required|numeric|gt:0',
            'method' => 'required|in:cash,bank,gateway',
            'direction' => 'in:in,out',
            'note' => 'max:255',
        ]);

        $member = CoopMember::find((int) $data['member_id']);

        if ($member === null || (int) $member['cooperative_id'] !== (int) $coop['id'] || $member['status'] !== 'active') {
            $this->fail(['member_id' => 'Choose an active co-op member.']);
        }

        try {
            if ($data['type'] === 'share_purchase') {
                $shareValue = (float) $coop['share_value'];
                $units = (float) $data['amount'] / max(0.01, $shareValue);

                if (abs($units - round($units)) > 0.01) {
                    throw new \RuntimeException('Share purchases must be a multiple of ' . money($shareValue, $group['currency']) . '.');
                }
            }

            if ($data['type'] === 'savings_deposit') {
                CoopService::recordSavings((int) $coop['id'], (int) $member['id'], (float) $data['amount'], 'in', (string) $data['method'], (int) user()['id'], $data['note'] ?: 'Manual savings deposit');
            } elseif ($data['type'] === 'savings_withdrawal') {
                CoopService::recordSavings((int) $coop['id'], (int) $member['id'], (float) $data['amount'], 'out', (string) $data['method'], (int) user()['id'], $data['note'] ?: 'Savings withdrawal');
            } elseif ($data['type'] === 'share_purchase') {
                CoopService::purchaseShares((int) $coop['id'], (int) $member['id'], (float) $data['amount'], (float) $coop['share_value'], (string) $data['method'], (int) user()['id'], $data['note'] ?: 'Share purchase');
            } else {
                CoopTransaction::record([
                    'cooperative_id' => (int) $coop['id'],
                    'member_id' => (int) $member['id'],
                    'type' => 'adjustment',
                    'direction' => $data['direction'] ?: 'in',
                    'amount' => (float) $data['amount'],
                    'method' => (string) $data['method'],
                    'recorded_by' => (int) user()['id'],
                    'note' => $data['note'] ?: 'Manual adjustment',
                ]);
            }
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('groups/' . $slug . '/coop/transactions');
        }

        $this->audit((int) $group['id'], 'coop.transaction_recorded', CoopTransaction::typeLabel((string) $data['type']) . ' for ' . $member['name'], 'coop_transaction', null);
        $this->flashSuccess('Co-op transaction recorded.');
        $this->redirect('groups/' . $slug . '/coop/transactions');
    }

    public function exportTransactions(string $slug): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);

        $transactions = CoopTransaction::forCoop((int) $coop['id'], [], 2000);
        $filename = 'classof-coop-' . $group['slug'] . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if ($output === false) {
            return;
        }

        fputcsv($output, ['Date', 'Member', 'Type', 'Direction', 'Amount', 'Method', 'Reference', 'Note']);

        foreach ($transactions as $transaction) {
            fputcsv($output, [
                $transaction['occurred_at'],
                $transaction['member_name'] ?? 'Co-op',
                CoopTransaction::typeLabel($transaction['type']),
                $transaction['direction'] === 'in' ? 'In' : 'Out',
                number_format((float) $transaction['amount'], 2, '.', ''),
                $transaction['method'],
                $transaction['reference'],
                $transaction['note'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /** @return array<string, mixed> */
    private function findCoopMember(int $cooperativeId, int $memberId): array
    {
        $member = CoopMember::find($memberId);

        if ($member === null || (int) $member['cooperative_id'] !== $cooperativeId) {
            Response::abort(404, 'That co-op member could not be found.');
        }

        return $member;
    }

    private function otherCoordinators(int $cooperativeId, int $excludeId): int
    {
        return (int) \App\Core\Database::value(
            'SELECT COUNT(*) FROM coop_members WHERE cooperative_id = ? AND role = "coordinator" AND status = "active" AND id <> ?',
            [$cooperativeId, $excludeId],
            0
        );
    }
}
