-- =====================================================
-- PANDA TRUCK RELOADED - SQL PRODUCCION COMPLETA
-- Importar en phpMyAdmin sobre la base creada en cPanel.
-- No borra datos existentes. Compatible con MySQL/MariaDB utf8mb4.
-- =====================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('superadmin','admin','dj','viewer') NOT NULL DEFAULT 'viewer',
  `dj_id` int(11) DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `user_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `djs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `socials` text DEFAULT NULL,
  `mixes` int(11) DEFAULT 0,
  `videos` int(11) DEFAULT 0,
  `featured_week` tinyint(1) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `plan` enum('free','pro','founder') NOT NULL DEFAULT 'free',
  `subscription_status` enum('active','expired','pending','cancelled') NOT NULL DEFAULT 'pending',
  `subscription_start` datetime DEFAULT NULL,
  `subscription_end` datetime DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 0,
  `email` varchar(150) DEFAULT NULL,
  `instagram` varchar(150) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `profile_photo` text DEFAULT NULL,
  `slug` varchar(160) DEFAULT NULL,
  `last_notice_7_days` datetime DEFAULT NULL,
  `last_notice_3_days` datetime DEFAULT NULL,
  `last_notice_1_day` datetime DEFAULT NULL,
  `last_notice_expired` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_djs_name` (`name`),
  KEY `idx_djs_subscription_end` (`subscription_end`),
  KEY `idx_djs_subscription_status` (`subscription_status`),
  KEY `idx_djs_plan_status` (`plan`, `subscription_status`),
  KEY `idx_djs_slug` (`slug`),
  KEY `idx_djs_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mixes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `dj` varchar(100) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `url` text NOT NULL,
  `cover` text DEFAULT NULL,
  `duration` varchar(10) DEFAULT NULL,
  `sizeMB` int(11) DEFAULT 0,
  `date` date DEFAULT NULL,
  `plays` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `tracks` text DEFAULT NULL,
  `is_superpack` tinyint(1) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dj` (`dj`),
  KEY `idx_genre` (`genre`),
  KEY `idx_active` (`active`),
  KEY `idx_superpack` (`is_superpack`),
  KEY `idx_mixes_dj_active` (`dj`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `dj` varchar(100) NOT NULL,
  `type` enum('mp4','youtube') NOT NULL DEFAULT 'mp4',
  `url` text NOT NULL,
  `cover` text DEFAULT NULL,
  `duration` varchar(10) DEFAULT NULL,
  `plays` int(11) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_videos_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `item_type` enum('mix','video') NOT NULL,
  `plays` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_item` (`item_id`, `item_type`)
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

CREATE TABLE IF NOT EXISTS `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('sidebar','horizontal','sponsor') DEFAULT 'sponsor',
  `image` text NOT NULL,
  `url` text DEFAULT NULL,
  `size` varchar(20) DEFAULT '150x60',
  `position` int(11) DEFAULT 1,
  `active` tinyint(4) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `clicks` int(11) DEFAULT 0,
  `impressions` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_banners_active_type` (`active`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `place` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'activo',
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `configuration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `config_type` varchar(20) DEFAULT 'string',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `player_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('mp4','youtube','twitch') NOT NULL DEFAULT 'mp4',
  `mp4Url` text DEFAULT NULL,
  `mp4Poster` text DEFAULT NULL,
  `mp4Title` varchar(255) DEFAULT NULL,
  `hero_video_url` text DEFAULT NULL,
  `hero_video_poster` text DEFAULT NULL,
  `hero_video_title` varchar(255) DEFAULT NULL,
  `hero_type` enum('mp4','youtube','twitch') NOT NULL DEFAULT 'mp4',
  `youtube_id` varchar(50) DEFAULT NULL,
  `twitch_channel` varchar(100) DEFAULT NULL,
  `bunny_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `radio_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `radioUrl` text NOT NULL,
  `radioName` varchar(255) NOT NULL DEFAULT 'Panda Truck Radio',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `cache_value` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cache_key` (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `weekly_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `data` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `week_start` (`week_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `playlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`username`, `password`, `email`, `role`, `active`)
SELECT 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tudominio.com', 'superadmin', 1
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'superadmin');

INSERT INTO `djs` (`name`, `genre`, `city`, `bio`, `avatar`, `active`, `slug`) VALUES
('@DJHALLO507', 'Urbano', 'Panamá', 'DJ HALLO, especialista en música urbana y creador de los mejores mixes.', 'https://yt3.ggpht.com/nu6MhAQ7vDs8binigBq2on8XwVgJUqWEcJR9Ldr36oHi4XQJPBQDo72-ySxaYWJxr5fok3q3=s176-c-k-c0x00ffffff-no-rj-mo', 1, 'djhallo507'),
('DJ_NELPTY', 'Urbano', 'Panamá', 'DJ Nelpty, productor y DJ con un estilo único.', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ+NELPTY.jpg', 1, 'dj-nelpty'),
('DJ JIMMY', 'Variado', 'Panamá', 'DJ Jimmy, propietario y fundador de Panda Truck Reloaded.', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ_JIMMY.jpg', 1, 'dj-jimmy'),
('DJ_IRVIN_ALGARETE', 'Crossover', 'Panamá', 'Dj Irvin Algarete conocido como el GUSANO, DJ crossover, creativo, jocoso.', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ+IRVING.jpg', 1, 'dj-irvin-algarete'),
('@DJMASTER507OFICIAL', 'Variado-mix', 'Panamá', 'DJ Master 507, creador de contenido musical.', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', 1, 'djmaster507oficial'),
('Dj-Joc-Pty', 'VARIADOS', 'Panama', '', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/Dj-Joc-Pty.jpg', 1, 'dj-joc-pty')
ON DUPLICATE KEY UPDATE
  `genre` = VALUES(`genre`),
  `city` = VALUES(`city`),
  `bio` = COALESCE(NULLIF(`bio`, ''), VALUES(`bio`)),
  `avatar` = VALUES(`avatar`),
  `active` = 1,
  `slug` = COALESCE(NULLIF(`slug`, ''), VALUES(`slug`));

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `group`, `description`) VALUES
('site_title', 'Panda Truck Reloaded', 'string', 'general', 'Título del sitio'),
('site_description', 'La casa de los DJs en Panamá - Descarga los mejores mixes', 'string', 'general', 'Descripción del sitio'),
('footer_text', 'Panda Truck Reloaded - La casa de los DJs en Panamá', 'string', 'general', 'Texto del footer'),
('maintenance_mode', '0', 'boolean', 'display', 'Modo mantenimiento'),
('radio_url', 'https://stream.zeno.fm/vjsa6jiwafavv', 'string', 'radio', 'URL de radio'),
('radio_name', 'Panda Truck Radio', 'string', 'radio', 'Nombre de radio'),
('guia_title', 'Guía para DJs - Panda Truck', 'string', 'guia', 'Título de guía'),
('guia_whatsapp', '50762115209', 'string', 'guia', 'WhatsApp de contacto'),
('dj_pro_whatsapp', '50762115209', 'string', 'dj_pro', 'WhatsApp para DJ PRO')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `radio_config` (`id`, `radioUrl`, `radioName`) VALUES
(1, 'https://stream.zeno.fm/vjsa6jiwafavv', 'Panda Truck Radio')
ON DUPLICATE KEY UPDATE `radioUrl` = VALUES(`radioUrl`), `radioName` = VALUES(`radioName`);

INSERT INTO `player_config` (`id`, `hero_type`, `hero_video_title`) VALUES
(1, 'mp4', 'Video Destacado')
ON DUPLICATE KEY UPDATE `hero_type` = COALESCE(`hero_type`, VALUES(`hero_type`));
