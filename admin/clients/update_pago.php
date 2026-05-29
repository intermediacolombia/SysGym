<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'M�todo no permitido.']);
    exit;
}

if (empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Falta el ID del cliente.']);
    exit;
}

$id = (int)$_POST['id'];
$credit = (isset($_POST['credit']) && $_POST['credit'] === 'true');
$valorPagado = isset($_POST['valorPagado']) ? floatval($_POST['valorPagado']) : 0;

$payment_method = trim($_POST['paymentMethod'] ?? '');
$bank = trim($_POST['bank'] ?? '');
$splitPayment = isset($_POST['splitPayment']) && $_POST['splitPayment'] === 'true';
$secondPaymentMethod = trim($_POST['secondPaymentMethod'] ?? '');
$secondBank = trim($_POST['secondBank'] ?? '');
$firstPaymentValue = floatval($_POST['first_payment_value'] ?? 0);
$secondPaymentValue = floatval($_POST['second_payment_value'] ?? 0);

$respetarFechas = isset($_POST['respetarFechas']) && $_POST['respetarFechas'] == '1';

try {

    // Obtener datos del cliente
    $stmt = db()->prepare("
        SELECT nombres, apellidos, dialCode, telefono, notificaciones,
               pago_plan, vencimiento_plan, plan, plan_tipo
        FROM clientes
        WHERE id = :id AND borrado = 0
    ");
    $stmt->execute([':id' => $id]);
    $clienteBase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$clienteBase) {
        echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
        exit;
    }

    // Obtener datos del plan según plan_tipo
    $esTiquetera = ($clienteBase['plan_tipo'] ?? 'plan') === 'tiquetera';
    if ($esTiquetera) {
        $stmtPlan = db()->prepare("SELECT nombre, precio, vigencia AS dias, 0 AS frecuencia FROM tiqueteras WHERE id = :id AND borrado = 0");
    } else {
        $stmtPlan = db()->prepare("SELECT nombre, precio, dias, frecuencia FROM planes WHERE id = :id AND borrado = 0");
    }
    $stmtPlan->execute([':id' => $clienteBase['plan']]);
    $planData = $stmtPlan->fetch(PDO::FETCH_ASSOC);

    if (!$planData) {
        echo json_encode(['status' => 'error', 'message' => 'Plan no encontrado.']);
        exit;
    }

    $cliente = array_merge($clienteBase, $planData);

    // Fechas base
    date_default_timezone_set('America/Bogota');
    $hoy = new DateTime('today');

    $vencAnterior = !empty($cliente['vencimiento_plan']) ? new DateTime($cliente['vencimiento_plan']) : null;
    $planDias = (int)$cliente['dias'];
    $planMeses = (int)$cliente['frecuencia'];
    $planPrecio = (float)$cliente['precio'];

    // Función para calcular fin de plan
    $calcFechaFin = function (DateTime $inicio, int $meses, int $dias): DateTime {
        $fin = clone $inicio;
        if ($meses > 0) {
            $fin->modify("+{$meses} months");
            $fin->modify('-1 day');
        }
        if ($dias > 0) {
            $fin->modify("+{$dias} days");
        }
        return $fin;
    };

    // Mantener fechas existentes (solo planes normales)
    if (!$esTiquetera && $respetarFechas && !empty($cliente['pago_plan']) && !empty($cliente['vencimiento_plan'])) {
        $pago_plan = $cliente['pago_plan'];
        $vencimiento_plan = $cliente['vencimiento_plan'];

    } else {

        if ($esTiquetera) {
            // Tiqueteras: siempre arrancan hoy sin importar nada
            $inicio = clone $hoy;
        } else {
            $estaVencidoOVenceHoy = (!$vencAnterior) || ($vencAnterior <= $hoy);
            $inicio = $estaVencidoOVenceHoy ? clone $hoy : (clone $vencAnterior)->modify('+1 day');
        }

        $nuevoVencimiento = $calcFechaFin($inicio, $planMeses, $planDias);

        $pago_plan = $hoy->format('Y-m-d');
        $vencimiento_plan = $nuevoVencimiento->format('Y-m-d');
    }

    // Actualizar cliente
    $stmt = db()->prepare("
    UPDATE clientes SET 
    pago_plan = :pago_plan,
    vencimiento_plan = :vencimiento_plan,
    estado = 'activo',
    congelado = 0,
    updated_at = NOW()
    WHERE id = :id
");
$stmt->execute([
    ':pago_plan' => $pago_plan,
    ':vencimiento_plan' => $vencimiento_plan,
    ':id' => $id
]);

// A�ADIR: Pasar fechaPlazo desde el POST
$fechaPlazo = isset($_POST['fechaPlazo']) ? trim($_POST['fechaPlazo']) : null;

// Generar factura
ob_start();
include_once('generate_factura.php');
ob_end_clean();
	
	
    // Obtener ID factura
    $stmtFactura = db()->query("SELECT MAX(id) AS factura_id FROM facturas");
    $facturaData = $stmtFactura->fetch(PDO::FETCH_ASSOC);
    $factura_id = $facturaData['factura_id'] ?? null;

    // Si es tiquetera, vincular la factura activa para el conteo de entradas
    if ($esTiquetera && $factura_id) {
        $stmtTiqFact = db()->prepare("UPDATE clientes SET tiquetera_factura_id = :fid WHERE id = :id");
        $stmtTiqFact->execute([':fid' => $factura_id, ':id' => $id]);
    }

    $detalleBase = "Factura #$factura_id";

    // REGISTRO DE VENTAS
    if ($splitPayment) {
        registrarVenta($id, $firstPaymentValue, $payment_method, $bank, $detalleBase);
        registrarVenta($id, $secondPaymentValue, $secondPaymentMethod, $secondBank, $detalleBase);
    } else {
        $valorVenta = $credit ? $valorPagado : $planPrecio;
        registrarVenta($id, $valorVenta, $payment_method, $bank, $detalleBase);
    }

    // Notificaciones WhatsApp
    if ((int)$cliente['notificaciones'] === 1) {

        $cp_nombres = $cliente['nombres'];
        $cp_apellidos = $cliente['apellidos'] ?? '';
        $cp_dialCode = $cliente['dialCode'];
        $cp_telefono = $cliente['telefono'];
        $cp_pago_plan = $pago_plan;
        $cp_vencimiento_plan = $vencimiento_plan;
        $facturaId = $factura_id;

        ob_start();
        include('../../whatsapp/client-pay.php');
        $clientPayResponse = ob_get_clean();
    }

    // Determinar valor real pagado
    if ($splitPayment) {
        $valorRegistrado = $firstPaymentValue + $secondPaymentValue;
    } else {
        $valorRegistrado = $credit ? $valorPagado : $planPrecio;
    }

    // LOGS
    require_once __DIR__ . '/../inc/log_action.php';
    $desc = json_encode([
        'cliente_id' => $id,
        'pago_plan' => $pago_plan,
        'vencimiento_plan' => $vencimiento_plan,
        'metodo_pago' => $payment_method,
        'banco' => $bank,
        'valor_pagado' => $valorRegistrado
    ], JSON_UNESCAPED_UNICODE);

    log_action('Marcar pago', $desc, 'Pagos');

    echo json_encode([
        'status' => 'success',
        'message' => 'Pago registrado correctamente.',
        'nombres' => $cliente['nombres'],
        'dialCode' => $cliente['dialCode'],
        'telefono' => $cliente['telefono'],
        'pago_plan' => $pago_plan,
        'vencimiento_plan' => $vencimiento_plan,
        'valor_pagado' => $valorRegistrado,
        'client_pay_response' => $clientPayResponse ?? ''
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}


/* ====================================
   FUNCI�N CORREGIDA (YA NO RECIBE db())
   ==================================== */
function registrarVenta($clienteId, $valor, $metodo, $banco, $detalle = 'Pago') {
    global $id_user;

    $stmtCaja = db()->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
    $stmtCaja->execute([':usuario_id' => $id_user]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

    if (!$caja) return false;

    $stmtInsert = db()->prepare("INSERT INTO ventas 
        (caja_id, detalle, cantidad, valor, payment_method, bank, fecha, hora)
        VALUES (:caja_id, :detalle, 1, :valor, :payment_method, :bank, :fecha, :hora)");

    return $stmtInsert->execute([
        ':caja_id' => $caja['id'],
        ':detalle' => $detalle,
        ':valor' => $valor,
        ':payment_method' => $metodo,
        ':bank' => $banco,
        ':fecha' => date('Y-m-d'),
        ':hora' => date('H:i:s')
    ]);
}
?>


















