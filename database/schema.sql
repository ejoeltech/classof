-- Classof — alumni group management platform
-- MySQL 8 / MariaDB 10.4+ · utf8mb4

CREATE DATABASE IF NOT EXISTS `classof`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `classof`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notification_deliveries`;
DROP TABLE IF EXISTS `group_sites`;
DROP TABLE IF EXISTS `welfare_contributions`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `coop_transactions`;
DROP TABLE IF EXISTS `coop_dividend_allocations`;
DROP TABLE IF EXISTS `coop_dividends`;
DROP TABLE IF EXISTS `coop_fines`;
DROP TABLE IF EXISTS `coop_meeting_attendance`;
DROP TABLE IF EXISTS `coop_meetings`;
DROP TABLE IF EXISTS `coop_loan_installments`;
DROP TABLE IF EXISTS `coop_loan_guarantors`;
DROP TABLE IF EXISTS `coop_loans`;
DROP TABLE IF EXISTS `coop_loan_products`;
DROP TABLE IF EXISTS `coop_members`;
DROP TABLE IF EXISTS `cooperatives`;
DROP TABLE IF EXISTS `votes`;
DROP TABLE IF EXISTS `election_candidates`;
DROP TABLE IF EXISTS `election_positions`;
DROP TABLE IF EXISTS `elections`;
DROP TABLE IF EXISTS `welfare_cases`;
DROP TABLE IF EXISTS `dues_cycles`;
DROP TABLE IF EXISTS `event_rsvps`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `invites`;
DROP TABLE IF EXISTS `group_channels`;
DROP TABLE IF EXISTS `group_requests`;
DROP TABLE IF EXISTS `education_records`;
DROP TABLE IF EXISTS `memberships`;
DROP TABLE IF EXISTS `alumni_groups`;
DROP TABLE IF EXISTS `platform_settings`;
DROP TABLE IF EXISTS `programmes`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `faculties`;
DROP TABLE IF EXISTS `institution_aliases`;
DROP TABLE IF EXISTS `institutions`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(40) NULL,
    `phone_e164` VARCHAR(20) NULL,
    `phone_verified_at` DATETIME NULL,
    `whatsapp_opt_in_at` DATETIME NULL,
    `whatsapp_opt_out_at` DATETIME NULL,
    `classmate_opt_in_at` DATETIME NULL,
    `avatar` VARCHAR(255) NULL,
    `occupation` VARCHAR(120) NULL,
    `location` VARCHAR(120) NULL,
    `bio` TEXT NULL,
    `platform_role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    `email_verified_at` DATETIME NULL,
    `remember_token` CHAR(64) NULL,
    `last_login_at` DATETIME NULL,
    `last_seen_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_status_index` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(190) NOT NULL,
    `token` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `password_resets_token_unique` (`token`),
    KEY `password_resets_email_index` (`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(190) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `attempted_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `login_attempts_email_ip_index` (`email`, `ip`, `attempted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `institutions` (
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

CREATE TABLE `institution_aliases` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `alias` VARCHAR(120) NOT NULL,
    `alias_key` VARCHAR(120) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `institution_aliases_key_unique` (`alias_key`),
    KEY `institution_aliases_institution_index` (`institution_id`),
    CONSTRAINT `institution_aliases_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `faculties` (
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

CREATE TABLE `departments` (
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

CREATE TABLE `programmes` (
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

CREATE TABLE `platform_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(120) NOT NULL,
    `value` TEXT NULL,
    `type` ENUM('string', 'boolean', 'integer') NOT NULL DEFAULT 'string',
    `updated_by` BIGINT UNSIGNED NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `platform_settings_key_unique` (`key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `alumni_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(160) NOT NULL,
    `slug` VARCHAR(180) NOT NULL,
    `type` ENUM('primary', 'secondary', 'university', 'polytechnic', 'college', 'vocational', 'other') NOT NULL DEFAULT 'secondary',
    `scope` ENUM('institution', 'class', 'department', 'faculty', 'association', 'other') NOT NULL DEFAULT 'class',
    `institution_id` BIGINT UNSIGNED NULL,
    `institution` VARCHAR(160) NULL,
    `faculty_id` BIGINT UNSIGNED NULL,
    `department_id` BIGINT UNSIGNED NULL,
    `faculty` VARCHAR(160) NULL,
    `department` VARCHAR(160) NULL,
    `graduation_year` SMALLINT UNSIGNED NULL,
    `country` VARCHAR(80) NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'NGN',
    `bank_name` VARCHAR(120) NULL,
    `bank_account_number` VARCHAR(40) NULL,
    `bank_account_holder` VARCHAR(160) NULL,
    `description` TEXT NULL,
    `logo` VARCHAR(255) NULL,
    `cover_color` VARCHAR(20) NOT NULL DEFAULT '#0B6E4F',
    `visibility` ENUM('public', 'private') NOT NULL DEFAULT 'public',
    `join_policy` ENUM('approval', 'invite') NOT NULL DEFAULT 'approval',
    `verification_status` ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'verified',
    `specificity` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alumni_groups_slug_unique` (`slug`),
    KEY `alumni_groups_type_index` (`type`),
    KEY `alumni_groups_scope_index` (`scope`),
    KEY `alumni_groups_institution_index` (`institution_id`),
    KEY `alumni_groups_faculty_index` (`faculty_id`),
    KEY `alumni_groups_department_index` (`department_id`),
    KEY `alumni_groups_verification_index` (`verification_status`),
    KEY `alumni_groups_status_index` (`status`),
    CONSTRAINT `alumni_groups_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `alumni_groups_faculty_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
    CONSTRAINT `alumni_groups_department_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `alumni_groups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `group_sites` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `subdomain` VARCHAR(63) NOT NULL,
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    `headline` VARCHAR(160) NULL,
    `tagline` VARCHAR(255) NULL,
    `about` TEXT NULL,
    `hero_image` VARCHAR(255) NULL,
    `accent_color` VARCHAR(20) NOT NULL DEFAULT '#0B6E4F',
    `contact_email` VARCHAR(190) NULL,
    `contact_phone` VARCHAR(40) NULL,
    `contact_address` VARCHAR(190) NULL,
    `facebook_url` VARCHAR(255) NULL,
    `instagram_url` VARCHAR(255) NULL,
    `x_url` VARCHAR(255) NULL,
    `linkedin_url` VARCHAR(255) NULL,
    `youtube_url` VARCHAR(255) NULL,
    `whatsapp_url` VARCHAR(255) NULL,
    `show_announcements` TINYINT(1) NOT NULL DEFAULT 1,
    `show_events` TINYINT(1) NOT NULL DEFAULT 1,
    `show_leaders` TINYINT(1) NOT NULL DEFAULT 1,
    `show_stats` TINYINT(1) NOT NULL DEFAULT 1,
    `show_join` TINYINT(1) NOT NULL DEFAULT 1,
    `editor_user_id` BIGINT UNSIGNED NULL,
    `published_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `group_sites_group_unique` (`group_id`),
    UNIQUE KEY `group_sites_subdomain_unique` (`subdomain`),
    KEY `group_sites_editor_index` (`editor_user_id`),
    CONSTRAINT `group_sites_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_sites_editor_foreign` FOREIGN KEY (`editor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `memberships` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role` ENUM('owner', 'admin', 'exco', 'member') NOT NULL DEFAULT 'member',
    `position_title` VARCHAR(80) NULL,
    `status` ENUM('pending', 'active', 'suspended', 'rejected', 'left') NOT NULL DEFAULT 'pending',
    `membership_no` VARCHAR(40) NULL,
    `proof_type` VARCHAR(30) NULL,
    `proof_reference` VARCHAR(160) NULL,
    `proof_file` VARCHAR(255) NULL,
    `proof_note` TEXT NULL,
    `proof_submitted_at` DATETIME NULL,
    `reviewed_by` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `education_record_id` BIGINT UNSIGNED NULL,
    `claim_match` VARCHAR(255) NULL,
    `claim_flagged` TINYINT(1) NOT NULL DEFAULT 0,
    `joined_at` DATETIME NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `memberships_group_user_unique` (`group_id`, `user_id`),
    KEY `memberships_status_index` (`status`),
    KEY `memberships_role_index` (`role`),
    CONSTRAINT `memberships_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `memberships_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `invites` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `role` ENUM('owner', 'admin', 'exco', 'member') NOT NULL DEFAULT 'member',
    `token` CHAR(48) NOT NULL,
    `invited_by` BIGINT UNSIGNED NULL,
    `expires_at` DATETIME NOT NULL,
    `accepted_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `invites_token_unique` (`token`),
    KEY `invites_email_index` (`email`),
    CONSTRAINT `invites_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `education_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `institution_id` BIGINT UNSIGNED NULL,
    `institution_name_raw` VARCHAR(200) NULL,
    `programme` VARCHAR(160) NULL,
    `faculty` VARCHAR(160) NULL,
    `department` VARCHAR(160) NULL,
    `faculty_id` BIGINT UNSIGNED NULL,
    `department_id` BIGINT UNSIGNED NULL,
    `programme_id` BIGINT UNSIGNED NULL,
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
    KEY `education_records_faculty_index` (`faculty_id`),
    KEY `education_records_department_index` (`department_id`),
    CONSTRAINT `education_records_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `education_records_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `education_records_faculty_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
    CONSTRAINT `education_records_department_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `education_records_programme_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `group_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(160) NOT NULL,
    `scope` ENUM('institution', 'class', 'department', 'faculty', 'association', 'other') NOT NULL DEFAULT 'class',
    `type` ENUM('primary', 'secondary', 'university', 'polytechnic', 'college', 'vocational', 'other') NOT NULL DEFAULT 'university',
    `faculty` VARCHAR(160) NULL,
    `department` VARCHAR(160) NULL,
    `faculty_id` BIGINT UNSIGNED NULL,
    `department_id` BIGINT UNSIGNED NULL,
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
    KEY `group_requests_faculty_index` (`faculty_id`),
    KEY `group_requests_department_index` (`department_id`),
    CONSTRAINT `group_requests_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_requests_institution_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_requests_faculty_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
    CONSTRAINT `group_requests_department_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `group_requests_group_foreign` FOREIGN KEY (`created_group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `group_channels` (
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

CREATE TABLE `notification_deliveries` (
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

CREATE TABLE `announcements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `body` MEDIUMTEXT NOT NULL,
    `pinned` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `announcements_group_index` (`group_id`, `created_at`),
    CONSTRAINT `announcements_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `announcements_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `description` TEXT NULL,
    `location` VARCHAR(200) NULL,
    `starts_at` DATETIME NOT NULL,
    `ends_at` DATETIME NULL,
    `capacity` INT UNSIGNED NULL,
    `fee` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `created_by` BIGINT UNSIGNED NULL,
    `status` ENUM('draft', 'published', 'cancelled') NOT NULL DEFAULT 'published',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `events_group_starts_index` (`group_id`, `starts_at`),
    CONSTRAINT `events_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `event_rsvps` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('going', 'maybe', 'declined') NOT NULL DEFAULT 'going',
    `guests` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `event_rsvps_event_user_unique` (`event_id`, `user_id`),
    CONSTRAINT `event_rsvps_event_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    CONSTRAINT `event_rsvps_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `dues_cycles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `description` TEXT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'NGN',
    `due_date` DATE NOT NULL,
    `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `dues_cycles_group_index` (`group_id`, `due_date`),
    CONSTRAINT `dues_cycles_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `welfare_cases` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `category` VARCHAR(30) NOT NULL DEFAULT 'other',
    `description` TEXT NOT NULL,
    `target_amount` DECIMAL(12, 2) NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'NGN',
    `status` ENUM('open', 'funded', 'closed') NOT NULL DEFAULT 'open',
    `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `closed_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `welfare_cases_group_status_index` (`group_id`, `status`),
    CONSTRAINT `welfare_cases_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `welfare_cases_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `elections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `description` TEXT NULL,
    `starts_at` DATETIME NOT NULL,
    `ends_at` DATETIME NOT NULL,
    `status` ENUM('draft', 'open', 'closed', 'published') NOT NULL DEFAULT 'draft',
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `elections_group_index` (`group_id`, `status`),
    CONSTRAINT `elections_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `election_positions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `election_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NULL,
    `seats` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `election_positions_election_index` (`election_id`, `sort_order`),
    CONSTRAINT `election_positions_election_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `election_candidates` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `position_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `manifesto` TEXT NULL,
    `photo` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'withdrawn') NOT NULL DEFAULT 'approved',
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `election_candidates_position_user_unique` (`position_id`, `user_id`),
    CONSTRAINT `election_candidates_position_foreign` FOREIGN KEY (`position_id`) REFERENCES `election_positions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `election_candidates_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `votes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `election_id` BIGINT UNSIGNED NOT NULL,
    `position_id` BIGINT UNSIGNED NOT NULL,
    `candidate_id` BIGINT UNSIGNED NOT NULL,
    `voter_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `votes_election_position_voter_unique` (`election_id`, `position_id`, `voter_id`),
    KEY `votes_candidate_index` (`candidate_id`),
    CONSTRAINT `votes_election_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `votes_position_foreign` FOREIGN KEY (`position_id`) REFERENCES `election_positions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `votes_candidate_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `election_candidates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `votes_voter_foreign` FOREIGN KEY (`voter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `cooperatives` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(160) NOT NULL,
    `registration_no` VARCHAR(60) NULL,
    `share_value` DECIMAL(12, 2) NOT NULL DEFAULT 1000.00,
    `monthly_savings` DECIMAL(12, 2) NOT NULL DEFAULT 5000.00,
    `loan_interest_rate` DECIMAL(6, 2) NOT NULL DEFAULT 10.00,
    `loan_duration_months` SMALLINT UNSIGNED NOT NULL DEFAULT 12,
    `max_loan_multiple` DECIMAL(6, 2) NOT NULL DEFAULT 3.00,
    `guarantors_required` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `fine_absent` DECIMAL(12, 2) NOT NULL DEFAULT 500.00,
    `meeting_frequency` VARCHAR(60) NULL,
    `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cooperatives_group_unique` (`group_id`),
    CONSTRAINT `cooperatives_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `cooperatives_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_members` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cooperative_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `member_no` VARCHAR(40) NULL,
    `role` ENUM('member', 'secretary', 'treasurer', 'loan_officer', 'coordinator') NOT NULL DEFAULT 'member',
    `status` ENUM('pending', 'active', 'suspended', 'rejected', 'exited') NOT NULL DEFAULT 'pending',
    `shares` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `joined_at` DATETIME NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `coop_members_coop_user_unique` (`cooperative_id`, `user_id`),
    KEY `coop_members_status_index` (`status`),
    CONSTRAINT `coop_members_coop_foreign` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_members_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_loan_products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cooperative_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NULL,
    `interest_rate` DECIMAL(6, 2) NOT NULL DEFAULT 10.00,
    `duration_months` SMALLINT UNSIGNED NOT NULL DEFAULT 12,
    `min_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `max_amount` DECIMAL(12, 2) NULL,
    `max_multiple` DECIMAL(6, 2) NULL,
    `guarantors_required` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `coop_loan_products_coop_index` (`cooperative_id`, `status`),
    CONSTRAINT `coop_loan_products_coop_foreign` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_loans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cooperative_id` BIGINT UNSIGNED NOT NULL,
    `member_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `principal` DECIMAL(12, 2) NOT NULL,
    `interest_rate` DECIMAL(6, 2) NOT NULL DEFAULT 10.00,
    `duration_months` SMALLINT UNSIGNED NOT NULL DEFAULT 12,
    `interest_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `total_repayable` DECIMAL(12, 2) NOT NULL,
    `monthly_repayment` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `purpose` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'disbursed', 'repaying', 'completed', 'defaulted', 'cancelled') NOT NULL DEFAULT 'pending',
    `applied_at` DATETIME NOT NULL,
    `reviewed_by` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `disbursed_by` BIGINT UNSIGNED NULL,
    `disbursed_at` DATETIME NULL,
    `due_date` DATE NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `coop_loans_coop_status_index` (`cooperative_id`, `status`),
    KEY `coop_loans_member_index` (`member_id`),
    CONSTRAINT `coop_loans_coop_foreign` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_loans_member_foreign` FOREIGN KEY (`member_id`) REFERENCES `coop_members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_loans_product_foreign` FOREIGN KEY (`product_id`) REFERENCES `coop_loan_products` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_loan_guarantors` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_id` BIGINT UNSIGNED NOT NULL,
    `member_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(12, 2) NULL,
    `status` ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    `responded_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `coop_loan_guarantors_loan_member_unique` (`loan_id`, `member_id`),
    KEY `coop_loan_guarantors_member_index` (`member_id`, `status`),
    CONSTRAINT `coop_loan_guarantors_loan_foreign` FOREIGN KEY (`loan_id`) REFERENCES `coop_loans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_loan_guarantors_member_foreign` FOREIGN KEY (`member_id`) REFERENCES `coop_members` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_loan_installments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_id` BIGINT UNSIGNED NOT NULL,
    `installment_no` SMALLINT UNSIGNED NOT NULL,
    `due_date` DATE NOT NULL,
    `amount_due` DECIMAL(12, 2) NOT NULL,
    `amount_paid` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'partial', 'paid') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `coop_loan_installments_loan_index` (`loan_id`, `installment_no`),
    CONSTRAINT `coop_loan_installments_loan_foreign` FOREIGN KEY (`loan_id`) REFERENCES `coop_loans` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_meetings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cooperative_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `agenda` TEXT NULL,
    `location` VARCHAR(160) NULL,
    `scheduled_at` DATETIME NOT NULL,
    `status` ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    `minutes` TEXT NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `coop_meetings_coop_index` (`cooperative_id`, `scheduled_at`),
    CONSTRAINT `coop_meetings_coop_foreign` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_meeting_attendance` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `meeting_id` BIGINT UNSIGNED NOT NULL,
    `member_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('present', 'absent', 'apology', 'excused') NOT NULL DEFAULT 'present',
    `note` VARCHAR(160) NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `coop_meeting_attendance_meeting_member_unique` (`meeting_id`, `member_id`),
    CONSTRAINT `coop_meeting_attendance_meeting_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `coop_meetings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_meeting_attendance_member_foreign` FOREIGN KEY (`member_id`) REFERENCES `coop_members` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_fines` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cooperative_id` BIGINT UNSIGNED NOT NULL,
    `member_id` BIGINT UNSIGNED NOT NULL,
    `meeting_id` BIGINT UNSIGNED NULL,
    `reason` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `status` ENUM('unpaid', 'paid', 'waived') NOT NULL DEFAULT 'unpaid',
    `issued_by` BIGINT UNSIGNED NULL,
    `issued_at` DATETIME NOT NULL,
    `paid_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `coop_fines_coop_status_index` (`cooperative_id`, `status`),
    KEY `coop_fines_member_index` (`member_id`),
    CONSTRAINT `coop_fines_coop_foreign` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_fines_member_foreign` FOREIGN KEY (`member_id`) REFERENCES `coop_members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_fines_meeting_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `coop_meetings` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_dividends` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cooperative_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `period` VARCHAR(60) NULL,
    `basis` ENUM('shares', 'savings') NOT NULL DEFAULT 'shares',
    `total_amount` DECIMAL(12, 2) NOT NULL,
    `status` ENUM('draft', 'distributed') NOT NULL DEFAULT 'draft',
    `created_by` BIGINT UNSIGNED NULL,
    `declared_at` DATETIME NOT NULL,
    `distributed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `coop_dividends_coop_index` (`cooperative_id`, `status`),
    CONSTRAINT `coop_dividends_coop_foreign` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_dividend_allocations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dividend_id` BIGINT UNSIGNED NOT NULL,
    `member_id` BIGINT UNSIGNED NOT NULL,
    `basis_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `transaction_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `coop_dividend_allocations_dividend_member_unique` (`dividend_id`, `member_id`),
    CONSTRAINT `coop_dividend_allocations_dividend_foreign` FOREIGN KEY (`dividend_id`) REFERENCES `coop_dividends` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_dividend_allocations_member_foreign` FOREIGN KEY (`member_id`) REFERENCES `coop_members` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `coop_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cooperative_id` BIGINT UNSIGNED NOT NULL,
    `member_id` BIGINT UNSIGNED NULL,
    `type` ENUM('share_purchase', 'savings_deposit', 'savings_withdrawal', 'loan_disbursement', 'loan_repayment', 'fine', 'fine_payment', 'dividend', 'adjustment') NOT NULL,
    `direction` ENUM('in', 'out') NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `method` ENUM('cash', 'bank', 'gateway', 'system') NOT NULL DEFAULT 'cash',
    `reference` VARCHAR(80) NULL,
    `payment_id` BIGINT UNSIGNED NULL,
    `loan_id` BIGINT UNSIGNED NULL,
    `fine_id` BIGINT UNSIGNED NULL,
    `dividend_id` BIGINT UNSIGNED NULL,
    `note` VARCHAR(255) NULL,
    `recorded_by` BIGINT UNSIGNED NULL,
    `occurred_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `coop_transactions_payment_unique` (`payment_id`),
    KEY `coop_transactions_coop_type_index` (`cooperative_id`, `type`),
    KEY `coop_transactions_member_index` (`member_id`),
    CONSTRAINT `coop_transactions_coop_foreign` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
    CONSTRAINT `coop_transactions_member_foreign` FOREIGN KEY (`member_id`) REFERENCES `coop_members` (`id`) ON DELETE SET NULL,
    CONSTRAINT `coop_transactions_loan_foreign` FOREIGN KEY (`loan_id`) REFERENCES `coop_loans` (`id`) ON DELETE SET NULL,
    CONSTRAINT `coop_transactions_fine_foreign` FOREIGN KEY (`fine_id`) REFERENCES `coop_fines` (`id`) ON DELETE SET NULL,
    CONSTRAINT `coop_transactions_dividend_foreign` FOREIGN KEY (`dividend_id`) REFERENCES `coop_dividends` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `purpose` ENUM('dues', 'welfare', 'event', 'donation', 'coop_savings', 'coop_shares', 'coop_loan', 'coop_fine') NOT NULL DEFAULT 'dues',
    `dues_cycle_id` BIGINT UNSIGNED NULL,
    `welfare_case_id` BIGINT UNSIGNED NULL,
    `event_id` BIGINT UNSIGNED NULL,
    `coop_loan_id` BIGINT UNSIGNED NULL,
    `coop_fine_id` BIGINT UNSIGNED NULL,
    `reference` VARCHAR(80) NOT NULL,
    `gateway` VARCHAR(30) NOT NULL DEFAULT 'manual',
    `amount` DECIMAL(12, 2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'NGN',
    `status` ENUM('pending', 'success', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `payer_name` VARCHAR(120) NULL,
    `payer_email` VARCHAR(190) NULL,
    `meta` JSON NULL,
    `paid_at` DATETIME NULL,
    `confirmed_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payments_reference_unique` (`reference`),
    KEY `payments_group_status_index` (`group_id`, `status`),
    KEY `payments_purpose_index` (`purpose`),
    KEY `payments_dues_cycle_index` (`dues_cycle_id`),
    KEY `payments_welfare_case_index` (`welfare_case_id`),
    KEY `payments_coop_loan_index` (`coop_loan_id`),
    KEY `payments_coop_fine_index` (`coop_fine_id`),
    CONSTRAINT `payments_group_foreign` FOREIGN KEY (`group_id`) REFERENCES `alumni_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `payments_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `payments_dues_foreign` FOREIGN KEY (`dues_cycle_id`) REFERENCES `dues_cycles` (`id`) ON DELETE SET NULL,
    CONSTRAINT `payments_welfare_foreign` FOREIGN KEY (`welfare_case_id`) REFERENCES `welfare_cases` (`id`) ON DELETE SET NULL,
    CONSTRAINT `payments_event_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
    CONSTRAINT `payments_coop_loan_foreign` FOREIGN KEY (`coop_loan_id`) REFERENCES `coop_loans` (`id`) ON DELETE SET NULL,
    CONSTRAINT `payments_coop_fine_foreign` FOREIGN KEY (`coop_fine_id`) REFERENCES `coop_fines` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `welfare_contributions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `welfare_case_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `payment_id` BIGINT UNSIGNED NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `note` VARCHAR(255) NULL,
    `anonymous` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `welfare_contributions_payment_unique` (`payment_id`),
    KEY `welfare_contributions_case_index` (`welfare_case_id`),
    CONSTRAINT `welfare_contributions_case_foreign` FOREIGN KEY (`welfare_case_id`) REFERENCES `welfare_cases` (`id`) ON DELETE CASCADE,
    CONSTRAINT `welfare_contributions_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `welfare_contributions_payment_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(80) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `target_type` VARCHAR(60) NULL,
    `target_id` BIGINT UNSIGNED NULL,
    `ip` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `audit_logs_group_index` (`group_id`, `created_at`),
    KEY `audit_logs_user_index` (`user_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
