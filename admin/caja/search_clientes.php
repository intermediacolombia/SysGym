<?php
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    db(); // conexión global optimizada
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la conexión']);
    exit;
}

if (isset($_GET['query'])) {

    $query = '%' . $_GET['query'] . '%';

    $stmt = db()->prepare("
        SELECT id, identificacion, nombres, apellidos
        FROM clientes
        WHERE estado = 'activo'
          AND (identificacion LIKE :query 
               OR nombres LIKE :query 
               OR apellidos LIKE :query)
        LIMIT 10
    ");
    $stmt->execute([':query' => $query]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clientes);

} else {
    echo json_encode([]);
}
