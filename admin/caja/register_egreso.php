<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';



// Verificar que exista una caja abierta para el usuario
$stmtCaja = db()->prepare("SELECT * FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
$stmtCaja->execute([':usuario_id' => $id_user]);
$cajaAbierta = $stmtCaja->fetch(PDO::FETCH_ASSOC);
if (!$cajaAbierta) {
    echo json_encode(['status'=>'error', 'message'=>'No hay caja abierta para registrar egreso.']);
    exit;
}
$caja_id = $cajaAbierta['id'];

// Validar datos del formulario
if(!isset($_POST['detalle']) || !isset($_POST['cantidad']) || !isset($_POST['valor'])){
    echo json_encode(['status'=>'error', 'message'=>'Faltan datos.']);
    exit;
}
$detalle = trim($_POST['detalle']);
$cantidad = intval($_POST['cantidad']);
$valorInput = floatval($_POST['valor']);

// Verificar que el valor sea mayor que cero
if($valorInput <= 0){
    echo json_encode(['status'=>'error', 'message'=>'El valor debe ser mayor que 0.']);
    exit;
}

// Convertir el valor a negativo para restar (si el usuario ingresa un valor positivo)
$valor = -$valorInput;

// Fecha y hora actuales
$fecha = date('Y-m-d');
$hora = date('H:i:s');

// Insertar el egreso en la tabla ventas
// Asumimos que para egresos, producto_id es NULL y payment_method se registra como 'Egreso'
$stmtInsert = db()->prepare("INSERT INTO ventas (caja_id, producto_id, detalle, cantidad, valor, fecha, hora, payment_method) VALUES (:caja_id, NULL, :detalle, :cantidad, :valor, :fecha, :hora, 'Egreso')");
if($stmtInsert->execute([
    ':caja_id'  => $caja_id,
    ':detalle'  => $detalle,
    ':cantidad' => $cantidad,
    ':valor'    => $valor,
    ':fecha'    => $fecha,
    ':hora'     => $hora
]))	
{
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
			'caja_id'  => $caja_id,
			'detalle'  => $detalle,
			'cantidad' => $cantidad,
			'valor'    => $valor,
			'fecha'    => $fecha,
			'hora'     => $hora
			
], JSON_UNESCAPED_UNICODE);
log_action('Registrar Egresos', $desc, 'Caja');
// END LOGS
    echo json_encode(['status'=>'success', 'message'=>'Egreso registrado correctamente.']);
} else {
    echo json_encode(['status'=>'error', 'message'=>'Error al registrar el egreso.']);
}
?>
