<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopMeeting
{
    public static function create(array $data): int
    {
        return Database::insert('coop_meetings', [
            'cooperative_id' => $data['cooperative_id'],
            'title' => $data['title'],
            'agenda' => $data['agenda'] ?? null,
            'location' => $data['location'] ?? null,
            'scheduled_at' => $data['scheduled_at'],
            'status' => 'scheduled',
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT m.*, c.name AS coop_name, c.group_id, g.name AS group_name, g.slug AS group_slug
             FROM coop_meetings m
             INNER JOIN cooperatives c ON c.id = m.cooperative_id
             INNER JOIN `alumni_groups` g ON g.id = c.group_id
             WHERE m.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forCoop(int $cooperativeId): array
    {
        return Database::fetchAll(
            'SELECT m.*,
                    (SELECT COUNT(*) FROM coop_meeting_attendance a WHERE a.meeting_id = m.id AND a.status = "present") AS present_count,
                    (SELECT COUNT(*) FROM coop_meeting_attendance a WHERE a.meeting_id = m.id) AS recorded_count
             FROM coop_meetings m
             WHERE m.cooperative_id = ?
             ORDER BY m.scheduled_at DESC',
            [$cooperativeId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function attendance(int $meetingId): array
    {
        return Database::fetchAll(
            'SELECT a.*, cm.id AS member_id, cm.member_no, u.name, u.avatar, u.email
             FROM coop_meeting_attendance a
             INNER JOIN coop_members cm ON cm.id = a.member_id
             INNER JOIN users u ON u.id = cm.user_id
             WHERE a.meeting_id = ?
             ORDER BY FIELD(a.status, "present", "apology", "excused", "absent"), u.name',
            [$meetingId]
        );
    }

    /**
     * Active members with their attendance row (if recorded).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function rollCall(int $cooperativeId, int $meetingId): array
    {
        return Database::fetchAll(
            'SELECT cm.id AS member_id, cm.member_no, u.name, u.avatar,
                    a.status, a.note
             FROM coop_members cm
             INNER JOIN users u ON u.id = cm.user_id
             LEFT JOIN coop_meeting_attendance a ON a.meeting_id = ? AND a.member_id = cm.id
             WHERE cm.cooperative_id = ? AND cm.status = "active"
             ORDER BY u.name',
            [$meetingId, $cooperativeId]
        );
    }

    public static function setAttendance(int $meetingId, int $memberId, string $status, ?string $note = null): void
    {
        $existing = Database::fetch(
            'SELECT id FROM coop_meeting_attendance WHERE meeting_id = ? AND member_id = ? LIMIT 1',
            [$meetingId, $memberId]
        );

        if ($existing !== null) {
            Database::update('coop_meeting_attendance', [
                'status' => $status,
                'note' => $note,
            ], ['id' => (int) $existing['id']]);

            return;
        }

        Database::insert('coop_meeting_attendance', [
            'meeting_id' => $meetingId,
            'member_id' => $memberId,
            'status' => $status,
            'note' => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function complete(int $id, ?string $minutes = null): void
    {
        Database::update('coop_meetings', [
            'status' => 'completed',
            'minutes' => $minutes,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function cancel(int $id): void
    {
        Database::update('coop_meetings', [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }
}
