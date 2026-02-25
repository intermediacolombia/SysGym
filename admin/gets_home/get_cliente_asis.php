<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');

$identificacion = trim($_GET['documento'] ?? '');
if (!$identificacion) {
    echo json_encode(null);
    exit;
}

try {
    $stmt = db()->prepare("
        SELECT 
            c.id,
            CONCAT(c.nombres, ' ', c.apellidos) AS nombre_completo,
            c.identificacion AS documento,
            c.estado,
            c.congelado,
            c.vencimiento_plan,
            (
                SELECT COUNT(*) 
                FROM asistencias a 
                WHERE a.idCliente = c.id 
                  AND a.fecha = :hoy
            ) AS ya_asistio
        FROM clientes c
        WHERE c.identificacion = :identificacion
          AND c.borrado = 0
        LIMIT 1
    ");
    $stmt->execute([
        ':identificacion' => $identificacion,
        ':hoy'            => $hoy,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(null);
        exit;
    }

    // Determinar estado real combinado
    $estadoFinal = $row['estado']; // 'activo' o 'inactivo'

    if ($row['congelado'] == 1) {
        $estadoFinal = 'congelado';
    } elseif ($row['vencimiento_plan'] < $hoy) {
        $estadoFinal = 'vencido';
    }

    $row['estado_real'] = $estadoFinal;

    echo json_encode($row);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}