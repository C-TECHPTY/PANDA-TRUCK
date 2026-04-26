-- =====================================================
-- PANDA TRUCK - VERIFICAR ESTADO CDN DE AUDIO
-- Pull Zone esperado:
-- https://f005.backblazeb2.com/file/
--
-- Rutas validas en mixes.url:
-- mixes-mp3/nombre.mp3
-- DJIMMY-PANDA/MIXES/nombre.mp3
-- =====================================================

SET NAMES utf8mb4;

SELECT
    COUNT(*) AS total_mixes_activos,
    SUM(CASE WHEN `url` LIKE 'mixes-mp3/%' THEN 1 ELSE 0 END) AS rutas_bucket_mixes_mp3,
    SUM(CASE WHEN `url` LIKE 'DJIMMY-PANDA/%' THEN 1 ELSE 0 END) AS rutas_bucket_djimmy_panda,
    SUM(CASE WHEN `url` LIKE 'https://%' THEN 1 ELSE 0 END) AS urls_completas_pendientes,
    SUM(CASE WHEN `url` LIKE 'MIXES/%' THEN 1 ELSE 0 END) AS rutas_mixes_sin_bucket,
    SUM(CASE WHEN `url` = '' OR `url` IS NULL THEN 1 ELSE 0 END) AS rutas_vacias
FROM `mixes`
WHERE `active` = 1;

SELECT
    `id`,
    `title`,
    `url`,
    CASE
        WHEN `url` LIKE 'mixes-mp3/%' THEN 'OK: mixes-mp3'
        WHEN `url` LIKE 'DJIMMY-PANDA/%' THEN 'OK: DJIMMY-PANDA'
        WHEN `url` LIKE 'https://%' THEN 'PENDIENTE: URL completa'
        WHEN `url` LIKE 'MIXES/%' THEN 'PENDIENTE: falta DJIMMY-PANDA/'
        WHEN `url` = '' OR `url` IS NULL THEN 'ERROR: ruta vacia'
        ELSE 'REVISAR: ruta no reconocida'
    END AS estado_cdn
FROM `mixes`
WHERE `active` = 1
ORDER BY estado_cdn DESC, `id` DESC;
