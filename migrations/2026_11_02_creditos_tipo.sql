-- Separa créditos de mensualidad vs créditos de producto (caja)
-- y agrega el toggle de suspensión automática por mensualidad vencida.

ALTER TABLE `creditos`
  ADD COLUMN `tipo` ENUM('mensualidad','producto') NOT NULL DEFAULT 'mensualidad' AFTER `descripcion`;

-- Backfill: los créditos con venta asociada son de producto (caja)
UPDATE `creditos` cr
JOIN `ventas` v ON v.`creditoId` = cr.`id`
SET cr.`tipo` = 'producto';

-- Setting: suspender asistencia si tiene mensualidad a crédito vencida
INSERT INTO `system_settings` (`setting_name`, `value`, `enabled`)
VALUES ('suspender_por_credito_mensualidad', '1', 1);
