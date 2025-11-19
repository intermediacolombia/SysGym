<?php
require_once __DIR__ . '/../../inc/config.php';

$cliente = intval($_GET['cliente_id'] ?? 0);

try {
    
    // Configurar idioma de días en español
    db()->exec("SET lc_time_names = 'es_ES';");

    $stmt = db()->prepare("
        SELECT 
            fecha,
            hora,
            DATE_FORMAT(fecha,'%W') AS dia_semana
        FROM asistencias
        WHERE idCliente = :id
        ORDER BY fecha DESC, hora DESC
    ");

    $stmt->execute([':id' => $cliente]);

    echo json_encode([
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'data'  => [],
        'error' => $e->getMessage()
    ]);
}

