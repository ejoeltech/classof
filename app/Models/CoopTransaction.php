<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopTransaction
{
    public const TYPES = [
        'share_purchase' => 'Share purchase',
        'savings_deposit' => 'Savings deposit',
        'savings_withdrawal' => 'Savings withdrawal',
        'loan_disbursement' => 'Loan disbursement',
        'loan_repayment' => 'Loan repayment',
        'fine' => 'Fine',
        'fine_payment' => 'Fine payment',
        'dividend' => 'Dividend',
        'adjustment' => 'Adjustment',
    ];

    /** @param array<string, mixed> $data */
    public static function record(array $data): int
    {
        return Database::insert('coop_transactions', [
            'cooperative_id' => $data['cooperative_id'],
            'member_id' => $data['member_id'] ?? null,
            'type' => $data['type'],
            'direction' => $data['direction'],
            'amount' => $data['amount'],
            'method' => $data['method'] ?? 'cash',
            'reference' => $data['reference'] ?? reference_code('CP'),
            'payment_id' => $data['payment_id'] ?? null,
            'loan_id' => $data['loan_id'] ?? null,
            'fine_id' => $data['fine_id'] ?? null,
            'dividend_id' => $data['dividend_id'] ?? null,
            'note' => $data['note'] ?? null,
            'recorded_by' => $data['recorded_by'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function existsForPayment(int $paymentId): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM coop_transactions WHERE payment_id = ?', [$paymentId], 0) > 0;
    }

    /**
     * @param array{type?: string, member_id?: int, search?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function forCoop(int $cooperativeId, array $filters = [], int $limit = 300): array
    {
        $sql = 'SELECT t.*, u.name AS member_name, u.avatar, rb.name AS recorder_name
                FROM coop_transactions t
                LEFT JOIN coop_members cm ON cm.id = t.member_id
                LEFT JOIN users u ON u.id = cm.user_id
                LEFT JOIN users rb ON rb.id = t.recorded_by
                WHERE t.cooperative_id = ?';
        $params = [$cooperativeId];

        if (!empty($filters['type'])) {
            $sql .= ' AND t.type = ?';
            $params[] = $filters['type'];
        }

        if (!empty($filters['member_id'])) {
            $sql .= ' AND t.member_id = ?';
            $params[] = (int) $filters['member_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (t.reference LIKE ? OR t.note LIKE ? OR u.name LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like);
        }

        $sql .= ' ORDER BY t.occurred_at DESC, t.id DESC LIMIT ' . $limit;

        return Database::fetchAll($sql, $params);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forMember(int $cooperativeId, int $memberId, int $limit = 200): array
    {
        return Database::fetchAll(
            'SELECT t.*, u.name AS member_name
             FROM coop_transactions t
             LEFT JOIN coop_members cm ON cm.id = t.member_id
             LEFT JOIN users u ON u.id = cm.user_id
             WHERE t.cooperative_id = ? AND t.member_id = ?
             ORDER BY t.occurred_at DESC, t.id DESC
             LIMIT ' . $limit,
            [$cooperativeId, $memberId]
        );
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
