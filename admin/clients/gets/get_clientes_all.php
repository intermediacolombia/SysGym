<?php
require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';
$permisopage = 'Ver Clientes';
include('../../login/restriction.php');



header('Content-Type: application/json; charset=utf-8');

try {

    $stmt = db()->prepare("
        SELECT
            c.*,
            COALESCE(p.nombre, t.nombre) AS nombre_plan
        FROM clientes c
        LEFT JOIN planes     p ON p.id = c.plan AND p.borrado = 0 AND COALESCE(c.plan_tipo,'plan') = 'plan'
        LEFT JOIN tiqueteras t ON t.id = c.plan AND t.borrado = 0 AND c.plan_tipo = 'tiquetera'
        WHERE c.borrado = 0
        ORDER BY c.identificacion DESC
    ");

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["data" => $rows], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
