<?php
// Incluir la sesión, que ya establece la conexión $pdo y obtiene el usuario
require_once __DIR__ . '/../login/session.php';

// Verificar si ya existe una caja abierta para este usuario (estado == 1)
$sql = "SELECT * FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':usuario_id' => $id_user]);
$cajaAbierta = $stmt->fetch(PDO::FETCH_ASSOC);

// Comparar el estado (si existe) para determinar si ya hay una caja abierta
if (isset($cajaAbierta['estado']) && $cajaAbierta['estado'] == 1) {
    echo "La caja ya está abierta.";
    exit;
}

// Obtener el monto inicial; se espera que venga por POST o puedes asignarlo manualmente
$monto_inicial = isset($_POST['monto_inicial']) ? floatval($_POST['monto_inicial']) : 0;

// Obtener la fecha y la hora actuales
$fecha_apertura = date('Y-m-d');
$hora_apertura  = date('H:i:s');

// Insertar el registro de apertura en la tabla "cajas" con estado = 1 y monto_inicial
$sqlInsert = "INSERT INTO cajas (usuario_id, monto_inicial, fecha_apertura, hora_apertura, estado) VALUES (:usuario_id, :monto_inicial, :fecha_apertura, :hora_apertura, 1)";
$stmtInsert = $pdo->prepare($sqlInsert);

// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
			'usuario_id'     => $id_user,
			'monto_inicial'  => $monto_inicial,
			'fecha_apertura' => $fecha_apertura,
			'hora_apertura'  => $hora_apertura		
], JSON_UNESCAPED_UNICODE);
log_action('Abrir Caja', $desc, 'Caja');
// END LOGS

if ($stmtInsert->execute([
    ':usuario_id'     => $id_user,
    ':monto_inicial'  => $monto_inicial,
    ':fecha_apertura' => $fecha_apertura,
    ':hora_apertura'  => $hora_apertura
])) {
    echo "Caja abierta exitosamente.";
} else {
    echo "Error al abrir la caja.";
}
?>


