-- =====================================================
-- PANDA TRUCK - NORMALIZAR RUTAS DE AUDIO PARA CDN
-- Ejecutar manualmente solo despues de verificar respaldo.
-- Convierte URLs completas de BunnyCDN/Backblaze a rutas relativas
-- compatibles con un Pull Zone Bunny apuntando a:
-- https://f005.backblazeb2.com/file/
--
-- Formato final esperado:
-- mixes-mp3/nombre.mp3
-- DJIMMY-PANDA/MIXES/nombre.mp3
-- No borra datos.
-- =====================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mixes_backup_before_cdn_paths` AS
SELECT *
FROM `mixes`;

UPDATE `mixes`
SET `url` = TRIM(LEADING '/' FROM REPLACE(`url`, 'https://panda-truck.b-cdn.net/', ''))
WHERE `url` LIKE 'https://panda-truck.b-cdn.net/%';

UPDATE `mixes`
SET `url` = CONCAT('DJIMMY-PANDA/', TRIM(LEADING '/' FROM REPLACE(`url`, 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/', '')))
WHERE `url` LIKE 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/%';

UPDATE `mixes`
SET `url` = CONCAT('mixes-mp3/', REPLACE(TRIM(LEADING '/' FROM REPLACE(`url`, 'https://f005.backblazeb2.com/file/mixes-mp3/', '')), '+', ' '))
WHERE `url` LIKE 'https://f005.backblazeb2.com/file/mixes-mp3/%';

UPDATE `mixes`
SET `url` = CONCAT('DJIMMY-PANDA/', `url`)
WHERE `url` LIKE 'MIXES/%';

UPDATE `mixes`
SET `url` = REPLACE(`url`, '%20', ' ')
WHERE INSTR(`url`, '%20') > 0;

-- Vista previa despues de ejecutar:
SELECT `id`, `title`, `url`
FROM `mixes`
WHERE `active` = 1
ORDER BY `id` DESC;
