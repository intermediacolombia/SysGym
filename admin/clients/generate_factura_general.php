<?php
// generate_factura_general.php

/* ---------------------------------------------------------
   Validación de variables requeridas
--------------------------------------------------------- */
if (!isset($id) || !isset($hoy) ||
    !isset($nombre_cliente) || !isset($identificacion) ||
    !isset($telefono) || !isset($direccion) ||
    !isset($detalle) || !isset($valor)) {
    return false;
}

/* ---------------------------------------------------------
   Variables opcionales seguras
--------------------------------------------------------- */
$payment_method = $payment_method ?? '';
$bank           = $bank ?? '';

/* ---------------------------------------------------------
   Obtener datos del usuario desde sesión
--------------------------------------------------------- */
$nombre   = $_SESSION['user']['nombre']  ?? '';
$apellido = $_SESSION['user']['apellido'] ?? '';

/* ---------------------------------------------------------
   Conexión a la base de datos
--------------------------------------------------------- */
require_once __DIR__ . '/../../inc/config.php';

/* ---------------------------------------------------------
   INSERTAR FACTURA
--------------------------------------------------------- */
$stmt = db()->prepare("
    INSERT INTO facturas 
        (fecha, nombre_cliente, idCliente, identificacion, telefono, direccion, detalle, valor, payment_method, bank, user)
    VALUES 
        (:fecha, :nombre_cliente, :idCliente, :identificacion, :telefono, :direccion, :detalle, :valor, :payment_method, :bank, :user)
");

$stmt->execute([
    ':fecha'          => $hoy,
    ':nombre_cliente' => $nombre_cliente,
    ':idCliente'      => $id,
    ':identificacion' => $identificacion,
    ':telefono'       => $telefono,
    ':direccion'      => $direccion,
    ':detalle'        => $detalle,
    ':valor'          => $valor,
    ':payment_method' => $payment_method,
    ':bank'           => $bank,
    ':user'           => $nombre . ' ' . $apellido
]);

/* ---------------------------------------------------------
   Retornar ID generado
--------------------------------------------------------- */
$facturaId = db()->lastInsertId();
return $facturaId;
?>

