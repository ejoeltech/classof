<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Guard;
use App\Models\DuesCycle;
use App\Models\Payment;

final class DuesController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug);
        $userId = (int) user()['id'];

        $cycles = DuesCycle::forGroup((int) $group['id']);

        foreach ($cycles as &$cycle) {
            $cycle['my_payment'] = DuesCycle::myPayment((int) $cycle['id'], $userId);
            $cycle['my_pending'] = \App\Core\Database::fetch(
                'SELECT * FROM payments WHERE dues_cycle_id = ? AND user_id = ? AND purpose = "dues" AND status = "pending" ORDER BY id DESC LIMIT 1',
                [(int) $cycle['id'], $userId]
            );
        }
        unset($cycle);

        $this->view('dues/index', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => Guard::canManage($membership),
            'cycles' => $cycles,
        ]);
    }

    public function store(string $slug): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);

        $data = $this->validate([
            'title' => 'required|min:3|max:160',
            'description' => 'max:3000',
            'amount' => 'required|numeric|gt:0',
            'due_date' => 'required|date',
        ]);

        $id = DuesCycle::create([
            'group_id' => (int) $group['id'],
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'amount' => (float) $data['amount'],
            'currency' => $group['currency'],
            'due_date' => date('Y-m-d', strtotime((string) $data['due_date'])),
            'created_by' => (int) user()['id'],
        ]);

        $this->audit((int) $group['id'], 'dues.created', 'Created dues cycle "' . $data['title'] . '"', 'dues_cycle', $id);
        $this->flashSuccess('Dues cycle created.');
        $this->redirect('groups/' . $slug . '/dues');
    }

    public function show(string $slug, string $id): void
    {
        [$group, $membership] = $this->groupContext($slug);
        $cycle = $this->findCycle((int) $group['id'], (int) $id);
        $isManager = Guard::canManage($membership);

        $this->view('dues/show', [
            'group' => $group,
            'membership' => $membership,
            'cycle' => $cycle,
            'isManager' => $isManager,
            'myPayment' => DuesCycle::myPayment((int) $cycle['id'], (int) user()['id']),
            'pendingPayment' => \App\Core\Database::fetch(
                'SELECT * FROM payments WHERE dues_cycle_id = ? AND user_id = ? AND purpose = "dues" AND status = "pending" ORDER BY id DESC LIMIT 1',
                [(int) $cycle['id'], (int) user()['id']]
            ),
            'payers' => DuesCycle::payers((int) $cycle['id']),
            'defaulters' => DuesCycle::defaulters((int) $cycle['id'], (int) $group['id']),
            'payments' => Payment::forGroup((int) $group['id'], ['purpose' => 'dues'], 100),
        ]);
    }

    public function close(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $cycle = $this->findCycle((int) $group['id'], (int) $id);

        DuesCycle::close((int) $cycle['id']);
        $this->audit((int) $group['id'], 'dues.closed', 'Closed dues cycle "' . $cycle['title'] . '"', 'dues_cycle', (int) $cycle['id']);

        $this->flashSuccess('Dues cycle closed.');
        $this->redirect('groups/' . $slug . '/dues/' . $id);
    }

    /** @return array<string, mixed> */
    private function findCycle(int $groupId, int $cycleId): array
    {
        $cycle = DuesCycle::find($cycleId);

        if ($cycle === null || (int) $cycle['group_id'] !== $groupId) {
            \App\Core\Response::abort(404, 'That dues cycle does not exist.');
        }

        return $cycle;
    }
}
