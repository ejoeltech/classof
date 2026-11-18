<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\CoopFine;
use App\Models\CoopMember;
use App\Models\Cooperative;
use App\Services\CoopService;

final class CoopFineController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);
        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);
        $isMember = $coopMember !== null && $coopMember['status'] === 'active';

        if (!$isOfficer && !$isMember) {
            $this->flashError('Only co-op members can view fines.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        $filters = ['status' => (string) Request::input('status', '')];

        $this->view('coop/fines/index', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'coopMember' => $coopMember,
            'isOfficer' => $isOfficer,
            'fines' => $isOfficer
                ? CoopFine::forCoop((int) $coop['id'], array_filter($filters))
                : CoopFine::forMember((int) $coopMember['id']),
            'filters' => $filters,
            'members' => $isOfficer ? CoopMember::active((int) $coop['id']) : [],
            'unpaidTotal' => $isOfficer
                ? CoopFine::unpaidTotal((int) $coop['id'])
                : CoopFine::unpaidTotal((int) $coop['id'], (int) $coopMember['id']),
        ]);
    }

    public function store(string $slug): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);

        $data = $this->validate([
            'member_id' => 'required|integer',
            'reason' => 'required|min:3|max:255',
            'amount' => 'required|numeric|gt:0',
        ]);

        $member = CoopMember::find((int) $data['member_id']);

        if ($member === null || (int) $member['cooperative_id'] !== (int) $coop['id'] || $member['status'] !== 'active') {
            $this->fail(['member_id' => 'Choose an active co-op member.']);
        }

        $id = CoopFine::create([
            'cooperative_id' => (int) $coop['id'],
            'member_id' => (int) $member['id'],
            'reason' => $data['reason'],
            'amount' => (float) $data['amount'],
            'issued_by' => (int) user()['id'],
        ]);

        $this->audit((int) $group['id'], 'coop.fine_issued', 'Issued a fine to ' . $member['name'], 'coop_fine', $id);
        $this->flashSuccess('Fine issued.');
        $this->redirect('groups/' . $slug . '/coop/fines');
    }

    public function markPaid(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $fine = $this->findFine((int) $coop['id'], (int) $id);

        if ($fine['status'] !== 'unpaid') {
            $this->flashError('Only an unpaid fine can be marked as paid.');
            $this->redirect('groups/' . $slug . '/coop/fines');
        }

        try {
            CoopService::payFine($fine, 'cash', (int) user()['id']);
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('groups/' . $slug . '/coop/fines');
        }

        $this->audit((int) $group['id'], 'coop.fine_paid', 'Marked a fine as paid', 'coop_fine', (int) $fine['id']);
        $this->flashSuccess('Fine marked as paid.');
        $this->redirect('groups/' . $slug . '/coop/fines');
    }

    public function waive(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $fine = $this->findFine((int) $coop['id'], (int) $id);

        CoopFine::waive((int) $fine['id']);
        $this->audit((int) $group['id'], 'coop.fine_waived', 'Waived a fine for ' . $fine['member_name'], 'coop_fine', (int) $fine['id']);
        $this->flashSuccess('Fine waived.');
        $this->redirect('groups/' . $slug . '/coop/fines');
    }

    /** @return array<string, mixed> */
    private function findFine(int $cooperativeId, int $fineId): array
    {
        $fine = CoopFine::find($fineId);

        if ($fine === null || (int) $fine['cooperative_id'] !== $cooperativeId) {
            Response::abort(404, 'That co-op fine could not be found.');
        }

        return $fine;
    }
}
