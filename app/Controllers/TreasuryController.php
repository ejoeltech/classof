<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Guard;
use App\Core\Request;
use App\Models\DuesCycle;
use App\Models\Group;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentService;

final class TreasuryController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug, ['owner', 'admin']);

        $filters = [
            'purpose' => (string) Request::input('purpose', ''),
            'status' => (string) Request::input('status', ''),
            'search' => trim((string) Request::input('q', '')),
        ];

        $payments = Payment::forGroup((int) $group['id'], array_filter($filters), 300);

        $this->view('treasury/index', [
            'group' => $group,
            'membership' => $membership,
            'payments' => $payments,
            'filters' => $filters,
            'summary' => Payment::summary((int) $group['id']),
            'treasury' => Group::treasury((int) $group['id']),
            'purposes' => Payment::PURPOSES,
            'pendingCount' => Payment::pendingCount((int) $group['id']),
            'members' => Membership::list((int) $group['id'], ['status' => 'active']),
            'openDues' => array_values(array_filter(
                DuesCycle::forGroup((int) $group['id']),
                static fn (array $cycle): bool => $cycle['status'] === 'open'
            )),
        ]);
    }

    public function recordCash(string $slug): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);

        $data = $this->validate([
            'member_id' => 'required|integer',
            'purpose' => 'required|in:dues,donation',
            'dues_cycle_id' => 'integer',
            'amount' => 'numeric',
            'note' => 'max:255',
        ]);

        $memberId = (int) $data['member_id'];
        $member = User::find($memberId);
        $groupMember = Membership::forUser((int) $group['id'], $memberId);

        if ($member === null || $groupMember === null || $groupMember['status'] !== 'active') {
            $this->fail(['member_id' => 'Choose an active member of this group.']);
        }

        $purpose = (string) $data['purpose'];
        $amount = 0.0;
        $duesCycleId = null;
        $title = 'Cash received';

        if ($purpose === 'dues') {
            $cycle = DuesCycle::find((int) ($data['dues_cycle_id'] ?? 0));

            if ($cycle === null || (int) $cycle['group_id'] !== (int) $group['id'] || $cycle['status'] !== 'open') {
                $this->fail(['dues_cycle_id' => 'Choose an open dues cycle for this group.']);
            }

            if (Payment::successFor('dues', (int) $cycle['id'], $memberId) !== null) {
                $this->fail(['member_id' => 'That member has already paid this dues cycle.']);
            }

            $amount = (float) $cycle['amount'];
            $duesCycleId = (int) $cycle['id'];
            $title = $cycle['title'];
        } else {
            $amount = (float) ($data['amount'] ?? 0);

            if ($amount < 1) {
                $this->fail(['amount' => 'Enter the amount received.']);
            }

            $title = trim((string) ($data['note'] ?? '')) ?: 'Cash donation';
        }

        $payment = Payment::create([
            'group_id' => (int) $group['id'],
            'user_id' => $memberId,
            'purpose' => $purpose,
            'dues_cycle_id' => $duesCycleId,
            'gateway' => 'manual',
            'amount' => round($amount, 2),
            'currency' => $group['currency'],
            'payer_name' => $member['name'],
            'payer_email' => $member['email'],
            'meta' => [
                'title' => $title,
                'cash' => true,
                'recorded_by' => (int) user()['id'],
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
            ],
        ]);

        PaymentService::complete($payment, ['cash' => true]);
        Payment::confirmManual((int) $payment['id'], (int) user()['id']);

        $this->audit(
            (int) $group['id'],
            'payment.cash_recorded',
            'Recorded cash ' . money($amount, $group['currency']) . ' from ' . $member['name'],
            'payment',
            (int) $payment['id']
        );

        $this->flashSuccess('Cash payment recorded and added to the treasury.');
        $this->redirect('groups/' . $slug . '/treasury');
    }

    public function export(string $slug): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);

        $payments = Payment::forGroup((int) $group['id'], [], 1000);
        $filename = 'classof-' . $group['slug'] . '-payments-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if ($output === false) {
            return;
        }

        fputcsv($output, ['Reference', 'Date', 'Member', 'Purpose', 'Amount', 'Currency', 'Status', 'Gateway', 'Paid at']);

        foreach ($payments as $payment) {
            fputcsv($output, [
                $payment['reference'],
                $payment['created_at'],
                $payment['user_name'] ?? '—',
                Payment::PURPOSES[$payment['purpose']] ?? $payment['purpose'],
                number_format((float) $payment['amount'], 2, '.', ''),
                $payment['currency'],
                $payment['status'],
                $payment['gateway'],
                $payment['paid_at'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }
}
