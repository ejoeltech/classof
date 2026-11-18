<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\CoopFine;
use App\Models\CoopMeeting;
use App\Models\Cooperative;

final class CoopMeetingController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);
        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);
        $isMember = $coopMember !== null && $coopMember['status'] === 'active';

        if (!$isOfficer && !$isMember) {
            $this->flashError('Only co-op members can view meetings.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        $this->view('coop/meetings/index', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'isOfficer' => $isOfficer,
            'meetings' => CoopMeeting::forCoop((int) $coop['id']),
        ]);
    }

    public function store(string $slug): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);

        $data = $this->validate([
            'title' => 'required|min:3|max:160',
            'scheduled_at' => 'required|date',
            'location' => 'max:160',
            'agenda' => 'max:3000',
        ]);

        $id = CoopMeeting::create([
            'cooperative_id' => (int) $coop['id'],
            'title' => $data['title'],
            'agenda' => $data['agenda'] ?: null,
            'location' => $data['location'] ?: null,
            'scheduled_at' => date('Y-m-d H:i:s', strtotime((string) $data['scheduled_at'])),
            'created_by' => (int) user()['id'],
        ]);

        $this->audit((int) $group['id'], 'coop.meeting_scheduled', 'Scheduled meeting "' . $data['title'] . '"', 'coop_meeting', $id);
        $this->flashSuccess('Meeting scheduled.');
        $this->redirect('groups/' . $slug . '/coop/meetings/' . $id);
    }

    public function show(string $slug, string $id): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);
        $meeting = $this->findMeeting((int) $coop['id'], (int) $id);
        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);

        if (!$isOfficer && ($coopMember === null || $coopMember['status'] !== 'active')) {
            Response::abort(403, 'You cannot view this meeting.');
        }

        $this->view('coop/meetings/show', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'meeting' => $meeting,
            'isOfficer' => $isOfficer,
            'rollCall' => CoopMeeting::rollCall((int) $coop['id'], (int) $meeting['id']),
            'attendance' => CoopMeeting::attendance((int) $meeting['id']),
        ]);
    }

    public function attendance(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $meeting = $this->findMeeting((int) $coop['id'], (int) $id);

        $statuses = (array) Request::input('status', []);
        $notes = (array) Request::input('note', []);
        $allowed = ['present', 'absent', 'apology', 'excused'];
        $updated = 0;

        foreach ($statuses as $memberId => $status) {
            $memberId = (int) $memberId;

            if (!in_array($status, $allowed, true)) {
                continue;
            }

            CoopMeeting::setAttendance((int) $meeting['id'], $memberId, (string) $status, isset($notes[$memberId]) ? mb_substr((string) $notes[$memberId], 0, 160) : null);
            $updated++;
        }

        $this->audit((int) $group['id'], 'coop.attendance_recorded', 'Recorded attendance for ' . $updated . ' members', 'coop_meeting', (int) $meeting['id']);
        $this->flashSuccess('Attendance saved for ' . $updated . ' members.');
        $this->redirect('groups/' . $slug . '/coop/meetings/' . $meeting['id']);
    }

    public function complete(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $meeting = $this->findMeeting((int) $coop['id'], (int) $id);

        $minutes = Request::string('minutes');
        CoopMeeting::complete((int) $meeting['id'], $minutes !== '' ? $minutes : null);

        $this->audit((int) $group['id'], 'coop.meeting_completed', 'Completed meeting "' . $meeting['title'] . '"', 'coop_meeting', (int) $meeting['id']);
        $this->flashSuccess('Meeting marked as completed.');
        $this->redirect('groups/' . $slug . '/coop/meetings/' . $meeting['id']);
    }

    public function fineAbsent(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $meeting = $this->findMeeting((int) $coop['id'], (int) $id);

        $amount = (float) $coop['fine_absent'];

        if ($amount <= 0) {
            $this->flashError('Set an absence fine amount in co-op settings first.');
            $this->redirect('groups/' . $slug . '/coop/meetings/' . $meeting['id']);
        }

        $created = 0;

        foreach (CoopMeeting::attendance((int) $meeting['id']) as $row) {
            if ($row['status'] !== 'absent') {
                continue;
            }

            if (CoopFine::existsForMeetingMember((int) $meeting['id'], (int) $row['member_id'])) {
                continue;
            }

            CoopFine::create([
                'cooperative_id' => (int) $coop['id'],
                'member_id' => (int) $row['member_id'],
                'meeting_id' => (int) $meeting['id'],
                'reason' => 'Absence from ' . $meeting['title'],
                'amount' => $amount,
                'issued_by' => (int) user()['id'],
            ]);

            $created++;
        }

        $this->audit((int) $group['id'], 'coop.absence_fines', 'Issued ' . $created . ' absence fine(s)', 'coop_meeting', (int) $meeting['id']);
        $this->flashSuccess($created > 0 ? $created . ' absence fine(s) issued.' : 'No new absence fines were needed.');
        $this->redirect('groups/' . $slug . '/coop/meetings/' . $meeting['id']);
    }

    public function cancel(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $meeting = $this->findMeeting((int) $coop['id'], (int) $id);

        CoopMeeting::cancel((int) $meeting['id']);
        $this->audit((int) $group['id'], 'coop.meeting_cancelled', 'Cancelled meeting "' . $meeting['title'] . '"', 'coop_meeting', (int) $meeting['id']);
        $this->flashSuccess('Meeting cancelled.');
        $this->redirect('groups/' . $slug . '/coop/meetings');
    }

    /** @return array<string, mixed> */
    private function findMeeting(int $cooperativeId, int $meetingId): array
    {
        $meeting = CoopMeeting::find($meetingId);

        if ($meeting === null || (int) $meeting['cooperative_id'] !== $cooperativeId) {
            Response::abort(404, 'That co-op meeting could not be found.');
        }

        return $meeting;
    }
}
