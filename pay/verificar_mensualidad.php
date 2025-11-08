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

$stmt = $pdo->prepare("
    SELECT 
        c.*,
        p.nombre  AS plan_nombre,
        p.precio  AS plan_precio
    FROM clientes c
    LEFT JOIN planes p ON p.id = c.plan
    WHERE c.identificacion = :ident
    LIMIT 1
");
$stmt->execute([':ident' => $identificacion]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo json_encode(['status' => 'error', 'message' => 'No se encontró ningún cliente con este documento.']);
    exit;
}

/* ---------- Estado del plan ---------- */
$estado = '';
$color = '';
$mensaje = '';
$mostrarBotonPago = false;

if (empty($cliente['plan'])) {
    $estado = 'Sin plan asignado';
    $color = 'secondary';
    $mensaje = 'Este cliente no tiene ningún plan activo.';
    $mostrarBotonPago = true;
} else {
    $fechaVenc = !empty($cliente['vencimiento_plan']) ? new DateTime($cliente['vencimiento_plan']) : null;
    $hoy = new DateTime();

    if (!$fechaVenc) {
        $estado = 'Sin vencimiento registrado';
        $color = 'warning';
        $mensaje = 'No se encontró fecha de vencimiento para este plan.';
        $mostrarBotonPago = true;
    } else {
        $diasRestantes = (int)$hoy->diff($fechaVenc)->format('%r%a'); // negativo si vencido

        if ($diasRestantes < 0) {
            // Plan vencido
            $estado = 'Plan vencido';
            $color = 'danger';
            $mensaje = 'La mensualidad venció el ' . $fechaVenc->format('d/m/Y') . '.';
            $mostrarBotonPago = true;

        } elseif (DAYS_ALLOWED_BEFORE_DUE == 0 || $diasRestantes <= DAYS_ALLOWED_BEFORE_DUE) {
            // Puede pagar en cualquier momento (si 0) o dentro de los días permitidos
            $estado = 'Por vencer';
            $color = 'warning';
            if (DAYS_ALLOWED_BEFORE_DUE == 0) {
                $mensaje = 'Puedes realizar tu pago en línea en cualquier momento.';
            } else {
                $mensaje = 'El plan vence en ' . $diasRestantes . ' día(s) (' . $fechaVenc->format('d/m/Y') . ').';
            }
            $mostrarBotonPago = true;

        } else {
            // Plan activo y fuera del rango de pago
            $estado = 'Al día';
            $color = 'success';
            $mensaje = 'El plan está vigente hasta el ' . $fechaVenc->format('d/m/Y') . '.';
        }
    }
}

/* ---------- Precio y total con adicional ---------- */
$precioBase = (float)($cliente['plan_precio'] ?? 0);
$adicional  = (float) (defined('ADDITIONAL_PERCENTAGE_PAYMENT') ? ADDITIONAL_PERCENTAGE_PAYMENT : 0);
$valorFinal = round($precioBase * (1 + ($adicional / 100)), 0);

function cop($n) { return '$' . number_format((float)$n, 0, ',', '.'); }

/* ---------- Tarjeta HTML ---------- */
$html = '
<div class="card p-3">
  <div class="d-flex align-items-center">
    ' . (!empty($cliente['imagen_perfil']) ? '<img src="'.$url.'/uploads/clientes/'.htmlspecialchars($cliente['imagen_perfil']).'" class="rounded-circle me-3" style="width:70px;height:70px;object-fit:cover;">' : '') . '
    <div>
      <h5 class="mb-0">'.htmlspecialchars($cliente['nombres'].' '.$cliente['apellidos']).'</h5>
      <small class="text-muted">'.htmlspecialchars($cliente['identificacion']).'</small>
    </div>
  </div>
  <hr>
  <p><strong>Estado del cliente:</strong> ' . ucfirst($cliente['estado']) . '</p>
  <p><strong>Plan actual:</strong> ' . ($cliente['plan_nombre'] ?? 'N/A') . '</p>
  <p><strong>Valor del plan:</strong> ' . cop($precioBase) . '</p>
  <p><strong>Fecha de pago:</strong> ' . (!empty($cliente['pago_plan']) ? htmlspecialchars($cliente['pago_plan']) : '-') . '</p>
  <p><strong>Vencimiento:</strong> ' . (!empty($cliente['vencimiento_plan']) ? htmlspecialchars($cliente['vencimiento_plan']) : '-') . '</p>
  <div class="alert alert-' . $color . ' mt-3">
    <strong>' . strtoupper($estado) . ':</strong> ' . $mensaje . '
  </div>';

if ($mostrarBotonPago && $precioBase > 0) {
    $html .= '
    <div class="text-center mt-3">
      <button 
        class="btn btn-success"
        id="btnPagar"
        data-id="'.(int)$cliente['id'].'"
        data-plan="'.htmlspecialchars($cliente['plan_nombre'] ?? 'Plan').'"
        data-valor="'.$precioBase.'"
        data-final="'.$valorFinal.'"
      >
        <i class="fa fa-credit-card"></i> Pagar Membresía
      </button>
    </div>';
}

$html .= '</div>';

echo json_encode([
    'status' => 'success',
    'html'   => $html,
    'data'   => [
        'id'           => (int)$cliente['id'],
        'nombres'      => $cliente['nombres'],
        'apellidos'    => $cliente['apellidos'],
        'plan'         => $cliente['plan_nombre'] ?? 'Plan',
        'valor'        => $precioBase,
        'valor_final'  => $valorFinal,
        'adicional'    => $adicional
    ]
]);