-- =====================================================
-- PANDA TRUCK RELOADED - MIGRACION SOLO CAMBIOS
-- Ejecutar sobre una base existente. No borra datos.
-- Requiere MariaDB 10.3+ o MySQL 8 compatible con cPanel.
-- =====================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `site_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_type` varchar(50) NOT NULL,
  `page_url` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `dj_id` int(11) DEFAULT NULL,
  `mix_id` int(11) DEFAULT NULL,
  `ip_hash` char(64) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(30) DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_site_visits_page_type` (`page_type`),
  KEY `idx_site_visits_created_at` (`created_at`),
  KEY `idx_site_visits_dj_id` (`dj_id`),
  KEY `idx_site_visits_mix_id` (`mix_id`),
  KEY `idx_site_visits_related_id` (`related_id`),
  KEY `idx_site_visits_ip_created` (`ip_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dj_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dj_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 10.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Yappy',
  `reference_number` varchar(120) DEFAULT NULL,
  `proof_image` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dj_payments_dj_id` (`dj_id`),
  KEY `idx_dj_payments_created_at` (`created_at`),
  KEY `idx_dj_payments_payment_date` (`payment_date`),
  KEY `idx_dj_payments_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `djs`
  ADD COLUMN IF NOT EXISTS `featured_week` tinyint(1) DEFAULT 0 AFTER `videos`,
  ADD COLUMN IF NOT EXISTS `plan` enum('free','pro','founder') NOT NULL DEFAULT 'free' AFTER `active`,
  ADD COLUMN IF NOT EXISTS `subscription_status` enum('active','expired','pending','cancelled') NOT NULL DEFAULT 'pending' AFTER `plan`,
  ADD COLUMN IF NOT EXISTS `subscription_start` datetime DEFAULT NULL AFTER `subscription_status`,
  ADD COLUMN IF NOT EXISTS `subscription_end` datetime DEFAULT NULL AFTER `subscription_start`,
  ADD COLUMN IF NOT EXISTS `is_featured` tinyint(1) NOT NULL DEFAULT 0 AFTER `subscription_end`,
  ADD COLUMN IF NOT EXISTS `priority` int(11) NOT NULL DEFAULT 0 AFTER `is_featured`,
  ADD COLUMN IF NOT EXISTS `email` varchar(150) DEFAULT NULL AFTER `priority`,
  ADD COLUMN IF NOT EXISTS `instagram` varchar(150) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `biography` text DEFAULT NULL AFTER `instagram`,
  ADD COLUMN IF NOT EXISTS `profile_photo` text DEFAULT NULL AFTER `biography`,
  ADD COLUMN IF NOT EXISTS `slug` varchar(160) DEFAULT NULL AFTER `profile_photo`,
  ADD COLUMN IF NOT EXISTS `last_notice_7_days` datetime DEFAULT NULL AFTER `slug`,
  ADD COLUMN IF NOT EXISTS `last_notice_3_days` datetime DEFAULT NULL AFTER `last_notice_7_days`,
  ADD COLUMN IF NOT EXISTS `last_notice_1_day` datetime DEFAULT NULL AFTER `last_notice_3_days`,
  ADD COLUMN IF NOT EXISTS `last_notice_expired` datetime DEFAULT NULL AFTER `last_notice_1_day`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `mixes`
  ADD COLUMN IF NOT EXISTS `is_superpack` tinyint(1) DEFAULT 0 AFTER `tracks`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `banners`
  ADD COLUMN IF NOT EXISTS `start_date` date DEFAULT NULL AFTER `active`,
  ADD COLUMN IF NOT EXISTS `end_date` date DEFAULT NULL AFTER `start_date`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `dj_id` int(11) DEFAULT NULL AFTER `role`,
  ADD COLUMN IF NOT EXISTS `avatar` text DEFAULT NULL AFTER `dj_id`,
  ADD COLUMN IF NOT EXISTS `last_login` datetime DEFAULT NULL AFTER `avatar`,
  ADD COLUMN IF NOT EXISTS `last_ip` varchar(45) DEFAULT NULL AFTER `last_login`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_user` (`user_id`),
  KEY `idx_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','int','boolean','json') DEFAULT 'string',
  `group` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `albumes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `artist` varchar(100) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `cover` text DEFAULT NULL,
  `zip_url` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `canciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `album_id` int(11) NOT NULL,
  `track_number` int(11) DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `duration` varchar(10) DEFAULT NULL,
  `url` text NOT NULL,
  `sizeMB` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_album` (`album_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `group`, `description`) VALUES
('maintenance_mode', '0', 'boolean', 'display', 'Modo mantenimiento'),
('dj_pro_whatsapp', '50762115209', 'string', 'dj_pro', 'WhatsApp para DJ PRO')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;

INSERT INTO `djs` (`name`, `genre`, `city`, `avatar`, `active`, `slug`) VALUES
('@DJHALLO507', 'Urbano', 'Panamá', 'https://yt3.ggpht.com/nu6MhAQ7vDs8binigBq2on8XwVgJUqWEcJR9Ldr36oHi4XQJPBQDo72-ySxaYWJxr5fok3q3=s176-c-k-c0x00ffffff-no-rj-mo', 1, 'djhallo507'),
('DJ_NELPTY', 'Urbano', 'Panamá', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ+NELPTY.jpg', 1, 'dj-nelpty'),
('DJ JIMMY', 'Variado', 'Panamá', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ_JIMMY.jpg', 1, 'dj-jimmy'),
('DJ_IRVIN_ALGARETE', 'Crossover', 'Panamá', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ+IRVING.jpg', 1, 'dj-irvin-algarete'),
('@DJMASTER507OFICIAL', 'Variado-mix', 'Panamá', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', 1, 'djmaster507oficial'),
('Dj-Joc-Pty', 'VARIADOS', 'Panama', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/Dj-Joc-Pty.jpg', 1, 'dj-joc-pty')
ON DUPLICATE KEY UPDATE
  `genre` = VALUES(`genre`),
  `city` = VALUES(`city`),
  `avatar` = VALUES(`avatar`),
  `active` = 1,
  `slug` = COALESCE(NULLIF(`slug`, ''), VALUES(`slug`));
