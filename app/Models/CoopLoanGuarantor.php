<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopLoanGuarantor
{
    public static function create(int $loanId, int $memberId, ?float $amount = null): int
    {
        return Database::insert('coop_loan_guarantors', [
            'loan_id' => $loanId,
            'member_id' => $memberId,
            'amount' => $amount,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forLoan(int $loanId): array
    {
        return Database::fetchAll(
            'SELECT gu.*, u.name, u.email, u.avatar, cm.member_no
             FROM coop_loan_guarantors gu
             INNER JOIN coop_members cm ON cm.id = gu.member_id
             INNER JOIN users u ON u.id = cm.user_id
             WHERE gu.loan_id = ?
             ORDER BY gu.status, u.name',
            [$loanId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function pendingForMember(int $memberId): array
    {
        return Database::fetchAll(
            'SELECT gu.*, l.principal, l.status AS loan_status, u.name AS borrower_name, u.avatar, g.slug AS group_slug
             FROM coop_loan_guarantors gu
             INNER JOIN coop_loans l ON l.id = gu.loan_id
             INNER JOIN coop_members bm ON bm.id = l.member_id
             INNER JOIN users u ON u.id = bm.user_id
             INNER JOIN cooperatives c ON c.id = l.cooperative_id
             INNER JOIN `alumni_groups` g ON g.id = c.group_id
             WHERE gu.member_id = ? AND gu.status = "pending" AND l.status IN ("pending", "approved")
             ORDER BY gu.created_at DESC',
            [$memberId]
        );
    }

    public static function acceptedCount(int $loanId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM coop_loan_guarantors WHERE loan_id = ? AND status = "accepted"',
            [$loanId],
            0
        );
    }

    public static function pendingCount(int $loanId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM coop_loan_guarantors WHERE loan_id = ? AND status = "pending"',
            [$loanId],
            0
        );
    }

    public static function respond(int $id, string $status): void
    {
        Database::update('coop_loan_guarantors', [
            'status' => $status,
            'responded_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM coop_loan_guarantors WHERE id = ? LIMIT 1', [$id]);
    }
}
