<?php
require_once __DIR__ . '/../../inc/config.php';

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obtener los clientes cuyo vencimiento del plan es dentro de 7 días
    $stmt = $pdo->prepare("SELECT id, nombres, apellidos, vencimiento_plan 
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
