<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Models\Membership;
use App\Models\WelfareCase;
use App\Models\WelfareContribution;

final class WelfareController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $status = (string) Request::input('status', '');
        $status = in_array($status, ['open', 'funded', 'closed'], true) ? $status : '';

        $cases = WelfareCase::forGroup((int) $group['id'], $status);

        $this->view('welfare/index', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => Guard::canManage($membership),
            'cases' => $cases,
            'status' => $status,
            'raised' => array_sum(array_map(static fn (array $case): float => (float) $case['raised'], $cases)),
            'categories' => WelfareCase::CATEGORIES,
        ]);
    }

    public function create(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $this->view('welfare/create', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => Guard::canManage($membership),
            'categories' => WelfareCase::CATEGORIES,
            'members' => Guard::canManage($membership) ? Membership::list((int) $group['id'], ['status' => 'active']) : [],
        ]);
    }

    public function store(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $data = $this->validate([
            'title' => 'required|min:5|max:180',
            'category' => 'required|in:bereavement,medical,wedding,childbirth,education,emergency,other',
            'description' => 'required|min:10|max:5000',
            'target_amount' => 'numeric',
            'beneficiary_id' => 'integer',
        ]);

        if (($data['target_amount'] ?? '') !== '' && (float) $data['target_amount'] <= 0) {
            $this->fail(['target_amount' => 'Enter a target amount greater than zero, or leave it blank.']);
        }

        $beneficiaryId = (int) user()['id'];

        if (Guard::canManage($membership) && !empty($data['beneficiary_id'])) {
            $beneficiary = Membership::forUser((int) $group['id'], (int) $data['beneficiary_id']);

            if ($beneficiary === null || $beneficiary['status'] !== 'active') {
                $this->fail(['beneficiary_id' => 'Choose an active member of this group.']);
            }

            $beneficiaryId = (int) $data['beneficiary_id'];
        }

        $id = WelfareCase::create([
            'group_id' => (int) $group['id'],
            'user_id' => $beneficiaryId,
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'],
            'target_amount' => ($data['target_amount'] ?? '') !== '' ? (float) $data['target_amount'] : null,
            'currency' => $group['currency'],
            'is_anonymous' => Request::boolean('is_anonymous'),
            'created_by' => (int) user()['id'],
        ]);

        $this->audit((int) $group['id'], 'welfare.created', 'Opened welfare case "' . $data['title'] . '"', 'welfare_case', $id);
        $this->flashSuccess('Welfare case opened. Members can now contribute.');
        $this->redirect('groups/' . $slug . '/welfare/' . $id);
    }

    public function show(string $slug, string $id): void
    {
        [$group, $membership] = $this->groupContext($slug);
        $case = $this->findCase((int) $group['id'], (int) $id);

        $raised = WelfareCase::raised((int) $case['id']);
        $target = (float) ($case['target_amount'] ?? 0);

        $this->view('welfare/show', [
            'group' => $group,
            'membership' => $membership,
            'case' => $case,
            'isManager' => Guard::canManage($membership),
            'raised' => $raised,
            'target' => $target,
            'progress' => $target > 0 ? percent($raised, $target) : null,
            'contributions' => WelfareContribution::forCase((int) $case['id']),
            'myContribution' => (float) Database::value(
                'SELECT COALESCE(SUM(amount), 0) FROM welfare_contributions WHERE welfare_case_id = ? AND user_id = ?',
                [(int) $case['id'], (int) user()['id']],
                0
            ),
        ]);
    }

    public function close(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $case = $this->findCase((int) $group['id'], (int) $id);

        WelfareCase::setStatus((int) $case['id'], 'closed');
        $this->audit((int) $group['id'], 'welfare.closed', 'Closed welfare case "' . $case['title'] . '"', 'welfare_case', (int) $case['id']);

        $this->flashSuccess('Welfare case closed.');
        $this->redirect('groups/' . $slug . '/welfare/' . $id);
    }

    /** @return array<string, mixed> */
    private function findCase(int $groupId, int $caseId): array
    {
        $case = WelfareCase::find($caseId);

        if ($case === null || (int) $case['group_id'] !== $groupId) {
            \App\Core\Response::abort(404, 'That welfare case does not exist.');
        }

        return $case;
    }
}
