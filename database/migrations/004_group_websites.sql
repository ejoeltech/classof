-- Release 4: public single-page websites for alumni groups.
-- Additive only.

CREATE TABLE IF NOT EXISTS `group_sites` (
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

-- Give every existing group a site row. The id suffix guarantees a unique,
-- valid label; owners can shorten it from the website editor.
INSERT INTO `group_sites`
    (`group_id`, `subdomain`, `status`, `headline`, `tagline`, `about`,
     `accent_color`, `published_at`, `created_at`, `updated_at`)
SELECT g.`id`,
       CONCAT(LEFT(g.`slug`, 40), '-', g.`id`),
       IF(g.`visibility` = 'public', 'published', 'draft'),
       g.`name`,
       NULL,
       g.`description`,
       COALESCE(NULLIF(g.`cover_color`, ''), '#0B6E4F'),
       IF(g.`visibility` = 'public', NOW(), NULL),
       NOW(),
       NOW()
FROM `alumni_groups` g
LEFT JOIN `group_sites` s ON s.`group_id` = g.`id`
WHERE s.`id` IS NULL;
