<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo json_encode(['status'=>'error', 'message'=>'Error en la conexión: ' . $e->getMessage()]);
    exit;
}

// Verificar que exista una caja abierta para el usuario
$stmtCaja = $pdo->prepare("SELECT * FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
$stmtCaja->execute([':usuario_id' => $id_user]);
$cajaAbierta = $stmtCaja->fetch(PDO::FETCH_ASSOC);

if (!$cajaAbierta) {
    echo json_encode(['status'=>'error', 'message'=>'No hay caja abierta para registrar ingreso.']);
    exit;
}

$caja_id = $cajaAbierta['id'];

// Validar datos del formulario
if(!isset($_POST['detalle']) || !isset($_POST['valor']) || !isset($_POST['metodo'])){
    echo json_encode(['status'=>'error', 'message'=>'Faltan datos obligatorios.']);
    exit;
}

$detalle = trim($_POST['detalle']);
$valor = floatval($_POST['valor']);
$metodo = trim($_POST['metodo']);
$banco = $_POST['banco'] ?? null;

// Validar valores
if($detalle === '' || $valor <= 0){
    echo json_encode(['status'=>'error', 'message'=>'El detalle no puede estar vacío y el valor debe ser mayor que 0.']);
    exit;
}

// Fecha y hora actuales
$fecha = date('Y-m-d');
$hora = date('H:i:s');

// Insertar el ingreso en la tabla ventas
// Asumimos que producto_id es NULL y payment_method será el método de pago real ("Efectivo" o "Transferencia")
$stmtInsert = $pdo->prepare("
    INSERT INTO ventas 
        (caja_id, producto_id, detalle, cantidad, valor, fecha, hora, payment_method, bank)
    VALUES 
        (:caja_id, NULL, :detalle, 1, :valor, :fecha, :hora, :metodo, :banco)
");

if($stmtInsert->execute([
    ':caja_id'  => $caja_id,
    ':detalle'  => $detalle,
    ':valor'    => $valor,
    ':fecha'    => $fecha,
    ':hora'     => $hora,
    ':metodo'   => $metodo,
    ':banco'    => $banco
])) {

    // === Registrar LOG ===
    require_once __DIR__ . '/../inc/log_action.php';
    $desc = json_encode([
        'caja_id'  => $caja_id,
        'detalle'  => $detalle,
        'valor'    => $valor,
        'metodo'   => $metodo,
        'banco'    => $banco,
        'fecha'    => $fecha,
        'hora'     => $hora
    ], JSON_UNESCAPED_UNICODE);
    log_action('Registrar Ingreso', $desc, 'Caja');
    // === Fin LOG ===

    echo json_encode(['status'=>'success', 'message'=>'Ingreso registrado correctamente.']);

} else {
    echo json_encode(['status'=>'error', 'message'=>'Error al registrar el ingreso.']);
}
?>
