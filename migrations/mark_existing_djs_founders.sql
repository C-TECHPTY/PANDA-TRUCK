-- Marcar DJs existentes como Fundadores.
-- Usar si ya importaste upgrade_dj_pro_stats.sql antes de agregar el plan founder.

ALTER TABLE `djs`
  MODIFY COLUMN `plan` ENUM('free','pro','founder') NOT NULL DEFAULT 'free';

UPDATE `djs`
SET
  `plan` = 'founder',
  `subscription_status` = 'active',
  `subscription_start` = COALESCE(`subscription_start`, NOW()),
  `subscription_end` = NULL,
  `is_featured` = COALESCE(`is_featured`, 0),
  `priority` = COALESCE(`priority`, 0)
WHERE `active` = 1;
