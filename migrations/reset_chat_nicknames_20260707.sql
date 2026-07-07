-- Reset de nicks del chat sin borrar usuarios, mensajes ni moderacion.
-- Ejecutar una sola vez despues de subir el nuevo flujo de confirmacion.

SET NAMES utf8mb4;

UPDATE `chat_users`
SET
  `nickname` = '',
  `nickname_locked` = 0
WHERE `nickname_locked` = 1
   OR `nickname` <> '';
