<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../inc/config.php';

$caja_id = isset($_GET['caja_id']) ? (int)$_GET['caja_id'] : 0;
if ($caja_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de caja inválido']);
    exit;
}

// Crear conexión local
if (!isset($pdoLocal)) {
    try {
        $pdoLocal = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",
            $dbuser,
            $dbpass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión: '.$e->getMessage()]);
        exit;
    }
}

try {
    // Tu consulta original, intacta
    $stmtVentas = $pdoLocal->prepare("
        SELECT 
          COUNT(*) AS total_registros,
          IFNULL(SUM(CASE WHEN payment_method='Efectivo' THEN valor ELSE 0 END),0) AS efectivo,
          IFNULL(SUM(CASE WHEN payment_method='Transferencia' THEN valor ELSE 0 END),0) AS transferencias
        FROM ventas
        WHERE caja_id = :caja_id
    ");
    $stmtVentas->execute([':caja_id' => $caja_id]);
    $ventas = $stmtVentas->fetch(PDO::FETCH_ASSOC);

    $efectivo = (float)$ventas['efectivo'];
    $transferencias = (float)$ventas['transferencias'];
    $totalCaja = $efectivo + $transferencias;

    // Respuesta en JSON
    echo json_encode([
        'status' => 'success',
        'caja_id' => $caja_id,
        'total_registros' => (int)$ventas['total_registros'],
        'efectivo' => $efectivo,
        'transferencias' => $transferencias,
        'total' => $totalCaja
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

