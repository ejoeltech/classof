<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Core\Database;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Payment;
use App\Models\WelfareCase;
use App\Models\WelfareContribution;

final class PaymentService
{
    /**
     * Mark a payment successful and apply its side effects exactly once.
     *
     * @param array<string, mixed> $payment
     * @param array<string, mixed> $meta
     */
    public static function complete(array $payment, array $meta = [], ?string $paidAt = null): bool
    {
        $fresh = Payment::find((int) $payment['id']);

        if ($fresh === null || $fresh['status'] === 'success') {
            return false;
        }

        $paymentMeta = [];

        if (!empty($fresh['meta'])) {
            $decoded = json_decode((string) $fresh['meta'], true);
            $paymentMeta = is_array($decoded) ? $decoded : [];
        }

        Database::transaction(static function () use ($fresh, $meta, $paidAt, $paymentMeta): void {
            Payment::markSuccess((int) $fresh['id'], $meta, $paidAt);

            if ($fresh['purpose'] === 'welfare' && $fresh['welfare_case_id'] !== null) {
                if (!WelfareContribution::existsForPayment((int) $fresh['id'])) {
                    WelfareContribution::create([
                        'welfare_case_id' => (int) $fresh['welfare_case_id'],
                        'user_id' => (int) $fresh['user_id'],
                        'payment_id' => (int) $fresh['id'],
                        'amount' => (float) $fresh['amount'],
                        'note' => $paymentMeta['note'] ?? null,
                    ]);
                }

                $case = WelfareCase::find((int) $fresh['welfare_case_id']);

                if ($case !== null && $case['status'] === 'open') {
                    $target = (float) ($case['target_amount'] ?? 0);
                    $raised = WelfareCase::raised((int) $case['id']);

                    if ($target > 0 && $raised >= $target) {
                        WelfareCase::setStatus((int) $case['id'], 'funded');
                    }
                }
            }

            if ($fresh['purpose'] === 'event' && $fresh['event_id'] !== null) {
                Event::setRsvp((int) $fresh['event_id'], (int) $fresh['user_id'], 'going');
            }

            if (str_starts_with((string) $fresh['purpose'], 'coop_')) {
                \App\Services\CoopService::applyPayment($fresh, $paidAt);
            }
        });

        AuditLog::record(
            (int) $fresh['group_id'],
            $fresh['user_id'] !== null ? (int) $fresh['user_id'] : null,
            'payment.completed',
            'Payment ' . $fresh['reference'] . ' confirmed (' . money($fresh['amount'], $fresh['currency']) . ')',
            'payment',
            (int) $fresh['id']
        );

        return true;
    }
}
