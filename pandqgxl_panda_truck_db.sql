-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 25-04-2026 a las 15:26:40
-- Versión del servidor: 11.4.10-MariaDB-cll-lve-log
-- Versión de PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pandqgxl_panda_truck_db`
--

DELIMITER $$
--
-- Procedimientos
--
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
                FROM vw_dj_stats
                WHERE total_downloads > 0
                LIMIT 10
            ),
            'total_mixes', (SELECT COUNT(*) FROM mixes WHERE active = 1),
            'total_downloads', (SELECT COALESCE(SUM(downloads), 0) FROM statistics WHERE last_updated >= v_week_start),
            'total_plays', (SELECT COALESCE(SUM(plays), 0) FROM statistics WHERE last_updated >= v_week_start)
        )
    ON DUPLICATE KEY UPDATE
        data = VALUES(data);
END$$

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
(1, 1, 'nelpty507', 'Instalación inicial', NULL, '127.0.0.1', NULL, '2026-03-23 21:12:28'),
(2, 1, 'nelpty507', 'Inicio de sesión', NULL, '::1', 'Mozilla/5.0', '2026-03-23 21:12:28'),
(3, 1, 'nelpty507', 'Inicio de sesión', NULL, '200.46.217.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 21:17:22'),
(4, 1, 'nelpty507', 'Inicio de sesión', NULL, '200.46.217.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 21:38:05'),
(5, 1, 'nelpty507', 'Inicio de sesión', NULL, '200.46.1.59', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-23 21:49:57'),
(6, 1, 'nelpty507', 'Inicio de sesión', NULL, '200.46.1.59', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-23 23:57:08'),
(7, 1, 'nelpty507', 'Inicio de sesión', NULL, '200.46.217.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 15:56:36'),
(8, 1, 'nelpty507', 'Inicio de sesión', NULL, '200.46.217.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 15:58:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albumes`
--

CREATE TABLE `albumes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist` varchar(100) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `cover` text DEFAULT NULL,
  `zip_url` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `albumes`
--

INSERT INTO `albumes` (`id`, `title`, `artist`, `genre`, `year`, `cover`, `zip_url`, `description`, `download_count`, `active`, `created_at`, `updated_at`) VALUES
(3, 'Da\' Crew', 'varios', 'reggae español', '2001', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/CD-ALBUM/PORTADAS/00-va-da_crew-sp-2000-shp.JPG', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/CD-ALBUM/Da_Crew-1-SP-2000.zip', '', 0, 1, '2026-03-27 22:51:38', '2026-03-27 22:51:38'),
(4, 'Da\' Crew 2 - It\'s Back', 'varios', 'reggae español', '1999', 'https://i.discogs.com/LHASVTOFzcO1lvYZbx2z0TV-bOwpZCuyoQzNFoLJGks/rs:fit/g:sm/q:90/h:600/w:598/czM6Ly9kaXNjb2dz/LWRhdGFiYXNlLWlt/YWdlcy9SLTU4ODAy/MzEtMTY1NDI5OTI4/MS05NDg0LmpwZWc.jpeg', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/CD-ALBUM/Da\'+Crew+2-It\'s+Back-1999.zip', '1	Unknown Artist–	Intro\r\nSeccion Retro Crew	\r\n2	Comando Copetín–	5 Pelitos\r\n3	Kafu Banton–	Batiman Batman\r\n4	Danger*–	Metralleta\r\n5	Toby King (2)–	Andan En Banda\r\n6	Rene Renegado–	Retro Mix\r\n7	Virgilio & Gustavo*–	Chica Especial\r\n8	Toby King (2)–	Chomba Loca\r\nSeccion Soca Crew	\r\n9	Papa Chan–	Gorrero\r\n10	Tomy Real*–	Corazon De Nadie\r\n11	Danger*–	Meneate Chomba\r\n12	Latin Fresh–	Muévelo\r\n13	Virgilio Y Gustavo–	Mala Paga\r\nSeccion Romantic Crew	\r\n14	El Roockie–	El Peluche\r\n15	Niga*–	Como Puedo\r\n16	Tomy Real*–	Fantasia Sex\r\n17	Macano*–	Deja De Fingir\r\n18	Mr. Sam (3)–	Quiero Amanecer\r\n19	Katherine*–	Tú\r\n20	Ghetto y Bantan*–	Escucha\r\nSeccion Rough Crew	\r\n21	Papa Chan–	La Tienda\r\n22	Kafu Banton–	Sistema\r\n23	El Roockie–	Duro En La Boca\r\n24	Danger*–	Guerra\r\n25	Rene Renegado–	Maripunk\r\n26	Tomy Real*–	Carcelero\r\n27	Toby King (2)–	Quiero Una Mujer\r\n28	Comandante*–	Maten Al Eneny', 0, 1, '2026-03-27 23:08:26', '2026-03-27 23:08:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banners`
--

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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canciones`
--

CREATE TABLE `canciones` (
  `id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  `track_number` int(11) DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `duration` varchar(10) DEFAULT NULL,
  `url` text NOT NULL,
  `sizeMB` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuration`
--

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
(1, 'radio_url', 'https://stream.zeno.fm/vjsa6jiwafavv', 'string', '2026-03-23 21:12:28'),
(2, 'radio_name', 'Panda Truck Radio', 'string', '2026-03-23 21:12:28'),
(3, 'site_title', 'Panda Truck Reloaded 2.0', 'string', '2026-03-23 21:12:28'),
(4, 'site_description', 'La casa de los DJs en Panamá - Descarga los mejores mixes', 'string', '2026-03-24 16:08:35'),
(7, 'footer_text', 'Panda Truck Reloaded - La casa de los DJs en Panamá', 'string', '2026-03-24 16:08:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `djs`
--

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
(1, '@DJHALLO507', 'Urbano', 'Panamá', 'DJ HALLO, especialista en música urbana y creador de los mejores mixes.', 'https://yt3.ggpht.com/nu6MhAQ7vDs8binigBq2on8XwVgJUqWEcJR9Ldr36oHi4XQJPBQDo72-ySxaYWJxr5fok3q3=s176-c-k-c0x00ffffff-no-rj-mo', NULL, 8, 0, 0, 1, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(2, 'DJ_NELPTY', 'Urbano', 'Panamá', 'DJ Nelpty, productor y DJ con un estilo único que combina salsa, vallenato y música urbana.', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ+NELPTY.jpg', '', 3, 0, 0, 1, '2026-03-23 21:12:28', '2026-04-05 04:49:01'),
(3, 'DJ JIMMY', 'Variado', 'Panamá', 'DJ Jimmy, propietario y fundador de Panda Truck Reloaded.', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ_JIMMY.jpg', '', 2, 1, 0, 1, '2026-03-23 21:12:28', '2026-04-05 04:47:30'),
(4, 'DJ_IRVIN_ALGARETE', 'Crossover', 'Panamá', 'Dj Irvin Algarete conocido como el GUSANO, DJ crossover, creativo, jocoso.', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/DJ+IRVING.jpg', '', 4, 0, 0, 1, '2026-03-23 21:12:28', '2026-04-05 04:51:44'),
(5, '@DJMASTER507OFICIAL', 'Variado-mix', 'Panamá', 'DJ Master 507, creador de contenido musical.', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '', 4, 0, 0, 1, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(6, 'Dj-Joc-Pty', 'VARIADOS', 'Panama', '', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/Dj-Joc-Pty.jpg', '', 0, 0, 0, 1, '2026-04-05 04:12:34', '2026-04-05 04:48:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `events`
--

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
  `is_superpack` tinyint(1) DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mixes`
--

INSERT INTO `mixes` (`id`, `title`, `dj`, `genre`, `url`, `cover`, `duration`, `sizeMB`, `date`, `plays`, `downloads`, `tracks`, `is_superpack`, `active`, `created_at`, `updated_at`) VALUES
(6, '@DJHALLO507 - ACTIVADERA TOTAL BY EL PANDA TRUCK', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+ACTIVADERA+TOTAL+BY+EL+PANDA+TRUCK.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/ACTIVADERA+TOTAL+PANDA+TRUCK.jpg', '1:07', 97, '2025-10-16', 18, 37, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 00:22:35'),
(7, '@DJHALLO507 - CRIMEN MIX TAPE (PANDA TRUCK)', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+CRIMEN+MIX+TAPE+(PANDA+TRUCK).mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/CRIMEN+MIX+TAPE+(PANDA+TRUCK).jpg', '39:31', 57, '2025-10-16', 15, 30, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-22 19:32:59'),
(8, '@DJHALLO507 - MIX DEL DIA DEL PADRE 2024', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+MIX+DEL+DIA+DEL+PADRE+2024.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJHALLO507+-+MIX+DEL+DIA+DEL+PADRE+2024.png', '1:29', 129, '2025-10-16', 9, 31, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 19:16:26'),
(9, '@DJHALLO507 - SUMMER TIME MIX 25', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+SUMMER+TIME+MIX__25.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/SUMMER%2BTIME%2BMIX__25.jpg', '1:21', 121, '2025-10-16', 23, 38, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-24 20:30:49'),
(10, '@DJHALLO507 - THE WAR TIME 1', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+THE+WAR+TIME+1+(EL+KNGRI%2CFERCHO507%2C+MECANICA+TRIPLE+G)_063920.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/DJHALLO507+-+THE+WAR+TIME+1+(EL+KNGRI%2CFERCHO507%2C+MECANICA+TRIPLE+G.png', '1:21', 127, '2025-10-16', 33, 44, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-22 07:21:02'),
(11, '@DJHALLO507 - UNA VAINA PARA PARKIAR', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+UNA+VAINA+PARA+PARKIAR(EL+PANDA+TRUCK)_24.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/UNA%2BVAINA%2BPARA%2BPARKIAR(EL%2BPANDA%2BTRUCK)_24.jpg', '1:12', 0, '2025-10-16', 48, 74, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 00:14:17'),
(12, '@DJHALLO507 - UNA VAINA SWEET #3', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+UNA+VAINA+SWEET+%233+(PANDA+TRUCK)__25.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/Generated+Image+October+06%2C+2025+-+2_07PM.png', '1:14', 0, '2025-10-16', 64, 57, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-23 02:48:48'),
(43, 'VARIADO MIX #1', '@DJHALLO507', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/@DJHALLO507+-+VARIADO+MIX+%231__BY+AIRES+DE+MI+TIERA__25.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJHALLO507+-+VARIADO+MIX+%231__BY+AIRES+DE+MI+TIERA__25.jpg', '01:32', 130, '2025-10-29', 101, 115, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-24 20:48:54'),
(57, 'Vallenato Del Alma VOL 1', 'DJ_NELPTY', 'Vallenato', 'https://f005.backblazeb2.com/file/mixes-mp3/Vallenato+Del+Alma+VOL+1.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/Vallenato+Del+Alma+VOL1.jpg', '26:06', 37, '2025-11-09', 31, 39, NULL, 0, 1, '2026-03-23 21:12:28', '2026-04-25 18:15:08'),
(59, 'MIX SALSA RETRO 2', 'DJ_NELPTY', 'Salsa', 'https://f005.backblazeb2.com/file/mixes-mp3/MIX+SALSA+RETRO+2.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/portada.png', '00:00', 0, '2025-11-09', 24, 39, NULL, 0, 1, '2026-03-23 21:12:28', '2026-04-24 20:41:26'),
(61, 'Mix Salsa Sensual VOL.5', 'DJ_NELPTY', 'Salsa', 'https://f005.backblazeb2.com/file/mixes-mp3/Mix+Salsa+Sensual+VOL.5.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/d5921885-cd23-4aaf-a92c-1723e7313631.png', '00:00', 0, '2025-11-09', 30, 44, NULL, 0, 1, '2026-03-23 21:12:28', '2026-04-23 20:45:17'),
(105, 'AIRES DE MI TIERRA', 'DJ JIMMY', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/AIRES+DE+MI+TIERRA.FT+DJ+JIMMY+.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/WhatsApp+Image+2025-11-15+at+9.49.06+AM.jpeg', '00:00', 0, '2025-11-16', 44, 41, NULL, 0, 1, '2026-03-23 21:12:28', '2026-04-22 10:40:28'),
(107, 'Panda Truck Urbano mix', 'DJ_IRVIN_ALGARETE', 'Urbano', 'https://f005.backblazeb2.com/file/mixes-mp3/panda+truck+mix+el+gusano.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/Generated+Image+November+15%2C+2025+-+11_21PM.png', '00:00', 0, '2025-11-16', 35, 54, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-23 08:42:10'),
(123, 'Panda Truck Variado mix 1 el gusano', 'DJ_IRVIN_ALGARETE', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/panda+truck+variado+mix+1+el+gusano.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/irvin.jpeg', '00:00', 0, '2025-11-24', 29, 36, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 18:34:23'),
(124, 'Arranco diciembre panda truck ft el gusano', 'DJ_IRVIN_ALGARETE', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/arranco+diciembre+panda+truck+ft+el+gusano.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/irving-.jpeg', '00:00', 0, '2025-12-02', 11, 32, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 18:32:31'),
(125, 'Arranco Diciembre vol: 2', 'DJ_IRVIN_ALGARETE', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/arranco+diciembre+2+el+gusano.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/IMG-20251207-WA0018.jpg', '00:00', 0, '2025-12-07', 14, 41, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 18:32:27'),
(127, 'BASSLIFE & KEVIN INSTAL FT DJ JIMMY VOL1', 'DJ JIMMY', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/BASSLIFE+%26+KEVIN+INSTAL+FT+DJ+JIMMY+VOL1.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/BASSLIFE+%26+KEVIN+INSTAL+FT+DJ+JIMMY+VOL1.jpeg', '00:00', 0, '2026-02-25', 57, 132, NULL, 0, 1, '2026-03-23 21:12:28', '2026-04-25 18:15:19'),
(128, '01 - TIPICO MIX - @DJMASTER507OFICIAL', '@DJMASTER507OFICIAL', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/01+-+TIPICO+MIX+-+@DJMASTER507OFICIAL+-+PANDATRUCKRELOADED+X+NEWSOUNDSYSTEM+FULL.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '00:00', 0, '2026-03-20', 58, 82, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 19:13:55'),
(129, '02 - SALSA SENSUAL MIX ', '@DJMASTER507OFICIAL', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/02+-+SALSA+SENSUAL+MIX+-+@DJMASTER507OFICIAL+-+PANDATRUCKRELOADED+X+NEWSOUNDSYSTEM+FULL.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '00:00', 0, '2026-03-20', 25, 58, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 17:42:47'),
(130, 'ROOTS & CULTURE ', '@DJMASTER507OFICIAL', 'Variado-mix', 'https://f005.backblazeb2.com/file/mixes-mp3/03+-+ROOTS+%26+CULTURE+-+@DJMASTER507OFICIAL+X+PANDATRUCK+X+NEWSOUNDSYSTEM+FULL.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '00:00', 0, '2026-03-20', 66, 66, NULL, 1, 1, '2026-03-23 21:12:28', '2026-04-25 18:13:05'),
(133, 'reggae roots 2 mix', '@DJMASTER507OFICIAL', 'variado mix', 'https://f005.backblazeb2.com/file/mixes-mp3/03+-+ROOTS+%26+CULTURE+-+@DJMASTER507OFICIAL+X+PANDATRUCK+X+NEWSOUNDSYSTEM+FULL.mp3', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/@DJMASTER507OFICIAL.jpeg', '', 0, '2026-03-22', 5, 1, NULL, 0, 0, '2026-03-23 21:12:28', '2026-03-23 21:38:20'),
(134, ' Paso A Paso Vol 2 ( Pindin )', 'Dj-Joc-Pty', 'Variado', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/Dj+Joc+Pty+-+Paso+A+Paso+Vol+2+(+Pindin+)+El+Panda+truck+Reloaded.mp3', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/IMG-20260403-WA0000.jpg', '', 0, '0000-00-00', 52, 629, NULL, 1, 1, '2026-04-03 17:53:49', '2026-04-25 07:32:39'),
(135, 'Tipicos-paso-a-paso-Mix-Panda-Truck-Reloaded', 'Dj-Joc-Pty', 'TIPICO', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/Dj-Joc-Pty-Tipicos-paso-a-paso-Mix-Panda-Truck-Reloaded.mp3', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/WhatsApp+Image+2026-04-04+at+3.54.29+PM.jpeg', '', 0, '0000-00-00', 28, 280, NULL, 1, 1, '2026-04-05 04:14:37', '2026-04-25 07:32:43'),
(136, 'Vallenatos-Mix-Panda-Truck-Reloaded', 'Dj-Joc-Pty', 'VALLENATO', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/Dj-Joc-Pty-Vallenatos-Mix-Panda-Truck-Reloaded.mp3', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/WhatsApp+Image+2026-04-04+at+3.54.29+PM.jpeg', '', 0, '0000-00-00', 12, 59, NULL, 1, 1, '2026-04-05 04:15:43', '2026-04-25 07:32:46'),
(137, 'Sound-Check-Panda-Truck-Reloaded', 'Dj-Joc-Pty', 'VARIADO', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/Dj-Joc-Pty-Sound-Check-Panda-Truck-Reloaded.mp3', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/WhatsApp+Image+2026-04-04+at+3.54.29+PM.jpeg', '', 0, '0000-00-00', 20, 61, NULL, 1, 1, '2026-04-05 04:16:38', '2026-04-25 07:32:51'),
(138, 'Salsa-Mix-Panda-Truck-Reloaded', 'Dj-Joc-Pty', 'SALSA', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/Dj-Joc-Pty-Salsa-Mix-Panda-Truck-Reloaded.mp3', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/WhatsApp+Image+2026-04-04+at+3.54.29+PM.jpeg', '', 0, '0000-00-00', 7, 63, NULL, 1, 1, '2026-04-05 04:17:32', '2026-04-25 07:32:54'),
(139, 'Bachata-Mix-Panda-Truck-Reloaded', 'Dj-Joc-Pty', 'BACHATA', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/Dj-Joc-Pty-Bachata-Mix-Panda-Truck-Reloaded.mp3', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/WhatsApp+Image+2026-04-04+at+3.54.29+PM.jpeg', '', 0, '0000-00-00', 10, 60, NULL, 1, 1, '2026-04-05 04:18:19', '2026-04-25 17:20:36'),
(140, ' PUCHO FT DEADPOOL PARKING BY DJ JIMMY', 'DJ JIMMY', 'VARIADO', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PUCHO_FT_DEADPOOL_PARKING.mp3', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/PUCHO-FT-DEADPOOL-PARKING.jpeg', '', 0, '0000-00-00', 83, 211, NULL, 0, 1, '2026-04-15 02:45:24', '2026-04-25 18:12:40'),
(141, 'PUCHO ANDS FRIENDS VARIADO', 'DJ JIMMY', 'variado', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PUCHO_ANDS_FRIENDS_VARIADO.mp3', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/PORTADA/PUCHO+ANDS+FRIENDS+VARIADO.jpg', '', 0, '0000-00-00', 53, 65, NULL, 0, 1, '2026-04-25 16:53:56', '2026-04-25 19:24:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `player_config`
--

CREATE TABLE `player_config` (
  `id` int(11) NOT NULL,
  `hero_type` enum('mp4','youtube','twitch') NOT NULL DEFAULT 'mp4',
  `hero_video_url` text DEFAULT NULL,
  `hero_video_poster` text DEFAULT NULL,
  `hero_video_title` varchar(255) DEFAULT NULL,
  `youtube_id` varchar(50) DEFAULT NULL,
  `twitch_channel` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bunny_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `player_config`
--

INSERT INTO `player_config` (`id`, `hero_type`, `hero_video_url`, `hero_video_poster`, `hero_video_title`, `youtube_id`, `twitch_channel`, `created_at`, `updated_at`, `bunny_id`) VALUES
(1, 'youtube', 'https://www.youtube.com/watch?v=LgyVpMcCSpM', '', 'Sensual by Dj Ipsen Vol 2 0', 'LgyVpMcCSpM', '', '2026-03-23 21:12:28', '2026-04-21 02:20:55', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `playlists`
--

CREATE TABLE `playlists` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radio_config`
--

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
(1, 'https://stream.zeno.fm/vjsa6jiwafavv', 'Panda Truck Radio', '2026-03-23 21:12:28', '2026-03-23 21:12:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `statistics`
--

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
(1, 6, 'mix', 18, 37, '2026-04-25 00:22:35'),
(2, 7, 'mix', 15, 30, '2026-04-22 19:32:59'),
(3, 8, 'mix', 9, 31, '2026-04-25 19:16:26'),
(4, 9, 'mix', 23, 38, '2026-04-24 20:30:49'),
(5, 10, 'mix', 33, 44, '2026-04-22 07:21:02'),
(6, 11, 'mix', 48, 74, '2026-04-25 00:14:17'),
(7, 12, 'mix', 64, 57, '2026-04-23 02:48:48'),
(8, 43, 'mix', 101, 115, '2026-04-24 20:48:54'),
(9, 57, 'mix', 31, 39, '2026-04-25 18:15:08'),
(10, 59, 'mix', 24, 39, '2026-04-24 20:41:26'),
(11, 61, 'mix', 30, 44, '2026-04-23 20:45:17'),
(12, 105, 'mix', 44, 41, '2026-04-22 10:40:28'),
(13, 107, 'mix', 35, 54, '2026-04-23 08:42:10'),
(14, 128, 'mix', 58, 81, '2026-04-25 19:13:55'),
(15, 129, 'mix', 25, 57, '2026-04-25 17:42:47'),
(28, 130, 'mix', 66, 66, '2026-04-25 18:13:05'),
(35, 127, 'mix', 57, 132, '2026-04-25 18:15:19'),
(39, 123, 'mix', 29, 36, '2026-04-25 18:34:23'),
(40, 124, 'mix', 11, 31, '2026-04-25 18:32:31'),
(44, 125, 'mix', 14, 40, '2026-04-25 18:32:27'),
(77, 133, 'mix', 5, 1, '2026-03-23 21:12:28'),
(515, 134, 'mix', 52, 610, '2026-04-25 07:32:39'),
(829, 135, 'mix', 28, 260, '2026-04-25 07:32:43'),
(831, 139, 'mix', 10, 34, '2026-04-25 17:20:36'),
(838, 136, 'mix', 12, 34, '2026-04-25 07:32:46'),
(839, 137, 'mix', 20, 36, '2026-04-25 07:32:51'),
(840, 138, 'mix', 7, 37, '2026-04-25 07:32:54'),
(1906, 140, 'mix', 83, 211, '2026-04-25 18:12:40'),
(2541, 141, 'mix', 53, 65, '2026-04-25 19:24:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sync_cache`
--

CREATE TABLE `sync_cache` (
  `id` int(11) NOT NULL,
  `cache_key` varchar(255) NOT NULL,
  `cache_value` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_settings`
--

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
(1, 'site_title', 'Panda Truck Reloaded 2.0', 'string', 'general', NULL, '2026-03-23 21:12:28', '2026-03-25 02:48:25'),
(2, 'site_description', 'La casa de los DJs en Panamá - Descarga los mejores mixes', 'string', 'general', NULL, '2026-03-23 21:12:28', '2026-03-25 02:48:25'),
(3, 'site_logo', 'assets/img/logo.png', 'string', 'general', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(4, 'footer_text', 'Panda Truck Reloaded - La casa de los DJs en Panamá', 'string', 'general', NULL, '2026-03-23 21:12:28', '2026-03-25 02:48:25'),
(5, 'maintenance_mode', '0', 'string', 'system', NULL, '2026-03-23 21:12:28', '2026-03-30 19:24:48'),
(6, 'radio_url', 'https://stream.zeno.fm/vjsa6jiwafavv', 'string', 'radio', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(7, 'radio_name', 'Panda Truck Radio', 'string', 'radio', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(8, 'backblaze_bucket', 'https://f005.backblazeb2.com/file/mixes-mp3/', 'string', 'storage', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(9, 'backblaze_videos', 'https://panda-truck-video.b-cdn.net/', 'string', 'storage', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(10, 'items_per_page', '12', 'string', 'display', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(11, 'featured_djs_count', '5', 'string', 'display', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(12, 'superpack_threshold', '4', 'string', 'display', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(13, 'guia_title', 'Guía para DJs - Panda Truck', 'string', 'guia', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28'),
(14, 'guia_whatsapp', '50762115209', 'string', 'guia', NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

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
(1, 'nelpty507', '$2y$10$/DcNs4xgX80pDTUQY24lme26LPRH8qmtTZ1GcsUbK.qroz/Vl/Onm', 'nelpty@pandatruck.com', 'superadmin', NULL, NULL, '2026-03-24 11:58:20', '200.46.217.234', '2026-03-23 21:12:28', '2026-03-24 15:58:20', 1),
(2, 'djimmypanda', '$2y$10$SiJyvF2L0jzFxXu4Oxy3IOSZ0Slhc85mDLL5Auz.myy7axuml2iZ2', 'djimmy@pandatruck.com', 'admin', NULL, NULL, NULL, NULL, '2026-03-23 21:12:28', '2026-03-23 21:12:28', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_activity`
--

CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

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
(1, 'FALA FALA (VIDEO OFICIAL)', 'Andiex', 'youtube', 'https://www.youtube.com/watch?v=UESi-xM0SG0&list=RDUESi-xM0SG0&start_radio=1', 'https://i.ytimg.com/vi/jjIGlQ_iKUo/maxresdefault.jpg', '4:30', 0, 1, '2026-03-23 21:12:28'),
(2, 'AIRES DE MI TIERRA', 'DJ JIMMY  FET DJ HALO', 'mp4', 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/VIDEO/AIRES%20DE%20MI%20TIERRA.mp4', 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/WhatsApp+Image+2025-11-15+at+9.49.06+AM.jpeg', '', 9, 0, '2026-03-25 21:00:44'),
(3, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:08'),
(4, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 1, '2026-03-27 23:14:11'),
(5, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:13'),
(6, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:13'),
(7, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(8, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(9, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(10, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(11, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(12, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(13, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(14, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(15, 'Latin Fresh - Nuevo (Video Oficial)', 'Latin Fresh', 'youtube', 'https://www.youtube.com/watch?v=QmOJ9H9p4_4&list=RDQmOJ9H9p4_4&start_radio=1', 'https://i.ytimg.com/vi/QmOJ9H9p4_4/hqdefault.jpg', '', 0, 0, '2026-03-27 23:14:23'),
(16, 'LA ROJA VA A BRILLAR (VIDEO OFICIAL)', 'MR. SAIK X ISACU', 'youtube', 'https://www.youtube.com/watch?v=EcABH9j566k&list=RDEcABH9j566k&start_radio=1', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSGOSJMRyhwFOy6woTwPNGliuk7Vdtmie0ZXA&s', '', 0, 1, '2026-03-27 23:21:11'),
(17, 'Sube La Marea Remix', 'varios artistas panameños', 'youtube', 'https://www.youtube.com/watch?v=55OqvQjG4eI&list=RD55OqvQjG4eI&start_radio=1', 'https://i.ytimg.com/vi/55OqvQjG4eI/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLA2tkxXmzcLxWTTIwReL4No4arbDw', '', 0, 1, '2026-03-27 23:23:36'),
(18, 'El juguete de tu amor', 'ALEJANDRO TORRES', 'youtube', 'https://www.youtube.com/watch?v=-ojEu-B8RcM', 'https://i.ytimg.com/vi/677fJB8nNKI/maxresdefault.jpg', '', 0, 1, '2026-03-27 23:28:14'),
(19, 'TEN FE, NO PRISA (Official Video)', 'DJ Chiqui Dubs, Sami Boy, Wvltz', 'youtube', 'https://www.youtube.com/watch?v=n0SEKCX3fBs&list=RDn0SEKCX3fBs&start_radio=1', 'https://i.ytimg.com/vi/n0SEKCX3fBs/maxresdefault.jpg', '', 0, 1, '2026-03-27 23:44:13'),
(20, 'DJ YELLOW MIX ROMANTIC STYLE VOL 2 / REGGAE ROMANTICO PANAMEÑO 90S - 2010', 'yellow', 'youtube', 'https://www.youtube.com/watch?v=Y6mPSzib-yU&list=RDY6mPSzib-yU&start_radio=1', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTIYyxQPz-UnPy8YU3anCeG6TrdjJvmRbHAJQ&s', '', 0, 1, '2026-04-08 00:29:23'),
(21, 'Solita 🌊💝 (Video Oficial) | Lyric / Letra', 'Beéle, Daddy Yankee, Arcángel, Kapo ', 'youtube', 'https://www.youtube.com/watch?v=IhuZ_C1ki_4&list=RDIhuZ_C1ki_4&start_radio=1', 'https://i.ytimg.com/vi/IhuZ_C1ki_4/maxresdefault.jpg', '', 0, 1, '2026-04-08 04:05:55'),
(22, ' GODMAN (Official Music Video)', 'Farruko, Kafu Banton, El Roockie', 'youtube', 'https://www.youtube.com/watch?v=h-42ixb3CoA&list=RDh-42ixb3CoA&start_radio=1', 'https://i.ytimg.com/vi/h-42ixb3CoA/maxresdefault.jpg', '', 0, 1, '2026-04-15 02:51:27'),
(23, 'Sensual by Dj Ipsen Vol 2 0', 'Dj Ipsen', 'youtube', 'https://www.youtube.com/watch?v=LgyVpMcCSpM', 'https://i.ytimg.com/vi/LgyVpMcCSpM/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLAdp7E-_POI2wQ69hxKdXtuR93d9A', '', 0, 1, '2026-04-21 02:22:09');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_dj_stats`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_dj_stats` (
`id` int(11)
,`name` varchar(100)
,`genre` varchar(50)
,`city` varchar(50)
,`avatar` text
,`socials` text
,`featured_week` tinyint(1)
,`total_mixes` bigint(21)
,`total_plays` decimal(32,0)
,`total_downloads` decimal(32,0)
,`total_videos` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_super_packs`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_super_packs` (
`dj` varchar(100)
,`mix_count` bigint(21)
,`last_mix_date` date
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `weekly_reports`
--

CREATE TABLE `weekly_reports` (
  `id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `albumes`
--
ALTER TABLE `albumes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `canciones`
--
ALTER TABLE `canciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_album` (`album_id`);

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
-- Indices de la tabla `playlists`
--
ALTER TABLE `playlists`
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
-- Indices de la tabla `sync_cache`
--
ALTER TABLE `sync_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cache_key` (`cache_key`);

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
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `weekly_reports`
--
ALTER TABLE `weekly_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `week_start` (`week_start`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `albumes`
--
ALTER TABLE `albumes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `canciones`
--
ALTER TABLE `canciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `configuration`
--
ALTER TABLE `configuration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `djs`
--
ALTER TABLE `djs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mixes`
--
ALTER TABLE `mixes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT de la tabla `player_config`
--
ALTER TABLE `player_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radio_config`
--
ALTER TABLE `radio_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `statistics`
--
ALTER TABLE `statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2701;

--
-- AUTO_INCREMENT de la tabla `sync_cache`
--
ALTER TABLE `sync_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `weekly_reports`
--
ALTER TABLE `weekly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_dj_stats`
--
DROP TABLE IF EXISTS `vw_dj_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_dj_stats`  AS SELECT `d`.`id` AS `id`, `d`.`name` AS `name`, `d`.`genre` AS `genre`, `d`.`city` AS `city`, `d`.`avatar` AS `avatar`, `d`.`socials` AS `socials`, `d`.`featured_week` AS `featured_week`, coalesce(`m`.`total_mixes`,0) AS `total_mixes`, coalesce(`m`.`total_plays`,0) AS `total_plays`, coalesce(`m`.`total_downloads`,0) AS `total_downloads`, coalesce(`v`.`total_videos`,0) AS `total_videos` FROM ((`djs` `d` left join (select `mixes`.`dj` AS `dj`,count(0) AS `total_mixes`,sum(`mixes`.`plays`) AS `total_plays`,sum(`mixes`.`downloads`) AS `total_downloads` from `mixes` where `mixes`.`active` = 1 group by `mixes`.`dj`) `m` on(`d`.`name` = `m`.`dj`)) left join (select `videos`.`dj` AS `dj`,count(0) AS `total_videos` from `videos` where `videos`.`active` = 1 group by `videos`.`dj`) `v` on(`d`.`name` = `v`.`dj`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_super_packs`
--
DROP TABLE IF EXISTS `vw_super_packs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_super_packs`  AS SELECT `mixes`.`dj` AS `dj`, count(0) AS `mix_count`, max(`mixes`.`date`) AS `last_mix_date` FROM `mixes` WHERE `mixes`.`active` = 1 GROUP BY `mixes`.`dj` HAVING count(0) >= 4 ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `canciones`
--
ALTER TABLE `canciones`
  ADD CONSTRAINT `fk_canciones_album` FOREIGN KEY (`album_id`) REFERENCES `albumes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
