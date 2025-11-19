<?php
// register_generic_sale.php
// Se asume que el script padre ya define las siguientes variables:
// $planNombre, $facturaValor, $clienteId y que $id_user ya está definido en la sesión

// Variables de ejemplo (en el script padre deberían definirse antes de incluir este archivo)
$planNombre = $detalle;
$facturaValorSale = $facturaValor; // Valor sin centavos, por ejemplo 250000
//$clienteId = 123;       // ID del cliente

if (!isset($planNombre) || !isset($facturaValorSale)) {
    throw new Exception("Faltan variables requeridas: planNombre, facturaValor o clienteId.");
}

require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

try {
    db();
} catch(PDOException $e) {
    throw new Exception("Error en la conexión: " . $e->getMessage());
}

// Verificar que el usuario tenga una caja abierta
$stmtCaja = db()->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
$stmtCaja->execute([':usuario_id' => $id_user]);
$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
if (!$caja) {
    throw new Exception("No tienes caja abierta.");
}
$caja_id = $caja['id'];

// Definir los datos de la venta
// Para un plan, la cantidad es fija (1) y el valor es igual a $facturaValor
$hoy = date('Y-m-d');
$hora = date('H:i:s');
$cantidad = 1;
$valor = $facturaValorSale;

// Registrar la venta en la tabla "ventas" sin insertar un valor para producto_id
// Se insertan los campos: caja_id, detalle, cantidad, valor, fecha y hora
$stmtInsert = db()->prepare("INSERT INTO ventas (caja_id, detalle, cantidad, valor, payment_method, bank, fecha, hora) VALUES (:caja_id, :detalle, :cantidad, :valor, :payment_method, :bank, :fecha, :hora)");
$stmtInsert->execute([
    ':caja_id' => $caja_id,
    ':detalle' => $planNombre.' Factura #'.$facturaId,
    ':cantidad' => $cantidad,
    ':valor' => $valor,
	':payment_method' => $payment_method,
	':bank' => $bank,
    ':fecha' => $hoy,
    ':hora' => $hora
]);

// Calcular el total en caja (suma de los valores de las ventas registradas para esta caja)
$stmtTotal = db()->prepare("SELECT IFNULL(SUM(valor), 0) as total FROM ventas WHERE caja_id = :caja_id");
$stmtTotal->execute([':caja_id' => $caja_id]);
$rowTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);
$totalCaja = $rowTotal ? $rowTotal['total'] : 0;

// Preparar el resultado para el script que lo incluya
$ventaResultado = [
    'status' => 'success',
    'message' => 'Venta registrada correctamente.',
    'total_caja' => $totalCaja
];
?>


