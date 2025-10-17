<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la conexión']);
    exit;
}

// Verificar que el usuario tenga una caja abierta
$stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
$stmtCaja->execute([':usuario_id' => $id_user]);
$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
if (!$caja) {
    echo json_encode(['status' => 'error', 'message' => 'No tienes caja abierta']);
    exit;
}
$caja_id = $caja['id'];

// Validar los datos recibidos
if (
    !isset($_POST['producto_id']) || !isset($_POST['cantidad']) || !isset($_POST['precio']) ||
    !isset($_POST['cliente_id']) || !isset($_POST['valor_pagado']) || !isset($_POST['fecha_limite']) ||
    !isset($_POST['payment_method'])
) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos']);
    exit;
}

$producto_id = $_POST['producto_id'];
$cantidad = intval($_POST['cantidad']);
$precio = floatval($_POST['precio']);
$cliente_id = intval($_POST['cliente_id']);
$valor_pagado = floatval($_POST['valor_pagado']);
$fecha_limite = $_POST['fecha_limite'];
$payment_method = $_POST['payment_method']; // Método de pago seleccionado
$bank = $_POST['bank'] ?? ''; // Banco seleccionado (si aplica)

// Calcular el total de la venta y el crédito restante
$total_venta = $cantidad * $precio;
$credito_restante = $total_venta - $valor_pagado;

if ($credito_restante < 0) {
    echo json_encode(['status' => 'error', 'message' => 'El valor pagado no puede ser mayor al total de la venta']);
    exit;
}

// Verificar el stock disponible del producto
$stmtProducto = $pdo->prepare("SELECT nombre, stock FROM productos WHERE id = :id AND borrado = 0");
$stmtProducto->execute([':id' => $producto_id]);
$producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);
if (!$producto) {
    echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado']);
    exit;
}
if ($producto['stock'] < $cantidad) {
    echo json_encode(['status' => 'error', 'message' => 'Stock insuficiente']);
    exit;
}

// Iniciar transacción
$pdo->beginTransaction();
try {
    // 1. Registrar el crédito en la tabla creditos
    $stmtInsertCredito = $pdo->prepare("
        INSERT INTO creditos (idCliente, fecha, valor, fecha_limite, descripcion, created_at, updated_at)
        VALUES (:idCliente, :fecha, :valor, :fecha_limite, :descripcion, NOW(), NOW())
    ");
    $stmtInsertCredito->execute([
        ':idCliente' => $cliente_id,
        ':fecha' => date('Y-m-d'),
        ':valor' => $credito_restante, // Valor restante del crédito
        ':fecha_limite' => $fecha_limite,
        ':descripcion' => $producto['nombre'] // Nombre del producto
    ]);

    // Obtener el ID del crédito recién creado
    $credito_id = $pdo->lastInsertId();
	
	
	// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
			'credito_id'     => $credito_id,
			'idCliente' => $cliente_id,
			'fecha' => date('Y-m-d'),
			'valor' => $credito_restante, // Valor restante del crédito
			'fecha_limite' => $fecha_limite,
			'descripcion' => $producto['nombre']
			
], JSON_UNESCAPED_UNICODE);
log_action('Registrar Creditos', $desc, 'Caja');
// END LOGS

    // 2. Registrar la venta en la tabla ventas
    $fecha = $hoy;
    //$hora = $hora;
    $detalle = $producto['nombre']; // Usar el nombre del producto como detalle

    $stmtInsertVenta = $pdo->prepare("
        INSERT INTO ventas (caja_id, producto_id, detalle, cantidad, valor, fecha, hora, payment_method, bank, creditoId)
        VALUES (:caja_id, :producto_id, :detalle, :cantidad, :valor, :fecha, :hora, :payment_method, :bank, :creditoId)
    ");
    $stmtInsertVenta->execute([
        ':caja_id' => $caja_id,
        ':producto_id' => $producto_id,
        ':detalle' => $detalle,
        ':cantidad' => $cantidad,
        ':valor' => $valor_pagado, // Solo el valor pagado va a la tabla ventas
        ':fecha' => $fecha,
        ':hora' => $hora,
        ':payment_method' => $payment_method, // Registrar el método de pago seleccionado
        ':bank' => $bank, // Registrar el banco seleccionado (si aplica)
        ':creditoId' => $credito_id // Guardar el ID del crédito
    ]);

    // 3. Actualizar el stock del producto
    $stmtUpdateStock = $pdo->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id");
    $stmtUpdateStock->execute([':cantidad' => $cantidad, ':id' => $producto_id]);

    // Confirmar la transacción
    $pdo->commit();

    // Obtener el nuevo stock del producto
    $stmtNewStock = $pdo->prepare("SELECT stock FROM productos WHERE id = :id");
    $stmtNewStock->execute([':id' => $producto_id]);
    $nuevoProducto = $stmtNewStock->fetch(PDO::FETCH_ASSOC);
    $nuevo_stock = $nuevoProducto ? $nuevoProducto['stock'] : 0;

    echo json_encode([
        'status' => 'success',
        'message' => 'Venta registrada correctamente',
        'nuevo_stock' => $nuevo_stock
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error al registrar la venta: ' . $e->getMessage()]);
}
?>