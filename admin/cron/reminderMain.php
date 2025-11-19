<?php
include(__DIR__ . '/../../inc/config.php');

try {
   // Consulta para obtener los clientes activos cuyo plan vence en 7 días
    // y que tienen activadas las notificaciones (notificaciones = 1)
    $stmt = db()->prepare("
        SELECT c.id, c.identificacion, c.nombres, c.apellidos, c.dialCode, c.telefono, c.vencimiento_plan,  
               p.nombre AS plan, p.precio AS valor_pago
        FROM clientes c
        LEFT JOIN planes p ON c.plan = p.id
        WHERE c.borrado = 0 
          AND c.estado = 'activo' AND c.congelado = 0
          AND c.notificaciones = 1
          AND DATE(c.vencimiento_plan) = DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error en la conexión: ' . $e->getMessage()
    ]);
    exit;
}

include(__DIR__ . '/../../whatsapp/paymentReminder.php');
?>



