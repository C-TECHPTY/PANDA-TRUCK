-- Respaldo de Base de Datos Panda Truck
-- Generado: 2025-09-27 23:01:40
-- Base de datos: panda_truck

-- --------------------------------------------------------
-- Table structure for table `configurations`
-- --------------------------------------------------------

CREATE TABLE `configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `configurations`
-- --------------------------------------------------------

INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `created_at`, `updated_at`) VALUES ('1', 'player', '{\"type\":\"youtube\",\"mp4Url\":\"\",\"mp4Poster\":\"\",\"youtubeId\":\"https:\\/\\/www.youtube.com\\/watch?v=ZvJxzo3f-wU&list=RDZvJxzo3f-wU&start_radio=1\",\"youtubeStart\":0,\"title\":\"Jeison Vega ❌ DJ Chiqui Dubs - TEN CUIDADO (Oficial VideoClip)\"}', '2025-09-24 20:51:21', '2025-09-24 20:52:28');

-- --------------------------------------------------------
-- Table structure for table `djs`
-- --------------------------------------------------------

CREATE TABLE `djs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `socials` text DEFAULT NULL,
  `mixes` int(11) DEFAULT 0,
  `videos` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `djs`
-- --------------------------------------------------------

INSERT INTO `djs` (`id`, `name`, `genre`, `city`, `bio`, `avatar`, `socials`, `mixes`, `videos`, `created_at`) VALUES ('1', 'DJ Panda', 'Salsa / Urbano', 'Ciudad de Panamá', 'DJ principal de Panda Truck Reloaded', 'https://api.dicebear.com/7.x/identicon/svg?seed=DJ Panda', 'https://instagram.com/djpanda', '5', '3', '2025-09-27 22:22:42');
INSERT INTO `djs` (`id`, `name`, `genre`, `city`, `bio`, `avatar`, `socials`, `mixes`, `videos`, `created_at`) VALUES ('2', 'Dj Nelpty', 'Urbano', 'Panama', '', '', '', '0', '0', '2025-09-27 22:22:42');
INSERT INTO `djs` (`id`, `name`, `genre`, `city`, `bio`, `avatar`, `socials`, `mixes`, `videos`, `created_at`) VALUES ('3', 'Dj Jimmy El panda', 'Urbano', 'panama', 'Dj del panda', '', '', '0', '0', '2025-09-27 22:22:42');
INSERT INTO `djs` (`id`, `name`, `genre`, `city`, `bio`, `avatar`, `socials`, `mixes`, `videos`, `created_at`) VALUES ('4', 'DJ HALLO507', 'URBANO', 'Panama', '', '', '', '0', '0', '2025-09-27 22:22:42');
INSERT INTO `djs` (`id`, `name`, `genre`, `city`, `bio`, `avatar`, `socials`, `mixes`, `videos`, `created_at`) VALUES ('5', 'dj tommy', 'urbano', 'panama', 'pabnaa', '', '', '0', '0', '2025-09-27 22:22:42');

-- --------------------------------------------------------
-- Table structure for table `events`
-- --------------------------------------------------------

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `date` varchar(50) NOT NULL,
  `time` varchar(20) NOT NULL,
  `place` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `events`
-- --------------------------------------------------------

INSERT INTO `events` (`id`, `title`, `date`, `time`, `place`, `status`, `created_at`) VALUES ('1', 'Romel fernandez', 'Sábado', '8:00 PM', 'Ciudad de Panamá', 'activo', '2025-09-24 20:42:04');
INSERT INTO `events` (`id`, `title`, `date`, `time`, `place`, `status`, `created_at`) VALUES ('2', 'Ocu festival el manito', 'domingo', '3:00 pm', 'panama', 'activo', '2025-09-24 20:42:04');

-- --------------------------------------------------------
-- Table structure for table `mixes`
-- --------------------------------------------------------

CREATE TABLE `mixes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `dj` varchar(100) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `plays` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `sizeMB` int(11) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `cover` text DEFAULT NULL,
  `tracks` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `mixes`
-- --------------------------------------------------------

INSERT INTO `mixes` (`id`, `title`, `dj`, `genre`, `plays`, `downloads`, `sizeMB`, `duration`, `url`, `cover`, `tracks`, `date`, `created_at`) VALUES ('1', 'SALSA RETRO vol 1', 'DJ Nel PTY', 'Salsa', '1240', '320', '142', '58:21', 'https://f005.backblazeb2.com/file/mixes-mp3/Salsa_Retro-By-Dj_Nelpty.mp3', 'img/Salsa_Retro-By-Dj_Nelpty.jpg', 'Tema 1,Tema 2,Tema 3', '2023-10-15', '2025-09-24 21:05:27');
INSERT INTO `mixes` (`id`, `title`, `dj`, `genre`, `plays`, `downloads`, `sizeMB`, `duration`, `url`, `cover`, `tracks`, `date`, `created_at`) VALUES ('2', 'ACTIVADERA TOTAL BY EL PANDA TRUCK', 'DJ HALLO507', 'Urbano', '0', '0', '0', '1:07', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+ACTIVADERA+TOTAL+BY+EL+PANDA+TRUCK.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/ACTIVADERA+TOTAL+PANDA+TRUCK.jpg', '', '2025-09-24', '2025-09-24 21:05:27');

-- --------------------------------------------------------
-- Table structure for table `videos`
-- --------------------------------------------------------

CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `dj` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `plays` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `duration` varchar(20) DEFAULT NULL,
  `sizeMB` int(11) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `cover` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `videos`
-- --------------------------------------------------------

