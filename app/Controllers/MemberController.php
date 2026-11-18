<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Guard;
use App\Core\Request;
use App\Models\Group;
use App\Models\Invite;
use App\Models\Membership;
use App\Models\User;
use App\Models\EducationRecord;
use App\Services\Mailer;

final class MemberController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $filters = [
            'search' => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'role' => (string) ($_GET['role'] ?? ''),
        ];

        $isManager = Guard::canManage($membership);

        $this->view('members/index', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => $isManager,
            'isOwner' => $membership['role'] === 'owner',
            'members' => Membership::list((int) $group['id'], $filters),
            'filters' => $filters,
            'roles' => Membership::ROLES,
            'pendingCount' => (int) \App\Core\Database::value(
                'SELECT COUNT(*) FROM memberships WHERE group_id = ? AND status = "pending"',
                [(int) $group['id']],
                0
            ),
            'invites' => $isManager ? Invite::pendingForGroup((int) $group['id']) : [],
        ]);
    }

    public function show(string $slug, string $id): void
    {
        $group = $this->findGroup($slug);
        $membership = Guard::membership((int) $group['id']);

        if ($membership === null) {
            \App\Core\Response::abort(403, 'You are not a member of this group.');
        }

        $target = $this->findMembership((int) $group['id'], (int) $id);
        $isSelf = (int) $target['user_id'] === (int) user()['id'];
        $isManager = Guard::canManage($membership);

        if (!$isSelf && !$isManager && $membership['status'] !== 'active') {
            \App\Core\Response::abort(403, 'Your membership is not active yet.');
        }

        $this->view('members/show', [
            'group' => $group,
            'membership' => $membership,
            'target' => $target,
            'isSelf' => $isSelf,
            'isManager' => $isManager,
            'isOwner' => $membership['role'] === 'owner',
            'canViewProof' => $isSelf || $isManager,
            'proofTypes' => Membership::PROOF_TYPES,
            'roles' => Membership::ROLES,
            'claimedEducation' => !empty($target['education_record_id'])
                ? EducationRecord::find((int) $target['education_record_id'])
                : null,
        ]);
    }

    public function approve(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);

        $target = $this->findMembership((int) $group['id'], (int) $id);

        Membership::approve((int) $target['id'], (int) $this->currentUser()['id']);
        $this->audit((int) $group['id'], 'member.approved', 'Approved ' . $target['name'] . "'s membership after reviewing their proof", 'membership', (int) $target['id']);

        $this->flashSuccess($target['name'] . ' is now an active member.');
        $this->redirect('groups/' . $slug . '/members');
    }

    public function reject(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);

        $target = $this->findMembership((int) $group['id'], (int) $id);
        $reason = Request::string('rejection_reason');

        Membership::reject((int) $target['id'], (int) $this->currentUser()['id'], $reason !== '' ? mb_substr($reason, 0, 255) : null);
        $this->audit((int) $group['id'], 'member.rejected', 'Rejected ' . $target['name'] . "'s membership request", 'membership', (int) $target['id']);

        $this->flashSuccess('Membership request rejected.');
        $this->redirect('groups/' . $slug . '/members');
    }

    public function updateRole(string $slug, string $id): void
    {
        [$group, $actor] = $this->groupContext($slug, ['owner', 'admin']);

        $target = $this->findMembership((int) $group['id'], (int) $id);
        $role = Request::string('role');
        $title = Request::string('position_title');

        if (!isset(Membership::ROLES[$role]) || $role === 'owner') {
            $this->flashError('Choose a valid role. Ownership can only be transferred by the current owner.');
            $this->redirect('groups/' . $slug . '/members');
        }

        if ($target['role'] === 'owner') {
            $this->flashError('The group owner\'s role cannot be changed.');
            $this->redirect('groups/' . $slug . '/members');
        }

        if ($actor['role'] === 'admin' && in_array($target['role'], ['owner', 'admin'], true)) {
            $this->flashError('Only the owner can change another admin\'s role.');
            $this->redirect('groups/' . $slug . '/members');
        }

        if ($actor['role'] === 'admin' && $role === 'admin') {
            $this->flashError('Only the owner can appoint new admins.');
            $this->redirect('groups/' . $slug . '/members');
        }

        Membership::setRole((int) $target['id'], $role, $title !== '' ? $title : null);
        $this->audit(
            (int) $group['id'],
            'member.role_changed',
            'Changed ' . $target['name'] . "'s role to " . role_label($role),
            'membership',
            (int) $target['id']
        );

        $this->flashSuccess($target['name'] . ' is now ' . strtolower(role_label($role)) . '.');
        $this->redirect('groups/' . $slug . '/members');
    }

    public function remove(string $slug, string $id): void
    {
        [$group, $actor] = $this->groupContext($slug, ['owner', 'admin']);

        $target = $this->findMembership((int) $group['id'], (int) $id);

        if ((int) $target['user_id'] === (int) $this->currentUser()['id']) {
            $this->flashError('Use the "Leave group" option in settings to leave.');
            $this->redirect('groups/' . $slug . '/members');
        }

        if ($target['role'] === 'owner') {
            $this->flashError('The group owner cannot be removed.');
            $this->redirect('groups/' . $slug . '/members');
        }

        if ($actor['role'] === 'admin' && $target['role'] === 'admin') {
            $this->flashError('Only the owner can remove another admin.');
            $this->redirect('groups/' . $slug . '/members');
        }

        \App\Services\Uploader::deleteProof((string) ($target['proof_file'] ?? ''));
        \App\Core\Database::delete('memberships', ['id' => (int) $target['id']]);
        $this->audit((int) $group['id'], 'member.removed', 'Removed ' . $target['name'] . ' from the group', 'membership', (int) $target['id']);

        $this->flashSuccess($target['name'] . ' has been removed from the group.');
        $this->redirect('groups/' . $slug . '/members');
    }

    public function inviteForm(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug, ['owner', 'admin']);

        $this->view('members/invite', [
            'group' => $group,
            'membership' => $membership,
            'isOwner' => $membership['role'] === 'owner',
            'invites' => Invite::pendingForGroup((int) $group['id']),
            'members' => Membership::list((int) $group['id']),
        ]);
    }

    public function invite(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug, ['owner', 'admin']);

        $data = $this->validate([
            'email' => 'required|email|max:190',
            'role' => 'required|in:member,exco,admin',
            'message' => 'max:1000',
        ]);

        if ($data['role'] === 'admin' && $membership['role'] !== 'owner') {
            $this->flashError('Only the owner can invite another admin.');
            $this->redirect('groups/' . $slug . '/members/invite');
        }

        $existingUser = User::findByEmail((string) $data['email']);

        if ($existingUser !== null) {
            $existing = Membership::forUser((int) $group['id'], (int) $existingUser['id']);

            if ($existing !== null && in_array($existing['status'], ['active', 'pending'], true)) {
                $this->flashError('That person is already ' . ($existing['status'] === 'active' ? 'a member' : 'pending approval') . ' of this group.');
                $this->redirect('groups/' . $slug . '/members/invite');
            }
        }

        $invite = Invite::create([
            'group_id' => (int) $group['id'],
            'email' => (string) $data['email'],
            'role' => (string) $data['role'],
            'invited_by' => (int) $this->currentUser()['id'],
        ]);

        $link = absolute_url('invite/' . $invite['token']);

        $body = 'You have been invited to join ' . $group['name'] . " on Classof.\n\n";
        if (($data['message'] ?? '') !== '') {
            $body .= $data['message'] . "\n\n";
        }
        $body .= 'Accept the invitation here: ' . $link . "\n\nThis link expires in 14 days.";

        Mailer::send((string) $data['email'], 'Invitation to ' . $group['name'] . ' on Classof', $body);

        if (config('app.env') === 'local') {
            \App\Core\Session::flash('invite_link', $link);
        }

        $this->audit((int) $group['id'], 'invite.sent', 'Invited ' . $data['email'] . ' to join', 'invite', (int) $invite['id']);
        $this->flashSuccess('Invitation created for ' . $data['email'] . '.');
        $this->redirect('groups/' . $slug . '/members/invite');
    }

    /** @return array<string, mixed> */
    private function findMembership(int $groupId, int $membershipId): array
    {
        $target = \App\Core\Database::fetch(
            'SELECT m.*, u.name, u.email, u.phone, u.avatar, u.occupation, u.location, u.bio,
                    (SELECT name FROM users WHERE id = m.reviewed_by) AS reviewer_name
             FROM memberships m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.id = ? AND m.group_id = ? LIMIT 1',
            [$membershipId, $groupId]
        );

        if ($target === null) {
            \App\Core\Response::abort(404, 'That member could not be found.');
        }

        return $target;
    }
}
