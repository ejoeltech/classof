<?php

declare(strict_types=1);

namespace App\Services\Payments;

interface Gateway
{
    public function slug(): string;

    public function label(): string;

    /**
     * @param array<string, mixed> $payment
     * @return array{redirect?: string, manual?: bool}
     */
    public function initialize(array $payment, string $callbackUrl): array;

    /**
     * @param array<string, mixed> $payment
     * @return array{status: string, meta?: array<string, mixed>, paid_at?: string|null}
     */
    public function verify(array $payment): array;

    public function isConfigured(): bool;
}
