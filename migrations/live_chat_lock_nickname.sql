-- Panda Truck Reloaded - lock one nickname per chat device/user
-- Run once in phpMyAdmin. It does not delete existing chat data.

SET NAMES utf8mb4;

ALTER TABLE `chat_users`
  ADD COLUMN `nickname_locked` tinyint(1) NOT NULL DEFAULT 0 AFTER `nickname`;

UPDATE `chat_users`
SET `nickname_locked` = 1
WHERE `nickname` IS NOT NULL
  AND TRIM(`nickname`) <> ''
  AND LOWER(TRIM(`nickname`)) <> 'oyente';
