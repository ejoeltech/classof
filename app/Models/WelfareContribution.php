<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class WelfareContribution
{
    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('welfare_contributions', [
            'welfare_case_id' => $data['welfare_case_id'],
            'user_id' => $data['user_id'],
            'payment_id' => $data['payment_id'] ?? null,
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'anonymous' => !empty($data['anonymous']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forCase(int $caseId): array
    {
        return Database::fetchAll(
            'SELECT c.*, u.name, u.avatar
             FROM welfare_contributions c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.welfare_case_id = ?
             ORDER BY c.created_at DESC',
            [$caseId]
        );
    }

    public static function existsForPayment(int $paymentId): bool
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM welfare_contributions WHERE payment_id = ?',
            [$paymentId],
            0
        ) > 0;
    }

    public static function totalForUser(int $userId): float
    {
        return (float) Database::value(
            'SELECT COALESCE(SUM(amount), 0) FROM welfare_contributions WHERE user_id = ?',
            [$userId],
            0
        );
    }
}
