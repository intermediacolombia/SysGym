<?php
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

header('Content-Type: application/json; charset=utf-8');

try {
    

    $hoy = date('Y-m-d');
    $cumpleaneros = [];

    // Buscar clientes que asistieron hoy y cumplen años hoy
    $stmt = db()->prepare("
        SELECT c.id, c.nombres, c.apellidos, c.fecha_nacimiento
        FROM asistencias a
        JOIN clientes c ON c.id = a.idCliente
        WHERE a.fecha = :hoy
          AND c.borrado = 0
          AND c.estado = 'activo'
        GROUP BY c.id, c.nombres, c.apellidos, c.fecha_nacimiento
    ");
    $stmt->execute([':hoy' => $hoy]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = date('m-d');

    foreach ($rows as $r) {
        if (!empty($r['fecha_nacimiento'])) {
            $nac = DateTime::createFromFormat('Y-m-d', $r['fecha_nacimiento']);
            if ($nac && $nac->format('m-d') === $today) {
                $cumpleaneros[] = $r['nombres'] . ' ' . $r['apellidos'];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'cumpleaneros' => $cumpleaneros
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
