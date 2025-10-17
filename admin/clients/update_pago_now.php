<?php
// update_pago_now.php

require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';

// Verificar que se use el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

// Verificar que se reciba el ID del cliente
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Falta el ID del cliente.']);
    exit;
}

$id = trim($_POST['id']);

// Recibir datos opcionales de crédito
$credit = (isset($_POST['credit']) && $_POST['credit'] === 'true') ? 1 : 0;
$valorPagado = isset($_POST['valorPagado']) ? trim($_POST['valorPagado']) : null;
$fechaPlazo = isset($_POST['fechaPlazo']) ? trim($_POST['fechaPlazo']) : null;

// Recibir datos del método de pago y banco
$payment_method = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';
$bank = isset($_POST['bank']) ? trim($_POST['bank']) : '';

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
    exit;
}

// 1. Obtener los datos del cliente (sin modificar las fechas)
$stmtClient = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND borrado = 0");
$stmtClient->execute([':id' => $id]);
$cliente = $stmtClient->fetch(PDO::FETCH_ASSOC);
if (!$cliente) {
    echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
    exit;
}

// Usar las fechas que ya están en la BD
$pago_plan = $cliente['pago_plan'];
$vencimiento_plan = $cliente['vencimiento_plan'];

// 2. Obtener datos del cliente para la factura
$nombres = $cliente['nombres'];
$apellidos = $cliente['apellidos'];
$dialCode = $cliente['dialCode'];
$telefono = $cliente['telefono'];
$identificacion = $cliente['identificacion'];
$direccion = $cliente['direccion'];

// 3. Si el cliente tiene un plan asignado, obtener el nombre del plan
$plan_nombre = "";
if (!empty($cliente['plan'])) {
    $stmtPlan = $pdo->prepare("SELECT nombre FROM planes WHERE id = :plan AND borrado = 0");
    $stmtPlan->execute([':plan' => $cliente['plan']]);
    $planData = $stmtPlan->fetch(PDO::FETCH_ASSOC);
    if ($planData) {
        $plan_nombre = $planData['nombre'];
    }
}
$detalle = $plan_nombre; // Usamos el nombre del plan como detalle

// 4. El valor de la factura será el monto abonado (por ejemplo, para crédito)
$valor_factura = $valorPagado;

// 5. Obtener el usuario de la sesión para incluirlo en la factura
$user = isset($_SESSION['user']) ? $_SESSION['user']['nombre'] . ' ' . $_SESSION['user']['apellido'] : '';

// 6. Definir la fecha actual
$hoy = date('Y-m-d');

// 7. Asignar las variables que usará generate_factura.php
$id = $cliente['id']; // ID del cliente
$nombre_cliente = $nombres . ' ' . $apellidos;
$identificacion = $identificacion;
$telefono = '+' . $dialCode . ' ' . $telefono;
$direccion = $direccion;
$detalle = $detalle;
$valor = $valor_factura;
$payment_method = $payment_method;
$bank = $bank;
$user = $user;

// 8. Incluir generate_factura.php para generar la factura
// Este archivo debe insertar en la tabla "facturas" utilizando las variables definidas
$facturaIdNueva = include('generate_factura.php');
if (!$facturaIdNueva) {
    echo json_encode(['status' => 'error', 'message' => 'Error al generar la factura.']);
    exit;
}

// 9. (Opcional) Si las notificaciones están activadas, incluir client-pay.php
$stmtNoti = $pdo->prepare("SELECT notificaciones FROM clientes WHERE id = :id");
$stmtNoti->execute([':id' => $cliente['id']]);
$notiData = $stmtNoti->fetch(PDO::FETCH_ASSOC);
$notificaciones = $notiData ? $notiData['notificaciones'] : 0;
if ($notificaciones == 1 || $notificaciones == 0) {
    ob_start();
    include('../../whatsapp/client-pay.php');
    $clientPayResponse = ob_get_clean();
}

// 10. Enviar respuesta final en JSON
echo json_encode([
    'status'            => 'success',
    'message'           => 'Factura generada correctamente.',
    'pago_plan'         => $pago_plan,
    'vencimiento_plan'  => $vencimiento_plan,
    'factura_id'        => $facturaIdNueva,
    'client_pay_response' => isset($clientPayResponse) ? $clientPayResponse : ''
]);
exit;
?>





