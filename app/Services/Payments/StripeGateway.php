<?php

declare(strict_types=1);

namespace App\Services\Payments;

use RuntimeException;

final class StripeGateway implements Gateway
{
    private const BASE = 'https://api.stripe.com/v1';

    public function slug(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        return (string) config('payments.stripe.secret_key') !== '';
    }

    private function secret(): string
    {
        $secret = (string) config('payments.stripe.secret_key');

        if ($secret === '') {
            throw new RuntimeException('Stripe is not configured. Add STRIPE_SECRET_KEY to your .env file.');
        }

        return $secret;
    }

    public function initialize(array $payment, string $callbackUrl): array
    {
        $separator = str_contains($callbackUrl, '?') ? '&' : '?';

        $payload = [
            'mode' => 'payment',
            'client_reference_id' => $payment['reference'],
            'customer_email' => $payment['payer_email'] ?: null,
            'success_url' => $callbackUrl . $separator . 'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $callbackUrl . $separator . 'cancelled=1',
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower((string) $payment['currency']),
                        'unit_amount' => (int) round(((float) $payment['amount']) * 100),
                        'product_data' => [
                            'name' => $payment['group_name'] . ' — ' . ucfirst((string) $payment['purpose']),
                            'description' => 'Reference ' . $payment['reference'],
                        ],
                    ],
                ],
            ],
            'metadata' => [
                'payment_id' => (string) $payment['id'],
                'reference' => (string) $payment['reference'],
            ],
        ];

        $response = HttpClient::request(
            'POST',
            self::BASE . '/checkout/sessions',
            [
                'Authorization: Bearer ' . $this->secret(),
                'Content-Type: application/x-www-form-urlencoded',
            ],
            HttpClient::form($payload)
        );

        if ($response['status'] >= 400 || empty($response['json']['url'])) {
            $message = $response['json']['error']['message'] ?? 'Stripe could not start this payment.';
            throw new RuntimeException((string) $message);
        }

        return ['redirect' => (string) $response['json']['url']];
    }

    public function verify(array $payment): array
    {
        $meta = [];

        if (!empty($payment['meta'])) {
            $decoded = json_decode((string) $payment['meta'], true);
            $meta = is_array($decoded) ? $decoded : [];
        }

        $sessionId = $meta['stripe_session_id'] ?? ($_GET['session_id'] ?? null);

        if (!is_string($sessionId) || $sessionId === '') {
            return ['status' => 'pending', 'meta' => ['reason' => 'Missing Stripe checkout session.']];
        }

        $response = HttpClient::request(
            'GET',
            self::BASE . '/checkout/sessions/' . rawurlencode($sessionId),
            ['Authorization: Bearer ' . $this->secret()]
        );

        if ($response['status'] >= 400 || !is_array($response['json'])) {
            return ['status' => 'failed', 'meta' => ['gateway_response' => $response['body']]];
        }

        $session = $response['json'];

        if (($session['payment_status'] ?? '') === 'paid') {
            return [
                'status' => 'success',
                'meta' => [
                    'gateway' => 'stripe',
                    'stripe_session_id' => $sessionId,
                    'payment_intent' => $session['payment_intent'] ?? null,
                ],
            ];
        }

        return [
            'status' => 'pending',
            'meta' => ['gateway' => 'stripe', 'stripe_session_id' => $sessionId, 'payment_status' => $session['payment_status'] ?? null],
        ];
    }

    public static function verifySignature(string $payload, ?string $header): bool
    {
        $secret = (string) config('payments.stripe.webhook_secret');

        if ($secret === '' || $header === null || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $event */
    public static function referenceFromEvent(array $event): ?string
    {
        return $event['data']['object']['client_reference_id'] ?? null;
    }
}
