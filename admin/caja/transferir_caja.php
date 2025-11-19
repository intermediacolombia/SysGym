<?php
require_once __DIR__ . '/../../inc/config.php';

$caja_id = $_POST['caja_id'] ?? 0;
$nuevo_usuario_id = $_POST['nuevo_usuario_id'] ?? 0;

if (!$caja_id || !$nuevo_usuario_id) {
  echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
  exit;
}

try {
  
  $stmt = db()->prepare("UPDATE cajas SET usuario_id = :nuevo WHERE id = :id");
  $stmt->execute([
    ':nuevo' => $nuevo_usuario_id,
    ':id' => $caja_id
  ]);
	
	// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
			'transferida_a' => $nuevo_usuario_id,
    		'caja_id' => $caja_id
			
], JSON_UNESCAPED_UNICODE);
log_action('Transferir Caja', $desc, 'Caja');
// END LOGS

  echo json_encode(['status' => 'success', 'message' => 'Caja transferida correctamente.']);
} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => 'Error al transferir la caja.']);
}
