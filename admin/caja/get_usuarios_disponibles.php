<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../inc/config.php';

try {

    $stmt = db()->query("
        SELECT id, nombre, apellido 
        FROM usuarios 
        WHERE borrado = 0 AND estado = 0
          AND id NOT IN (
            SELECT usuario_id FROM cajas WHERE estado = 1
          )
        ORDER BY nombre ASC
    ");

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'data' => $usuarios
    ]);
    
} catch (Throwable $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}


