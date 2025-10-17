<?php
// generate_factura_general.php

// Verificar que existan las variables requeridas
if (!isset($id) || !isset($hoy) || 
    !isset($nombre_cliente) || !isset($identificacion) || 
    !isset($telefono) || !isset($direccion) || 
    !isset($detalle) || !isset($valor)) {
    return false;
}

// Si la conexión no existe, la creamos
if (!isset($pdo)) {
    require_once __DIR__ . '/../../inc/config.php';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        return false;
    }
}

// Insertar la factura en la tabla "facturas" con los campos solicitados
$stmt = $pdo->prepare("INSERT INTO facturas 
    (fecha, nombre_cliente, idCliente, identificacion, telefono, direccion, detalle, valor, payment_method, bank, user)
    VALUES (:fecha, :nombre_cliente, :idCliente, :identificacion, :telefono, :direccion, :detalle, :valor, :payment_method, :bank, :user)");


$stmt->execute([
    ':fecha'           => $hoy,
    ':nombre_cliente'  => $nombre_cliente,
    ':idCliente'       => $id,
    ':identificacion'  => $identificacion,
    ':telefono'        => $telefono,
    ':direccion'       => $direccion,
    ':detalle'         => $detalle,
    ':valor'           => $valor,
    ':payment_method' 	 => $payment_method,
	':bank' 	 		 => $bank,
	':user'				 => $nombre.' '.$apellido
]);



// Puedes devolver el ID de la factura recién creada si lo necesitas
$facturaId = $pdo->lastInsertId();
return $facturaId;



?>
