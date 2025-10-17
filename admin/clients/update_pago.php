<?php
require_once __DIR__ . '/../login/session.php'; // Ya maneja la sesión
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
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
$respetarFechas = isset($_POST['respetarFechas']) && $_POST['respetarFechas'] == '1'; // 👈 nuevo campo

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // === Obtener datos del cliente y su plan ===
    $stmt = $pdo->prepare("
        SELECT c.nombres, c.apellidos, c.dialCode, c.telefono, c.notificaciones,
               c.pago_plan, c.vencimiento_plan, c.plan,
               p.precio, p.dias, p.frecuencia
        FROM clientes c
        INNER JOIN planes p ON p.id = c.plan
        WHERE c.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
        exit;
    }

    // === Fechas base ===
    date_default_timezone_set('America/Bogota');
    $hoy = new DateTime('today');

    $vencAnterior = !empty($cliente['vencimiento_plan']) ? new DateTime($cliente['vencimiento_plan']) : null;
    $planDias = (int)$cliente['dias'];
    $planMeses = (int)$cliente['frecuencia'];
    $planPrecio = (float)$cliente['precio'];

    // === Lógica para respetar fechas guardadas ===
    if ($respetarFechas && !empty($cliente['pago_plan']) && !empty($cliente['vencimiento_plan'])) {
        // ✅ Mantener las fechas actuales
        $pago_plan = $cliente['pago_plan'];
        $vencimiento_plan = $cliente['vencimiento_plan'];
    } else {
        // === Calcular nuevo vencimiento (inclusivo, considerando meses y días) ===
        $calcFechaFin = function (DateTime $inicio, int $meses, int $dias): DateTime {
            $fin = clone $inicio;
            if ($meses > 0) {
                $fin->modify("+{$meses} months");
                $fin->modify('-1 day'); // Inclusivo al mes
            }
            if ($dias > 0) {
                // Si además tiene días, se suman, pero se respeta la inclusividad
                $fin->modify('+' . ($dias) . ' days');
            }
            return $fin;
        };

        $estaVencidoOVenceHoy = (!$vencAnterior) || ($vencAnterior <= $hoy);

        if ($estaVencidoOVenceHoy) {
            // Si el plan está vencido o vence hoy → se renueva desde hoy
            $inicio = clone $hoy;
        } else {
            // Pago anticipado → se renueva desde el día siguiente al vencimiento actual
            $inicio = clone $vencAnterior;
            $inicio->modify('+1 day');
        }

        $nuevoVencimiento = $calcFechaFin($inicio, $planMeses, $planDias);

        $pago_plan = $hoy->format('Y-m-d');
        $vencimiento_plan = $nuevoVencimiento->format('Y-m-d');
    }

    // === Actualizar cliente ===
    $stmt = $pdo->prepare("UPDATE clientes SET 
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

    // === Registrar factura ===
    ob_start();
    include_once('generate_factura.php');
    ob_end_clean();

    $stmtFactura = $pdo->query("SELECT MAX(id) as factura_id FROM facturas");
    $facturaData = $stmtFactura->fetch(PDO::FETCH_ASSOC);
    $factura_id = $facturaData ? $facturaData['factura_id'] : null;
    $detalleBase = "Factura #$factura_id";

    // === Registrar venta ===
    if ($splitPayment) {
        registrarVenta($pdo, $id, $firstPaymentValue, $payment_method, $bank, $detalleBase);
        registrarVenta($pdo, $id, $secondPaymentValue, $secondPaymentMethod, $secondBank, $detalleBase);
    } else {
        $valorVenta = $credit ? $valorPagado : $planPrecio;
        registrarVenta($pdo, $id, $valorVenta, $payment_method, $bank, $detalleBase);
    }

    // === Notificar por WhatsApp (si aplica) ===
    if ((int)$cliente['notificaciones'] === 1) {

        // Variables esperadas por client-pay.php
        $cp_nombres          = $cliente['nombres'];
        $cp_apellidos        = $cliente['apellidos'] ?? '';
        $cp_dialCode         = $cliente['dialCode'];
        $cp_telefono         = $cliente['telefono'];
        $cp_pago_plan        = $pago_plan;
        $cp_vencimiento_plan = $vencimiento_plan;
        $facturaId           = $factura_id;

        ob_start();
        include('../../whatsapp/client-pay.php');
        $clientPayResponse = ob_get_clean();

        // Log opcional (para debug)
        file_put_contents(__DIR__ . '/whatsapp_log.txt',
            date('Y-m-d H:i:s') . " | WS Pago cliente $id | " . $clientPayResponse . "\n",
            FILE_APPEND
        );
    }
	
//LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'cliente_id' => $id,
    'pago_plan' => $pago_plan,
    'vencimiento_plan' => $vencimiento_plan,
    'metodo_pago' => $payment_method,
    'banco' => $bank,
    'valor_pagado' => $valorPagado
], JSON_UNESCAPED_UNICODE);

log_action('Marcar pago', $desc, 'Pagos');
	
//End LOGS	

    echo json_encode([
        'status' => 'success',
        'message' => 'Pago registrado correctamente.',
        'nombres' => $cliente['nombres'],
        'dialCode' => $cliente['dialCode'],
        'telefono' => $cliente['telefono'],
        'pago_plan' => $pago_plan,
        'vencimiento_plan' => $vencimiento_plan,
        'valor_pagado' => $valorPagado,
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

// === Función para registrar la venta ===
function registrarVenta($pdo, $clienteId, $valor, $metodo, $banco, $detalle = 'Pago') {
    global $id_user;

    $stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
    $stmtCaja->execute([':usuario_id' => $id_user]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
    if (!$caja) return false;

    $stmtInsert = $pdo->prepare("INSERT INTO ventas 
        (caja_id, detalle, cantidad, valor, payment_method, bank, fecha, hora)
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

















