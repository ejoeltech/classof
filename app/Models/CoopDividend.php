<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopDividend
{
    public static function create(array $data): int
    {
        return Database::insert('coop_dividends', [
            'cooperative_id' => $data['cooperative_id'],
            'title' => $data['title'],
            'period' => $data['period'] ?? null,
            'basis' => $data['basis'] ?? 'shares',
            'total_amount' => $data['total_amount'],
            'status' => 'draft',
            'created_by' => $data['created_by'] ?? null,
            'declared_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT d.*, c.name AS coop_name, c.group_id, c.share_value, g.slug AS group_slug, g.name AS group_name
             FROM coop_dividends d
             INNER JOIN cooperatives c ON c.id = d.cooperative_id
             INNER JOIN `alumni_groups` g ON g.id = c.group_id
             WHERE d.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forCoop(int $cooperativeId): array
    {
        return Database::fetchAll(
            'SELECT d.*,
                    (SELECT COUNT(*) FROM coop_dividend_allocations a WHERE a.dividend_id = d.id) AS allocation_count,
                    (SELECT COALESCE(SUM(a.amount), 0) FROM coop_dividend_allocations a WHERE a.dividend_id = d.id) AS allocated_amount
             FROM coop_dividends d
             WHERE d.cooperative_id = ?
             ORDER BY d.declared_at DESC',
            [$cooperativeId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function allocations(int $dividendId): array
    {
        return Database::fetchAll(
            'SELECT a.*, u.name, u.avatar, cm.member_no
             FROM coop_dividend_allocations a
             INNER JOIN coop_members cm ON cm.id = a.member_id
             INNER JOIN users u ON u.id = cm.user_id
             WHERE a.dividend_id = ?
             ORDER BY a.amount DESC, u.name',
            [$dividendId]
        );
    }
}
