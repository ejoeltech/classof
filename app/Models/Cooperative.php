<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Cooperative
{
    public const OFFICER_ROLES = ['coordinator', 'treasurer', 'loan_officer', 'secretary'];

    public const ROLE_LABELS = [
        'member' => 'Member',
        'secretary' => 'Secretary',
        'treasurer' => 'Treasurer',
        'loan_officer' => 'Loan Officer',
        'coordinator' => 'Coordinator',
    ];

    /** @return array<string, mixed>|null */
    public static function forGroup(int $groupId): ?array
    {
        return Database::fetch('SELECT * FROM cooperatives WHERE group_id = ? LIMIT 1', [$groupId]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT c.*, g.name AS group_name, g.slug AS group_slug, g.currency
             FROM cooperatives c
             INNER JOIN `alumni_groups` g ON g.id = c.group_id
             WHERE c.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('cooperatives', [
            'group_id' => $data['group_id'],
            'name' => $data['name'],
            'registration_no' => $data['registration_no'] ?? null,
            'share_value' => $data['share_value'] ?? 1000,
            'monthly_savings' => $data['monthly_savings'] ?? 5000,
            'loan_interest_rate' => $data['loan_interest_rate'] ?? 10,
            'loan_duration_months' => $data['loan_duration_months'] ?? 12,
            'max_loan_multiple' => $data['max_loan_multiple'] ?? 3,
            'guarantors_required' => $data['guarantors_required'] ?? 2,
            'fine_absent' => $data['fine_absent'] ?? 500,
            'meeting_frequency' => $data['meeting_frequency'] ?? 'Monthly',
            'status' => 'active',
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('cooperatives', $data, ['id' => $id]);
    }

    /** @return array<string, float|int> */
    public static function summary(int $cooperativeId): array
    {
        $savings = (float) Database::value(
            'SELECT COALESCE(SUM(CASE WHEN type = "savings_deposit" THEN amount WHEN type = "savings_withdrawal" THEN -amount ELSE 0 END), 0)
             FROM coop_transactions WHERE cooperative_id = ?',
            [$cooperativeId],
            0
        );

        $shares = (float) Database::value(
            'SELECT COALESCE(SUM(amount), 0) FROM coop_transactions
             WHERE cooperative_id = ? AND type = "share_purchase" AND direction = "in"',
            [$cooperativeId],
            0
        );

        $cash = (float) Database::value(
            'SELECT COALESCE(SUM(CASE WHEN direction = "in" THEN amount ELSE -amount END), 0)
             FROM coop_transactions WHERE cooperative_id = ?',
            [$cooperativeId],
            0
        );

        $fines = (float) Database::value(
            'SELECT COALESCE(SUM(amount), 0) FROM coop_fines WHERE cooperative_id = ? AND status = "unpaid"',
            [$cooperativeId],
            0
        );

        $loansOutstanding = (float) Database::value(
            'SELECT COALESCE(SUM(total_repayable), 0) FROM coop_loans
             WHERE cooperative_id = ? AND status IN ("disbursed", "repaying", "defaulted")',
            [$cooperativeId],
            0
        ) - (float) Database::value(
            'SELECT COALESCE(SUM(t.amount), 0)
             FROM coop_transactions t
             INNER JOIN coop_loans l ON l.id = t.loan_id
             WHERE l.cooperative_id = ? AND t.type = "loan_repayment"',
            [$cooperativeId],
            0
        );

        return [
            'members' => (int) Database::value(
                'SELECT COUNT(*) FROM coop_members WHERE cooperative_id = ? AND status = "active"',
                [$cooperativeId],
                0
            ),
            'pending_members' => (int) Database::value(
                'SELECT COUNT(*) FROM coop_members WHERE cooperative_id = ? AND status = "pending"',
                [$cooperativeId],
                0
            ),
            'savings' => $savings,
            'shares' => $shares,
            'cash' => $cash,
            'fines_unpaid' => $fines,
            'loans_outstanding' => max(0, $loansOutstanding),
            'active_loans' => (int) Database::value(
                'SELECT COUNT(*) FROM coop_loans WHERE cooperative_id = ? AND status IN ("disbursed", "repaying")',
                [$cooperativeId],
                0
            ),
            'pending_loans' => (int) Database::value(
                'SELECT COUNT(*) FROM coop_loans WHERE cooperative_id = ? AND status = "pending"',
                [$cooperativeId],
                0
            ),
        ];
    }

    public static function canManage(?array $groupMembership, ?array $coopMember): bool
    {
        if ($groupMembership !== null
            && in_array($groupMembership['role'] ?? '', ['owner', 'admin'], true)
            && ($groupMembership['status'] ?? '') === 'active') {
            return true;
        }

        return $coopMember !== null
            && ($coopMember['status'] ?? '') === 'active'
            && in_array($coopMember['role'] ?? '', self::OFFICER_ROLES, true);
    }

    public static function isOfficer(?array $coopMember): bool
    {
        return $coopMember !== null
            && ($coopMember['status'] ?? '') === 'active'
            && in_array($coopMember['role'] ?? '', self::OFFICER_ROLES, true);
    }

    public static function roleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst($role);
    }
}
