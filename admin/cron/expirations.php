<?php
require_once __DIR__ . '/../../inc/config.php';

try {
   // Consulta para obtener los clientes cuyo vencimiento del plan es dentro de 7 días
    $stmt = db()->prepare("SELECT id, nombres, apellidos, vencimiento_plan 
                           FROM clientes 
                           WHERE borrado = 0 
                             AND DATE(vencimiento_plan) = DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mostrar resultados en formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'success',
        'clients' => $clients
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
