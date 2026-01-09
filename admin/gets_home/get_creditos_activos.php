<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$hoy = date('Y-m-d'); // Faltaba definir esta variable

try {
    $stmt = db()->prepare("
        SELECT  
            c.id,
            c.idCliente, 
            cli.nombres,
            cli.apellidos,
            cli.imagen_perfil,
            c.fecha,
            c.descripcion,
            c.valor,
            c.fecha_limite,
            DATEDIFF(c.fecha_limite, :hoy) AS dias_restantes
        FROM creditos c
        JOIN clientes cli ON cli.id = c.idCliente
        WHERE c.estado = 0 
          AND c.deleted = 0
          AND cli.estado = 'activo'
          AND cli.borrado = 0
        ORDER BY c.fecha_limite ASC
    ");
    $stmt->execute([':hoy' => $hoy]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total global
    $total = 0;
    foreach ($data as $cr) {
        $total += intval($cr['valor']);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "data"  => $data,
        "total" => $total
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "data" => [], 
        "total" => 0,
        "error" => $e->getMessage()
    ]);
}