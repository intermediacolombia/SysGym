<?php
require_once __DIR__ . '/../../inc/config.php';

try {  
  
    // Consulta para obtener los clientes que cumplen años hoy
    $stmt = db()->prepare("SELECT * 
                           FROM clientes 
                           WHERE borrado = 0 AND estado = 'activo' 
                             AND MONTH(fecha_nacimiento) = MONTH(CURDATE()) 
                             AND DAY(fecha_nacimiento) = DAY(CURDATE())");
    $stmt->execute();
    $birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error en la conexión: ' . $e->getMessage()
    ]);
    exit;
}

// Filtrar clientes que tienen notificaciones activadas (notificaciones = 1)
$birthdaysToNotify = array_filter($birthdays, function($client) {
    return isset($client['notificaciones']) && $client['notificaciones'] == 1;
});

if (!empty($birthdaysToNotify)) {
    // Se envía WhatsApp solo a los clientes con notificaciones activadas
    include(__DIR__ . '/../../whatsapp/hbd.php');
} else {
    echo "No hay clientes con notificaciones activadas para cumpleaños hoy.";
}
?>


