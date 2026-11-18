<?php

declare(strict_types=1);

namespace App\Services\Payments;

final class ManualGateway implements Gateway
{
    public function slug(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Bank transfer';
    }

    public function initialize(array $payment, string $callbackUrl): array
    {
        return [
            'manual' => true,
            'redirect' => url('payments/' . $payment['id'] . '/instructions'),
        ];
    }

    public function verify(array $payment): array
    {
        return [
            'status' => 'pending',
            'meta' => ['note' => 'Awaiting confirmation by a group administrator.'],
        ];
    }

    public function isConfigured(): bool
    {
        return true;
    }
}
