<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopLoan
{
    public const STATUSES = [
        'pending' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'disbursed' => 'Disbursed',
        'repaying' => 'Repaying',
        'completed' => 'Completed',
        'defaulted' => 'Defaulted',
        'cancelled' => 'Cancelled',
    ];

    public const ACTIVE_STATUSES = ['pending', 'approved', 'disbursed', 'repaying', 'defaulted'];

    /**
     * Flat simple interest over the loan duration.
     *
     * @return array{interest: float, total: float, monthly: float}
     */
    public static function calculateTerms(float $principal, float $rate, int $months): array
    {
        $months = max(1, $months);
        $interest = round($principal * ($rate / 100) * ($months / 12), 2);
        $total = round($principal + $interest, 2);

        return [
            'interest' => $interest,
            'total' => $total,
            'monthly' => round($total / $months, 2),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('coop_loans', [
            'cooperative_id' => $data['cooperative_id'],
            'member_id' => $data['member_id'],
            'product_id' => $data['product_id'] ?? null,
            'principal' => $data['principal'],
            'interest_rate' => $data['interest_rate'],
            'duration_months' => $data['duration_months'],
            'interest_amount' => $data['interest_amount'],
            'total_repayable' => $data['total_repayable'],
            'monthly_repayment' => $data['monthly_repayment'],
            'purpose' => $data['purpose'] ?? null,
            'status' => 'pending',
            'applied_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT l.*, u.name AS member_name, u.email AS member_email, u.avatar,
                    cm.user_id, cm.member_no, cm.shares,
                    p.name AS product_name,
                    c.name AS coop_name, c.share_value, c.group_id,
                    g.name AS group_name, g.slug AS group_slug, g.currency,
                    (SELECT name FROM users WHERE id = l.approved_by) AS approver_name,
                    (SELECT name FROM users WHERE id = l.disbursed_by) AS disburser_name
             FROM coop_loans l
             INNER JOIN coop_members cm ON cm.id = l.member_id
             INNER JOIN users u ON u.id = cm.user_id
             INNER JOIN cooperatives c ON c.id = l.cooperative_id
             INNER JOIN `alumni_groups` g ON g.id = c.group_id
             LEFT JOIN coop_loan_products p ON p.id = l.product_id
             WHERE l.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /**
     * @param array{status?: string, member_id?: int, search?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function forCoop(int $cooperativeId, array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT l.*, u.name AS member_name, u.avatar, cm.member_no, p.name AS product_name,
                       (SELECT COUNT(*) FROM coop_loan_guarantors gu WHERE gu.loan_id = l.id AND gu.status = "accepted") AS accepted_guarantors,
                       (SELECT COUNT(*) FROM coop_loan_guarantors gu WHERE gu.loan_id = l.id) AS guarantor_count
                FROM coop_loans l
                INNER JOIN coop_members cm ON cm.id = l.member_id
                INNER JOIN users u ON u.id = cm.user_id
                LEFT JOIN coop_loan_products p ON p.id = l.product_id
                WHERE l.cooperative_id = ?';
        $params = [$cooperativeId];

        if (!empty($filters['status'])) {
            $sql .= ' AND l.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['member_id'])) {
            $sql .= ' AND l.member_id = ?';
            $params[] = (int) $filters['member_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (u.name LIKE ? OR l.purpose LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like);
        }

        $sql .= ' ORDER BY FIELD(l.status, "pending", "approved", "repaying", "disbursed", "defaulted", "completed", "rejected", "cancelled"), l.applied_at DESC LIMIT ' . $limit;

        return Database::fetchAll($sql, $params);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forMember(int $memberId): array
    {
        return Database::fetchAll(
            'SELECT l.*, p.name AS product_name,
                    (SELECT COALESCE(SUM(t.amount), 0) FROM coop_transactions t WHERE t.loan_id = l.id AND t.type = "loan_repayment") AS repaid
             FROM coop_loans l
             LEFT JOIN coop_loan_products p ON p.id = l.product_id
             WHERE l.member_id = ?
             ORDER BY l.applied_at DESC',
            [$memberId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function activeForMember(int $memberId): ?array
    {
        return Database::fetch(
            'SELECT * FROM coop_loans
             WHERE member_id = ? AND status IN ("pending", "approved", "disbursed", "repaying", "defaulted")
             ORDER BY id DESC LIMIT 1',
            [$memberId]
        );
    }

    public static function setStatus(int $id, string $status, array $extra = []): void
    {
        $extra['status'] = $status;
        $extra['updated_at'] = date('Y-m-d H:i:s');
        Database::update('coop_loans', $extra, ['id' => $id]);
    }

    public static function totalRepaid(int $loanId): float
    {
        return (float) Database::value(
            'SELECT COALESCE(SUM(amount), 0) FROM coop_transactions WHERE loan_id = ? AND type = "loan_repayment"',
            [$loanId],
            0
        );
    }

    /** @param array<string, mixed> $loan */
    public static function outstanding(array $loan): float
    {
        return max(0.0, (float) $loan['total_repayable'] - self::totalRepaid((int) $loan['id']));
    }

    /** @param array<string, mixed> $loan */
    public static function progress(array $loan): float
    {
        $total = (float) $loan['total_repayable'];

        return $total > 0 ? min(100.0, (self::totalRepaid((int) $loan['id']) / $total) * 100) : 0.0;
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUSES[$status] ?? ucfirst($status);
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'pending' => 'warn',
            'approved' => 'info',
            'repaying', 'disbursed' => 'success',
            'completed' => 'neutral',
            'rejected', 'cancelled', 'defaulted' => 'danger',
            default => 'neutral',
        };
    }
}
