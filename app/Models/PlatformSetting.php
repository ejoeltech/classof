<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PlatformSetting
{
    /**
     * Defaults are also inserted by the seeder/migration. Keeping them here
     * means a missing row behaves exactly like today's product.
     *
     * @return array<string, array{value: string, type: string, label: string, help: string}>
     */
    public static function defaults(): array
    {
        return [
            'education.suggest_from_unverified' => [
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Suggest from unverified education records',
                'help' => 'When off, only education records an admin has confirmed produce matched-group suggestions.',
            ],
            'education.require_profile_verification' => [
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Require a confirmed education record before requesting',
                'help' => 'When on, a member cannot request to join a group unless an admin has confirmed an education record for that institution and, where applicable, unit and year.',
            ],
            'education.flag_unverified_claims' => [
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Flag claims from unconfirmed education records',
                'help' => 'Join requests snapshot the claimed education and mark it for reviewer attention when the record is not confirmed.',
            ],
            'education.auto_approve_institution_groups' => [
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Auto-approve institution-wide groups',
                'help' => 'When on, a matching education record can join an institution-wide group without proof review. Class, faculty, department and association groups still require approval. Invite-only groups always require an invite.',
            ],
            'classmates.enabled' => [
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable classmate discovery',
                'help' => 'Lets members who opt in see other opted-in members with the same confirmed education record. No contact upload or global people search is used.',
            ],
            'assistant.enabled' => [
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable chatbots',
                'help' => 'Shows the group-website assistant and the signed-in help assistant. Answers use the configured AI endpoint when available, otherwise built-in guides. Disable to hide both.',
            ],
            'whatsapp.enabled' => [
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Enable WhatsApp Cloud API delivery',
                'help' => 'Turns on the notification outbox. Without credentials the worker writes messages to storage/logs/whatsapp.log instead of calling Meta.',
            ],
            'whatsapp.announcements' => [
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Queue WhatsApp for announcements',
                'help' => 'When on, publishing an announcement queues a WhatsApp template message for opted-in active members, subject to the group daily limit.',
            ],
            'whatsapp.event_reminders' => [
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Queue WhatsApp event reminders',
                'help' => 'The reminder CLI queues messages for opted-in members when a published event starts within the next 24 hours.',
            ],
            'whatsapp.group_daily_limit' => [
                'value' => '100',
                'type' => 'integer',
                'label' => 'WhatsApp messages per group per day',
                'help' => 'Caps queued WhatsApp deliveries for one group in a rolling calendar day.',
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $rows = Database::fetchAll('SELECT * FROM platform_settings ORDER BY `key`');
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row['key']] = $row;
        }

        return $settings;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $defaults = self::defaults();
        $fallback = $default ?? ($defaults[$key]['value'] ?? null);
        $value = Database::value('SELECT value FROM platform_settings WHERE `key` = ?', [$key]);

        return $value === false || $value === null ? $fallback : (string) $value;
    }

    public static function bool(string $key): bool
    {
        return in_array(self::get($key), ['1', 'true', 'on', 'yes'], true);
    }

    public static function set(string $key, string $value, string $type, ?int $userId = null): void
    {
        $existing = Database::fetch('SELECT id FROM platform_settings WHERE `key` = ? LIMIT 1', [$key]);

        if ($existing !== null) {
            Database::update('platform_settings', [
                'value' => $value,
                'type' => $type,
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $existing['id']]);

            return;
        }

        Database::insert('platform_settings', [
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function seedDefaults(): void
    {
        foreach (self::defaults() as $key => $definition) {
            if (Database::value('SELECT COUNT(*) FROM platform_settings WHERE `key` = ?', [$key], 0) == 0) {
                self::set($key, $definition['value'], $definition['type']);
            }
        }
    }
}
