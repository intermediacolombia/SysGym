<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

date_default_timezone_set('America/Bogota');
$hoy = "2025-11-22";

try {

    $stmt = db()->prepare("
        SELECT 
            c.id,
            c.nombres,
            c.apellidos,
            c.imagen_perfil,
            c.vencimiento_plan,
            c.congelado,
            p.nombre AS plan
        FROM clientes c
        LEFT JOIN planes p ON p.id = c.plan AND p.borrado = 0
        WHERE c.borrado = 0
          AND c.estado = 'activo'
          AND DATE(c.vencimiento_plan) = :hoy
          AND c.congelado = 0
        ORDER BY c.nombres ASC
    ");

    $stmt->execute([':hoy' => $hoy]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(["data" => [], "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
