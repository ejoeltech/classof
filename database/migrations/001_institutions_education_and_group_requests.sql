-- Release 1: institutions catalogue, education records, group requests, and
-- catalogue-linked groups. Additive only: no existing table is dropped or
-- rewritten, and existing groups remain discoverable.

CREATE TABLE IF NOT EXISTS `institutions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `name_key` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(220) NOT NULL,
    `type` ENUM('primary', 'secondary', 'university', 'polytechnic', 'college', 'vocational', 'other') NOT NULL DEFAULT 'university',
    `ownership` ENUM('federal', 'state', 'private', 'other') NOT NULL DEFAULT 'other',
    `state` VARCHAR(80) NULL,
    `country_code` CHAR(2) NOT NULL DEFAULT 'NG',
    `status` ENUM('verified', 'pending', 'rejected') NOT NULL DEFAULT 'pending',
    `source` VARCHAR(120) NULL,
    `source_url` VARCHAR(255) NULL,
    `dataset_version` VARCHAR(40) NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `reviewed_by` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `institutions_slug_unique` (`slug`),
    UNIQUE KEY `institutions_name_key_unique` (`name_key`),
    KEY `institutions_status_type_index` (`status`, `type`),
    KEY `institutions_state_index` (`state`),
    CONSTRAINT `institutions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `institutions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `institution_aliases` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `alias` VARCHAR(120) NOT NULL,
    `alias_key` VARCHAR(120) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `institution_aliases_key_unique` (`alias_key`),
    KEY `institution_aliases_institution_index` (`institution_id`),
    CONSTRAINT `institution_aliases_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `education_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `institution_id` BIGINT UNSIGNED NULL,
    `institution_name_raw` VARCHAR(200) NULL,
    `programme` VARCHAR(160) NULL,
    `faculty` VARCHAR(160) NULL,
    `department` VARCHAR(160) NULL,
    `graduation_year` SMALLINT UNSIGNED NULL,
    `status` ENUM('unverified', 'pending', 'verified', 'rejected') NOT NULL DEFAULT 'unverified',
    `source` ENUM('signup', 'profile', 'import') NOT NULL DEFAULT 'profile',
    `verified_by` BIGINT UNSIGNED NULL,
    `verified_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `education_records_user_index` (`user_id`),
    KEY `education_records_institution_index` (`institution_id`),
    CONSTRAINT `education_records_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `education_records_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `group_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(160) NOT NULL,
    `scope` ENUM('institution', 'class', 'department', 'faculty', 'association', 'other') NOT NULL DEFAULT 'class',
    `type` ENUM('primary', 'secondary', 'university', 'polytechnic', 'college', 'vocational', 'other') NOT NULL DEFAULT 'university',
    `faculty` VARCHAR(160) NULL,
    `department` VARCHAR(160) NULL,
    `graduation_year` SMALLINT UNSIGNED NULL,
    `description` TEXT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `created_group_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `group_requests_user_index` (`user_id`, `status`),
    KEY `group_requests_status_index` (`status`, `created_at`),
    CONSTRAINT `group_requests_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_requests_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_requests_group_foreign` FOREIGN KEY (`created_group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `alumni_groups`
    ADD COLUMN `institution_id` BIGINT UNSIGNED NULL AFTER `scope`,
    ADD COLUMN `verification_status` ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'verified' AFTER `join_policy`,
    ADD COLUMN `specificity` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `verification_status`,
    ADD KEY `alumni_groups_institution_index` (`institution_id`),
    ADD KEY `alumni_groups_verification_index` (`verification_status`),
    ADD CONSTRAINT `alumni_groups_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL;
