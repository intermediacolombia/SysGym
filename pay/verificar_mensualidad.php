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

$identificacion = $_POST['identificacion'] ?? '';
if (empty($identificacion)) {
    echo json_encode(['status' => 'error', 'message' => 'Documento no proporcionado.']);
    exit;
}

// Buscar cliente por identificación (activo o inactivo)
$stmt = $pdo->prepare("SELECT c.*, p.nombre AS plan_nombre 
                       FROM clientes c 
                       LEFT JOIN planes p ON p.id = c.plan
                       WHERE c.identificacion = :ident LIMIT 1");
$stmt->execute([':ident' => $identificacion]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo json_encode(['status' => 'error', 'message' => 'No se encontró ningún cliente con este documento.']);
    exit;
}

// === Lógica de estado del plan ===
$estado = '';
$color = '';
$mensaje = '';

if (empty($cliente['plan'])) {
    $estado = 'Sin plan asignado';
    $color = 'secondary';
    $mensaje = 'Este cliente no tiene ningún plan activo.';
} else {
    $fechaVenc = new DateTime($cliente['vencimiento_plan']);
    $hoy = new DateTime();
    $diasRestantes = (int)$hoy->diff($fechaVenc)->format('%r%a'); // negativo si vencido

    if ($diasRestantes < 0) {
        $estado = 'Plan vencido';
        $color = 'danger';
        $mensaje = 'La mensualidad venció el ' . $fechaVenc->format('d/m/Y') . '.';
    } elseif ($diasRestantes <= 7) {
        $estado = 'Por vencer';
        $color = 'warning';
        $mensaje = 'El plan vence en ' . $diasRestantes . ' día(s) (' . $fechaVenc->format('d/m/Y') . ').';
    } else {
        $estado = 'Al día';
        $color = 'success';
        $mensaje = 'El plan está vigente hasta el ' . $fechaVenc->format('d/m/Y') . '.';
    }
}

$html = '
<div class="card p-3">
  <div class="d-flex align-items-center">
    ' . (!empty($cliente['imagen_perfil']) ? '<img src="'.$url.'/'.$cliente['imagen_perfil'].'" class="rounded-circle me-3" style="width:70px;height:70px;object-fit:cover;">' : '') . '
    <div>
      <h5 class="mb-0">'.htmlspecialchars($cliente['nombres'].' '.$cliente['apellidos']).'</h5>
      <small class="text-muted">'.htmlspecialchars($cliente['identificacion']).'</small>
    </div>
  </div>
  <hr>
  <p><strong>Estado del cliente:</strong> ' . ucfirst($cliente['estado']) . '</p>
  <p><strong>Plan actual:</strong> ' . ($cliente['plan_nombre'] ?? 'N/A') . '</p>
  <p><strong>Fecha de pago:</strong> ' . ($cliente['pago_plan'] ?? '-') . '</p>
  <p><strong>Vencimiento:</strong> ' . ($cliente['vencimiento_plan'] ?? '-') . '</p>
  <div class="alert alert-' . $color . ' mt-3">
    <strong>' . strtoupper($estado) . ':</strong> ' . $mensaje . '
  </div>
</div>
';

echo json_encode(['status' => 'success', 'html' => $html]);

