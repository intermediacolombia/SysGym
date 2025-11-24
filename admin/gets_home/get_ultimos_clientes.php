<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

try {

    $stmt = db()->prepare("
        SELECT 
            c.id,
            c.nombres,
            c.apellidos,
            c.telefono,
            c.vencimiento_plan,
            c.imagen_perfil,
            p.nombre AS plan
        FROM clientes c
        LEFT JOIN planes p ON c.plan = p.id
        WHERE c.borrado = 0
        ORDER BY c.id DESC
        LIMIT 5
    ");

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode(["data" => [], "error" => $e->getMessage()]);
}
