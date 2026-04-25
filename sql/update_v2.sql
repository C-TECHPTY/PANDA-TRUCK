-- sql/update_v2.sql
-- Actualización de la base de datos para la versión 2.0

-- Tabla de usuarios para roles
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('superadmin','admin','dj') NOT NULL DEFAULT 'dj',
  `dj_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  FOREIGN KEY (`dj_id`) REFERENCES `djs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario super admin por defecto
INSERT INTO `users` (`username`, `password`, `email`, `role`) 
VALUES ('jimmypanda', '$2y$10$YourHashedPasswordHere', 'admin@pandatruck.com', 'superadmin');

-- Insertar usuario admin por defecto
INSERT INTO `users` (`username`, `password`, `email`, `role`) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'admin2@pandatruck.com', 'admin');

-- Tabla de logs de actividad
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` varchar(20) DEFAULT 'string',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración inicial
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('site_title', 'Panda Truck Reloaded 2.0'),
('site_description', 'La casa de los DJs en Panamá'),
('maintenance_mode', '0'),
('auto_sync_interval', '300');

-- Agregar columna de Super Pack a mixes (opcional)
ALTER TABLE `mixes` ADD COLUMN `is_superpack` tinyint(1) DEFAULT 0 AFTER `active`;

-- Tabla de reproducciones por usuario (para estadísticas detalladas)
CREATE TABLE IF NOT EXISTS `user_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('mix','video') NOT NULL,
  `action` enum('play','download','share') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`,`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualizar estadísticas con datos existentes
INSERT INTO `statistics` (`item_id`, `item_type`, `plays`, `downloads`, `last_updated`)
SELECT id, 'mix', plays, downloads, NOW() 
FROM mixes 
WHERE plays > 0 OR downloads > 0
ON DUPLICATE KEY UPDATE 
plays = VALUES(plays),
downloads = VALUES(downloads);