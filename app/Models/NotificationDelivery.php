<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class NotificationDelivery
{
    public static function queue(array $data): bool
    {
        $existing = Database::value(
            'SELECT COUNT(*) FROM notification_deliveries WHERE idempotency_key = ?',
            [$data['idempotency_key']],
            0
        );

        if ((int) $existing > 0) {
            return false;
        }

        Database::insert('notification_deliveries', [
            'user_id' => $data['user_id'],
            'group_id' => $data['group_id'] ?? null,
            'channel' => $data['channel'] ?? 'whatsapp',
            'template' => $data['template'],
            'payload' => json_encode($data['payload'] ?? []),
            'status' => 'pending',
            'idempotency_key' => $data['idempotency_key'],
            'attempts' => 0,
            'scheduled_at' => $data['scheduled_at'] ?? date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /** @return array<int, array<string, mixed>> */
    public static function due(int $limit = 50): array
    {
        return Database::fetchAll(
            'SELECT d.*, u.name AS user_name, u.phone_e164
             FROM notification_deliveries d
             INNER JOIN users u ON u.id = d.user_id
             WHERE d.status = "pending" AND d.scheduled_at <= NOW()
             ORDER BY d.scheduled_at ASC, d.id ASC
             LIMIT ' . max(1, $limit)
        );
    }

    public static function claim(int $id): bool
    {
        return Database::run(
            'UPDATE notification_deliveries SET status = "processing", attempts = attempts + 1, updated_at = ?
             WHERE id = ? AND status = "pending"',
            [date('Y-m-d H:i:s'), $id]
        )->rowCount() === 1;
    }

    public static function markSent(int $id, ?string $providerMessageId = null): void
    {
        Database::update('notification_deliveries', [
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'provider_status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'last_error' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function markFailed(int $id, string $error): void
    {
        Database::update('notification_deliveries', [
            'status' => 'failed',
            'provider_status' => 'failed',
            'last_error' => mb_substr($error, 0, 255),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function markProviderStatus(int $id, string $status): void
    {
        $data = [
            'provider_status' => mb_substr($status, 0, 30),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === 'failed') {
            $data['status'] = 'failed';
            $data['last_error'] = 'WhatsApp reported a failed delivery.';
        }

        Database::update('notification_deliveries', $data, ['id' => $id]);
    }

    public static function retry(int $id): void
    {
        Database::update('notification_deliveries', [
            'status' => 'pending',
            'last_error' => null,
            'scheduled_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function cancel(int $id): void
    {
        Database::update('notification_deliveries', [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function todayCount(?int $groupId = null): int
    {
        if ($groupId === null) {
            return (int) Database::value(
                'SELECT COUNT(*) FROM notification_deliveries WHERE channel = "whatsapp" AND created_at >= CURDATE()',
                [],
                0
            );
        }

        return (int) Database::value(
            'SELECT COUNT(*) FROM notification_deliveries WHERE channel = "whatsapp" AND group_id = ? AND created_at >= CURDATE()',
            [$groupId],
            0
        );
    }

    /** @return array<string, int> */
    public static function counts(): array
    {
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) AS total FROM notification_deliveries GROUP BY status'
        );
        $counts = ['pending' => 0, 'processing' => 0, 'sent' => 0, 'failed' => 0, 'cancelled' => 0];

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /** @return array<int, array<string, mixed>> */
    public static function recent(int $limit = 50): array
    {
        return Database::fetchAll(
            'SELECT d.*, u.name AS user_name, g.name AS group_name
             FROM notification_deliveries d
             INNER JOIN users u ON u.id = d.user_id
             LEFT JOIN `alumni_groups` g ON g.id = d.group_id
             ORDER BY d.created_at DESC
             LIMIT ' . max(1, $limit)
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM notification_deliveries WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findByProviderId(string $providerId): ?array
    {
        return Database::fetch(
            'SELECT * FROM notification_deliveries WHERE provider_message_id = ? LIMIT 1',
            [$providerId]
        );
    }
}
