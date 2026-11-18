<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Guard;
use App\Core\Request;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\DuesCycle;
use App\Models\Election;
use App\Models\Event;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\GroupChannel;
use App\Models\Institution;
use App\Models\Invite;
use App\Models\Membership;
use App\Models\PlatformSetting;
use App\Models\WelfareCase;
use App\Services\EligibilityService;
use App\Services\Uploader;

final class GroupController extends Controller
{
    public function show(string $slug): void
    {
        $group = $this->findGroup($slug);
        $membership = Guard::membership((int) $group['id']);
        $isMember = $membership !== null && $membership['status'] === 'active';

        if (!$isMember) {
            if ($group['visibility'] === 'private') {
                $this->view('groups/private', ['group' => $group, 'membership' => $membership]);

                return;
            }

            $this->view('groups/public', [
                'group' => $group,
                'membership' => $membership,
                'memberCount' => Membership::countActive((int) $group['id']),
                'exco' => Membership::exco((int) $group['id']),
            ]);

            return;
        }

        $isManager = Guard::canManage($membership);
        $stats = Group::stats((int) $group['id']);

        $dues = DuesCycle::forGroup((int) $group['id']);
        $openDue = null;
        foreach ($dues as $cycle) {
            if ($cycle['status'] === 'open') {
                $openDue = $cycle;
                break;
            }
        }

        $welfare = WelfareCase::forGroup((int) $group['id'], 'open');
        $elections = Election::forGroup((int) $group['id']);

        $this->view('groups/show', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => $isManager,
            'stats' => $stats,
            'announcements' => Announcement::forGroup((int) $group['id'], 4),
            'events' => Event::forGroup((int) $group['id'], 'upcoming', 3),
            'dues' => $dues,
            'openDue' => $openDue,
            'welfare' => $welfare,
            'elections' => $elections,
            'exco' => Membership::exco((int) $group['id']),
            'treasury' => $isManager ? Group::treasury((int) $group['id']) : null,
            'recentActivity' => $isManager ? AuditLog::recent((int) $group['id'], 8) : [],
            'myDues' => $openDue !== null ? DuesCycle::myPayment((int) $openDue['id'], (int) user()['id']) : null,
            'channels' => GroupChannel::visibleFor((int) $group['id'], $isManager),
        ]);
    }

    public function joinConfirm(string $slug): void
    {
        $group = $this->findGroup($slug);
        $membership = Guard::membership((int) $group['id']);

        if ($membership !== null && in_array($membership['status'], ['active', 'pending'], true)) {
            if ($membership['status'] === 'pending') {
                $this->flashSuccess('Your join request is already awaiting review.');
            }
            $this->redirect('groups/' . $group['slug']);
        }

        if ($group['visibility'] === 'private' || $group['join_policy'] === 'invite') {
            $this->flashError('This group is invitation only. Ask an admin for an invite link.');
            $this->redirect('dashboard');
        }

        $this->view('groups/join', [
            'group' => $group,
            'membership' => $membership,
            'memberCount' => Membership::countActive((int) $group['id']),
            'proofTypes' => Membership::PROOF_TYPES,
        ]);
    }

    public function join(string $slug): void
    {
        $group = $this->findGroup($slug);
        $userId = (int) $this->currentUser()['id'];
        $membership = Guard::membership((int) $group['id']);

        if ($membership !== null && in_array($membership['status'], ['active', 'pending'], true)) {
            if ($membership['status'] === 'pending') {
                $this->flashSuccess('Your request is already pending review.');
            }
            $this->redirect('groups/' . $group['slug']);
        }

        if ($group['visibility'] === 'private' || $group['join_policy'] === 'invite') {
            $this->flashError('This group is invitation only.');
            $this->redirect('dashboard');
        }

        if ($membership !== null && $membership['status'] === 'suspended') {
            $this->flashError('Your membership is suspended. Contact a group admin.');
            $this->redirect('dashboard');
        }

        $match = EligibilityService::bestMatchForGroup($userId, $group);
        $requireVerification = PlatformSetting::bool('education.require_profile_verification');

        if ($requireVerification && ($match === null || $match['status'] !== 'verified')) {
            $this->flashError('A platform admin must confirm your education record for this institution before you can request to join.');
            $this->redirect('join/' . $group['slug']);
        }

        $autoApprove = PlatformSetting::bool('education.auto_approve_institution_groups')
            && $group['scope'] === 'institution'
            && $match !== null
            && (!$requireVerification || $match['status'] === 'verified');

        $data = [];
        $proofFile = null;

        if (!$autoApprove) {
            $data = $this->validate([
                'proof_type' => 'required|in:alumni_id,student_id,certificate,transcript,admission_letter,other',
                'proof_reference' => 'required|min:2|max:160',
                'proof_note' => 'max:1000',
            ]);

            $file = Request::file('proof_file');

            if ($file !== null) {
                try {
                    $proofFile = Uploader::proof($file);
                } catch (\RuntimeException $e) {
                    $this->flashError($e->getMessage());
                    $this->redirect('join/' . $group['slug']);
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $status = $autoApprove ? 'active' : 'pending';
        $claimFlagged = !$autoApprove
            && PlatformSetting::bool('education.flag_unverified_claims')
            && ($match === null || $match['status'] !== 'verified');

        $proof = [
            'status' => $status,
            'role' => 'member',
            'position_title' => null,
            'proof_type' => $autoApprove ? 'education_record' : $data['proof_type'],
            'proof_reference' => $autoApprove
                ? mb_substr('Education record #' . $match['id'] . ' · ' . $match['match_reason'], 0, 160)
                : $data['proof_reference'],
            'proof_file' => $proofFile,
            'proof_note' => $autoApprove
                ? 'Auto-approved by the institution-wide matching policy.'
                : (($data['proof_note'] ?? '') !== '' ? $data['proof_note'] : null),
            'proof_submitted_at' => $now,
            'education_record_id' => $match['id'] ?? null,
            'claim_match' => $match['match_reason'] ?? null,
            'claim_flagged' => $claimFlagged ? 1 : 0,
            'reviewed_by' => null,
            'reviewed_at' => $autoApprove ? $now : null,
            'rejection_reason' => null,
            'approved_by' => null,
            'joined_at' => $autoApprove ? $now : null,
            'updated_at' => $now,
        ];

        // A previously rejected or departed member may submit a fresh request.
        if ($membership !== null) {
            if ($membership['status'] === 'active') {
                $this->redirect('groups/' . $group['slug']);
            }

            \App\Core\Database::update('memberships', $proof, ['id' => (int) $membership['id']]);

            if (!empty($membership['proof_file']) && $membership['proof_file'] !== $proofFile) {
                Uploader::deleteProof((string) $membership['proof_file']);
            }

            $this->audit((int) $group['id'], 'member.requested', $autoApprove ? 'Auto-approved by matching policy' : 'Submitted a new membership request with proof', 'membership', (int) $membership['id']);
        } else {
            Membership::create(array_merge($proof, [
                'group_id' => (int) $group['id'],
                'user_id' => $userId,
            ]));

            $this->audit((int) $group['id'], 'member.requested', $autoApprove ? 'Auto-approved by matching policy' : 'Requested to join with proof of alumni status', 'group', (int) $group['id']);
        }

        if ($autoApprove) {
            $this->flashSuccess('Your education record matched this institution-wide group, so you were added automatically.');
            $this->redirect('groups/' . $group['slug']);
        }

        $this->flashSuccess('Your proof has been submitted. A group admin will review it before you are admitted.');
        $this->redirect('dashboard');
    }

    public function acceptInvite(string $token): void
    {
        $invite = Invite::findByToken($token);

        if ($invite === null) {
            $this->flashError('That invitation link is invalid.');
            $this->redirect('dashboard');
        }

        if ($invite['accepted_at'] !== null) {
            $this->flashError('That invitation has already been used.');
            $this->redirect('dashboard');
        }

        if (strtotime((string) $invite['expires_at']) < time()) {
            $this->flashError('That invitation has expired. Ask an admin to send a new one.');
            $this->redirect('dashboard');
        }

        $user = $this->currentUser();

        if (strtolower((string) $invite['email']) !== strtolower((string) $user['email'])) {
            $this->flashError('This invitation was sent to a different email address. Sign in with ' . $invite['email'] . ' to accept it.');
            $this->redirect('dashboard');
        }

        $existing = Membership::forUser((int) $invite['group_id'], (int) $user['id']);

        if ($existing !== null && $existing['status'] === 'active') {
            Invite::markAccepted((int) $invite['id']);
            $this->flashSuccess('You are already an active member of ' . $invite['group_name'] . '.');
            $this->redirect('groups/' . $invite['group_slug']);
        }

        $inviter = $invite['invited_by'] !== null ? (int) $invite['invited_by'] : null;
        $now = date('Y-m-d H:i:s');

        $proof = [
            'status' => 'active',
            'role' => $invite['role'],
            'approved_by' => $inviter,
            'reviewed_by' => $inviter,
            'reviewed_at' => $now,
            'proof_type' => 'invitation',
            'proof_reference' => 'Invited by ' . ($invite['inviter_name'] ?? 'a group admin'),
            'proof_submitted_at' => $now,
            'joined_at' => $now,
            'rejection_reason' => null,
            'updated_at' => $now,
        ];

        if ($existing !== null) {
            \App\Core\Database::update('memberships', $proof, ['id' => (int) $existing['id']]);
        } else {
            Membership::create(array_merge($proof, [
                'group_id' => (int) $invite['group_id'],
                'user_id' => (int) $user['id'],
            ]));
        }

        Invite::markAccepted((int) $invite['id']);
        $this->audit((int) $invite['group_id'], 'invite.accepted', 'Accepted an invitation to join', 'group', (int) $invite['group_id']);

        $this->flashSuccess('You have joined ' . $invite['group_name'] . '.');
        $this->redirect('groups/' . $invite['group_slug']);
    }

    public function settings(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug, ['owner', 'admin']);

        $this->view('groups/settings', [
            'group' => $group,
            'membership' => $membership,
            'types' => Group::TYPES,
            'scopes' => Group::SCOPES,
            'currencies' => Group::currencies(),
            'institutions' => Institution::verified(),
            'stats' => Group::stats((int) $group['id']),
            'invites' => Invite::pendingForGroup((int) $group['id']),
            'channels' => GroupChannel::forGroup((int) $group['id'], true),
        ]);
    }

    public function updateSettings(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug, ['owner', 'admin']);

        $data = $this->groupPayload();

        $update = array_merge($data, ['updated_at' => date('Y-m-d H:i:s')]);

        $logo = Request::file('logo');

        if ($logo !== null) {
            try {
                $update['logo'] = Uploader::image($logo, 'group');
                Uploader::delete($group['logo'] ?? null);
            } catch (\RuntimeException $e) {
                $this->flashError($e->getMessage());
                $this->redirect('groups/' . $slug . '/settings');
            }
        }

        \App\Core\Database::update('alumni_groups', $update, ['id' => (int) $group['id']]);

        $this->audit((int) $group['id'], 'group.updated', 'Updated group scope and settings', 'group', (int) $group['id']);
        $this->flashSuccess('Group settings saved.');
        $this->redirect('groups/' . $slug . '/settings');
    }

    /**
     * Validate and normalise the shared create/settings payload.
     *
     * @return array<string, mixed>
     */
    private function groupPayload(): array
    {
        $data = $this->validate([
            'name' => 'required|min:3|max:160',
            'scope' => 'required|in:institution,class,department,faculty,association,other',
            'type' => 'required|in:primary,secondary,university,polytechnic,college,vocational,other',
            'institution_id' => 'required|integer',
            'faculty_id' => 'integer',
            'department_id' => 'integer',
            'graduation_year' => 'integer',
            'country' => 'max:80',
            'currency' => 'required|in:NGN,USD,GBP,EUR,GHS,KES,ZAR,CAD,AUD',
            'bank_name' => 'max:120',
            'bank_account_number' => 'max:40',
            'bank_account_holder' => 'max:160',
            'description' => 'max:3000',
            'visibility' => 'required|in:public,private',
            'join_policy' => 'required|in:approval,invite',
        ]);

        $institution = Institution::find((int) $data['institution_id']);

        if ($institution === null || $institution['status'] !== 'verified') {
            $this->fail(['institution_id' => 'Choose a verified institution from the catalogue.']);
        }

        $scope = (string) $data['scope'];
        $year = (string) ($data['graduation_year'] ?? '');
        $facultyId = (int) ($data['faculty_id'] ?? 0) ?: null;
        $departmentId = (int) ($data['department_id'] ?? 0) ?: null;
        $facultyName = null;
        $departmentName = null;

        if ($facultyId !== null) {
            $faculty = Faculty::find($facultyId);

            if ($faculty === null || (int) $faculty['institution_id'] !== (int) $institution['id']) {
                $this->fail(['faculty_id' => 'Choose a faculty that belongs to the selected institution.']);
            }

            $facultyName = $faculty['name'];
        }

        if ($departmentId !== null) {
            $department = Department::find($departmentId);

            if ($department === null || (int) $department['institution_id'] !== (int) $institution['id']) {
                $this->fail(['department_id' => 'Choose a department that belongs to the selected institution.']);
            }

            if ($facultyId !== null && (int) $department['faculty_id'] !== $facultyId) {
                $this->fail(['department_id' => 'That department does not belong to the selected faculty.']);
            }

            $departmentName = $department['name'];

            if ($facultyId === null) {
                $facultyId = (int) $department['faculty_id'];
                $facultyName = $department['faculty_name'];
            }
        }

        if ($scope === 'class' && $year === '') {
            $this->fail(['graduation_year' => 'A class / set group needs a graduation year.']);
        }

        if ($year !== '' && ((int) $year < 1900 || (int) $year > (int) date('Y') + 10)) {
            $this->fail(['graduation_year' => 'Enter a realistic graduation year.']);
        }

        if ($scope === 'department' && $departmentId === null) {
            $this->fail(['department_id' => 'A department group needs the department.']);
        }

        if ($scope === 'faculty' && $facultyId === null) {
            $this->fail(['faculty_id' => 'A faculty group needs the faculty.']);
        }

        $bankFields = [
            trim((string) ($data['bank_name'] ?? '')),
            trim((string) ($data['bank_account_number'] ?? '')),
            trim((string) ($data['bank_account_holder'] ?? '')),
        ];
        $bankFilled = count(array_filter($bankFields, static fn (string $value): bool => $value !== ''));

        if ($bankFilled > 0 && $bankFilled < 3) {
            $this->fail(['bank_name' => 'Provide the bank name, account number and account name together, or leave all three blank.']);
        }

        return [
            'name' => $data['name'],
            'scope' => $scope,
            'type' => $data['type'],
            'institution_id' => (int) $institution['id'],
            'institution' => $institution['name'],
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'faculty' => $facultyName,
            'department' => $departmentName,
            'graduation_year' => in_array($scope, ['institution', 'association'], true)
                ? null
                : ($year !== '' ? (int) $year : null),
            'country' => $data['country'] ?: null,
            'currency' => $data['currency'],
            'bank_name' => trim((string) ($data['bank_name'] ?? '')) ?: null,
            'bank_account_number' => trim((string) ($data['bank_account_number'] ?? '')) ?: null,
            'bank_account_holder' => trim((string) ($data['bank_account_holder'] ?? '')) ?: null,
            'description' => $data['description'] ?: null,
            'visibility' => $data['visibility'],
            'join_policy' => $data['join_policy'],
        ];
    }

    public function storeChannel(string $slug): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);

        $data = $this->validate([
            'label' => 'required|min:2|max:80',
            'invite_url' => 'required|max:255',
            'visible_to' => 'required|in:members,manager',
        ]);

        if (!GroupChannel::validInviteUrl((string) $data['invite_url'])) {
            $this->fail(['invite_url' => 'Use a valid https WhatsApp invite link from chat.whatsapp.com or wa.me.']);
        }

        $id = GroupChannel::create([
            'group_id' => (int) $group['id'],
            'label' => $data['label'],
            'invite_url' => $data['invite_url'],
            'visible_to' => $data['visible_to'],
            'is_primary' => Request::boolean('is_primary'),
            'created_by' => (int) user()['id'],
        ]);

        $this->audit((int) $group['id'], 'channel.created', 'Added WhatsApp channel "' . $data['label'] . '"', 'group_channel', $id);
        $this->flashSuccess('WhatsApp channel added.');
        $this->redirect('groups/' . $slug . '/settings');
    }

    public function updateChannel(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $channel = $this->findChannel((int) $group['id'], (int) $id);

        $data = $this->validate([
            'label' => 'required|min:2|max:80',
            'invite_url' => 'required|max:255',
            'visible_to' => 'required|in:members,manager',
        ]);

        if (!GroupChannel::validInviteUrl((string) $data['invite_url'])) {
            $this->fail(['invite_url' => 'Use a valid https WhatsApp invite link from chat.whatsapp.com or wa.me.']);
        }

        GroupChannel::update((int) $channel['id'], [
            'label' => $data['label'],
            'invite_url' => $data['invite_url'],
            'visible_to' => $data['visible_to'],
            'is_primary' => Request::boolean('is_primary'),
        ]);

        $this->audit((int) $group['id'], 'channel.updated', 'Updated WhatsApp channel "' . $data['label'] . '"', 'group_channel', (int) $channel['id']);
        $this->flashSuccess('WhatsApp channel updated.');
        $this->redirect('groups/' . $slug . '/settings');
    }

    public function revokeChannel(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $channel = $this->findChannel((int) $group['id'], (int) $id);

        GroupChannel::revoke((int) $channel['id']);
        $this->audit((int) $group['id'], 'channel.revoked', 'Revoked WhatsApp channel "' . $channel['label'] . '"', 'group_channel', (int) $channel['id']);
        $this->flashSuccess('WhatsApp channel revoked.');
        $this->redirect('groups/' . $slug . '/settings');
    }

    /** @return array<string, mixed> */
    private function findChannel(int $groupId, int $channelId): array
    {
        $channel = GroupChannel::find($channelId);

        if ($channel === null || (int) $channel['group_id'] !== $groupId) {
            \App\Core\Response::abort(404, 'That WhatsApp channel could not be found.');
        }

        return $channel;
    }

    public function leave(string $slug): void
    {        [$group, $membership] = $this->groupContext($slug);

        if ($membership['role'] === 'owner') {
            $this->flashError('As the owner you cannot leave the group. Promote another admin first.');
            $this->redirect('groups/' . $slug . '/settings');
        }

        Uploader::deleteProof((string) ($membership['proof_file'] ?? ''));
        \App\Core\Database::delete('memberships', ['id' => (int) $membership['id']]);
        $this->audit((int) $group['id'], 'member.left', 'Left the group', 'group', (int) $group['id']);

        $this->flashSuccess('You have left ' . $group['name'] . '.');
        $this->redirect('dashboard');
    }
}
