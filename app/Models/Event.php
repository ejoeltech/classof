<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Event
{
    /** @return array<int, array<string, mixed>> */
    public static function forGroup(int $groupId, string $scope = 'upcoming', int $limit = 100): array
    {
        $where = $scope === 'past' ? 'e.starts_at < NOW()' : 'e.starts_at >= NOW()';
        $order = $scope === 'past' ? 'DESC' : 'ASC';

        return Database::fetchAll(
            'SELECT e.*, u.name AS creator_name,
                    (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id AND r.status = "going") AS going_count,
                    (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id AND r.status = "maybe") AS maybe_count
             FROM events e
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.group_id = ? AND e.status = "published" AND ' . $where . '
             ORDER BY e.starts_at ' . $order . '
             LIMIT ' . $limit,
            [$groupId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function upcomingForUser(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT e.*, g.name AS group_name, g.slug AS group_slug,
                    (SELECT r.status FROM event_rsvps r WHERE r.event_id = e.id AND r.user_id = ? LIMIT 1) AS my_rsvp
             FROM events e
             INNER JOIN `alumni_groups` g ON g.id = e.group_id
             INNER JOIN memberships m ON m.group_id = e.group_id AND m.user_id = ? AND m.status = "active"
             WHERE e.starts_at >= NOW() AND e.status = "published"
             ORDER BY e.starts_at ASC
             LIMIT ' . $limit,
            [$userId, $userId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT e.*, g.name AS group_name, g.slug AS group_slug, u.name AS creator_name
             FROM events e
             INNER JOIN `alumni_groups` g ON g.id = e.group_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('events', [
            'group_id' => $data['group_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'fee' => $data['fee'] ?? 0,
            'created_by' => $data['created_by'],
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function rsvpFor(int $eventId, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM event_rsvps WHERE event_id = ? AND user_id = ? LIMIT 1',
            [$eventId, $userId]
        );
    }

    public static function setRsvp(int $eventId, int $userId, string $status, int $guests = 0): void
    {
        $existing = self::rsvpFor($eventId, $userId);

        if ($existing !== null) {
            Database::update('event_rsvps', [
                'status' => $status,
                'guests' => $guests,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $existing['id']]);

            return;
        }

        Database::insert('event_rsvps', [
            'event_id' => $eventId,
            'user_id' => $userId,
            'status' => $status,
            'guests' => $guests,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, int> */
    public static function rsvpCounts(int $eventId): array
    {
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) AS total FROM event_rsvps WHERE event_id = ? GROUP BY status',
            [$eventId]
        );

        $counts = ['going' => 0, 'maybe' => 0, 'declined' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /** @return array<int, array<string, mixed>> */
    public static function attendees(int $eventId): array
    {
        return Database::fetchAll(
            'SELECT r.*, u.name, u.avatar, u.email
             FROM event_rsvps r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.event_id = ?
             ORDER BY FIELD(r.status, "going", "maybe", "declined"), u.name',
            [$eventId]
        );
    }
}
