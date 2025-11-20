<?php
// admin/api/asistencias_count.php
require_once __DIR__ . '/../inc/config.php';   // $host, $dbname, $dbuser, $dbpass, $hoy

header('Content-Type: application/json');

try {
    

    /* —— una sola asistencia por cliente hoy —— */
    $sql = "
        SELECT COUNT(DISTINCT a.idCliente) AS total
        FROM   asistencias a
        JOIN   clientes    c ON c.id = a.idCliente
        WHERE  a.fecha   = :hoy
          AND  c.borrado = 0
          AND  c.estado  = 'activo'
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':hoy' => $hoy]);

    echo json_encode(['count' => (int)$stmt->fetchColumn()]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

