<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';  // ← agregar


header('Content-Type: application/json');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Verificar si existe una caja abierta para este usuario (estado == 1)
$sql = "SELECT * FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1";
$stmt = db()->prepare($sql);
$stmt->execute([':usuario_id' => $id_user]);
$cajaAbierta = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar que se haya encontrado una caja abierta y que su estado sea 1
if (!$cajaAbierta || ((int)$cajaAbierta['estado']) !== 1) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'No hay caja abierta para cerrar.'
    ]);
    exit;
}

// Obtener la base de la caja actual (monto_inicial)
$base = (float)$cajaAbierta['monto_inicial'];

// Consultar el total de ventas para la caja abierta
$stmtVentas = db()->prepare("
    SELECT IFNULL(SUM(valor), 0) AS total_ventas
    FROM ventas
    WHERE caja_id = :caja_id
");
$stmtVentas->execute([':caja_id' => $cajaAbierta['id']]);
$rowVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC);
$totalVentas = $rowVentas ? (float)$rowVentas['total_ventas'] : 0.0;

// Calcular total_cierre = base + total_vendido
$totalCierre = $base + $totalVentas;

// Obtener la fecha y la hora actuales para el cierre
$fecha_cierre = date('Y-m-d');
$hora_cierre  = date('H:i:s');

// Actualizar la caja con estado=0, total_vendido y total_cierre
$sqlUpdate = "
    UPDATE cajas
    SET fecha_cierre   = :fecha_cierre,
        hora_cierre    = :hora_cierre,
        estado         = 0,
        total_vendido  = :total_vendido,
        total_cierre   = :total_cierre
    WHERE id            = :id
";
$stmtUpdate = db()->prepare($sqlUpdate);

// LOGS
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
			'id_caja'		=> $cajaAbierta['id'],
			'fecha_cierre'  => $fecha_cierre,
			'hora_cierre'   => $hora_cierre,
			'total_vendido' => $totalVentas,
			'total_cierre'  => $totalCierre	
			
], JSON_UNESCAPED_UNICODE);

log_action('Cerrar Caja', $desc, 'Caja');
// END LOGS

if ($stmtUpdate->execute([
    ':fecha_cierre'  => $fecha_cierre,
    ':hora_cierre'   => $hora_cierre,
    ':total_vendido' => $totalVentas,
    ':total_cierre'  => $totalCierre,
    ':id'            => $cajaAbierta['id']
])) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Caja cerrada exitosamente. ' 
                    . 'Base: $' . number_format($base, 0, '', '.')
                    . ' / Ventas: $' . number_format($totalVentas, 0, '', '.')
                    . ' / Total en caja: $' . number_format($totalCierre, 0, '', '.')
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al cerrar la caja.'
    ]);
}
?>











