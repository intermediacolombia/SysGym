<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../whatsapp/send_ws_alert.php'; // <- Función para enviar WhatsApp

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo json_encode(['status'=>'error', 'message'=>'Error en la conexión']);
    exit;
}

// 1) Verificar caja abierta del usuario actual
$stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
$stmtCaja->execute([':usuario_id' => $id_user]);
$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
if(!$caja){
    echo json_encode(['status'=>'error', 'message'=>'No tienes caja abierta']);
    exit;
}
$caja_id = (int)$caja['id'];

// 2) Validar datos mínimos
$required = ['producto_id','cantidad','precio','coste','detalle','payment_method'];
foreach ($required as $k) {
    if (!isset($_POST[$k])) {
        echo json_encode(['status'=>'error', 'message'=>'Faltan datos: '.$k]);
        exit;
    }
}

$producto_id    = (int)$_POST['producto_id'];
$cantidad_req   = (int)$_POST['cantidad'];
$precio_unit    = (float)$_POST['precio'];
$coste_total    = (float)($_POST['coste']);
$detalle        = trim((string)$_POST['detalle']);
$payment_method = trim((string)$_POST['payment_method']);
$bank           = '';
$valor_override = isset($_POST['valor_override']) ? (int)$_POST['valor_override'] : null;
$skip_stock     = !empty($_POST['skip_stock']);

if ($payment_method === 'Transferencia') {
    if (empty($_POST['bank'])) {
        echo json_encode(['status'=>'error', 'message'=>'Debe seleccionar un banco para la Transferencia']);
        exit;
    }
    $bank = trim((string)$_POST['bank']);
}

$cantidad_final = $skip_stock ? 0 : $cantidad_req;
$coste_efectivo = $skip_stock ? 0.0 : (float)$coste_total;

$stock_actual = null;
$minimo_stock = null;

if (!$skip_stock) {
    $stmtProducto = $pdo->prepare("
        SELECT stock, alerta_stock, minimo_stock, nombre 
        FROM productos 
        WHERE id = :id AND borrado = 0
    ");
    $stmtProducto->execute([':id' => $producto_id]);
    $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);
    if(!$producto){
        echo json_encode(['status'=>'error', 'message'=>'Producto no encontrado']);
        exit;
    }
    $stock_actual  = (int)$producto['stock'];
    $minimo_stock  = (int)$producto['minimo_stock'];
    $alerta_stock  = (int)$producto['alerta_stock'];
    $nombre_producto = $producto['nombre'];

    if ($cantidad_final <= 0) {
        echo json_encode(['status'=>'error', 'message'=>'Cantidad inválida']);
        exit;
    }
    if($stock_actual < $cantidad_final){
        echo json_encode(['status'=>'error', 'message'=>'Stock insuficiente']);
        exit;
    }
}

// Calcular valor de la venta
$valor = ($valor_override !== null)
    ? (($skip_stock || $valor_override >= 0) ? $valor_override : 0)
    : (int)round($cantidad_final * $precio_unit);

// ============================================
// REGISTRAR VENTA
// ============================================
$pdo->beginTransaction();
try {
    $fecha = date('Y-m-d');
    $hora  = date('H:i:s');

    $stmtInsert = $pdo->prepare("
        INSERT INTO ventas 
        (caja_id, producto_id, detalle, cantidad, valor, coste, fecha, hora, payment_method, bank)
        VALUES 
        (:caja_id, :producto_id, :detalle, :cantidad, :valor, :coste, :fecha, :hora, :payment_method, :bank)
    ");
    $stmtInsert->execute([
        ':caja_id'        => $caja_id,
        ':producto_id'    => $producto_id,
        ':detalle'        => $detalle,
        ':cantidad'       => $cantidad_final,
        ':valor'          => $valor,
        ':coste'          => $coste_efectivo,
        ':fecha'          => $fecha,
        ':hora'           => $hora,
        ':payment_method' => $payment_method,
        ':bank'           => $bank
    ]);

    if (!$skip_stock) {
        $stmtUpdate = $pdo->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id");
        $stmtUpdate->execute([':cantidad' => $cantidad_final, ':id' => $producto_id]);
    }

    $pdo->commit();

    $nuevo_stock = $skip_stock ? $stock_actual : ($stock_actual - $cantidad_final);

    
	//Alertas
	
	if (!$skip_stock && $alerta_stock == 1 && $nuevo_stock == $minimo_stock) {
    require_once __DIR__ . '/../whatsapp/send_ws_alert.php';

    $stmtUsuarios = $pdo->query("SELECT nombres, apellidos, dialCode, telefono FROM usuarios WHERE recibir_alerta_stock = 1 AND telefono IS NOT NULL");
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

    $mensaje = "⚠️ ALERTA DE STOCK: El producto '$nombre_producto' ha alcanzado el stock mínimo establecido ($nuevo_stock unidades).";

    foreach ($usuarios as $u) {
        $telefonoCompleto = ltrim($u['dialCode'], '+') . $u['telefono'];
        sendWSAlert($telefonoCompleto, $mensaje);
    }
}

	//fin Alertas
	
	
	
    // Calcular total en caja
    $stmtTotal = $pdo->prepare("SELECT IFNULL(SUM(valor), 0) AS total FROM ventas WHERE caja_id = :caja_id");
    $stmtTotal->execute([':caja_id' => $caja_id]);
    $rowTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $total_caja = $rowTotal ? (int)$rowTotal['total'] : 0;

    echo json_encode([
        'status'       => 'success',
        'message'      => 'Venta registrada correctamente',
        'nuevo_stock'  => $nuevo_stock,
        'total_caja'   => $total_caja
    ]);
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>'Error al registrar la venta: ' . $e->getMessage()]);
}
?>



