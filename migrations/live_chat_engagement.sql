-- Panda Truck Reloaded - chat engagement features
-- Run once in phpMyAdmin. It does not delete existing chat data.

SET NAMES utf8mb4;

ALTER TABLE `chat_messages`
  ADD COLUMN `is_featured` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_deleted`;

CREATE TABLE IF NOT EXISTS `chat_polls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(180) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_polls_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_poll_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `option_text` varchar(80) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_chat_poll_options_poll` (`poll_id`),
  CONSTRAINT `fk_chat_poll_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `chat_polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_poll_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `device_hash` varchar(64) DEFAULT NULL,
  `client_id` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_chat_poll_user` (`poll_id`, `user_id`),
  UNIQUE KEY `uniq_chat_poll_device` (`poll_id`, `device_hash`),
  KEY `idx_chat_poll_votes_poll` (`poll_id`),
  KEY `idx_chat_poll_votes_option` (`option_id`),
  CONSTRAINT `fk_chat_poll_votes_poll` FOREIGN KEY (`poll_id`) REFERENCES `chat_polls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_poll_votes_option` FOREIGN KEY (`option_id`) REFERENCES `chat_poll_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_poll_votes_user` FOREIGN KEY (`user_id`) REFERENCES `chat_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
