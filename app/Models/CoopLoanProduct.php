<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopLoanProduct
{
    /** @return array<int, array<string, mixed>> */
    public static function forCoop(int $cooperativeId): array
    {
        return Database::fetchAll(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM coop_loans l WHERE l.product_id = p.id) AS loan_count
             FROM coop_loan_products p
             WHERE p.cooperative_id = ?
             ORDER BY p.status, p.name',
            [$cooperativeId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function active(int $cooperativeId): array
    {
        return Database::fetchAll(
            'SELECT * FROM coop_loan_products WHERE cooperative_id = ? AND status = "active" ORDER BY name',
            [$cooperativeId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM coop_loan_products WHERE id = ? LIMIT 1', [$id]);
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('coop_loan_products', [
            'cooperative_id' => $data['cooperative_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'interest_rate' => $data['interest_rate'],
            'duration_months' => $data['duration_months'],
            'min_amount' => $data['min_amount'] ?? 0,
            'max_amount' => $data['max_amount'] ?? null,
            'max_multiple' => $data['max_multiple'] ?? null,
            'guarantors_required' => $data['guarantors_required'] ?? 2,
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::update('coop_loan_products', ['status' => $status], ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('coop_loan_products', ['id' => $id]);
    }
}
