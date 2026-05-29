<?php
require_once __DIR__ . '/../../inc/config.php';

// Clientes cuya tiquetera se agotó ayer (última entrada fue ayer y alcanzó el límite)
try {
    $stmt = db()->prepare("
        SELECT c.id, c.nombres, c.apellidos, c.dialCode, c.telefono,
               t.limite_entradas,
               COUNT(tc.id) AS consumidas
        FROM clientes c
        JOIN tiqueteras t ON t.id = c.plan AND t.borrado = 0
        JOIN tiquetera_consumos tc ON tc.cliente_id = c.id AND tc.factura_id = c.tiquetera_factura_id
        WHERE c.borrado = 0
          AND c.plan_tipo = 'tiquetera'
          AND c.tiquetera_factura_id IS NOT NULL
          AND c.notificaciones = 1
          AND c.telefono IS NOT NULL AND c.telefono != ''
        GROUP BY c.id, t.limite_entradas
        HAVING consumidas >= t.limite_entradas
           AND MAX(TIMESTAMP(tc.fecha, tc.hora)) >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $agotadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[cron tiquetera_agotada] ' . $e->getMessage());
    return;
}

foreach ($agotadas as $row) {
    $cp_nombres   = $row['nombres'];
    $cp_apellidos = $row['apellidos'];
    $cp_dialCode  = $row['dialCode'];
    $cp_telefono  = $row['telefono'];
    $cp_entradas  = (int)$row['limite_entradas'];

    include(__DIR__ . '/../../whatsapp/tiquetera-agotada.php');
}
