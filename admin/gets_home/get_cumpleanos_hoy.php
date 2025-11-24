<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

date_default_timezone_set('America/Bogota');
$hoy = date('Y-m-d'); // ⚠️ ESTO FALTABA EN TU CÓDIGO ORIGINAL

try {
    $stmt = db()->prepare("
        SELECT 
            id,
            nombres,
            apellidos,
            telefono,
            fecha_nacimiento,
            imagen_perfil
        FROM clientes
        WHERE borrado = 0
          AND estado = 'activo'
          AND MONTH(fecha_nacimiento) = MONTH(:hoy)
          AND DAY(fecha_nacimiento)   = DAY(:hoy)
        ORDER BY nombres ASC
    ");
    $stmt->execute([':hoy' => $hoy]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CALCULA EDAD
    $hoyY = (int)date('Y');
    foreach ($data as &$c) {
        $nacY = (int)date('Y', strtotime($c['fecha_nacimiento']));
        $c['edad'] = $hoyY - $nacY;
    }
    unset($c); // Rompe la referencia
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(["data" => [], "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

