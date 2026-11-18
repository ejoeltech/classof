<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuditLog
{
    public static function record(
        ?int $groupId,
        ?int $userId,
        string $action,
        string $description,
        ?string $targetType = null,
        ?int $targetId = null
    ): void {
        Database::insert('audit_logs', [
            'group_id' => $groupId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function recent(?int $groupId = null, int $limit = 30): array
    {
        if ($groupId === null) {
            return Database::fetchAll(
                'SELECT a.*, u.name AS user_name, u.avatar
                 FROM audit_logs a
                 LEFT JOIN users u ON u.id = a.user_id
                 ORDER BY a.created_at DESC
                 LIMIT ' . $limit
            );
        }

        return Database::fetchAll(
            'SELECT a.*, u.name AS user_name, u.avatar
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.group_id = ?
             ORDER BY a.created_at DESC
             LIMIT ' . $limit,
            [$groupId]
        );
    }
}
