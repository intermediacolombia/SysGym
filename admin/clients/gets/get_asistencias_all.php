<?php
// get_asistencias_all.php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json; charset=utf-8');

try {

    db()->exec("SET lc_time_names = 'es_ES';");

    $sql = "
        SELECT  
            a.fecha,
            a.hora,
            DATE_FORMAT(a.fecha,'%W') AS dia,
            c.id AS idCliente,
            c.identificacion,
            c.nombres,
            c.apellidos
        FROM asistencias a
        JOIN clientes c ON c.id = a.idCliente
        WHERE c.borrado = 0
        ORDER BY a.fecha DESC, a.hora DESC
    ";

    $stmt = db()->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "data" => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
