<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopLoanInstallment
{
    /** @param array<string, mixed> $loan */
    public static function generateForLoan(array $loan): void
    {
        $count = max(1, (int) $loan['duration_months']);
        $total = (float) $loan['total_repayable'];
        $monthly = round($total / $count, 2);
        $base = strtotime((string) ($loan['disbursed_at'] ?? date('Y-m-d H:i:s')));
        $scheduled = 0.0;

        for ($i = 1; $i <= $count; $i++) {
            $amount = $i === $count ? round($total - $scheduled, 2) : $monthly;
            $scheduled += $amount;

            Database::insert('coop_loan_installments', [
                'loan_id' => (int) $loan['id'],
                'installment_no' => $i,
                'due_date' => date('Y-m-d', strtotime('+' . $i . ' month', $base)),
                'amount_due' => $amount,
                'amount_paid' => 0,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function forLoan(int $loanId): array
    {
        return Database::fetchAll(
            'SELECT * FROM coop_loan_installments WHERE loan_id = ? ORDER BY installment_no',
            [$loanId]
        );
    }

    /**
     * Apply a repayment across the oldest unpaid instalments.
     *
     * @return float the amount applied
     */
    public static function applyRepayment(int $loanId, float $amount): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $installments = Database::fetchAll(
            'SELECT * FROM coop_loan_installments
             WHERE loan_id = ? AND status <> "paid"
             ORDER BY installment_no',
            [$loanId]
        );

        $remaining = $amount;

        foreach ($installments as $installment) {
            if ($remaining <= 0) {
                break;
            }

            $due = (float) $installment['amount_due'];
            $paid = (float) $installment['amount_paid'];
            $outstanding = max(0.0, $due - $paid);
            $apply = min($remaining, $outstanding);

            if ($apply <= 0) {
                continue;
            }

            $newPaid = round($paid + $apply, 2);
            $status = $newPaid >= $due - 0.009 ? 'paid' : 'partial';

            Database::update('coop_loan_installments', [
                'amount_paid' => $newPaid,
                'status' => $status,
            ], ['id' => (int) $installment['id']]);

            $remaining = round($remaining - $apply, 2);
        }

        return round($amount - max(0.0, $remaining), 2);
    }

    public static function overdueCount(int $loanId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM coop_loan_installments
             WHERE loan_id = ? AND status <> "paid" AND due_date < CURDATE()',
            [$loanId],
            0
        );
    }
}
