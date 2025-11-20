<?php
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
    echo json_encode($usuarios);

} catch (Exception $e) {
    echo json_encode([]);
}

