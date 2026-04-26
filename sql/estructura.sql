-- =====================================================
-- PANDA TRUCK RELOADED 2.0 - BASE DE DATOS
-- =====================================================

CREATE DATABASE IF NOT EXISTS pandatruck_v2 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE pandatruck_v2;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('superadmin','admin','dj') NOT NULL DEFAULT 'dj',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de DJs
CREATE TABLE IF NOT EXISTS `djs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `bio` text,
  `avatar` text,
  `socials` text,
  `email` varchar(150) DEFAULT NULL,
  `instagram` varchar(150) DEFAULT NULL,
  `biography` text,
  `profile_photo` text,
  `slug` varchar(160) DEFAULT NULL,
  `mixes` int(11) DEFAULT 0,
  `videos` int(11) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_dj_name` (`name`),
  KEY `idx_djs_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de mixes
CREATE TABLE IF NOT EXISTS `mixes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `dj` varchar(100) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `url` text NOT NULL,
  `cover` text,
  `duration` varchar(10) DEFAULT NULL,
  `sizeMB` int(11) DEFAULT 0,
  `date` date DEFAULT NULL,
  `plays` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `tracks` text,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de videos
CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `dj` varchar(100) NOT NULL,
  `type` enum('mp4','youtube') DEFAULT 'mp4',
  `url` text NOT NULL,
  `cover` text,
  `duration` varchar(10) DEFAULT NULL,
  `sizeMB` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `plays` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de estadísticas
CREATE TABLE IF NOT EXISTS `statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `item_type` enum('mix','video') NOT NULL,
  `plays` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_item` (`item_id`,`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de visitas publicas
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

-- Tabla de eventos
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `place` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'activo',
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de banners
CREATE TABLE IF NOT EXISTS `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('sidebar','horizontal','sponsor') DEFAULT 'sponsor',
  `image` text NOT NULL,
  `url` text DEFAULT '#',
  `size` varchar(20) DEFAULT '150x60',
  `position` int(11) DEFAULT 1,
  `active` tinyint(4) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `clicks` int(11) DEFAULT 0,
  `impressions` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar usuarios (contraseña: djimmy01 y 0182NsD*)
INSERT INTO `users` (`username`, `password`, `email`, `role`) VALUES
('jimmypanda', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jimmy@pandatruck.com', 'admin'),
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin@pandatruck.com', 'superadmin');

-- Insertar DJs de ejemplo
INSERT INTO `djs` (`name`, `genre`, `city`, `avatar`) VALUES
('@DJHALLO507', 'Urbano', 'Panamá', 'https://yt3.ggpht.com/nu6MhAQ7vDs8binigBq2on8XwVgJUqWEcJR9Ldr36oHi4XQJPBQDo72-ySxaYWJxr5fok3q3=s176-c-k-c0x00ffffff-no-rj-mo'),
('DJ_NELPTY', 'Urbano', 'Panamá', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ+NELPTY.jpg'),
('DJ JIMMY', 'Variado', 'Panamá', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ_JIMMY.jpg'),
('DJ_IRVIN_ALGARETE', 'Crossover', 'Panamá', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ+IRVING.jpg'),
('@DJMASTER507OFICIAL', 'Variado-mix', 'Panamá', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg'),
('Dj-Joc-Pty', 'VARIADOS', 'Panama', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/Dj-Joc-Pty.jpg')
ON DUPLICATE KEY UPDATE
  `genre` = VALUES(`genre`),
  `city` = VALUES(`city`),
  `avatar` = VALUES(`avatar`),
  `active` = 1;

-- Insertar mixes de ejemplo
INSERT INTO `mixes` (`title`, `dj`, `genre`, `url`, `cover`, `duration`, `sizeMB`, `date`) VALUES
('Activadera Total', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+ACTIVADERA+TOTAL+BY+EL+PANDA+TRUCK.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/ACTIVADERA+TOTAL+PANDA+TRUCK.jpg', '1:07:00', 97, CURDATE()),
('Vallenato Del Alma VOL 1', 'DJ_NELPTY', 'Vallenato', 'https://f005.backblazeb2.com/file/mixes-mp3/Vallenato+Del+Alma+VOL+1.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/Vallenato+Del+Alma+VOL1.jpg', '26:06', 37, CURDATE());
