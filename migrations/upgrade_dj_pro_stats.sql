-- PANDA TRUCK RELOADED - DJ PRO, visitas y pagos manuales
-- Compatible con XAMPP / MariaDB 10.4+ y hosting Namecheap.
-- Ejecutar una sola vez sobre la base actual. No borra datos existentes.

SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `djs`
  ADD COLUMN IF NOT EXISTS `plan` ENUM('free','pro') NOT NULL DEFAULT 'free' AFTER `active`,
  ADD COLUMN IF NOT EXISTS `subscription_status` ENUM('active','expired','pending','cancelled') NOT NULL DEFAULT 'pending' AFTER `plan`,
  ADD COLUMN IF NOT EXISTS `subscription_start` DATETIME NULL AFTER `subscription_status`,
  ADD COLUMN IF NOT EXISTS `subscription_end` DATETIME NULL AFTER `subscription_start`,
  ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `subscription_end`,
  ADD COLUMN IF NOT EXISTS `priority` INT NOT NULL DEFAULT 0 AFTER `is_featured`,
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(150) NULL AFTER `priority`,
  ADD COLUMN IF NOT EXISTS `instagram` VARCHAR(150) NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `biography` TEXT NULL AFTER `instagram`,
  ADD COLUMN IF NOT EXISTS `profile_photo` TEXT NULL AFTER `biography`,
  ADD COLUMN IF NOT EXISTS `slug` VARCHAR(160) NULL AFTER `profile_photo`,
  ADD COLUMN IF NOT EXISTS `last_notice_7_days` DATETIME NULL AFTER `slug`,
  ADD COLUMN IF NOT EXISTS `last_notice_3_days` DATETIME NULL AFTER `last_notice_7_days`,
  ADD COLUMN IF NOT EXISTS `last_notice_1_day` DATETIME NULL AFTER `last_notice_3_days`,
  ADD COLUMN IF NOT EXISTS `last_notice_expired` DATETIME NULL AFTER `last_notice_1_day`;

UPDATE `djs`
SET
  `biography` = COALESCE(NULLIF(`biography`, ''), `bio`),
  `profile_photo` = COALESCE(NULLIF(`profile_photo`, ''), `avatar`),
  `slug` = COALESCE(NULLIF(`slug`, ''), LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`name`, '@', ''), ' ', '-'), '_', '-'), '.', ''), '--', '-')))
WHERE `slug` IS NULL OR `slug` = '' OR `biography` IS NULL OR `profile_photo` IS NULL;

CREATE TABLE IF NOT EXISTS `site_visits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_type` VARCHAR(50) NOT NULL,
  `page_url` TEXT NOT NULL,
  `related_id` INT NULL,
  `dj_id` INT NULL,
  `mix_id` INT NULL,
  `ip_hash` CHAR(64) NOT NULL,
  `user_agent` TEXT NULL,
  `device_type` VARCHAR(30) NULL,
  `referer` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_site_visits_page_type` (`page_type`),
  KEY `idx_site_visits_created_at` (`created_at`),
  KEY `idx_site_visits_dj_id` (`dj_id`),
  KEY `idx_site_visits_mix_id` (`mix_id`),
  KEY `idx_site_visits_related_id` (`related_id`),
  KEY `idx_site_visits_ip_created` (`ip_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dj_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dj_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 10.00,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Yappy',
  `reference_number` VARCHAR(120) NULL,
  `proof_image` TEXT NULL,
  `notes` TEXT NULL,
  `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dj_payments_dj_id` (`dj_id`),
  KEY `idx_dj_payments_created_at` (`created_at`),
  KEY `idx_dj_payments_payment_date` (`payment_date`),
  KEY `idx_dj_payments_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS `idx_djs_subscription_end` ON `djs` (`subscription_end`);
CREATE INDEX IF NOT EXISTS `idx_djs_subscription_status` ON `djs` (`subscription_status`);
CREATE INDEX IF NOT EXISTS `idx_djs_plan_status` ON `djs` (`plan`, `subscription_status`);
CREATE INDEX IF NOT EXISTS `idx_djs_slug` ON `djs` (`slug`);
CREATE INDEX IF NOT EXISTS `idx_djs_priority` ON `djs` (`priority`);
CREATE INDEX IF NOT EXISTS `idx_mixes_dj_active` ON `mixes` (`dj`, `active`);

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `group`, `description`)
VALUES
  ('dj_pro_price', '10.00', 'string', 'dj_pro', 'Precio mensual del plan DJ PRO'),
  ('dj_pro_whatsapp', '50762115209', 'string', 'dj_pro', 'WhatsApp para activaciones DJ PRO'),
  ('cdn_base_url', '', 'string', 'storage', 'Base URL opcional para servir MP3 por CDN')
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`),
  `updated_at` = NOW();

SET FOREIGN_KEY_CHECKS=1;
