<?php
require_once __DIR__ . '/../../inc/config.php';

$origen = $_POST['origen_id'] ?? null;
$destino = $_POST['destino_id'] ?? null;

if (!$origen || !$destino) {
  echo json_encode(['status' => 'error', 'message' => 'IDs inválidos']);
  exit;
}

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Obtener plan y fechas del cliente origen
  $stmt = $pdo->prepare("SELECT plan, pago_plan, vencimiento_plan FROM clientes WHERE id = ?");
  $stmt->execute([$origen]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$data || !$data['plan']) {
    echo json_encode(['status' => 'error', 'message' => 'El cliente origen no tiene plan.']);
    exit;
  }

  // Transferir al cliente destino
  $stmt = $pdo->prepare("
    UPDATE clientes 
    SET estado = 'activo', plan = :plan, pago_plan = :pago, vencimiento_plan = :venc
    WHERE id = :dest
  ");
  $stmt->execute([
    ':plan' => $data['plan'],
    ':pago' => $data['pago_plan'],
    ':venc' => $data['vencimiento_plan'],
    ':dest' => $destino
  ]);

  // Eliminar el plan del cliente original (opcional)
  $pdo->prepare("UPDATE clientes SET estado = 'inactivo', plan = NULL, pago_plan = NULL, vencimiento_plan = NULL WHERE id = ?")->execute([$origen]);
	
	
// LOGS
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
					'origen' => $origen,
					'destino' => $destino,
					'plan' => $data['plan'],
					'pago' => $data['pago_plan'],
					'venc' => $data['vencimiento_plan']
			
], JSON_UNESCAPED_UNICODE);
log_action('Transferir Plan', $desc, 'Clientes');
// END LOGS

  echo json_encode(['status' => 'success', 'message' => 'Plan transferido correctamente.']);

} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => 'Error interno.']);
}
