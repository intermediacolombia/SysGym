<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';
$permisopage = 'Cerrar Otras Cajas';
include('../login/restriction.php');

header('Content-Type: application/json');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Verificar que el usuario tenga rol de admin
if ($_SESSION['user']['rol'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para cerrar cajas por ID.']);
    exit;
}

// Validar el parámetro id
if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionó el ID de la caja.']);
    exit;
}
$caja_id = intval($_GET['id']);
if ($caja_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de caja inválido.']);
    exit;
}

// Conectar a la BD
try {
    db(); // intenta conexión global
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la conexión']);
    exit;
}


// Verificar que exista una caja abierta (estado = 1) con ese ID
$stmt = db()->prepare("SELECT * FROM cajas WHERE id = :id AND estado = 1 LIMIT 1");
$stmt->execute([':id' => $caja_id]);
$cajaAbierta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cajaAbierta) {
    echo json_encode(['status' => 'error', 'message' => 'No hay caja abierta con ese ID para cerrar.']);
    exit;
}

// Obtener la base de la caja actual (monto_inicial)
$base = floatval($cajaAbierta['monto_inicial']);

// Consultar el total de ventas para la caja abierta
$stmtVentas = db()->prepare("SELECT IFNULL(SUM(valor), 0) AS total_ventas FROM ventas WHERE caja_id = :caja_id");
$stmtVentas->execute([':caja_id' => $caja_id]);
$rowVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC);
$totalVentas = $rowVentas ? floatval($rowVentas['total_ventas']) : 0.0;

// Calcular total_cierre = base + totalVentas
$totalCierre = $base + $totalVentas;

// Obtener la fecha y hora actuales para el cierre
$fecha_cierre = date('Y-m-d');
$hora_cierre  = date('H:i:s');

// Actualizar la caja: cerrar (estado = 0) y guardar los totales
$sqlUpdate = "
    UPDATE cajas 
    SET fecha_cierre = :fecha_cierre, 
        hora_cierre = :hora_cierre, 
        estado = 0, 
        total_vendido = :total_vendido, 
        total_cierre = :total_cierre 
    WHERE id = :id
";
$stmtUpdate = db()->prepare($sqlUpdate);

// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
			'id_caja'		=> $caja_id,
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
    ':id'            => $caja_id
])) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Caja cerrada exitosamente. Base: $' . number_format($base, 0, '', '.') .
                     ' / Ventas: $' . number_format($totalVentas, 0, '', '.') .
                     ' / Total en caja: $' . number_format($totalCierre, 0, '', '.')
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al cerrar la caja.']);
}
?>
