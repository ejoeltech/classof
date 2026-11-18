<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\CoopDividend;
use App\Models\Cooperative;
use App\Services\CoopService;

final class CoopDividendController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);
        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);
        $isMember = $coopMember !== null && $coopMember['status'] === 'active';

        if (!$isOfficer && !$isMember) {
            $this->flashError('Only co-op members can view dividends.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        $this->view('coop/dividends/index', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'isOfficer' => $isOfficer,
            'coopMember' => $coopMember,
            'dividends' => CoopDividend::forCoop((int) $coop['id']),
        ]);
    }

    public function store(string $slug): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);

        $data = $this->validate([
            'title' => 'required|min:3|max:160',
            'period' => 'max:60',
            'basis' => 'required|in:shares,savings',
            'total_amount' => 'required|numeric|gt:0',
        ]);

        $result = CoopService::declareDividend($coop, $data, (int) user()['id']);

        if (!$result['ok']) {
            $this->flashError($result['error'] ?? 'The dividend could not be declared.');
            $this->redirect('groups/' . $slug . '/coop/dividends');
        }

        $this->audit((int) $group['id'], 'coop.dividend_declared', 'Declared dividend "' . $data['title'] . '"', 'coop_dividend', (int) $result['dividend_id']);
        $this->flashSuccess('Dividend declared. Review the allocations, then distribute.');
        $this->redirect('groups/' . $slug . '/coop/dividends/' . $result['dividend_id']);
    }

    public function show(string $slug, string $id): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);
        $dividend = $this->findDividend((int) $coop['id'], (int) $id);
        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);
        $isMember = $coopMember !== null && $coopMember['status'] === 'active';

        if (!$isOfficer && !$isMember) {
            Response::abort(403, 'You cannot view this dividend.');
        }

        $allocations = CoopDividend::allocations((int) $dividend['id']);

        if (!$isOfficer) {
            $allocations = array_values(array_filter(
                $allocations,
                static fn (array $row): bool => (int) $row['member_id'] === (int) $coopMember['id']
            ));
        }

        $this->view('coop/dividends/show', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'dividend' => $dividend,
            'allocations' => $allocations,
            'isOfficer' => $isOfficer,
        ]);
    }

    public function distribute(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $dividend = $this->findDividend((int) $coop['id'], (int) $id);

        try {
            CoopService::distributeDividend($dividend, (int) user()['id']);
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('groups/' . $slug . '/coop/dividends/' . $dividend['id']);
        }

        $this->audit((int) $group['id'], 'coop.dividend_distributed', 'Distributed dividend "' . $dividend['title'] . '"', 'coop_dividend', (int) $dividend['id']);
        $this->flashSuccess('Dividend distributed and recorded for each member.');
        $this->redirect('groups/' . $slug . '/coop/dividends/' . $dividend['id']);
    }

    /** @return array<string, mixed> */
    private function findDividend(int $cooperativeId, int $dividendId): array
    {
        $dividend = CoopDividend::find($dividendId);

        if ($dividend === null || (int) $dividend['cooperative_id'] !== $cooperativeId) {
            Response::abort(404, 'That dividend could not be found.');
        }

        return $dividend;
    }
}
