<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json; charset=UTF-8');

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

// Validar ID
if (empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Falta el ID del cliente.']);
    exit;
}

$id = (int)$_POST['id'];

$credit        = (isset($_POST['credit']) && $_POST['credit'] === 'true') ? 1 : 0;
$valorPagado   = isset($_POST['valorPagado']) ? floatval($_POST['valorPagado']) : null;
$fechaPlazo    = $_POST['fechaPlazo'] ?? null;

$payment_method = trim($_POST['paymentMethod'] ?? '');
$bank           = trim($_POST['bank'] ?? '');

// ===============================
// 1. Obtener cliente
// ===============================
$stmtClient = db()->prepare("SELECT * FROM clientes WHERE id = :id AND borrado = 0");
$stmtClient->execute([':id' => $id]);
$cliente = $stmtClient->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
    exit;
}

$pago_plan        = $cliente['pago_plan'];
$vencimiento_plan = $cliente['vencimiento_plan'];

// ===============================
// 2. Datos para factura
// ===============================
$nombres        = $cliente['nombres'];
$apellidos      = $cliente['apellidos'];
$dialCode       = $cliente['dialCode'];
$telefono       = $cliente['telefono'];
$identificacion = $cliente['identificacion'];
$direccion      = $cliente['direccion'];

// ===============================
// 3. Obtener nombre del plan
// ===============================
$plan_nombre = "";
if (!empty($cliente['plan'])) {
    $tabla = ($cliente['plan_tipo'] ?? 'plan') === 'tiquetera' ? 'tiqueteras' : 'planes';
    $stmtPlan = db()->prepare("SELECT nombre, estado FROM $tabla WHERE id = :plan AND borrado = 0");
    $stmtPlan->execute([':plan' => $cliente['plan']]);
    $planData = $stmtPlan->fetch(PDO::FETCH_ASSOC);
    $plan_nombre = $planData ? $planData['nombre'] : '';

    if ($planData && ($planData['estado'] ?? '') === 'inactivo') {
        echo json_encode(['status' => 'error', 'message' => 'El plan "' . $planData['nombre'] . '" está inactivo. Por favor asigne un plan activo al cliente antes de registrar el pago.']);
        exit;
    }
}

$detalle = $plan_nombre;
$valor   = $valorPagado; // valor real que se factura

$hoy = date('Y-m-d');

$user = isset($_SESSION['user']) 
    ? $_SESSION['user']['nombre'] . ' ' . $_SESSION['user']['apellido']
    : 'Sistema';

// ===============================
// 4. Generar Factura
// ===============================
$nombre_cliente = $nombres . ' ' . $apellidos;
$telefonoFull   = '+' . $dialCode . ' ' . $telefono;

$facturaIdNueva = include('generate_factura.php');

if (!$facturaIdNueva) {
    echo json_encode(['status' => 'error', 'message' => 'Error al generar la factura.']);
    exit;
}

// ===============================
// 5. Registrar venta en caja
// ===============================
try {
    $stmtCaja = db()->prepare("SELECT id FROM cajas WHERE usuario_id = :id AND estado = 1 LIMIT 1");
    $stmtCaja->execute([':id' => $id_user]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

    if ($caja) {
        $stmtVenta = db()->prepare("INSERT INTO ventas
            (caja_id, detalle, cantidad, valor, payment_method, bank, fecha, hora)
            VALUES (:caja, :detalle, 1, :valor, :metodo, :banco, :fecha, :hora)");

        $stmtVenta->execute([
            ':caja'   => $caja['id'],
            ':detalle'=> "Factura #$facturaIdNueva (Pago inmediato)",
            ':valor'  => $valor,
            ':metodo' => $payment_method,
            ':banco'  => $bank,
            ':fecha'  => date('Y-m-d'),
            ':hora'   => date('H:i:s')
        ]);
    }
} catch (Exception $e) {
    // No detener el proceso, solo registrar
    error_log("Error al registrar venta inmediata: " . $e->getMessage());
}

// ===============================
// 6. Notificación por WhatsApp
// ===============================
$stmtNoti = db()->prepare("SELECT notificaciones FROM clientes WHERE id = :id");
$stmtNoti->execute([':id' => $id]);
$notificaciones = $stmtNoti->fetchColumn();

if ($notificaciones === 1 || $notificaciones === 0) {

    $cp_nombres          = $nombres;
    $cp_apellidos        = $apellidos;
    $cp_dialCode         = $dialCode;
    $cp_telefono         = $telefono;
    $cp_pago_plan        = $pago_plan;
    $cp_vencimiento_plan = $vencimiento_plan;
    $facturaId           = $facturaIdNueva;

    ob_start();
    include('../../whatsapp/client-pay.php');
    $clientPayResponse = ob_get_clean();
}

// ===============================
// 7. Logs
// ===============================
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
    'cliente_id'       => $id,
    'valor_pagado'     => $valor,
    'payment_method'   => $payment_method,
    'bank'             => $bank,
    'plan'             => $plan_nombre,
    'factura_id'       => $facturaIdNueva
], JSON_UNESCAPED_UNICODE);

log_action('Pago inmediato', $desc, 'Pagos');

// ===============================
// 8. Respuesta Final
// ===============================
echo json_encode([
    'status'             => 'success',
    'message'            => 'Factura generada correctamente.',
    'pago_plan'          => $pago_plan,
    'vencimiento_plan'   => $vencimiento_plan,
    'factura_id'         => $facturaIdNueva,
    'valor'              => $valor,
    'client_pay_response'=> $clientPayResponse ?? ''
]);
exit;
?>






