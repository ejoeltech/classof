<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Payment
{
    public const PURPOSES = [
        'dues' => 'Dues',
        'welfare' => 'Welfare contribution',
        'event' => 'Event fee',
        'donation' => 'Donation',
        'coop_savings' => 'Co-op savings',
        'coop_shares' => 'Co-op shares',
        'coop_loan' => 'Co-op loan repayment',
        'coop_fine' => 'Co-op fine',
    ];

    /** @param array<string, mixed> $data */
    public static function create(array $data): array
    {
        $reference = $data['reference'] ?? reference_code('CLS');

        $id = Database::insert('payments', [
            'group_id' => $data['group_id'],
            'user_id' => $data['user_id'],
            'purpose' => $data['purpose'],
            'dues_cycle_id' => $data['dues_cycle_id'] ?? null,
            'welfare_case_id' => $data['welfare_case_id'] ?? null,
            'event_id' => $data['event_id'] ?? null,
            'coop_loan_id' => $data['coop_loan_id'] ?? null,
            'coop_fine_id' => $data['coop_fine_id'] ?? null,
            'reference' => $reference,
            'gateway' => $data['gateway'] ?? 'manual',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'NGN',
            'status' => 'pending',
            'payer_name' => $data['payer_name'] ?? null,
            'payer_email' => $data['payer_email'] ?? null,
            'meta' => isset($data['meta']) ? json_encode($data['meta']) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return self::find($id) ?? [];
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT p.*, u.name AS user_name, u.email AS user_email, g.slug AS group_slug, g.name AS group_name
             FROM payments p
             LEFT JOIN users u ON u.id = p.user_id
             INNER JOIN `alumni_groups` g ON g.id = p.group_id
             WHERE p.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @return array<string, mixed>|null */
    public static function findByReference(string $reference): ?array
    {
        return Database::fetch(
            'SELECT p.*, u.name AS user_name, u.email AS user_email, g.slug AS group_slug, g.name AS group_name
             FROM payments p
             LEFT JOIN users u ON u.id = p.user_id
             INNER JOIN `alumni_groups` g ON g.id = p.group_id
             WHERE p.reference = ?
             LIMIT 1',
            [$reference]
        );
    }

    /** @param array<string, mixed> $meta */
    public static function markSuccess(int $id, array $meta = [], ?string $paidAt = null): void
    {
        $existing = Database::fetch('SELECT meta FROM payments WHERE id = ?', [$id]);
        $merged = [];

        if (!empty($existing['meta'])) {
            $decoded = json_decode((string) $existing['meta'], true);
            $merged = is_array($decoded) ? $decoded : [];
        }

        Database::update('payments', [
            'status' => 'success',
            'paid_at' => $paidAt ?? date('Y-m-d H:i:s'),
            'meta' => json_encode(array_merge($merged, $meta)),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /** @param array<string, mixed> $meta */
    public static function markFailed(int $id, array $meta = []): void
    {
        Database::update('payments', [
            'status' => 'failed',
            'meta' => json_encode($meta),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /** @param array<string, mixed> $meta */
    public static function mergeMeta(int $id, array $meta): void
    {
        $existing = Database::fetch('SELECT meta FROM payments WHERE id = ?', [$id]);
        $merged = [];

        if (!empty($existing['meta'])) {
            $decoded = json_decode((string) $existing['meta'], true);
            $merged = is_array($decoded) ? $decoded : [];
        }

        Database::update('payments', [
            'meta' => json_encode(array_merge($merged, $meta)),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function confirmManual(int $id, int $confirmedBy): void
    {
        Database::update('payments', [
            'confirmed_by' => $confirmedBy,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /** @return array<string, mixed>|null */
    public static function successFor(string $purpose, int $relatedId, int $userId): ?array
    {
        $column = match ($purpose) {
            'dues' => 'dues_cycle_id',
            'welfare' => 'welfare_case_id',
            'event' => 'event_id',
            default => 'id',
        };

        return Database::fetch(
            'SELECT * FROM payments WHERE purpose = ? AND ' . $column . ' = ? AND user_id = ? AND status = "success"
             ORDER BY paid_at DESC LIMIT 1',
            [$purpose, $relatedId, $userId]
        );
    }

    /**
     * @param array{purpose?: string, status?: string, search?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function forGroup(int $groupId, array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT p.*, u.name AS user_name, u.email AS user_email, u.avatar
                FROM payments p
                LEFT JOIN users u ON u.id = p.user_id
                WHERE p.group_id = ?';
        $params = [$groupId];

        if (!empty($filters['purpose'])) {
            $sql .= ' AND p.purpose = ?';
            $params[] = $filters['purpose'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (p.reference LIKE ? OR u.name LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT ' . $limit;

        return Database::fetchAll($sql, $params);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 100): array
    {
        return Database::fetchAll(
            'SELECT p.*, g.name AS group_name, g.slug AS group_slug
             FROM payments p
             INNER JOIN `alumni_groups` g ON g.id = p.group_id
             WHERE p.user_id = ?
             ORDER BY p.created_at DESC
             LIMIT ' . $limit,
            [$userId]
        );
    }

    /** @return array<string, float|int> */
    public static function summary(int $groupId): array
    {
        $rows = Database::fetchAll(
            'SELECT purpose, status, COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount
             FROM payments WHERE group_id = ?
             GROUP BY purpose, status',
            [$groupId]
        );

        $summary = [
            'collected' => 0.0,
            'pending' => 0.0,
            'pending_count' => 0,
            'dues' => 0.0,
            'welfare' => 0.0,
            'events' => 0.0,
            'coop' => 0.0,
        ];

        foreach ($rows as $row) {
            $amount = (float) $row['amount'];

            if ($row['status'] === 'success') {
                $summary['collected'] += $amount;
                if ($row['purpose'] === 'dues') {
                    $summary['dues'] += $amount;
                } elseif ($row['purpose'] === 'welfare') {
                    $summary['welfare'] += $amount;
                } elseif ($row['purpose'] === 'event') {
                    $summary['events'] += $amount;
                } elseif (str_starts_with((string) $row['purpose'], 'coop_')) {
                    $summary['coop'] += $amount;
                }
            }

            if ($row['status'] === 'pending') {
                $summary['pending'] += $amount;
                $summary['pending_count'] += (int) $row['total'];
            }
        }

        return $summary;
    }

    public static function pendingCount(int $groupId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM payments WHERE group_id = ? AND status = "pending"',
            [$groupId],
            0
        );
    }
}
