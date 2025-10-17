<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo json_encode(['status'=>'error', 'message'=>'Error en la conexión']);
    exit;
}

// Verificar que el usuario tenga una caja abierta
$stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
$stmtCaja->execute([':usuario_id' => $id_user]);
$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
if(!$caja){
    echo json_encode(['status'=>'error', 'message'=>'No tienes caja abierta']);
    exit;
}
$caja_id = $caja['id'];

// Validar los datos recibidos
if(!isset($_POST['producto_id']) || !isset($_POST['cantidad']) || !isset($_POST['precio']) || !isset($_POST['coste']) || !isset($_POST['detalle'])){
    echo json_encode(['status'=>'error', 'message'=>'Faltan datos']);
    exit;
}

$producto_id = $_POST['producto_id'];
$cantidad = intval($_POST['cantidad']);
$precio = floatval($_POST['precio']);
$coste = floatval($_POST['coste'] * $_POST['cantidad']);
$detalle = trim($_POST['detalle']);

// Validar el método de pago
if (!isset($_POST['payment_method'])) {
    echo json_encode(['status'=>'error', 'message'=>'Falta el método de pago']);
    exit;
}
$payment_method = $_POST['payment_method'];
$bank = '';
if ($payment_method === 'Transferencia') {
    if (!isset($_POST['bank']) || empty($_POST['bank'])) {
       echo json_encode(['status'=>'error', 'message'=>'Debe seleccionar un banco para la Transferencia']);
       exit;
    }
    $bank = $_POST['bank'];
}

// Verificar el stock disponible del producto
$stmtProducto = $pdo->prepare("SELECT stock FROM productos WHERE id = :id AND borrado = 0");
$stmtProducto->execute([':id' => $producto_id]);
$producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);
if(!$producto){
    echo json_encode(['status'=>'error', 'message'=>'Producto no encontrado']);
    exit;
}
if($producto['stock'] < $cantidad){
    echo json_encode(['status'=>'error', 'message'=>'Stock insuficiente']);
    exit;
}

// Iniciar transacción
$pdo->beginTransaction();
try {
    // Registrar la venta en la tabla ventas
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    $valor = $cantidad * $precio;
    
    $stmtInsert = $pdo->prepare("INSERT INTO ventas (caja_id, producto_id, detalle, cantidad, valor, coste, fecha, hora, payment_method, bank) VALUES (:caja_id, :producto_id, :detalle, :cantidad, :valor, :coste, :fecha, :hora, :payment_method, :bank)");
    $stmtInsert->execute([
        ':caja_id'      => $caja_id,
        ':producto_id'  => $producto_id,
        ':detalle'      => $detalle,
        ':cantidad'     => $cantidad,
        ':valor'        => $valor,
		':coste'        => $coste,
        ':fecha'        => $fecha,
        ':hora'         => $hora,
        ':payment_method' => $payment_method,
        ':bank'         => $bank
    ]);
    
    // Actualizar el stock del producto
    $stmtUpdate = $pdo->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id");
    $stmtUpdate->execute([':cantidad' => $cantidad, ':id' => $producto_id]);
    
    $pdo->commit();
    
    // Obtener el nuevo stock del producto
    $stmtNew = $pdo->prepare("SELECT stock FROM productos WHERE id = :id");
    $stmtNew->execute([':id' => $producto_id]);
    $nuevoProducto = $stmtNew->fetch(PDO::FETCH_ASSOC);
    $nuevo_stock = $nuevoProducto ? $nuevoProducto['stock'] : 0;
    
    // Calcular el total en caja
    $stmtTotal = $pdo->prepare("SELECT IFNULL(SUM(valor), 0) as total FROM ventas WHERE caja_id = :caja_id");
    $stmtTotal->execute([':caja_id' => $caja_id]);
    $rowTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $total_caja = $rowTotal ? $rowTotal['total'] : 0;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Venta registrada correctamente',
        'nuevo_stock' => $nuevo_stock,
        'total_caja' => $total_caja
    ]);
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>'Error al registrar la venta: ' . $e->getMessage()]);
}
?>


