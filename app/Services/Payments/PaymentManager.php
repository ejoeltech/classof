<?php

declare(strict_types=1);

namespace App\Services\Payments;

final class PaymentManager
{
    public static function resolve(?string $slug = null): Gateway
    {
        $slug = $slug ?? (string) config('payments.gateway', 'manual');

        $gateway = match ($slug) {
            'paystack' => new PaystackGateway(),
            'stripe' => new StripeGateway(),
            default => new ManualGateway(),
        };

        // If a gateway is selected but not configured, fall back to bank transfer
        // rather than breaking the payment flow.
        return $gateway->isConfigured() ? $gateway : new ManualGateway();
    }

    public static function current(): Gateway
    {
        return self::resolve();
    }

    public static function isManual(): bool
    {
        return self::current()->slug() === 'manual';
    }

    /** @return array<int, Gateway> */
    public static function available(): array
    {
        $gateways = [new ManualGateway(), new PaystackGateway(), new StripeGateway()];

        return array_values(array_filter($gateways, static fn (Gateway $gateway): bool => $gateway->isConfigured()));
    }
}
