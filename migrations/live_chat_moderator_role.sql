-- Panda Truck Reloaded - rol limitado para moderadores del chat
-- Ejecutar una sola vez en phpMyAdmin.

SET NAMES utf8mb4;

ALTER TABLE `users`
  MODIFY `role` enum('superadmin','admin','chat_moderator','dj','viewer') NOT NULL DEFAULT 'viewer';

ALTER TABLE `chat_messages`
  MODIFY `role` enum('viewer','chat_admin','chat_moderator','admin','superadmin') NOT NULL DEFAULT 'viewer';
