<?php
require_once __DIR__ . '/../login/session.php';

require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');

// Validar que se proporcione un ID de caja
if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID de caja no proporcionado.']);
    exit;
}

$caja_id = intval($_GET['id']);
if ($caja_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de caja inválido.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// Primero, obtener el usuario dueño de la caja y el estado actual de la misma
$stmtCaja = $pdo->prepare("SELECT usuario_id, estado FROM cajas WHERE id = :id");
$stmtCaja->execute([':id' => $caja_id]);
$cajaData = $stmtCaja->fetch(PDO::FETCH_ASSOC);
if (!$cajaData) {
    echo json_encode(['status' => 'error', 'message' => 'Caja no encontrada.']);
    exit;
}

$usuario_id = $cajaData['usuario_id'];

// Si la caja ya está abierta, no es necesario reabrirla
if ($cajaData['estado'] == 1) {
    echo json_encode(['status' => 'error', 'message' => 'La caja ya está abierta.']);
    exit;
}

// Verificar si el usuario dueño de la caja ya tiene alguna caja abierta
$stmtCheck = $pdo->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1");
$stmtCheck->execute([':usuario_id' => $usuario_id]);
$openCaja = $stmtCheck->fetch(PDO::FETCH_ASSOC);
if ($openCaja) {
    echo json_encode(['status' => 'error', 'message' => 'El usuario ya tiene una caja abierta, debe cerrar la caja abierta primero.']);
    exit;
}

// Proceder a abrir la caja actual actualizando su estado a 1
$stmtUpdate = $pdo->prepare("UPDATE cajas SET estado = 1 WHERE id = :id");
if ($stmtUpdate->execute([':id' => $caja_id])) {
    echo json_encode(['status' => 'success', 'message' => 'Caja abierta exitosamente.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al abrir la caja.']);
}
?>




