<?php 
// generate_factura.php

if (!isset($id) || !isset($pago_plan) || !isset($vencimiento_plan)) {
    return false;
}

// Asignar valores por defecto a las variables opcionales
$credit = isset($credit) ? $credit : 0;
$valorPagado = isset($valorPagado) ? floatval($valorPagado) : null; // Convertir a número

// Nuevo: leer períodos desde POST o variable global
$periodosPagar = isset($_POST['periodos_pagar']) ? intval($_POST['periodos_pagar']) : 1;
if ($periodosPagar < 1) $periodosPagar = 1;



$fechaPlazo = isset($fechaPlazo) && !empty($fechaPlazo) ? $fechaPlazo : date('Y-m-d', strtotime('+30 days'));

// Asegurarse de que exista la conexión PDO
if (!isset($pdo)) {
    require_once __DIR__ . '/../../inc/config.php';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        return false;
    }
}

// Obtener datos del cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
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

// Obtener el precio del plan
$valor = 0;
$detalle = "Sin Plan";
if (!empty($planAssigned)) {
    $stmtPlan = $pdo->prepare("SELECT nombre, precio FROM planes WHERE id = :plan AND borrado = 0");
    $stmtPlan->execute([':plan' => $planAssigned]);
    $planData = $stmtPlan->fetch(PDO::FETCH_ASSOC);
    if ($planData) {
        $detalle = $planData['nombre'];
        $valor = floatval($planData['precio']);
    }
}
// Multiplicar por los períodos seleccionados
$planTotal = $valor * $periodosPagar;
$planNombre = $detalle . " - " . $periodosPagar . " período(s)";


// Determinar el monto a registrar en la factura y el saldo restante
$facturaValor = $credit ? $valorPagado : $planTotal;
$remaining = $credit ? ($planTotal - $valorPagado) : 0;

// Definir fechas
$fechaFactura = $hoy;
$fechaInicio  = $pago_plan;

// Asegurarse de que $payment_method esté definido (si no, se asigna un valor vacío)
//$payment_method = isset($payment_method) ? $payment_method : '';
//$bank_selection = isset($_POST['bank_selection']) ? trim($_POST['bank_selection']) : '';

// Insertar la factura en la tabla facturas, incluyendo el campo payment_method
$stmtFactura = $pdo->prepare("INSERT INTO facturas 
    (fecha, nombre_cliente, idCliente, identificacion, telefono, direccion, detalle, fecha_inicio, fecha_vencimiento, valor, payment_method, bank, user)
    VALUES (:fecha, :nombre_cliente, :idCliente, :identificacion, :telefono, :direccion, :detalle, :fecha_inicio, :fecha_vencimiento, :valor, :payment_method, :bank, :user)");

$stmtFactura->execute([
    ':fecha'             => $fechaFactura,
    ':nombre_cliente'    => $nombres.' '.$apellidos,
    ':idCliente'         => $id,
    ':identificacion'    => $identificacion,
    ':telefono'          => '+'.$dialCode.' '.$telefono,
    ':direccion'         => $direccion,
    ':detalle' => "Pago de " . $planNombre,
    ':fecha_inicio'      => $fechaInicio,
    ':fecha_vencimiento' => $vencimiento_plan,
    ':valor'             => $facturaValor,
    ':payment_method' 	 => $payment_method,
	':bank' 	 		 => $bank,
	':user'				 => $nombre.' '.$apellido

]);

// Obtener el id de la factura recién creada
$facturaId = $pdo->lastInsertId();
$creditoId = null;
//include('../caja/register_generic_sale.php');
// Si se seleccionó crédito, insertar el crédito, incluso si el pago inicial es 0
if ($credit == 1 && $remaining > 0) {
    $stmtCredito = $pdo->prepare("INSERT INTO creditos 
        (idCliente, factura_id, fecha, valor, fecha_limite, estado, descripcion)
        VALUES (:idCliente, :factura_id, :fecha, :valor, :fecha_limite, 0, :descripcion)");
    $stmtCredito->execute([
        ':idCliente'    => $id,
        ':factura_id'   => $facturaId,
        ':fecha'        => $hoy,
        ':valor'        => $remaining,
        ':fecha_limite' => $fechaPlazo,
        ':descripcion'  => $planNombre
    ]);
    $creditoId = $pdo->lastInsertId();

    // Actualizar la factura con el id del crédito
    $stmtUpdate = $pdo->prepare("UPDATE facturas SET credito_id = :credito_id WHERE id = :facturaId");
    $stmtUpdate->execute([
        ':credito_id' => $creditoId,
        ':facturaId'  => $facturaId
    ]);
}

return true;
?>





