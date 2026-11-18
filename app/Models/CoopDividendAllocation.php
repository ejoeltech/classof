<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopDividendAllocation
{
    public static function create(int $dividendId, int $memberId, float $basisAmount, float $amount): int
    {
        return Database::insert('coop_dividend_allocations', [
            'dividend_id' => $dividendId,
            'member_id' => $memberId,
            'basis_amount' => $basisAmount,
            'amount' => $amount,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function setTransaction(int $allocationId, int $transactionId): void
    {
        Database::update('coop_dividend_allocations', [
            'transaction_id' => $transactionId,
        ], ['id' => $allocationId]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forMember(int $memberId): array
    {
        return Database::fetchAll(
            'SELECT a.*, d.title, d.period, d.declared_at
             FROM coop_dividend_allocations a
             INNER JOIN coop_dividends d ON d.id = a.dividend_id
             WHERE a.member_id = ?
             ORDER BY d.declared_at DESC',
            [$memberId]
        );
    }
}
