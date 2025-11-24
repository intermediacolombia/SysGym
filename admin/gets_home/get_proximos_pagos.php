<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

date_default_timezone_set('America/Bogota');
$hoy = date('Y-m-d');

try {

    $stmt = db()->prepare("
        SELECT 
            c.id,
            c.nombres,
            c.apellidos,
            c.telefono,
            c.imagen_perfil,
            c.vencimiento_plan,
            c.congelado,
            p.nombre AS plan,
            p.precio AS valor_pago,
            DATEDIFF(c.vencimiento_plan, :hoy) AS dias_restantes
        FROM clientes c
        LEFT JOIN planes p ON p.id = c.plan
        WHERE c.borrado = 0
          AND c.estado = 'activo'
          AND c.congelado = 0
          AND DATE(c.vencimiento_plan)
              BETWEEN :hoy AND DATE_ADD(:hoy, INTERVAL 7 DAY)
        ORDER BY c.vencimiento_plan ASC
    ");

    $stmt->execute([':hoy' => $hoy]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["data" => [], "error" => $e->getMessage()]);
}
