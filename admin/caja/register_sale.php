<?php

require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

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
$cantidad_req   = (int)$_POST['cantidad'];                 // cantidad solicitada (antes de aplicar skip_stock)
$precio_unit    = (float)$_POST['precio'];                 // precio unitario
$coste_total    = (float)($_POST['coste']);                // OJO: ya te llegaba coste*cantidad; lo respetamos para venta normal
$detalle        = trim((string)$_POST['detalle']);
$payment_method = trim((string)$_POST['payment_method']);
$bank           = '';

// Opcionales para pago dividido
$valor_override = isset($_POST['valor_override']) ? (int)$_POST['valor_override'] : null;
$skip_stock     = !empty($_POST['skip_stock']); // true si viene 1 / "1"

// 3) Validación método de pago / banco
if ($payment_method === 'Transferencia') {
    if (!isset($_POST['bank']) || $_POST['bank'] === '') {
        echo json_encode(['status'=>'error', 'message'=>'Debe seleccionar un banco para la Transferencia']);
        exit;
    }
    $bank = trim((string)$_POST['bank']);
} else {
    $bank = ''; // aseguramos vacío si no es transferencia
}

// 4) Lógica de stock según tipo de movimiento
//    - skip_stock = true  -> no verifica stock ni descuenta, fuerza cantidad=0 y coste=0
//    - skip_stock = false -> venta normal, valida stock y descuenta cantidad_req
$cantidad_final = $skip_stock ? 0 : $cantidad_req;

// Normalizamos coste: si no afecta stock, no afectamos coste
if ($skip_stock) {
    $coste_efectivo = 0.0;
} else {
    // Mantener tu lógica: coste ya viene con coste*cantidad
    $coste_efectivo = (float)$coste_total;
}

// 5) Obtener stock actual sólo si vamos a afectar stock
$stock_actual = null;
if (!$skip_stock) {
    $stmtProducto = $pdo->prepare("SELECT stock FROM productos WHERE id = :id AND borrado = 0");
    $stmtProducto->execute([':id' => $producto_id]);
    $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);
    if(!$producto){
        echo json_encode(['status'=>'error', 'message'=>'Producto no encontrado']);
        exit;
    }
    $stock_actual = (int)$producto['stock'];

    if ($cantidad_final <= 0) {
        echo json_encode(['status'=>'error', 'message'=>'Cantidad inválida']);
        exit;
    }
    if($stock_actual < $cantidad_final){
        echo json_encode(['status'=>'error', 'message'=>'Stock insuficiente']);
        exit;
    }
}

// 6) Calcular valor (monto) de la venta
if ($valor_override !== null) {
    // Con override: si es la primera parte (afecta stock), que no supere el total teórico
    if (!$skip_stock) {
        $max_valor = (int)round($cantidad_final * $precio_unit);
        if ($valor_override < 0 || $valor_override > $max_valor) {
            echo json_encode(['status'=>'error', 'message'=>'Valor inválido para el primer pago.']);
            exit;
        }
    }
    $valor = (int)$valor_override;
} else {
    // Sin override: usa cantidad * precio (sólo aplica a venta normal)
    $valor = (int)round($cantidad_final * $precio_unit);
}

// 7) Registrar transacción
$pdo->beginTransaction();
try {
    $fecha = date('Y-m-d');
    $hora  = date('H:i:s');

    // Insert en ventas
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
        ':cantidad'       => $cantidad_final,   // 0 si skip_stock
        ':valor'          => $valor,
        ':coste'          => $coste_efectivo,   // 0 si skip_stock
        ':fecha'          => $fecha,
        ':hora'           => $hora,
        ':payment_method' => $payment_method,
        ':bank'           => $bank
    ]);

    // Descontar stock sólo si corresponde
    if (!$skip_stock) {
        $stmtUpdate = $pdo->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id");
        $stmtUpdate->execute([':cantidad' => $cantidad_final, ':id' => $producto_id]);
    }

    $pdo->commit();

    // 8) Obtener nuevo stock (si afectó), si no, mantener el actual
    if ($skip_stock) {
        // Si no hubo afectación, devolvemos el stock vigente (consultamos para consistencia)
        $stmtNew = $pdo->prepare("SELECT stock FROM productos WHERE id = :id");
        $stmtNew->execute([':id' => $producto_id]);
        $nuevo = $stmtNew->fetch(PDO::FETCH_ASSOC);
        $nuevo_stock = $nuevo ? (int)$nuevo['stock'] : 0;
    } else {
        // Si afectó, podemos calcular sin otra query:
        $nuevo_stock = ($stock_actual !== null) ? ($stock_actual - $cantidad_final) : 0;
    }

    // 9) Total en caja para esta caja
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



