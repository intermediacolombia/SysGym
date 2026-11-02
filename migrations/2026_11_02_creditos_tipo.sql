-- Separa créditos de mensualidad vs créditos de producto (caja)
-- y agrega el toggle de suspensión automática por mensualidad vencida.

ALTER TABLE `creditos`
  ADD COLUMN `tipo` ENUM('mensualidad','producto') NOT NULL DEFAULT 'mensualidad' AFTER `descripcion`;

-- Backfill: marca como 'producto' todo crédito cuya descripción NO coincida con
-- un plan existente (planes.nombre) ni con los textos conocidos que genera el
-- sistema para pagos de plan: "Saldo pendiente del plan", "<Plan> - N período(s)",
-- y "Quincena" (legacy).
UPDATE `creditos` cr
LEFT JOIN `planes` p
       ON cr.`descripcion` = p.`nombre`
       OR cr.`descripcion` LIKE CONCAT(p.`nombre`, ' -%')
SET cr.`tipo` = 'producto'
WHERE p.`id` IS NULL
  AND cr.`descripcion` NOT LIKE 'Saldo pendiente del plan%'
  AND cr.`descripcion` <> 'Quincena';

-- Setting: suspender asistencia si tiene mensualidad a crédito vencida
INSERT INTO `system_settings` (`setting_name`, `value`, `enabled`)
VALUES ('suspender_por_credito_mensualidad', '1', 1);
