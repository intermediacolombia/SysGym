<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

date_default_timezone_set('America/Bogota');

$hoy = date('Y-m-d');

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
    foreach ($data as &$c) {
        $c['edad'] = date('Y') - date('Y', strtotime($c['fecha_nacimiento']));
    }

    header('Content-Type: application/json');
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(["data" => [], "error" => $e->getMessage()]);
}

