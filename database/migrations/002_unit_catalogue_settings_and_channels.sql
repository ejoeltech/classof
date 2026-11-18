-- Release 2: unit catalogue, configurable education verification, WhatsApp
-- invite links, and in-app new-group notices. Additive only.

ALTER TABLE `users`
    ADD COLUMN `last_seen_at` DATETIME NULL AFTER `last_login_at`;

CREATE TABLE IF NOT EXISTS `faculties` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `name_key` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `source` VARCHAR(120) NULL,
    `status` ENUM('verified', 'pending', 'rejected') NOT NULL DEFAULT 'verified',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `faculties_institution_name_unique` (`institution_id`, `name_key`),
    KEY `faculties_institution_index` (`institution_id`),
    CONSTRAINT `faculties_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `faculty_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `name_key` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `source` VARCHAR(120) NULL,
    `status` ENUM('verified', 'pending', 'rejected') NOT NULL DEFAULT 'verified',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `departments_faculty_name_unique` (`faculty_id`, `name_key`),
    KEY `departments_faculty_index` (`faculty_id`),
    CONSTRAINT `departments_faculty_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `programmes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `department_id` BIGINT UNSIGNED NULL,
    `name` VARCHAR(180) NOT NULL,
    `name_key` VARCHAR(180) NOT NULL,
    `level` VARCHAR(40) NULL,
    `field` VARCHAR(80) NULL,
    `source` VARCHAR(120) NULL,
    `status` ENUM('verified', 'pending', 'rejected') NOT NULL DEFAULT 'verified',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `programmes_name_key_unique` (`name_key`),
    KEY `programmes_department_index` (`department_id`),
    CONSTRAINT `programmes_department_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `platform_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(120) NOT NULL,
    `value` TEXT NULL,
    `type` ENUM('string', 'boolean', 'integer') NOT NULL DEFAULT 'string',
    `updated_by` BIGINT UNSIGNED NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `platform_settings_key_unique` (`key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `group_channels` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `label` VARCHAR(80) NOT NULL,
    `invite_url` VARCHAR(255) NOT NULL,
    `visible_to` ENUM('members', 'manager') NOT NULL DEFAULT 'members',
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `last_verified_at` DATETIME NULL,
    `revoked_at` DATETIME NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `group_channels_group_index` (`group_id`, `revoked_at`),
    CONSTRAINT `group_channels_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `alumni_groups`
    ADD COLUMN `faculty_id` BIGINT UNSIGNED NULL AFTER `institution`,
    ADD COLUMN `department_id` BIGINT UNSIGNED NULL AFTER `faculty_id`,
    ADD KEY `alumni_groups_faculty_index` (`faculty_id`),
    ADD KEY `alumni_groups_department_index` (`department_id`),
    ADD CONSTRAINT `alumni_groups_faculty_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `alumni_groups_department_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

ALTER TABLE `education_records`
    ADD COLUMN `faculty_id` BIGINT UNSIGNED NULL AFTER `department`,
    ADD COLUMN `department_id` BIGINT UNSIGNED NULL AFTER `faculty_id`,
    ADD COLUMN `programme_id` BIGINT UNSIGNED NULL AFTER `department_id`,
    ADD KEY `education_records_faculty_index` (`faculty_id`),
    ADD KEY `education_records_department_index` (`department_id`),
    ADD CONSTRAINT `education_records_faculty_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `education_records_department_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `education_records_programme_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL;

ALTER TABLE `group_requests`
    ADD COLUMN `faculty_id` BIGINT UNSIGNED NULL AFTER `department`,
    ADD COLUMN `department_id` BIGINT UNSIGNED NULL AFTER `faculty_id`,
    ADD KEY `group_requests_faculty_index` (`faculty_id`),
    ADD KEY `group_requests_department_index` (`department_id`),
    ADD CONSTRAINT `group_requests_faculty_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `group_requests_department_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

ALTER TABLE `memberships`
    ADD COLUMN `education_record_id` BIGINT UNSIGNED NULL AFTER `rejection_reason`,
    ADD COLUMN `claim_match` VARCHAR(255) NULL AFTER `education_record_id`,
    ADD COLUMN `claim_flagged` TINYINT(1) NOT NULL DEFAULT 0 AFTER `claim_match`;
