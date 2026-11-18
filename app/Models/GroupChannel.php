<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class GroupChannel
{
    public const HOSTS = ['chat.whatsapp.com', 'www.chat.whatsapp.com', 'wa.me', 'www.wa.me', 'api.whatsapp.com'];

    /** @return array<int, array<string, mixed>> */
    public static function forGroup(int $groupId, bool $includeRevoked = false): array
    {
        $sql = 'SELECT * FROM group_channels WHERE group_id = ?';

        if (!$includeRevoked) {
            $sql .= ' AND revoked_at IS NULL';
        }

        $sql .= ' ORDER BY is_primary DESC, label';

        return Database::fetchAll($sql, [$groupId]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function visibleFor(int $groupId, bool $isManager): array
    {
        $sql = 'SELECT * FROM group_channels
                WHERE group_id = ? AND revoked_at IS NULL';
        $params = [$groupId];

        if (!$isManager) {
            $sql .= ' AND visible_to = "members"';
        }

        $sql .= ' ORDER BY is_primary DESC, label';

        return Database::fetchAll($sql, $params);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM group_channels WHERE id = ? LIMIT 1', [$id]);
    }

    public static function create(array $data): int
    {
        if (!empty($data['is_primary'])) {
            Database::run('UPDATE group_channels SET is_primary = 0 WHERE group_id = ?', [(int) $data['group_id']]);
        }

        return Database::insert('group_channels', [
            'group_id' => $data['group_id'],
            'label' => $data['label'],
            'invite_url' => $data['invite_url'],
            'visible_to' => $data['visible_to'] ?? 'members',
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
            'last_verified_at' => date('Y-m-d H:i:s'),
            'revoked_at' => null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function update(int $id, array $data): void
    {
        if (!empty($data['is_primary'])) {
            $channel = self::find($id);

            if ($channel !== null) {
                Database::run('UPDATE group_channels SET is_primary = 0 WHERE group_id = ?', [(int) $channel['group_id']]);
            }
        }

        Database::update('group_channels', [
            'label' => $data['label'],
            'invite_url' => $data['invite_url'],
            'visible_to' => $data['visible_to'],
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
            'last_verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function revoke(int $id): void
    {
        Database::update('group_channels', [
            'revoked_at' => date('Y-m-d H:i:s'),
            'is_primary' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function validInviteUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        return in_array($host, self::HOSTS, true) && (string) ($parts['path'] ?? '') !== '';
    }
}
