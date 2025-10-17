<?php 
require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($_POST['id'], $_POST['pago_plan'], $_POST['vencimiento_plan'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos.']);
    exit;
}

$id = trim($_POST['id']);
$newPagoPlan = trim($_POST['pago_plan']);
$newVencimientoPlan = trim($_POST['vencimiento_plan']);

$credit = (isset($_POST['credit']) && $_POST['credit'] === 'true') ? 1 : 0;
$valorPagado = isset($_POST['valorPagado']) ? floatval($_POST['valorPagado']) : null;
$fechaPlazo = isset($_POST['fechaPlazo']) ? trim($_POST['fechaPlazo']) : null;

$payment_method = trim($_POST['paymentMethod'] ?? '');
$bank = trim($_POST['bank'] ?? '');
$splitPayment = isset($_POST['splitPayment']) && $_POST['splitPayment'] === 'true';
$secondPaymentMethod = trim($_POST['secondPaymentMethod'] ?? '');
$secondBank = trim($_POST['secondBank'] ?? '');
$firstPaymentValue = floatval($_POST['first_payment_value'] ?? 0);
$secondPaymentValue = floatval($_POST['second_payment_value'] ?? 0);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtCheck = $pdo->prepare("SELECT pago_plan, vencimiento_plan FROM clientes WHERE id = :id");
    $stmtCheck->execute([':id' => $id]);
    $clientDates = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    $pago_plan = (!empty($clientDates['pago_plan']) && strtotime($newPagoPlan) <= strtotime($clientDates['pago_plan'])) 
                    ? $clientDates['pago_plan'] 
                    : $newPagoPlan;

    $vencimiento_plan = (!empty($clientDates['vencimiento_plan']) && strtotime($newVencimientoPlan) <= strtotime($clientDates['vencimiento_plan'])) 
                    ? $clientDates['vencimiento_plan'] 
                    : $newVencimientoPlan;

    $stmt = $pdo->prepare("UPDATE clientes SET 
        pago_plan = :pago_plan, 
        vencimiento_plan = :vencimiento_plan, 
        estado = 'activo', 
        updated_at = NOW() 
        WHERE id = :id");
    $stmt->execute([
        ':pago_plan' => $pago_plan,
        ':vencimiento_plan' => $vencimiento_plan,
        ':id' => $id
    ]);

    $stmtSelect = $pdo->prepare("SELECT nombres, dialCode, telefono, notificaciones FROM clientes WHERE id = :id");
    $stmtSelect->execute([':id' => $id]);
    $clientData = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    $nombres = $clientData['nombres'];
    $dialCode = $clientData['dialCode'];
    $telefono = $clientData['telefono'];
    $notificaciones = $clientData['notificaciones'];

    $cp_nombres = $nombres;
    $cp_dialCode = $dialCode;
    $cp_telefono = $telefono;
    $cp_pago_plan = $pago_plan;
    $cp_vencimiento_plan = $vencimiento_plan;

    ob_start();
    include_once('generate_factura.php');
    ob_end_clean();

    $stmtFactura = $pdo->query("SELECT MAX(id) as factura_id FROM facturas");
    $facturaData = $stmtFactura->fetch(PDO::FETCH_ASSOC);
    $factura_id = $facturaData ? $facturaData['factura_id'] : null;
    $detalleBase = "Factura #$factura_id";

    //include_once('../caja/register_generic_sale.php');

    if ($splitPayment) {
        registrarVenta($pdo, $id, $firstPaymentValue, $payment_method, $bank, $detalleBase);
        registrarVenta($pdo, $id, $secondPaymentValue, $secondPaymentMethod, $secondBank, $detalleBase);
    } else {
        $stmtPrecio = $pdo->prepare("SELECT precio FROM planes WHERE id = (SELECT plan FROM clientes WHERE id = :id)");
        $stmtPrecio->execute([':id' => $id]);
        $planPrecio = floatval($stmtPrecio->fetchColumn());
        //$valorVenta = $valorPagado ?: $planPrecio;
		
		$valorVenta = isset($_POST['valorPagado']) && $_POST['valorPagado'] !== ''
    ? floatval($_POST['valorPagado'])
    : ($credit ? floatval($valorPagado) : $planPrecio);
		
        registrarVenta($pdo, $id, $valorVenta, $payment_method, $bank, $detalleBase);
    }

    if ($notificaciones == 1 || $notificaciones == 0) {
        ob_start();
        include('../../whatsapp/client-pay.php');
        $clientPayResponse = ob_get_clean();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Factura y ventas registradas correctamente.',
        'nombres' => $nombres,
        'dialCode' => $dialCode,
        'telefono' => $telefono,
        'pago_plan' => $pago_plan,
        'vencimiento_plan' => $vencimiento_plan,
        'client_pay_response' => $clientPayResponse ?? ''
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
    exit;
} catch (Exception $ex) {
    echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
    exit;
}

function registrarVenta($pdo, $clienteId, $valor, $metodo, $banco, $detalle = 'Pago') {
    global $id_user;

    $stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
    $stmtCaja->execute([':usuario_id' => $id_user]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

    if (!$caja) return false;

    $stmtInsert = $pdo->prepare("INSERT INTO ventas (caja_id, detalle, cantidad, valor, payment_method, bank, fecha, hora)
        VALUES (:caja_id, :detalle, :cantidad, :valor, :payment_method, :bank, :fecha, :hora)");

    return $stmtInsert->execute([
        ':caja_id' => $caja['id'],
        ':detalle' => $detalle,
        ':cantidad' => 1,
        ':valor' => $valor,
        ':payment_method' => $metodo,
        ':bank' => $banco,
        ':fecha' => date('Y-m-d'),
        ':hora' => date('H:i:s')
    ]);
}
?>














