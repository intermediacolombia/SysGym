<?php
/**
 * suspendOverdueCredit.php
 * Marca como 'inactivo' a los clientes con mensualidad a crédito vencida.
 * Respeta el toggle suspender_por_credito_mensualidad.
 * La reactivación ocurre automáticamente al pagar el crédito
 * (update_credito.php / pagar_creditos_masivo.php).
 */

require_once __DIR__ . '/../../inc/config.php';
if (!defined('CRON_RUNNING')) require_cron_key();

if (empty($settings['suspender_por_credito_mensualidad'])) {
    echo json_encode(['status' => 'skipped', 'reason' => 'toggle off']);
    return;
}

try {
    // 1. Detectar clientes a suspender (todavía activos, con mensualidad vencida)
    $sel = db()->query("
        SELECT c.id, c.nombres, c.apellidos, c.dialCode, c.telefono, c.identificacion, c.notificaciones,
               SUM(cr.valor) AS total_credito,
               GROUP_CONCAT(CONCAT(cr.descripcion, ' ($', FORMAT(cr.valor,0), ' - vence ', cr.fecha_limite, ')') SEPARATOR '\n') AS detalle_creditos
        FROM clientes c
        JOIN creditos cr ON cr.idCliente = c.id
        WHERE c.estado = 'activo'
          AND c.borrado = 0
          AND cr.estado = 0
          AND cr.deleted = 0
          AND cr.tipo = 'mensualidad'
          AND cr.fecha_limite < CURDATE()
        GROUP BY c.id
    ");
    $suspended = $sel->fetchAll(PDO::FETCH_ASSOC);

    if (!$suspended) {
        echo json_encode(['status' => 'success', 'suspendidos' => 0]);
        return;
    }

    // 2. Suspender
    $ids = array_column($suspended, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $upd = db()->prepare("UPDATE clientes SET estado = 'inactivo', updated_at = NOW() WHERE id IN ($ph)");
    $upd->execute($ids);

    // 3. Notificar por WhatsApp (solo los que tengan notificaciones activas)
    $suspended = array_filter($suspended, fn($c) => in_array((int)$c['notificaciones'], [0, 1], true));
    if ($suspended) {
        include __DIR__ . '/../../whatsapp/notify_credit_suspended.php';
    }

    echo json_encode(['status' => 'success', 'suspendidos' => count($ids)]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
