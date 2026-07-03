-- Panda Truck Reloaded - Live chat module
-- Run this once in phpMyAdmin after uploading the new files.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `chat_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` varchar(80) NOT NULL,
  `nickname` varchar(40) NOT NULL,
  `role` enum('viewer','chat_admin') NOT NULL DEFAULT 'viewer',
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `banned_reason` varchar(180) DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_chat_client` (`client_id`),
  KEY `idx_chat_users_seen` (`last_seen`),
  KEY `idx_chat_users_banned` (`is_banned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `admin_user_id` int(11) DEFAULT NULL,
  `recipient_user_id` int(11) DEFAULT NULL,
  `nickname` varchar(40) NOT NULL,
  `role` enum('viewer','chat_admin','admin','superadmin') NOT NULL DEFAULT 'viewer',
  `message` varchar(500) NOT NULL,
  `message_type` enum('public','private','system') NOT NULL DEFAULT 'public',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_messages_created` (`created_at`),
  KEY `idx_chat_messages_user` (`user_id`),
  KEY `idx_chat_messages_recipient` (`recipient_user_id`),
  CONSTRAINT `fk_chat_messages_user` FOREIGN KEY (`user_id`) REFERENCES `chat_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chat_messages_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `chat_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `reaction` varchar(12) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_reactions_created` (`created_at`),
  CONSTRAINT `fk_chat_reactions_user` FOREIGN KEY (`user_id`) REFERENCES `chat_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_settings` (
  `setting_key` varchar(60) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chat_settings` (`setting_key`, `setting_value`)
VALUES
  ('enabled', '1'),
  ('rules', 'Respeta a los DJs y a los oyentes. No spam, insultos, datos privados ni enlaces sospechosos.'),
  ('welcome_message', 'Bienvenido a la sala en vivo de Panda Truck Reloaded.')
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);
