<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CoopDividend;
use App\Models\CoopDividendAllocation;
use App\Models\CoopFine;
use App\Models\CoopLoan;
use App\Models\CoopLoanGuarantor;
use App\Models\CoopLoanInstallment;
use App\Models\CoopMember;
use App\Models\CoopTransaction;
use App\Models\Cooperative;
use RuntimeException;

final class CoopService
{
    /* ------------------------------------------------------------- Savings */

    public static function recordSavings(
        int $cooperativeId,
        int $memberId,
        float $amount,
        string $direction,
        string $method = 'cash',
        ?int $recordedBy = null,
        ?string $note = null,
        ?int $paymentId = null,
        ?string $occurredAt = null
    ): int {
        if ($amount <= 0) {
            throw new RuntimeException('Enter an amount greater than zero.');
        }

        if ($direction === 'out') {
            $balance = CoopMember::savingsBalance($cooperativeId, $memberId);

            if ($amount > $balance) {
                throw new RuntimeException('The withdrawal is more than the available savings balance.');
            }
        }

        return CoopTransaction::record([
            'cooperative_id' => $cooperativeId,
            'member_id' => $memberId,
            'type' => $direction === 'out' ? 'savings_withdrawal' : 'savings_deposit',
            'direction' => $direction,
            'amount' => round($amount, 2),
            'method' => $method,
            'recorded_by' => $recordedBy,
            'note' => $note,
            'payment_id' => $paymentId,
            'occurred_at' => $occurredAt,
        ]);
    }

    public static function purchaseShares(
        int $cooperativeId,
        int $memberId,
        float $amount,
        float $shareValue,
        string $method = 'cash',
        ?int $recordedBy = null,
        ?string $note = null,
        ?int $paymentId = null,
        ?string $occurredAt = null
    ): int {
        if ($amount <= 0 || $shareValue <= 0) {
            throw new RuntimeException('Enter a valid share purchase amount.');
        }

        $units = round($amount / $shareValue, 2);

        $id = CoopTransaction::record([
            'cooperative_id' => $cooperativeId,
            'member_id' => $memberId,
            'type' => 'share_purchase',
            'direction' => 'in',
            'amount' => round($amount, 2),
            'method' => $method,
            'recorded_by' => $recordedBy,
            'note' => $note,
            'payment_id' => $paymentId,
            'occurred_at' => $occurredAt,
        ]);

        CoopMember::addShares($memberId, $units);

        return $id;
    }

    /* --------------------------------------------------------------- Fines */

    public static function payFine(
        array $fine,
        string $method = 'cash',
        ?int $recordedBy = null,
        ?int $paymentId = null,
        ?string $occurredAt = null
    ): int {
        if (($fine['status'] ?? '') === 'paid') {
            throw new RuntimeException('This fine has already been paid.');
        }

        $id = CoopTransaction::record([
            'cooperative_id' => (int) $fine['cooperative_id'],
            'member_id' => (int) $fine['member_id'],
            'type' => 'fine_payment',
            'direction' => 'in',
            'amount' => (float) $fine['amount'],
            'method' => $method,
            'fine_id' => (int) $fine['id'],
            'recorded_by' => $recordedBy,
            'note' => 'Fine payment',
            'payment_id' => $paymentId,
            'occurred_at' => $occurredAt,
        ]);

        CoopFine::markPaid((int) $fine['id']);

        return $id;
    }

    /* --------------------------------------------------------------- Loans */

    /**
     * @return array{ok: bool, error?: string, loan_id?: int}
     */
    public static function applyForLoan(array $coop, array $member, array $data): array
    {
        $existing = CoopLoan::activeForMember((int) $member['id']);

        if ($existing !== null) {
            return ['ok' => false, 'error' => 'You already have a loan in progress. Complete it before applying for another.'];
        }

        $product = null;

        if (!empty($data['product_id'])) {
            $product = \App\Models\CoopLoanProduct::find((int) $data['product_id']);

            if ($product === null || (int) $product['cooperative_id'] !== (int) $coop['id']) {
                return ['ok' => false, 'error' => 'Choose a valid loan product.'];
            }
        }

        $principal = round((float) $data['principal'], 2);
        $rate = (float) ($product['interest_rate'] ?? $coop['loan_interest_rate']);
        $months = max(1, (int) ($data['duration_months'] ?? ($product['duration_months'] ?? $coop['loan_duration_months'])));
        $savings = CoopMember::savingsBalance((int) $coop['id'], (int) $member['id']);
        $multiple = (float) ($product['max_multiple'] ?? $coop['max_loan_multiple']);
        $limit = $savings * $multiple;

        if (!empty($product['max_amount'])) {
            $limit = min($limit, (float) $product['max_amount']);
        }

        $minimum = (float) ($product['min_amount'] ?? 0);

        if ($principal < max(1, $minimum)) {
            return ['ok' => false, 'error' => 'The minimum loan amount is ' . number_format(max(1, $minimum), 2) . '.'];
        }

        if ($principal > $limit) {
            return ['ok' => false, 'error' => 'Your loan limit is ' . number_format($limit, 2) . ' based on your savings and the co-op rules.'];
        }

        $required = max(0, (int) ($product['guarantors_required'] ?? $coop['guarantors_required']));
        $guarantorIds = array_values(array_unique(array_map('intval', $data['guarantors'] ?? [])));
        $guarantorIds = array_values(array_filter($guarantorIds, static fn (int $id): bool => $id !== (int) $member['id']));

        if ($required > 0 && count($guarantorIds) < $required) {
            return ['ok' => false, 'error' => 'You need ' . $required . ' guarantor(s) for this loan.'];
        }

        foreach ($guarantorIds as $guarantorId) {
            $guarantor = CoopMember::find($guarantorId);

            if ($guarantor === null || (int) $guarantor['cooperative_id'] !== (int) $coop['id'] || $guarantor['status'] !== 'active') {
                return ['ok' => false, 'error' => 'One of the selected guarantors is not an active co-op member.'];
            }
        }

        $terms = CoopLoan::calculateTerms($principal, $rate, $months);

        $loanId = CoopLoan::create([
            'cooperative_id' => (int) $coop['id'],
            'member_id' => (int) $member['id'],
            'product_id' => $product['id'] ?? null,
            'principal' => $principal,
            'interest_rate' => $rate,
            'duration_months' => $months,
            'interest_amount' => $terms['interest'],
            'total_repayable' => $terms['total'],
            'monthly_repayment' => $terms['monthly'],
            'purpose' => $data['purpose'] ?? null,
        ]);

        foreach ($guarantorIds as $guarantorId) {
            CoopLoanGuarantor::create($loanId, (int) $guarantorId, round($principal / max(1, count($guarantorIds)), 2));
        }

        return ['ok' => true, 'loan_id' => $loanId];
    }

    /** @return array{ok: bool, error?: string} */
    public static function approveLoan(array $loan, int $officerId): array
    {
        if ($loan['status'] !== 'pending') {
            return ['ok' => false, 'error' => 'Only a pending loan can be approved.'];
        }

        $required = (int) (\App\Models\Cooperative::find((int) $loan['cooperative_id'])['guarantors_required'] ?? 1);

        if (!empty($loan['product_id'])) {
            $product = \App\Models\CoopLoanProduct::find((int) $loan['product_id']);

            if ($product !== null) {
                $required = (int) $product['guarantors_required'];
            }
        }

        $accepted = CoopLoanGuarantor::acceptedCount((int) $loan['id']);

        if ($accepted < $required) {
            return ['ok' => false, 'error' => 'This loan needs ' . $required . ' accepted guarantor(s); only ' . $accepted . ' have accepted.'];
        }

        CoopLoan::setStatus((int) $loan['id'], 'approved', [
            'approved_by' => $officerId,
            'approved_at' => date('Y-m-d H:i:s'),
            'reviewed_by' => $officerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => null,
        ]);

        return ['ok' => true];
    }

    public static function rejectLoan(array $loan, int $officerId, ?string $reason): void
    {
        CoopLoan::setStatus((int) $loan['id'], 'rejected', [
            'reviewed_by' => $officerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
        ]);
    }

    /** @return array{ok: bool, error?: string} */
    public static function disburseLoan(array $loan, int $officerId, string $method, ?string $note = null): array
    {
        if ($loan['status'] !== 'approved') {
            return ['ok' => false, 'error' => 'Approve the loan before recording disbursement.'];
        }

        $now = date('Y-m-d H:i:s');
        $dueDate = date('Y-m-d', strtotime('+' . max(1, (int) $loan['duration_months']) . ' months'));

        CoopLoan::setStatus((int) $loan['id'], 'repaying', [
            'disbursed_by' => $officerId,
            'disbursed_at' => $now,
            'due_date' => $dueDate,
        ]);

        $loan['disbursed_at'] = $now;
        CoopLoanInstallment::generateForLoan($loan);

        CoopTransaction::record([
            'cooperative_id' => (int) $loan['cooperative_id'],
            'member_id' => (int) $loan['member_id'],
            'type' => 'loan_disbursement',
            'direction' => 'out',
            'amount' => (float) $loan['principal'],
            'method' => $method,
            'loan_id' => (int) $loan['id'],
            'recorded_by' => $officerId,
            'note' => $note ?: 'Loan disbursement',
            'occurred_at' => $now,
        ]);

        return ['ok' => true];
    }

    public static function recordRepayment(
        array $loan,
        float $amount,
        string $method = 'cash',
        ?int $recordedBy = null,
        ?int $paymentId = null,
        ?string $note = null,
        ?string $occurredAt = null
    ): float {
        $outstanding = CoopLoan::outstanding($loan);
        $amount = min(round($amount, 2), $outstanding);

        if ($amount <= 0) {
            return 0.0;
        }

        CoopTransaction::record([
            'cooperative_id' => (int) $loan['cooperative_id'],
            'member_id' => (int) $loan['member_id'],
            'type' => 'loan_repayment',
            'direction' => 'in',
            'amount' => $amount,
            'method' => $method,
            'loan_id' => (int) $loan['id'],
            'recorded_by' => $recordedBy,
            'note' => $note ?: 'Loan repayment',
            'payment_id' => $paymentId,
            'occurred_at' => $occurredAt,
        ]);

        CoopLoanInstallment::applyRepayment((int) $loan['id'], $amount);

        $repaid = CoopLoan::totalRepaid((int) $loan['id']);
        $total = (float) $loan['total_repayable'];

        CoopLoan::setStatus(
            (int) $loan['id'],
            $repaid >= $total - 0.009 ? 'completed' : 'repaying'
        );

        return $amount;
    }

    /* ----------------------------------------------------------- Dividends */

    /**
     * @return array<int, array{member_id: int, basis_amount: float, amount: float}>
     */
    public static function computeAllocations(array $coop, string $basis, float $total): array
    {
        $members = CoopMember::active((int) $coop['id']);
        $weights = [];

        foreach ($members as $member) {
            $weight = $basis === 'savings'
                ? CoopMember::savingsBalance((int) $coop['id'], (int) $member['id'])
                : (float) $member['shares'];

            if ($weight > 0) {
                $weights[(int) $member['id']] = ['weight' => $weight, 'member' => $member];
            }
        }

        $totalWeight = array_sum(array_column($weights, 'weight'));

        if ($totalWeight <= 0) {
            return [];
        }

        $rows = [];
        $allocated = 0.0;

        foreach ($weights as $memberId => $entry) {
            $amount = round($total * ($entry['weight'] / $totalWeight), 2);
            $allocated += $amount;
            $rows[] = [
                'member_id' => $memberId,
                'basis_amount' => round($entry['weight'], 2),
                'amount' => $amount,
            ];
        }

        // Put any rounding remainder on the largest allocation.
        $remainder = round($total - $allocated, 2);

        if ($remainder !== 0.0 && $rows !== []) {
            $maxIndex = 0;
            foreach ($rows as $index => $row) {
                if ($row['amount'] > $rows[$maxIndex]['amount']) {
                    $maxIndex = $index;
                }
            }
            $rows[$maxIndex]['amount'] = round($rows[$maxIndex]['amount'] + $remainder, 2);
        }

        return $rows;
    }

    /** @return array{ok: bool, error?: string, dividend_id?: int} */
    public static function declareDividend(array $coop, array $data, int $createdBy): array
    {
        $total = round((float) $data['total_amount'], 2);

        if ($total <= 0) {
            return ['ok' => false, 'error' => 'Enter a dividend amount greater than zero.'];
        }

        $basis = in_array($data['basis'] ?? 'shares', ['shares', 'savings'], true) ? $data['basis'] : 'shares';
        $allocations = self::computeAllocations($coop, $basis, $total);

        if ($allocations === []) {
            return ['ok' => false, 'error' => 'There are no members with a ' . $basis . ' balance to share this dividend.'];
        }

        $dividendId = CoopDividend::create([
            'cooperative_id' => (int) $coop['id'],
            'title' => $data['title'],
            'period' => $data['period'] ?? null,
            'basis' => $basis,
            'total_amount' => $total,
            'created_by' => $createdBy,
        ]);

        foreach ($allocations as $allocation) {
            CoopDividendAllocation::create($dividendId, $allocation['member_id'], $allocation['basis_amount'], $allocation['amount']);
        }

        return ['ok' => true, 'dividend_id' => $dividendId];
    }

    public static function distributeDividend(array $dividend, int $by): void
    {
        if ($dividend['status'] === 'distributed') {
            throw new RuntimeException('This dividend has already been distributed.');
        }

        $allocations = \App\Models\CoopDividend::allocations((int) $dividend['id']);

        foreach ($allocations as $allocation) {
            $transactionId = CoopTransaction::record([
                'cooperative_id' => (int) $dividend['cooperative_id'],
                'member_id' => (int) $allocation['member_id'],
                'type' => 'dividend',
                'direction' => 'out',
                'amount' => (float) $allocation['amount'],
                'method' => 'system',
                'dividend_id' => (int) $dividend['id'],
                'recorded_by' => $by,
                'note' => $dividend['title'] . ' dividend',
            ]);

            CoopDividendAllocation::setTransaction((int) $allocation['id'], $transactionId);
        }

        \App\Core\Database::update('coop_dividends', [
            'status' => 'distributed',
            'distributed_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $dividend['id']]);
    }

    /* ---------------------------------------------------- Gateway payments */

    public static function applyPayment(array $payment, ?string $paidAt = null): void
    {
        $paymentId = (int) $payment['id'];

        if (CoopTransaction::existsForPayment($paymentId)) {
            return;
        }

        $coop = Cooperative::forGroup((int) $payment['group_id']);

        if ($coop === null) {
            throw new RuntimeException('That cooperative could not be found.');
        }

        $member = CoopMember::forUser((int) $coop['id'], (int) $payment['user_id']);

        if ($member === null || $member['status'] !== 'active') {
            throw new RuntimeException('You are not an active co-op member.');
        }

        $occurredAt = $paidAt ?? date('Y-m-d H:i:s');
        $amount = (float) $payment['amount'];

        switch ($payment['purpose']) {
            case 'coop_savings':
                self::recordSavings((int) $coop['id'], (int) $member['id'], $amount, 'in', 'gateway', null, 'Savings deposit', $paymentId, $occurredAt);
                break;

            case 'coop_shares':
                self::purchaseShares((int) $coop['id'], (int) $member['id'], $amount, (float) $coop['share_value'], 'gateway', null, 'Share purchase', $paymentId, $occurredAt);
                break;

            case 'coop_loan':
                $loan = CoopLoan::find((int) $payment['coop_loan_id']);

                if ($loan !== null) {
                    self::recordRepayment($loan, $amount, 'gateway', null, $paymentId, 'Loan repayment', $occurredAt);
                }
                break;

            case 'coop_fine':
                $fine = CoopFine::find((int) $payment['coop_fine_id']);

                if ($fine !== null && $fine['status'] !== 'paid') {
                    self::payFine($fine, 'gateway', null, $paymentId, $occurredAt);
                }
                break;
        }
    }
}
