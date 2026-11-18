<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Invite
{
    /** @param array<string, mixed> $data */
    public static function create(array $data): array
    {
        $token = random_token(24);

        $id = Database::insert('invites', [
            'group_id' => $data['group_id'],
            'email' => strtolower((string) $data['email']),
            'role' => $data['role'] ?? 'member',
            'token' => $token,
            'invited_by' => $data['invited_by'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['id' => $id, 'token' => $token];
    }

    /** @return array<string, mixed>|null */
    public static function findByToken(string $token): ?array
    {
        return Database::fetch(
            'SELECT i.*, g.name AS group_name, g.slug AS group_slug, g.type AS group_type,
                    u.name AS inviter_name
             FROM invites i
             INNER JOIN `alumni_groups` g ON g.id = i.group_id
             LEFT JOIN users u ON u.id = i.invited_by
             WHERE i.token = ?
             LIMIT 1',
            [$token]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function pendingForGroup(int $groupId): array
    {
        return Database::fetchAll(
            'SELECT i.*, u.name AS inviter_name
             FROM invites i
             LEFT JOIN users u ON u.id = i.invited_by
             WHERE i.group_id = ? AND i.accepted_at IS NULL AND i.expires_at > NOW()
             ORDER BY i.created_at DESC',
            [$groupId]
        );
    }

    public static function markAccepted(int $id): void
    {
        Database::update('invites', ['accepted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
    }
}
