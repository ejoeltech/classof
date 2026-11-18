<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Announcement
{
    /** @return array<int, array<string, mixed>> */
    public static function forGroup(int $groupId, int $limit = 50): array
    {
        return Database::fetchAll(
            'SELECT a.*, u.name AS author_name, u.avatar
             FROM announcements a
             INNER JOIN users u ON u.id = a.user_id
             WHERE a.group_id = ?
             ORDER BY a.pinned DESC, a.created_at DESC
             LIMIT ' . $limit,
            [$groupId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function latestForUser(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT a.*, g.name AS group_name, g.slug AS group_slug, u.name AS author_name, u.avatar
             FROM announcements a
             INNER JOIN `alumni_groups` g ON g.id = a.group_id
             INNER JOIN memberships m ON m.group_id = a.group_id AND m.user_id = ? AND m.status = "active"
             INNER JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT ' . $limit,
            [$userId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT a.*, u.name AS author_name, u.avatar, g.slug AS group_slug, g.name AS group_name
             FROM announcements a
             INNER JOIN users u ON u.id = a.user_id
             INNER JOIN `alumni_groups` g ON g.id = a.group_id
             WHERE a.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('announcements', [
            'group_id' => $data['group_id'],
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'body' => $data['body'],
            'pinned' => !empty($data['pinned']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
