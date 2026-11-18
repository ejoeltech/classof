<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopMember
{
    /** @return array<string, mixed>|null */
    public static function forUser(int $cooperativeId, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM coop_members WHERE cooperative_id = ? AND user_id = ? LIMIT 1',
            [$cooperativeId, $userId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT cm.*, u.name, u.email, u.phone, u.avatar, u.occupation, u.location,
                    (SELECT name FROM users WHERE id = cm.approved_by) AS approver_name
             FROM coop_members cm
             INNER JOIN users u ON u.id = cm.user_id
             WHERE cm.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $status = $data['status'] ?? 'pending';
        $now = date('Y-m-d H:i:s');

        return Database::insert('coop_members', [
            'cooperative_id' => $data['cooperative_id'],
            'user_id' => $data['user_id'],
            'member_no' => $data['member_no'] ?? self::nextNumber((int) $data['cooperative_id']),
            'role' => $data['role'] ?? 'member',
            'status' => $status,
            'shares' => $data['shares'] ?? 0,
            'joined_at' => $data['joined_at'] ?? ($status === 'active' ? $now : null),
            'approved_by' => $data['approved_by'] ?? null,
            'reviewed_at' => $data['reviewed_at'] ?? ($status === 'active' ? $now : null),
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public static function nextNumber(int $cooperativeId): string
    {
        $count = (int) Database::value('SELECT COUNT(*) FROM coop_members WHERE cooperative_id = ?', [$cooperativeId], 0);

        return 'COOP-' . str_pad((string) $cooperativeId, 3, '0', STR_PAD_LEFT) . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param array{status?: string, search?: string, role?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function list(int $cooperativeId, array $filters = []): array
    {
        $sql = 'SELECT cm.*, u.name, u.email, u.phone, u.avatar, u.occupation,
                       (SELECT COALESCE(SUM(CASE WHEN t.type = "savings_deposit" THEN t.amount WHEN t.type = "savings_withdrawal" THEN -t.amount ELSE 0 END), 0)
                        FROM coop_transactions t WHERE t.member_id = cm.id) AS savings_balance
                FROM coop_members cm
                INNER JOIN users u ON u.id = cm.user_id
                WHERE cm.cooperative_id = ?';
        $params = [$cooperativeId];

        if (!empty($filters['status'])) {
            $sql .= ' AND cm.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['role'])) {
            $sql .= ' AND cm.role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR cm.member_no LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like);
        }

        $sql .= ' ORDER BY FIELD(cm.role, "coordinator", "treasurer", "loan_officer", "secretary", "member"), u.name';

        return Database::fetchAll($sql, $params);
    }

    /** @return array<int, array<string, mixed>> */
    public static function active(int $cooperativeId): array
    {
        return Database::fetchAll(
            'SELECT cm.*, u.name, u.email, u.avatar
             FROM coop_members cm
             INNER JOIN users u ON u.id = cm.user_id
             WHERE cm.cooperative_id = ? AND cm.status = "active"
             ORDER BY u.name',
            [$cooperativeId]
        );
    }

    public static function approve(int $id, int $approvedBy): void
    {
        $now = date('Y-m-d H:i:s');

        Database::update('coop_members', [
            'status' => 'active',
            'joined_at' => $now,
            'approved_by' => $approvedBy,
            'reviewed_at' => $now,
            'rejection_reason' => null,
            'updated_at' => $now,
        ], ['id' => $id]);
    }

    public static function reject(int $id, int $reviewedBy, ?string $reason = null): void
    {
        $now = date('Y-m-d H:i:s');

        Database::update('coop_members', [
            'status' => 'rejected',
            'reviewed_at' => $now,
            'rejection_reason' => $reason,
            'updated_at' => $now,
        ], ['id' => $id]);
    }

    public static function setRole(int $id, string $role): void
    {
        Database::update('coop_members', [
            'role' => $role,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::update('coop_members', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function addShares(int $id, float $units): void
    {
        Database::run(
            'UPDATE coop_members SET shares = shares + ?, updated_at = ? WHERE id = ?',
            [$units, date('Y-m-d H:i:s'), $id]
        );
    }

    public static function savingsBalance(int $cooperativeId, int $memberId): float
    {
        return (float) Database::value(
            'SELECT COALESCE(SUM(CASE WHEN type = "savings_deposit" THEN amount WHEN type = "savings_withdrawal" THEN -amount ELSE 0 END), 0)
             FROM coop_transactions WHERE cooperative_id = ? AND member_id = ?',
            [$cooperativeId, $memberId],
            0
        );
    }

    public static function shareCapital(int $cooperativeId, int $memberId): float
    {
        $row = Database::fetch(
            'SELECT cm.shares, c.share_value
             FROM coop_members cm
             INNER JOIN cooperatives c ON c.id = cm.cooperative_id
             WHERE cm.id = ? AND cm.cooperative_id = ? LIMIT 1',
            [$memberId, $cooperativeId]
        );

        return $row === null ? 0.0 : (float) $row['shares'] * (float) $row['share_value'];
    }
}
