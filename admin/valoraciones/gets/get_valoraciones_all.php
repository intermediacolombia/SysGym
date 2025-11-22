<?php
require_once __DIR__ . '/../../login/session.php';
$permisopage = 'Manejar Valoraciones';
include('../../login/restriction.php');

require_once __DIR__ . '/../../../inc/config.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $sql = "
        SELECT 
            v.id,
            CONCAT(c.nombres, ' ', c.apellidos) AS cliente,
            DATE(v.created_at)         AS fecha_creacion,
            v.fecha                    AS fecha_valoracion,
            v.peso,
            v.estatura
        FROM valoraciones v
        JOIN clientes c ON c.id = v.cliente_id
        WHERE v.borrado = 0
        ORDER BY v.id DESC
    ";

    $stmt = db()->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["data" => $rows], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
