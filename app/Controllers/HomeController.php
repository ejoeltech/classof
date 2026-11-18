<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Group;
use App\Services\EligibilityService;

final class HomeController extends Controller
{
    public function index(): void
    {
        if (user() !== null) {
            $this->redirect('dashboard');
        }

        $groups = Group::explore('', null, null, 6);
        $stats = [
            'groups' => (int) Database::value('SELECT COUNT(*) FROM `alumni_groups` WHERE status = "active"', [], 0),
            'members' => (int) Database::value('SELECT COUNT(*) FROM memberships WHERE status = "active"', [], 0),
            'welfare' => (float) Database::value('SELECT COALESCE(SUM(amount), 0) FROM welfare_contributions', [], 0),
        ];

        $this->view('home/index', [
            'groups' => $groups,
            'stats' => $stats,
        ], 'layouts/public');
    }

    public function explore(): void
    {
        if (user() !== null) {
            $myGroups = array_column(Group::forUser((int) user()['id']), 'id');
            $forYou = (string) ($_GET['for'] ?? '') === 'you';

            if ($forYou) {
                $matches = EligibilityService::matchesForUser((int) user()['id']);

                $this->view('groups/explore', [
                    'groups' => [],
                    'matches' => $matches,
                    'matchCount' => count($matches),
                    'forYou' => true,
                    'search' => '',
                    'type' => null,
                    'scope' => null,
                    'scopes' => Group::SCOPES,
                    'myGroups' => $myGroups,
                ]);

                return;
            }

            $search = trim((string) ($_GET['q'] ?? ''));
            $type = isset($_GET['type']) && isset(Group::TYPES[$_GET['type']]) ? (string) $_GET['type'] : null;
            $scope = isset($_GET['scope']) && isset(Group::SCOPES[$_GET['scope']]) ? (string) $_GET['scope'] : null;

            $this->view('groups/explore', [
                'groups' => Group::explore($search, $type, $scope, 60),
                'matches' => [],
                'matchCount' => 0,
                'forYou' => false,
                'search' => $search,
                'type' => $type,
                'scope' => $scope,
                'scopes' => Group::SCOPES,
                'myGroups' => $myGroups,
            ]);

            return;
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        $type = isset($_GET['type']) && isset(Group::TYPES[$_GET['type']]) ? (string) $_GET['type'] : null;
        $scope = isset($_GET['scope']) && isset(Group::SCOPES[$_GET['scope']]) ? (string) $_GET['scope'] : null;

        $this->view('home/explore', [
            'groups' => Group::explore($search, $type, $scope, 60),
            'search' => $search,
            'type' => $type,
            'scope' => $scope,
            'scopes' => Group::SCOPES,
        ], 'layouts/public');
    }
}
