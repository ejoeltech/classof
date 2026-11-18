-- Release 3: WhatsApp Cloud API outbox and opt-in classmate discovery.
-- Additive only.

ALTER TABLE `users`
    ADD COLUMN `phone_e164` VARCHAR(20) NULL AFTER `phone`,
    ADD COLUMN `phone_verified_at` DATETIME NULL AFTER `phone_e164`,
    ADD COLUMN `whatsapp_opt_in_at` DATETIME NULL AFTER `phone_verified_at`,
    ADD COLUMN `whatsapp_opt_out_at` DATETIME NULL AFTER `whatsapp_opt_in_at`,
    ADD COLUMN `classmate_opt_in_at` DATETIME NULL AFTER `whatsapp_opt_out_at`;

CREATE TABLE IF NOT EXISTS `notification_deliveries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `group_id` BIGINT UNSIGNED NULL,
    `channel` ENUM('whatsapp', 'email') NOT NULL DEFAULT 'whatsapp',
    `template` VARCHAR(80) NOT NULL,
    `payload` JSON NULL,
    `status` ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `idempotency_key` VARCHAR(190) NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `last_error` VARCHAR(255) NULL,
    `provider_message_id` VARCHAR(120) NULL,
    `provider_status` VARCHAR(30) NULL,
    `scheduled_at` DATETIME NOT NULL,
    `sent_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `notification_deliveries_idempotency_unique` (`idempotency_key`),
    KEY `notification_deliveries_status_index` (`status`, `scheduled_at`),
    KEY `notification_deliveries_group_index` (`group_id`, `created_at`),
    CONSTRAINT `notification_deliveries_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `notification_deliveries_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
