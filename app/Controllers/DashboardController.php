<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Announcement;
use App\Models\CoopLoan;
use App\Models\CoopMember;
use App\Models\Cooperative;
use App\Models\DuesCycle;
use App\Models\EducationRecord;
use App\Models\Election;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupSite;
use App\Models\WelfareCase;
use App\Models\WelfareContribution;
use App\Models\User;
use App\Services\ClassmateService;
use App\Services\EligibilityService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $this->currentUser();
        $userId = (int) $user['id'];

        $groups = Group::forUser($userId, 'active');
        $pendingGroups = Group::forUser($userId, 'pending');

        $outstanding = [];
        $soonestDue = null;

        foreach ($groups as $group) {
            $cycle = Database::fetch(
                'SELECT * FROM dues_cycles WHERE group_id = ? AND status = "open" ORDER BY due_date ASC LIMIT 1',
                [(int) $group['id']]
            );

            if ($cycle === null) {
                continue;
            }

            if (DuesCycle::myPayment((int) $cycle['id'], $userId) === null) {
                $outstanding[] = ['group' => $group, 'cycle' => $cycle];

                if ($soonestDue === null || $cycle['due_date'] < $soonestDue) {
                    $soonestDue = $cycle['due_date'];
                }
            }
        }

        $coops = [];

        foreach ($groups as $group) {
            $coop = Cooperative::forGroup((int) $group['id']);

            if ($coop === null) {
                continue;
            }

            $coopMember = CoopMember::forUser((int) $coop['id'], $userId);

            if ($coopMember === null || !in_array($coopMember['status'], ['active', 'pending'], true)) {
                continue;
            }

            $isActiveCoopMember = $coopMember['status'] === 'active';

            $coops[] = [
                'group' => $group,
                'coop' => $coop,
                'member' => $coopMember,
                'savings' => $isActiveCoopMember ? CoopMember::savingsBalance((int) $coop['id'], (int) $coopMember['id']) : 0,
                'shares' => $isActiveCoopMember ? CoopMember::shareCapital((int) $coop['id'], (int) $coopMember['id']) : 0,
                'loan' => $isActiveCoopMember ? CoopLoan::activeForMember((int) $coopMember['id']) : null,
            ];
        }

        $allMatches = EligibilityService::matchesForUser($userId);
        $classmateAll = ClassmateService::suggestions($userId);
        $classmateSuggestions = array_slice($classmateAll, 0, 3);
        $lastSeen = User::lastSeen($userId);
        $newMatches = $lastSeen !== null
            ? array_values(array_filter(
                $allMatches,
                static fn (array $group): bool => (string) $group['created_at'] > $lastSeen
            ))
            : [];

        User::touchSeen($userId);

        $this->view('dashboard/index', [
            'groups' => $groups,
            'pendingGroups' => $pendingGroups,
            'announcements' => Announcement::latestForUser($userId, 4),
            'events' => Event::upcomingForUser($userId, 4),
            'welfare' => WelfareCase::latestForUser($userId, 4),
            'elections' => Election::openForUser($userId, 4),
            'outstanding' => $outstanding,
            'soonestDue' => $soonestDue,
            'coops' => $coops,
            'suggestions' => array_slice($allMatches, 0, 3),
            'matchCount' => count($allMatches),
            'newMatches' => array_slice($newMatches, 0, 3),
            'newMatchCount' => count($newMatches),
            'classmates' => $classmateSuggestions,
            'classmateCount' => count($classmateAll),
            'siteEditorGroups' => GroupSite::editableForUser($userId),
            'educationCount' => EducationRecord::countForUser($userId),
            'contributed' => WelfareContribution::totalForUser($userId),
            'pendingInvites' => (int) Database::value(
                'SELECT COUNT(*) FROM invites WHERE email = ? AND accepted_at IS NULL AND expires_at > NOW()',
                [$user['email']],
                0
            ),
        ], 'layouts/app');
    }
}
