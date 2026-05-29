<?php
// generate_factura.php

/* ---------------------------------------------------------
   Validación inicial
--------------------------------------------------------- */
if (!isset($id) || !isset($pago_plan) || !isset($vencimiento_plan)) {
    return false;
}

// Variables opcionales con valores por defecto
$credit        = $credit        ?? 0;
$valorPagado   = isset($valorPagado) ? floatval($valorPagado) : null;
$fechaPlazo    = isset($fechaPlazo) && !empty($fechaPlazo)
                    ? $fechaPlazo
                    : date('Y-m-d', strtotime('+30 days'));

// ---------------------------------------------------------
// Asegurar conexión con PDO
// ---------------------------------------------------------
require_once __DIR__ . '/../../inc/config.php';

// ---------------------------------------------------------
// Datos del usuario que genera la factura
// ---------------------------------------------------------
$nombre   = $_SESSION['user']['nombre']  ?? '';
$apellido = $_SESSION['user']['apellido'] ?? '';

$payment_method = $payment_method ?? '';
$bank           = $bank           ?? '';

/* ---------------------------------------------------------
   Obtener datos del cliente
--------------------------------------------------------- */
$stmt = db()->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->execute([':id' => $id]);
$clientData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clientData) {
    return false;
}

$nombres        = $clientData['nombres'];
$apellidos      = $clientData['apellidos'];
$dialCode       = $clientData['dialCode'];
$telefono       = $clientData['telefono'];
$identificacion = $clientData['identificacion'];
$direccion      = $clientData['direccion'];
$planAssigned   = $clientData['plan'];

/* ---------------------------------------------------------
   Obtener precio y nombre del plan
--------------------------------------------------------- */
$valor = 0;
$detalle = "Sin Plan";

if (!empty($planAssigned)) {
    $esTiqueteraFact = ($clientData['plan_tipo'] ?? 'plan') === 'tiquetera';
    $tablaFact = $esTiqueteraFact ? 'tiqueteras' : 'planes';
    $stmtPlan = db()->prepare("SELECT nombre, precio FROM $tablaFact WHERE id = :plan AND borrado = 0");
    $stmtPlan->execute([':plan' => $planAssigned]);
    $planData = $stmtPlan->fetch(PDO::FETCH_ASSOC);

    if ($planData) {
        $detalle = $planData['nombre'];
        $valor   = floatval($planData['precio']);
    }
}

$planTotal = $valor;
$planNombre = $detalle;

/* ---------------------------------------------------------
   Determinar valor de factura
--------------------------------------------------------- */
$facturaValor = ($credit == 1) ? $valorPagado : $planTotal;
$remaining    = ($credit == 1) ? ($planTotal - $valorPagado) : 0;

/* ---------------------------------------------------------
   Fechas
--------------------------------------------------------- */
$fechaFactura = date('Y-m-d');

// Convertir DateTime a string si aplica
foreach (['hoy', 'pago_plan', 'vencimiento_plan', 'fechaPlazo'] as $var) {
    if (isset(${$var}) && ${$var} instanceof DateTime) {
        ${$var} = ${$var}->format('Y-m-d');
    }
}

/* ---------------------------------------------------------
   Insertar FACTURA
--------------------------------------------------------- */
$stmtFactura = db()->prepare("
    INSERT INTO facturas 
        (fecha, nombre_cliente, idCliente, identificacion, telefono, direccion, detalle, 
         fecha_inicio, fecha_vencimiento, valor, payment_method, bank, user)
    VALUES 
        (:fecha, :nombre_cliente, :idCliente, :identificacion, :telefono, :direccion, :detalle,
         :fecha_inicio, :fecha_vencimiento, :valor, :payment_method, :bank, :user)
");

$stmtFactura->execute([
    ':fecha'             => $fechaFactura,
    ':nombre_cliente'    => $nombres . ' ' . $apellidos,
    ':idCliente'         => $id,
    ':identificacion'    => $identificacion,
    ':telefono'          => '+' . $dialCode . ' ' . $telefono,
    ':direccion'         => $direccion,
    ':detalle'           => $planNombre,
    ':fecha_inicio'      => $pago_plan,
    ':fecha_vencimiento' => $vencimiento_plan,
    ':valor'             => $facturaValor,
    ':payment_method'    => $payment_method,
    ':bank'              => $bank,
    ':user'              => $nombre . ' ' . $apellido
]);

$facturaId = db()->lastInsertId();
$creditoId = null;

/* ---------------------------------------------------------
   Insertar crédito si aplica
--------------------------------------------------------- */
if ($credit == 1 && $remaining > 0) {

    $stmtCredito = db()->prepare("
        INSERT INTO creditos 
            (idCliente, factura_id, fecha, valor, fecha_limite, estado, descripcion)
        VALUES 
            (:idCliente, :factura_id, :fecha, :valor, :fecha_limite, 0, :descripcion)
    ");

    $stmtCredito->execute([
        ':idCliente'    => $id,
        ':factura_id'   => $facturaId,
        ':fecha'        => $fechaFactura,
        ':valor'        => $remaining,
        ':fecha_limite' => $fechaPlazo,
        ':descripcion'  => $planNombre
    ]);

    $creditoId = db()->lastInsertId();

    // Asociar crédito a la factura
    $stmtUpdate = db()->prepare("
        UPDATE facturas SET credito_id = :credito_id WHERE id = :facturaId
    ");
    $stmtUpdate->execute([
        ':credito_id' => $creditoId,
        ':facturaId'  => $facturaId
    ]);
}

/* ---------------------------------------------------------
   Retornar el ID de la factura creada
--------------------------------------------------------- */
return $facturaId ?? null;

?>






