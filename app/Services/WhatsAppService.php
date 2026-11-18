<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\NotificationDelivery;
use App\Models\PlatformSetting;
use RuntimeException;

final class WhatsAppService
{
    public static function driver(): string
    {
        return (string) config('whatsapp.driver', 'log');
    }

    public static function cloudConfigured(): bool
    {
        return (string) config('whatsapp.phone_number_id') !== ''
            && (string) config('whatsapp.access_token') !== '';
    }

    public static function queueAnnouncement(array $announcement, array $group, string $authorName): int
    {
        if (!PlatformSetting::bool('whatsapp.enabled') || !PlatformSetting::bool('whatsapp.announcements')) {
            return 0;
        }

        $limit = max(0, (int) PlatformSetting::get('whatsapp.group_daily_limit', '100'));
        $remaining = max(0, $limit - NotificationDelivery::todayCount((int) $group['id']));

        if ($remaining === 0) {
            return 0;
        }

        $members = self::optedInMembers((int) $group['id'], $remaining);
        $template = (string) config('whatsapp.templates.announcement', 'classof_announcement');
        $queued = 0;

        foreach ($members as $member) {
            if (NotificationDelivery::queue([
                'user_id' => (int) $member['id'],
                'group_id' => (int) $group['id'],
                'channel' => 'whatsapp',
                'template' => $template,
                'payload' => [
                    'parameters' => [
                        $group['name'],
                        $announcement['title'],
                        str_limit((string) $announcement['body'], 120),
                        $authorName,
                    ],
                ],
                'idempotency_key' => 'announcement:' . $announcement['id'] . ':user:' . $member['id'],
            ])) {
                $queued++;
            }
        }

        return $queued;
    }

    public static function queueEventReminders(): int
    {
        if (!PlatformSetting::bool('whatsapp.enabled') || !PlatformSetting::bool('whatsapp.event_reminders')) {
            return 0;
        }

        $events = Database::fetchAll(
            'SELECT e.*, g.name AS group_name
             FROM events e
             INNER JOIN `alumni_groups` g ON g.id = e.group_id
             WHERE e.status = "published" AND e.starts_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
             ORDER BY e.starts_at ASC'
        );

        $limit = max(0, (int) PlatformSetting::get('whatsapp.group_daily_limit', '100'));
        $template = (string) config('whatsapp.templates.event_reminder', 'classof_event_reminder');
        $queued = 0;

        foreach ($events as $event) {
            $remaining = max(0, $limit - NotificationDelivery::todayCount((int) $event['group_id']));

            if ($remaining === 0) {
                continue;
            }

            foreach (self::optedInMembers((int) $event['group_id'], $remaining) as $member) {
                if (NotificationDelivery::queue([
                    'user_id' => (int) $member['id'],
                    'group_id' => (int) $event['group_id'],
                    'channel' => 'whatsapp',
                    'template' => $template,
                    'payload' => [
                        'parameters' => [
                            $event['group_name'],
                            $event['title'],
                            date('D, M j · g:i A', strtotime((string) $event['starts_at'])),
                            $event['location'] ?: 'See Classof for details',
                        ],
                    ],
                    'idempotency_key' => 'event-reminder:' . $event['id'] . ':user:' . $member['id'],
                ])) {
                    $queued++;
                }
            }
        }

        return $queued;
    }

    /**
     * Send one queued delivery. Returns a provider message id when available.
     */
    public static function send(array $delivery): ?string
    {
        $payload = [];

        if (!empty($delivery['payload'])) {
            $decoded = json_decode((string) $delivery['payload'], true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $parameters = array_values(array_map('strval', (array) ($payload['parameters'] ?? [])));

        if (self::driver() === 'log' || !self::cloudConfigured()) {
            Logger::whatsapp(sprintf(
                'to=%s user=%s group=%s template=%s params=%s',
                $delivery['phone_e164'] ?: 'missing-phone',
                $delivery['user_id'],
                $delivery['group_id'] ?? '-',
                $delivery['template'],
                json_encode($parameters, JSON_UNESCAPED_UNICODE)
            ));

            return 'log-' . $delivery['id'];
        }

        $version = (string) config('whatsapp.api_version', 'v20.0');
        $phoneNumberId = (string) config('whatsapp.phone_number_id');
        $url = 'https://graph.facebook.com/' . $version . '/' . $phoneNumberId . '/messages';

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $delivery['phone_e164'],
            'type' => 'template',
            'template' => [
                'name' => $delivery['template'],
                'language' => ['code' => (string) config('whatsapp.language', 'en_US')],
            ],
        ];

        if ($parameters !== []) {
            $body['template']['components'] = [[
                'type' => 'body',
                'parameters' => array_map(
                    static fn (string $text): array => ['type' => 'text', 'text' => $text],
                    $parameters
                ),
            ]];
        }

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . (string) config('whatsapp.access_token'),
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new RuntimeException('WhatsApp API unreachable: ' . $error);
        }

        $decoded = json_decode((string) $response, true);

        if ($status >= 400) {
            $message = $decoded['error']['message'] ?? 'WhatsApp API rejected the message.';
            throw new RuntimeException((string) $message);
        }

        return $decoded['messages'][0]['id'] ?? null;
    }

    public static function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = (string) config('whatsapp.app_secret');

        if ($secret === '' || $signature === null || !str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /** @return array<int, array<string, mixed>> */
    private static function optedInMembers(int $groupId, int $limit): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.name, u.phone_e164
             FROM memberships m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.group_id = ? AND m.status = "active" AND u.status = "active"
               AND u.whatsapp_opt_in_at IS NOT NULL
               AND u.whatsapp_opt_out_at IS NULL
               AND u.phone_e164 IS NOT NULL
             ORDER BY u.name
             LIMIT ' . max(1, $limit),
            [$groupId]
        );
    }
}
