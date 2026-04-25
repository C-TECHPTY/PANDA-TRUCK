-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-03-2026 a las 22:38:10
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `panda_truck_v2`
--

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `sp_generate_weekly_report`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generate_weekly_report` ()   BEGIN
    DECLARE v_week_start DATE;
    DECLARE v_week_end DATE;
    
    SET v_week_start = DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY);
    SET v_week_end = DATE_ADD(v_week_start, INTERVAL 6 DAY);
    
    INSERT INTO weekly_reports (week_start, week_end, data)
    SELECT 
        v_week_start,
        v_week_end,
        JSON_OBJECT(
            'top_djs', (
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'name', name,
                        'downloads', total_downloads,
                        'plays', total_plays
                    )
                )
                FROM vw_weekly_top
            ),
            'total_mixes', (SELECT COUNT(*) FROM mixes WHERE active = 1),
            'total_downloads', (SELECT SUM(downloads) FROM statistics WHERE last_updated >= v_week_start),
            'total_plays', (SELECT SUM(plays) FROM statistics WHERE last_updated >= v_week_start)
        )
    ON DUPLICATE KEY UPDATE
        data = VALUES(data);
END$$

DROP PROCEDURE IF EXISTS `sp_update_statistics`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_statistics` (IN `p_item_id` INT, IN `p_item_type` VARCHAR(10), IN `p_action` VARCHAR(10))   BEGIN
    INSERT INTO statistics (item_id, item_type, plays, downloads, last_updated)
    VALUES (
        p_item_id,
        p_item_type,
        IF(p_action = 'play', 1, 0),
        IF(p_action = 'download', 1, 0),
        NOW()
    )
    ON DUPLICATE KEY UPDATE
        plays = plays + IF(p_action = 'play', 1, 0),
        downloads = downloads + IF(p_action = 'download', 1, 0),
        last_updated = NOW();
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'nelpty507', 'Inicio de sesión', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-22 21:22:12'),
(2, 1, 'nelpty507', 'Inicio de sesión', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-22 21:25:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banners`
--

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('sidebar','horizontal','sponsor') DEFAULT 'sponsor',
  `image` text NOT NULL,
  `url` text DEFAULT '#',
  `size` varchar(20) DEFAULT '150x60',
  `position` int(11) DEFAULT 1,
  `active` tinyint(4) DEFAULT 1,
  `clicks` int(11) DEFAULT 0,
  `impressions` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuration`
--

DROP TABLE IF EXISTS `configuration`;
CREATE TABLE `configuration` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `config_type` varchar(20) DEFAULT 'string',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuration`
--

INSERT INTO `configuration` (`id`, `config_key`, `config_value`, `config_type`, `updated_at`) VALUES
(1, 'radio_url', 'https://stream.zeno.fm/vjsa6jiwafavv', 'string', '2026-03-22 21:21:07'),
(2, 'radio_name', 'Panda Truck Radio', 'string', '2026-03-22 21:21:07'),
(3, 'site_title', 'Panda Truck Reloaded 2.0', 'string', '2026-03-22 21:21:07'),
(4, 'site_description', 'La casa de los DJs en Panamá', 'string', '2026-03-22 21:21:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `djs`
--

DROP TABLE IF EXISTS `djs`;
CREATE TABLE `djs` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `djs`
--

INSERT INTO `djs` (`id`, `name`, `genre`, `city`, `bio`, `avatar`, `socials`, `mixes`, `videos`, `featured_week`, `active`, `created_at`, `updated_at`) VALUES
(1, '@DJHALLO507', 'Urbano', 'Panamá', 'DJ HALLO, especialista en música urbana y creador de los mejores mixes.', 'https://yt3.ggpht.com/nu6MhAQ7vDs8binigBq2on8XwVgJUqWEcJR9Ldr36oHi4XQJPBQDo72-ySxaYWJxr5fok3q3=s176-c-k-c0x00ffffff-no-rj-mo', NULL, 8, 0, 0, 1, '2026-03-22 21:03:45', '2026-03-22 21:05:15'),
(2, 'DJ_NELPTY', 'Urbano', 'Panamá', 'DJ Nelpty, productor y DJ con un estilo único que combina salsa, vallenato y música urbana.', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/DJNEL.png', NULL, 3, 0, 0, 1, '2026-03-22 21:03:45', '2026-03-22 21:05:15'),
(3, 'DJ JIMMY', 'Variado', 'Panamá', 'DJ Jimmy, propietario y fundador de Panda Truck Reloaded.', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/DJ+JIMMY.png', NULL, 2, 0, 0, 1, '2026-03-22 21:03:45', '2026-03-22 21:05:15'),
(4, 'DJ_IRVIN_ALGARETE', 'Crossover', 'Panamá', 'Dj Irvin Algarete conocido como el GUSANO, DJ crossover, creativo, jocoso.', 'https://urbandjs507.com/wp-content/uploads/2025/09/WhatsApp-Image-2025-09-10-at-4.52.09-PM.jpeg', NULL, 4, 0, 0, 1, '2026-03-22 21:03:45', '2026-03-22 21:05:15'),
(5, '@DJMASTER507OFICIAL', 'Variado-mix', 'Panamá', 'DJ Master 507, creador de contenido musical.', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', NULL, 4, 0, 0, 1, '2026-03-22 21:03:45', '2026-03-22 21:05:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `place` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'activo',
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mixes`
--

DROP TABLE IF EXISTS `mixes`;
CREATE TABLE `mixes` (
  `id` int(11) NOT NULL,
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
  `is_superpack` tinyint(1) DEFAULT 0 COMMENT 'Forma parte de un Super Pack',
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mixes`
--

INSERT INTO `mixes` (`id`, `title`, `dj`, `genre`, `url`, `cover`, `duration`, `sizeMB`, `date`, `plays`, `downloads`, `tracks`, `is_superpack`, `active`, `created_at`, `updated_at`) VALUES
(6, '@DJHALLO507 - ACTIVADERA TOTAL BY EL PANDA TRUCK', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+ACTIVADERA+TOTAL+BY+EL+PANDA+TRUCK.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/ACTIVADERA+TOTAL+PANDA+TRUCK.jpg', '1:07', 97, '2025-10-16', 7, 8, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-22 00:21:29'),
(7, '@DJHALLO507 - CRIMEN MIX TAPE (PANDA TRUCK)', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+CRIMEN+MIX+TAPE+(PANDA+TRUCK).mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/CRIMEN+MIX+TAPE+(PANDA+TRUCK).jpg', '39:31', 57, '2025-10-16', 10, 6, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-21 23:27:21'),
(8, '@DJHALLO507 - MIX DEL DIA DEL PADRE 2024', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+MIX+DEL+DIA+DEL+PADRE+2024.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJHALLO507+-+MIX+DEL+DIA+DEL+PADRE+2024.png', '1:29', 129, '2025-10-16', 2, 4, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-21 23:27:21'),
(9, '@DJHALLO507 - SUMMER TIME MIX 25', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+SUMMER+TIME+MIX__25.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/SUMMER%2BTIME%2BMIX__25.jpg', '1:21', 121, '2025-10-16', 16, 12, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-21 23:27:21'),
(10, '@DJHALLO507 - THE WAR TIME 1', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+THE+WAR+TIME+1+(EL+KNGRI%2CFERCHO507%2C+MECANICA+TRIPLE+G)_063920.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/DJHALLO507+-+THE+WAR+TIME+1+(EL+KNGRI%2CFERCHO507%2C+MECANICA+TRIPLE+G.png', '1:21', 127, '2025-10-16', 29, 17, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-21 23:27:21'),
(11, '@DJHALLO507 - UNA VAINA PARA PARKIAR', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+UNA+VAINA+PARA+PARKIAR(EL+PANDA+TRUCK)_24.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/UNA%2BVAINA%2BPARA%2BPARKIAR(EL%2BPANDA%2BTRUCK)_24.jpg', '1:12', 0, '2025-10-16', 46, 48, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-21 23:27:21'),
(12, '@DJHALLO507 - UNA VAINA SWEET #3', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+UNA+VAINA+SWEET+%233+(PANDA+TRUCK)__25.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/Generated+Image+October+06%2C+2025+-+2_07PM.png', '1:14', 0, '2025-10-16', 62, 31, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-21 23:27:21'),
(43, ' VARIADO MIX #1', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+VARIADO+MIX+%231__BY+AIRES+DE+MI+TIERA__25.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJHALLO507+-+VARIADO+MIX+%231__BY+AIRES+DE+MI+TIERA__25.jpg', '01:32', 130, '2025-10-29', 88, 89, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-22 05:39:26'),
(57, 'Vallenato Del Alma VOL 1', 'DJ_NELPTY', 'Vallenato', 'https://f005.backblazeb2.com/file/mixes-mp3/Vallenato+Del+Alma+VOL+1.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/Vallenato+Del+Alma+VOL1.jpg', '26:06', 37, '2025-11-09', 26, 13, NULL, 0, 1, '2026-03-21 23:26:44', '2026-03-21 23:26:44'),
(59, 'MIX SALSA RETRO 2', 'DJ_NELPTY', 'Salsa', 'https://f005.backblazeb2.com/file/mixes-mp3/MIX+SALSA+RETRO+2.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/portada.png', '00:00', 0, '2025-11-09', 20, 13, NULL, 0, 1, '2026-03-21 23:26:44', '2026-03-21 23:26:44'),
(61, 'Mix Salsa Sensual VOL.5', 'DJ_NELPTY', 'Salsa', 'https://f005.backblazeb2.com/file/mixes-mp3/Mix+Salsa+Sensual+VOL.5.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/d5921885-cd23-4aaf-a92c-1723e7313631.png', '00:00', 0, '2025-11-09', 28, 15, NULL, 0, 1, '2026-03-21 23:26:44', '2026-03-21 23:26:44'),
(105, 'AIRES DE MI TIERRA', 'DJ JIMMY', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/AIRES+DE+MI+TIERRA.FT+DJ+JIMMY+.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/WhatsApp+Image+2025-11-15+at+9.49.06+AM.jpeg', '00:00', 0, '2025-11-16', 35, 16, NULL, 0, 1, '2026-03-21 23:26:44', '2026-03-22 05:49:51'),
(107, 'Panda Truck Urbano mix', 'DJ_IRVIN_ALGARETE', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/panda+truck+mix+el+gusano.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/Generated+Image+November+15%2C+2025+-+11_21PM.png', '00:00', 0, '2025-11-16', 30, 22, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-21 23:50:04'),
(123, 'Panda Truck Variado mix 1 el gusano', 'DJ_IRVIN_ALGARETE', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/panda+truck+variado+mix+1+el+gusano.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/irvin.jpeg', '00:00', 0, '2025-11-24', 7, 2, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-22 02:28:55'),
(124, 'Arranco diciembre panda truck ft el gusano', 'DJ_IRVIN_ALGARETE', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/arranco+diciembre+panda+truck+ft+el+gusano.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/irving-.jpeg', '00:00', 0, '2025-12-02', 3, 3, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-22 00:48:38'),
(125, 'Arranco Diciembre vol: 2', 'DJ_IRVIN_ALGARETE', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/arranco+diciembre+2+el+gusano.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/IMG-20251207-WA0018.jpg', '00:00', 0, '2025-12-07', 3, 0, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-22 06:04:14'),
(127, 'BASSLIFE & KEVIN INSTAL FT DJ JIMMY VOL1', 'DJ JIMMY', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/BASSLIFE+%26+KEVIN+INSTAL+FT+DJ+JIMMY+VOL1.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/BASSLIFE+%26+KEVIN+INSTAL+FT+DJ+JIMMY+VOL1.jpeg', '00:00', 0, '2026-02-25', 2, 1, NULL, 0, 1, '2026-03-21 23:26:44', '2026-03-21 23:48:46'),
(128, '01 - TIPICO MIX - @DJMASTER507OFICIAL', '@DJMASTER507OFICIAL', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/01+-+TIPICO+MIX+-+@DJMASTER507OFICIAL+-+PANDATRUCKRELOADED+X+NEWSOUNDSYSTEM+FULL.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '00:00', 0, '2026-03-20', 19, 8, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-22 03:57:34'),
(129, '02 - SALSA SENSUAL MIX ', '@DJMASTER507OFICIAL', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/02+-+SALSA+SENSUAL+MIX+-+@DJMASTER507OFICIAL+-+PANDATRUCKRELOADED+X+NEWSOUNDSYSTEM+FULL.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '00:00', 0, '2026-03-20', 3, 5, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-22 03:57:41'),
(130, '03 - ROOTS & CULTURE  23', '@DJMASTER507OFICIAL', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/03+-+ROOTS+%26+CULTURE+-+@DJMASTER507OFICIAL+X+PANDATRUCK+X+NEWSOUNDSYSTEM+FULL.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '00:00', 0, '2026-03-20', 3, 3, NULL, 1, 1, '2026-03-21 23:26:44', '2026-03-22 05:14:40'),
(132, 'Mix de Pruebassss', 'DJ Prueba', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/test.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/test.jpg', '1:30:00', 45, '2024-01-01', 0, 0, NULL, 1, 0, '2026-03-22 03:30:46', '2026-03-22 03:35:47'),
(133, 'reggae roots 2 mix', '@DJMASTER507OFICIAL', 'variado mix', 'https://f005.backblazeb2.com/file/mixes-mp3/03+-+ROOTS+%26+CULTURE+-+@DJMASTER507OFICIAL+X+PANDATRUCK+X+NEWSOUNDSYSTEM+FULL.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '', 0, '0000-00-00', 4, 0, NULL, 0, 1, '2026-03-22 04:00:18', '2026-03-22 05:10:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `player_config`
--

DROP TABLE IF EXISTS `player_config`;
CREATE TABLE `player_config` (
  `id` int(11) NOT NULL,
  `type` enum('mp4','youtube','twitch') NOT NULL DEFAULT 'mp4',
  `mp4Url` text DEFAULT NULL,
  `mp4Poster` text DEFAULT NULL,
  `mp4Title` varchar(255) DEFAULT NULL,
  `hero_video_url` text DEFAULT NULL,
  `hero_video_poster` text DEFAULT NULL,
  `hero_video_title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `player_config`
--

INSERT INTO `player_config` (`id`, `type`, `mp4Url`, `mp4Poster`, `mp4Title`, `hero_video_url`, `hero_video_poster`, `hero_video_title`, `created_at`, `updated_at`) VALUES
(1, 'mp4', NULL, NULL, NULL, 'https://panda-truck-video.b-cdn.net/AIRES%20DE%20MI%20TIERRA.mp4', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/video+portada2.png', 'AIRES DE MI TIERRA', '2026-03-22 21:27:48', '2026-03-22 21:27:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `playlists`
--
-- Error leyendo la estructura de la tabla panda_truck_v2.playlists: #1932 - Table 'panda_truck_v2.playlists' doesn't exist in engine
-- Error leyendo datos de la tabla panda_truck_v2.playlists: #1064 - Algo está equivocado en su sintax cerca 'FROM `panda_truck_v2`.`playlists`' en la linea 1

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radio_config`
--

DROP TABLE IF EXISTS `radio_config`;
CREATE TABLE `radio_config` (
  `id` int(11) NOT NULL,
  `radioUrl` text NOT NULL,
  `radioName` varchar(255) NOT NULL DEFAULT 'Panda Truck Radio',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `radio_config`
--

INSERT INTO `radio_config` (`id`, `radioUrl`, `radioName`, `created_at`, `updated_at`) VALUES
(1, 'https://stream.zeno.fm/vjsa6jiwafavv', 'Panda Truck Radio', '2026-03-22 21:21:07', '2026-03-22 21:21:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `statistics`
--

DROP TABLE IF EXISTS `statistics`;
CREATE TABLE `statistics` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('mix','video') NOT NULL,
  `plays` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `statistics`
--

INSERT INTO `statistics` (`id`, `item_id`, `item_type`, `plays`, `downloads`, `last_updated`) VALUES
(1, 6, 'mix', 7, 8, '2026-03-22 00:21:29'),
(2, 7, 'mix', 10, 6, '2026-03-21 23:27:05'),
(3, 8, 'mix', 2, 4, '2026-03-21 23:27:05'),
(4, 9, 'mix', 16, 12, '2026-03-21 23:27:05'),
(5, 10, 'mix', 29, 17, '2026-03-21 23:27:05'),
(6, 11, 'mix', 46, 48, '2026-03-21 23:27:05'),
(7, 12, 'mix', 62, 31, '2026-03-21 23:27:05'),
(8, 43, 'mix', 88, 89, '2026-03-21 23:27:05'),
(9, 57, 'mix', 26, 13, '2026-03-21 23:27:05'),
(10, 59, 'mix', 20, 13, '2026-03-21 23:27:05'),
(11, 61, 'mix', 28, 15, '2026-03-21 23:27:05'),
(12, 105, 'mix', 35, 16, '2026-03-22 00:22:32'),
(13, 107, 'mix', 30, 22, '2026-03-21 23:50:04'),
(14, 128, 'mix', 19, 7, '2026-03-22 02:29:14'),
(15, 129, 'mix', 3, 4, '2026-03-22 03:47:46'),
(28, 130, 'mix', 3, 3, '2026-03-22 05:14:40'),
(35, 127, 'mix', 2, 1, '2026-03-21 23:48:46'),
(39, 123, 'mix', 7, 2, '2026-03-22 02:28:55'),
(40, 124, 'mix', 3, 2, '2026-03-22 00:48:38'),
(44, 125, 'mix', 3, 0, '2026-03-22 06:04:14'),
(77, 133, 'mix', 4, 0, '2026-03-22 05:10:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sync_cache`
--
-- Error leyendo la estructura de la tabla panda_truck_v2.sync_cache: #1932 - Table 'panda_truck_v2.sync_cache' doesn't exist in engine
-- Error leyendo datos de la tabla panda_truck_v2.sync_cache: #1064 - Algo está equivocado en su sintax cerca 'FROM `panda_truck_v2`.`sync_cache`' en la linea 1

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','int','boolean','json') DEFAULT 'string',
  `group` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `group`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'Panda Truck Reloaded 2.0', 'string', 'general', 'Título del sitio web', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(2, 'site_description', 'La casa de los DJs en Panamá - Descarga los mejores mixes', 'string', 'general', 'Descripción del sitio', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(3, 'site_logo', 'assets/img/logo.png', 'string', 'general', 'URL del logo', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(4, 'footer_text', 'Panda Truck Reloaded - La casa de los DJs en Panamá', 'string', 'general', 'Texto del footer', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(5, 'maintenance_mode', '0', 'boolean', 'system', 'Modo mantenimiento', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(6, 'radio_url', 'https://stream.zeno.fm/vjsa6jiwafavv', 'string', 'radio', 'URL del stream de radio', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(7, 'radio_name', 'Panda Truck Radio', 'string', 'radio', 'Nombre de la radio', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(8, 'backblaze_bucket', 'https://f005.backblazeb2.com/file/mixes-mp3/', 'string', 'storage', 'URL del bucket Backblaze', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(9, 'backblaze_videos', 'https://panda-truck-video.b-cdn.net/', 'string', 'storage', 'URL de videos en CDN', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(10, 'items_per_page', '12', 'int', 'display', 'Items por página', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(11, 'featured_djs_count', '5', 'int', 'display', 'Número de DJs destacados', '2026-03-22 21:23:51', '2026-03-22 21:23:51'),
(12, 'superpack_threshold', '4', 'int', 'display', 'Mínimo de mixes para Super Pack', '2026-03-22 21:23:51', '2026-03-22 21:23:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('superadmin','admin','dj','viewer') NOT NULL DEFAULT 'viewer',
  `dj_id` int(11) DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `dj_id`, `avatar`, `last_login`, `last_ip`, `created_at`, `updated_at`, `active`) VALUES
(1, 'nelpty507', '$2y$10$/DcNs4xgX80pDTUQY24lme26LPRH8qmtTZ1GcsUbK.qroz/Vl/Onm', 'nelpty@pandatruck.com', 'superadmin', NULL, NULL, '2026-03-22 16:25:15', '::1', '2026-03-22 21:04:21', '2026-03-22 21:25:15', 1),
(2, 'djimmypanda', '$2y$10$SiJyvF2L0jzFxXu4Oxy3IOSZ0Slhc85mDLL5Auz.myy7axuml2iZ2', 'djimmy@pandatruck.com', 'admin', NULL, NULL, NULL, NULL, '2026-03-22 21:04:21', '2026-03-22 21:04:21', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_activity`
--
-- Error leyendo la estructura de la tabla panda_truck_v2.user_activity: #1932 - Table 'panda_truck_v2.user_activity' doesn't exist in engine
-- Error leyendo datos de la tabla panda_truck_v2.user_activity: #1064 - Algo está equivocado en su sintax cerca 'FROM `panda_truck_v2`.`user_activity`' en la linea 1

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

DROP TABLE IF EXISTS `videos`;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `dj` varchar(100) NOT NULL,
  `type` enum('mp4','youtube') NOT NULL DEFAULT 'mp4',
  `url` text NOT NULL,
  `cover` text DEFAULT NULL,
  `duration` varchar(10) DEFAULT NULL,
  `plays` int(11) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `videos`
--

INSERT INTO `videos` (`id`, `title`, `dj`, `type`, `url`, `cover`, `duration`, `plays`, `active`, `created_at`) VALUES
(1, 'AIRES DE MI TIERRA', 'DJ JIMMY', 'mp4', 'https://panda-truck-video.b-cdn.net/AIRES%20DE%20MI%20TIERRA.mp4', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/video+portada2.png', '4:30', 0, 1, '2026-03-22 21:09:23');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_dj_stats`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vw_dj_stats`;
CREATE TABLE `vw_dj_stats` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_super_packs`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vw_super_packs`;
CREATE TABLE `vw_super_packs` (
`dj` varchar(100)
,`mix_count` bigint(21)
,`last_mix_date` date
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `weekly_reports`
--
-- Error leyendo la estructura de la tabla panda_truck_v2.weekly_reports: #1932 - Table 'panda_truck_v2.weekly_reports' doesn't exist in engine
-- Error leyendo datos de la tabla panda_truck_v2.weekly_reports: #1064 - Algo está equivocado en su sintax cerca 'FROM `panda_truck_v2`.`weekly_reports`' en la linea 1

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_dj_stats`
--
DROP TABLE IF EXISTS `vw_dj_stats`;

DROP VIEW IF EXISTS `vw_dj_stats`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_dj_stats`  AS SELECT `d`.`id` AS `id`, `d`.`name` AS `name`, `d`.`genre` AS `genre`, `d`.`city` AS `city`, `d`.`avatar` AS `avatar`, `d`.`socials` AS `socials`, `d`.`featured_week` AS `featured_week`, coalesce(`m`.`total_mixes`,0) AS `total_mixes`, coalesce(`m`.`total_plays`,0) AS `total_plays`, coalesce(`m`.`total_downloads`,0) AS `total_downloads`, coalesce(`v`.`total_videos`,0) AS `total_videos` FROM ((`pandatruck_v2`.`djs` `d` left join (select `pandatruck_v2`.`mixes`.`dj` AS `dj`,count(0) AS `total_mixes`,sum(`pandatruck_v2`.`mixes`.`plays`) AS `total_plays`,sum(`pandatruck_v2`.`mixes`.`downloads`) AS `total_downloads` from `pandatruck_v2`.`mixes` where `pandatruck_v2`.`mixes`.`active` = 1 group by `pandatruck_v2`.`mixes`.`dj`) `m` on(`d`.`name` = `m`.`dj`)) left join (select `pandatruck_v2`.`videos`.`dj` AS `dj`,count(0) AS `total_videos` from `pandatruck_v2`.`videos` where `pandatruck_v2`.`videos`.`active` = 1 group by `pandatruck_v2`.`videos`.`dj`) `v` on(`d`.`name` = `v`.`dj`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_super_packs`
--
DROP TABLE IF EXISTS `vw_super_packs`;

DROP VIEW IF EXISTS `vw_super_packs`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_super_packs`  AS SELECT `mixes`.`dj` AS `dj`, count(0) AS `mix_count`, max(`mixes`.`date`) AS `last_mix_date` FROM `mixes` WHERE `mixes`.`active` = 1 GROUP BY `mixes`.`dj` HAVING count(0) >= 4 ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuration`
--
ALTER TABLE `configuration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indices de la tabla `djs`
--
ALTER TABLE `djs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mixes`
--
ALTER TABLE `mixes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dj` (`dj`),
  ADD KEY `idx_genre` (`genre`),
  ADD KEY `idx_active` (`active`),
  ADD KEY `idx_superpack` (`is_superpack`);

--
-- Indices de la tabla `player_config`
--
ALTER TABLE `player_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `radio_config`
--
ALTER TABLE `radio_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `statistics`
--
ALTER TABLE `statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item` (`item_id`,`item_type`);

--
-- Indices de la tabla `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_group` (`group`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `dj_id` (`dj_id`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuration`
--
ALTER TABLE `configuration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `djs`
--
ALTER TABLE `djs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mixes`
--
ALTER TABLE `mixes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT de la tabla `player_config`
--
ALTER TABLE `player_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `radio_config`
--
ALTER TABLE `radio_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `statistics`
--
ALTER TABLE `statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de la tabla `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`dj_id`) REFERENCES `djs` (`id`) ON DELETE SET NULL;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
