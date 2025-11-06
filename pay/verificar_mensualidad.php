<?php
require_once __DIR__ . '/../inc/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al conectar: ' . $e->getMessage()]);
    exit;
}

$documento = $_POST['documento'] ?? '';
if (empty($documento)) {
    echo json_encode(['status' => 'error', 'message' => 'Documento no proporcionado.']);
    exit;
}

// Buscar cliente por documento (activo o inactivo)
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE documento = :doc LIMIT 1");
$stmt->execute([':doc' => $documento]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo json_encode(['status' => 'error', 'message' => 'No se encontró ningún cliente con este documento.']);
    exit;
}

// Consultar plan actual
$stmtPlan = $pdo->prepare("
    SELECT p.nombre AS plan_nombre, cp.fecha_inicio, cp.fecha_fin
    FROM clientes_planes cp
    LEFT JOIN planes p ON p.id = cp.plan_id
    WHERE cp.cliente_id = :id
    ORDER BY cp.id DESC LIMIT 1
");
$stmtPlan->execute([':id' => $cliente['id']]);
$plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

$estado = '';
$color = '';
$mensaje = '';

if (!$plan) {
    $estado = 'Sin plan asignado';
    $color = 'secondary';
    $mensaje = 'Este cliente no tiene una mensualidad activa.';
} else {
    $fechaFin = new DateTime($plan['fecha_fin']);
    $hoy = new DateTime();
    $diasRestantes = $hoy->diff($fechaFin)->days;
    $expirado = $fechaFin < $hoy;

    if ($expirado) {
        $estado = 'Plan vencido';
        $color = 'danger';
        $mensaje = 'La mensualidad de este cliente ya venció el ' . $fechaFin->format('d/m/Y');
    } elseif ($diasRestantes <= 7) {
        $estado = 'Por vencer';
        $color = 'warning';
        $mensaje = 'El plan vencerá en ' . $diasRestantes . ' día(s) (' . $fechaFin->format('d/m/Y') . ')';
    } else {
        $estado = 'Al día';
        $color = 'success';
        $mensaje = 'El plan está vigente hasta el ' . $fechaFin->format('d/m/Y');
    }
}

$html = '
<div class="card p-3">
  <p><strong>Cliente:</strong> ' . htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']) . '</p>
  <p><strong>Documento:</strong> ' . htmlspecialchars($cliente['documento']) . '</p>
  <p><strong>Estado del cliente:</strong> ' . ($cliente['estado'] == 1 ? 'Activo' : 'Inactivo') . '</p>
  <hr>
  <p><strong>Plan actual:</strong> ' . ($plan['plan_nombre'] ?? 'N/A') . '</p>
  <p><strong>Inicio:</strong> ' . ($plan['fecha_inicio'] ?? '-') . '</p>
  <p><strong>Fin:</strong> ' . ($plan['fecha_fin'] ?? '-') . '</p>
  <div class="alert alert-' . $color . ' mt-3" role="alert">
    <strong>' . strtoupper($estado) . ':</strong> ' . $mensaje . '
  </div>
</div>
';

echo json_encode(['status' => 'success', 'html' => $html]);
