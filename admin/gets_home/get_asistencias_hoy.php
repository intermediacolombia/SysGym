<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

date_default_timezone_set('America/Bogota');
$hoy = "2025-11-21";

try {
    $stmt = db()->prepare("
        SELECT 
            c.id,
            c.nombres,
            c.apellidos,
            c.imagen_perfil,
            MAX(a.hora) AS hora
        FROM asistencias a
        JOIN clientes c ON c.id = a.idCliente
        WHERE a.fecha = :hoy
          AND c.borrado = 0
          AND c.estado = 'activo'
        GROUP BY c.id
        ORDER BY hora DESC
    ");

    $stmt->execute([':hoy' => $hoy]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formato hora bonita
    foreach ($data as &$row) {
        $h = $row['hora'];
        $obj = DateTime::createFromFormat('H:i:s', $h);
        $row['hora_bonita'] = $obj ? $obj->format('g:i a') : $h;
    }
    unset($row);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(["data" => [], "error" => $e->getMessage()]);
}
