<?php
// generate_factura_pasarela.php
require_once __DIR__ . '/../../inc/config.php';

if (!isset($id) || !isset($pago_plan) || !isset($vencimiento_plan)) {
    return false;
}

$credit = isset($credit) ? $credit : 0;
$valorPagado = isset($valorPagado) ? floatval($valorPagado) : 0.0;
$porcentajeAdicional = defined('ADDITIONAL_PERCENTAGE_PAYMENT') ? (float) ADDITIONAL_PERCENTAGE_PAYMENT : 0.0;



try {
    db();
} catch (PDOException $e) {
    return false;
}
// === Obtener datos del cliente ===
$stmt = db()->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cliente) return false;

$nombres        = $cliente['nombres'];
$apellidos      = $cliente['apellidos'];
$dialCode       = $cliente['dialCode'];
$telefono       = $cliente['telefono'];
$identificacion = $cliente['identificacion'];
$direccion      = $cliente['direccion'];
$planId         = $cliente['plan'];

// === Obtener datos del plan ===
$stmtPlan = db()->prepare("SELECT nombre, precio FROM planes WHERE id = :pid AND borrado = 0");
$stmtPlan->execute([':pid' => $planId]);
$plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

$planNombre = $plan['nombre'] ?? 'Plan sin nombre';
$precioBase = isset($plan['precio']) ? (float)$plan['precio'] : 0.0;

// === Calcular el total con el adicional ===
$totalConAdicional = $porcentajeAdicional > 0 
    ? round($precioBase * (1 + ($porcentajeAdicional / 100)), 2)
    : $precioBase;

// Si MercadoPago devolvió un monto diferente, usamos el real
if ($valorPagado > 0) {
    $totalConAdicional = $valorPagado;
}

// === Registrar factura ===
$fechaFactura = date('Y-m-d');

$stmtFactura = db()->prepare("
    INSERT INTO facturas 
    (fecha, nombre_cliente, idCliente, identificacion, telefono, direccion, detalle, fecha_inicio, fecha_vencimiento, valor, payment_method, bank, user)
    VALUES 
    (:fecha, :nombre_cliente, :idCliente, :identificacion, :telefono, :direccion, :detalle, :fecha_inicio, :fecha_vencimiento, :valor, :payment_method, :bank, :user)
");

// Obtener nombre del usuario que genera la factura

$stmtFactura->execute([
    ':fecha'             => $fechaFactura,
    ':nombre_cliente'    => $nombres . ' ' . $apellidos,
    ':idCliente'         => $id,
    ':identificacion'    => $identificacion,
    ':telefono'          => '+' . $dialCode . ' ' . $telefono,
    ':direccion'         => $direccion,
    ':detalle'           => $planNombre . " (+{$porcentajeAdicional}% pasarela)",
    ':fecha_inicio'      => $pago_plan,
    ':fecha_vencimiento' => $vencimiento_plan,
    ':valor'             => $totalConAdicional,
    ':payment_method'    => $payment_method ?? 'Pago en Línea',
    ':bank'              => $bank ?? 'Mercado Pago',
    ':user' 			 => 'Pasarela de Pago'
]);

$facturaId = db()->lastInsertId();
return $facturaId ?: null;
