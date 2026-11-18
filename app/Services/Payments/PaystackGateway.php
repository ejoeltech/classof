<?php

declare(strict_types=1);

namespace App\Services\Payments;

use RuntimeException;

final class PaystackGateway implements Gateway
{
    private const BASE = 'https://api.paystack.co';

    public function slug(): string
    {
        return 'paystack';
    }

    public function label(): string
    {
        return 'Paystack';
    }

    public function isConfigured(): bool
    {
        return (string) config('payments.paystack.secret_key') !== '';
    }

    private function secret(): string
    {
        $secret = (string) config('payments.paystack.secret_key');

        if ($secret === '') {
            throw new RuntimeException('Paystack is not configured. Add PAYSTACK_SECRET_KEY to your .env file.');
        }

        return $secret;
    }

    public function initialize(array $payment, string $callbackUrl): array
    {
        $payload = [
            'email' => $payment['payer_email'] ?: 'member@classof.local',
            'amount' => (int) round(((float) $payment['amount']) * 100),
            'currency' => $payment['currency'],
            'reference' => $payment['reference'],
            'callback_url' => $callbackUrl,
            'metadata' => [
                'payment_id' => (int) $payment['id'],
                'group' => $payment['group_name'] ?? null,
                'purpose' => $payment['purpose'],
            ],
        ];

        $response = HttpClient::request(
            'POST',
            self::BASE . '/transaction/initialize',
            ['Authorization: Bearer ' . $this->secret(), 'Content-Type: application/json'],
            json_encode($payload)
        );

        if ($response['status'] >= 400 || empty($response['json']['status'])) {
            $message = $response['json']['message'] ?? 'Paystack could not start this payment.';
            throw new RuntimeException((string) $message);
        }

        return ['redirect' => (string) $response['json']['data']['authorization_url']];
    }

    public function verify(array $payment): array
    {
        $response = HttpClient::request(
            'GET',
            self::BASE . '/transaction/verify/' . rawurlencode((string) $payment['reference']),
            ['Authorization: Bearer ' . $this->secret()]
        );

        if ($response['status'] >= 400 || !is_array($response['json'])) {
            return ['status' => 'failed', 'meta' => ['gateway_response' => $response['body']]];
        }

        $data = $response['json']['data'] ?? [];
        $status = $data['status'] ?? 'failed';

        if ($status === 'success') {
            $expected = (int) round(((float) $payment['amount']) * 100);
            $paid = (int) ($data['amount'] ?? 0);

            if ($paid < $expected) {
                return ['status' => 'failed', 'meta' => ['reason' => 'Amount paid is less than the expected amount.', 'paid' => $paid]];
            }

            return [
                'status' => 'success',
                'paid_at' => isset($data['paid_at']) ? date('Y-m-d H:i:s', strtotime((string) $data['paid_at'])) : null,
                'meta' => [
                    'gateway' => 'paystack',
                    'gateway_reference' => $data['reference'] ?? null,
                    'channel' => $data['channel'] ?? null,
                    'currency' => $data['currency'] ?? null,
                ],
            ];
        }

        return [
            'status' => in_array($status, ['abandoned', 'ongoing', 'pending'], true) ? 'pending' : 'failed',
            'meta' => ['gateway' => 'paystack', 'gateway_status' => $status],
        ];
    }

    public static function verifySignature(string $payload, ?string $signature): bool
    {
        $secret = (string) config('payments.paystack.secret_key');

        if ($secret === '' || $signature === null || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }

    /** @param array<string, mixed> $event */
    public static function referenceFromEvent(array $event): ?string
    {
        return $event['data']['reference'] ?? null;
    }
}
