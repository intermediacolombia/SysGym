<?php
require_once __DIR__ . '/../inc/config.php';
header('Content-Type: application/json; charset=utf-8');

$identificacion = $_POST['identificacion'] ?? '';
if (empty($identificacion)) {
    echo json_encode(['status' => 'error', 'message' => 'Documento no proporcionado.']);
    exit;
}

$stmt = db()->prepare("SELECT * FROM clientes WHERE identificacion = :ident LIMIT 1");
$stmt->execute([':ident' => $identificacion]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo json_encode(['status' => 'error', 'message' => 'No se encontró ningún cliente con este documento.']);
    exit;
}

$esTiquetera = ($cliente['plan_tipo'] ?? 'plan') === 'tiquetera';

/* ── Iniciales ─────────────────────────────────────────── */
$estado           = '';
$color            = '';
$mensaje          = '';
$mostrarBotonPago = false;
$planNombre       = 'N/A';
$precioBase       = 0.0;
$infoBadge        = '';   // HTML extra para tiquetera

function cop($n) { return '$' . number_format((float)$n, 0, ',', '.'); }

/* ══════════════════════════════════════════════════════════
   SIN PLAN
══════════════════════════════════════════════════════════ */
if (empty($cliente['plan'])) {
    $estado           = 'Sin plan asignado';
    $color            = 'secondary';
    $mensaje          = 'Este cliente no tiene ningún plan activo.';
    $mostrarBotonPago = true;

/* ══════════════════════════════════════════════════════════
   TIQUETERA
══════════════════════════════════════════════════════════ */
} elseif ($esTiquetera) {

    $stmtT = db()->prepare("SELECT nombre, precio, limite_entradas FROM tiqueteras WHERE id = :id AND borrado = 0 LIMIT 1");
    $stmtT->execute([':id' => $cliente['plan']]);
    $tiquetera = $stmtT->fetch(PDO::FETCH_ASSOC);

    if (!$tiquetera) {
        $estado           = 'Tiquetera no encontrada';
        $color            = 'warning';
        $mensaje          = 'No se pudo obtener información de la tiquetera asignada.';
        $mostrarBotonPago = true;
    } else {
        $planNombre     = $tiquetera['nombre'];
        $precioBase     = (float)$tiquetera['precio'];
        $limiteEntradas = (int)$tiquetera['limite_entradas'];

        /* Entradas consumidas en la factura activa */
        $consumidas = 0;
        if (!empty($cliente['tiquetera_factura_id'])) {
            $stmtC = db()->prepare("SELECT COUNT(*) FROM tiquetera_consumos WHERE cliente_id = :cid AND factura_id = :fid");
            $stmtC->execute([':cid' => $cliente['id'], ':fid' => $cliente['tiquetera_factura_id']]);
            $consumidas = (int)$stmtC->fetchColumn();
        }
        $restantes = max(0, $limiteEntradas - $consumidas);

        /* Vencimiento */
        $hoy      = new DateTime('today');
        $fechaVenc = !empty($cliente['vencimiento_plan']) ? new DateTime($cliente['vencimiento_plan']) : null;
        $vencida   = !$fechaVenc || $fechaVenc < $hoy;

        /* Badge informativo */
        $pctUsado  = $limiteEntradas > 0 ? round($consumidas / $limiteEntradas * 100) : 0;
        $barColor  = $restantes === 0 ? 'bg-danger' : ($pctUsado >= 70 ? 'bg-warning' : 'bg-success');
        $infoBadge = '
          <p class="mb-1"><strong>Tipo de plan:</strong> <span class="badge bg-info text-dark">Tiquetera</span></p>
          <p class="mb-1"><strong>Entradas:</strong> ' . $consumidas . ' / ' . $limiteEntradas . ' usadas
            &nbsp;<span class="fw-bold ' . ($restantes > 0 ? 'text-success' : 'text-danger') . '">'
              . $restantes . ' restante' . ($restantes !== 1 ? 's' : '') . '</span>
          </p>
          <div class="progress mb-2" style="height:8px">
            <div class="progress-bar ' . $barColor . '" style="width:' . $pctUsado . '%"></div>
          </div>';

        if ($vencida) {
            $estado           = 'Tiquetera vencida';
            $color            = 'danger';
            $mensaje          = 'La tiquetera venció el ' . ($fechaVenc ? $fechaVenc->format('d/m/Y') : 'fecha desconocida') . '. Puedes renovarla ahora.';
            $mostrarBotonPago = true;

        } elseif ($restantes === 0) {
            $estado           = 'Entradas agotadas';
            $color            = 'warning';
            $mensaje          = 'Usaste todas tus entradas. Renueva para seguir entrenando'
                              . ($fechaVenc ? ' (vence el ' . $fechaVenc->format('d/m/Y') . ')' : '') . '.';
            $mostrarBotonPago = true;

        } else {
            $estado           = 'Tiquetera vigente';
            $color            = 'success';
            $mensaje          = 'Tienes ' . $restantes . ' entrada' . ($restantes !== 1 ? 's' : '') . ' disponible'
                              . ($restantes !== 1 ? 's' : '') . '. Vence el '
                              . ($fechaVenc ? $fechaVenc->format('d/m/Y') : '-') . '.';
            $mostrarBotonPago = false;
        }
    }

/* ══════════════════════════════════════════════════════════
   PLAN REGULAR (mensual / por período)
══════════════════════════════════════════════════════════ */
} else {

    $stmtP = db()->prepare("SELECT nombre, precio FROM planes WHERE id = :id AND borrado = 0 LIMIT 1");
    $stmtP->execute([':id' => $cliente['plan']]);
    $plan = $stmtP->fetch(PDO::FETCH_ASSOC);

    if ($plan) {
        $planNombre = $plan['nombre'];
        $precioBase = (float)$plan['precio'];
    }

    $hoy       = new DateTime();
    $fechaVenc = !empty($cliente['vencimiento_plan']) ? new DateTime($cliente['vencimiento_plan']) : null;

    if (!$fechaVenc) {
        $estado           = 'Sin vencimiento registrado';
        $color            = 'warning';
        $mensaje          = 'No se encontró fecha de vencimiento para este plan.';
        $mostrarBotonPago = true;

    } else {
        $diasRestantes = (int)$hoy->diff($fechaVenc)->format('%r%a'); // negativo = vencido

        if ($diasRestantes < 0) {
            $estado           = 'Plan vencido';
            $color            = 'danger';
            $mensaje          = 'La mensualidad venció el ' . $fechaVenc->format('d/m/Y') . '.';
            $mostrarBotonPago = true;

        } elseif (DAYS_ALLOWED_BEFORE_DUE == 0 || $diasRestantes <= DAYS_ALLOWED_BEFORE_DUE) {
            $estado           = 'Por vencer';
            $color            = 'warning';
            $mensaje          = DAYS_ALLOWED_BEFORE_DUE == 0
                ? 'Puedes realizar tu pago en línea en cualquier momento.'
                : 'El plan vence en ' . $diasRestantes . ' día(s) (' . $fechaVenc->format('d/m/Y') . ').';
            $mostrarBotonPago = true;

        } else {
            $estado           = 'Al día';
            $color            = 'success';
            $mensaje          = 'El plan está vigente hasta el ' . $fechaVenc->format('d/m/Y') . '.';
            $mostrarBotonPago = false;
        }
    }
}

/* ── Precio final ───────────────────────────────────────── */
$adicional  = (float)(defined('ADDITIONAL_PERCENTAGE_PAYMENT') ? ADDITIONAL_PERCENTAGE_PAYMENT : 0);
$valorFinal = $precioBase > 0 ? round($precioBase * (1 + ($adicional / 100)), 0) : 0;

$labelBoton = $esTiquetera ? 'Renovar Tiquetera' : 'Pagar Membresía';

/* ── HTML de la tarjeta ─────────────────────────────────── */
$html = '
<div class="card p-3">
  <div class="d-flex align-items-center mb-2">
    ' . (!empty($cliente['imagen_perfil'])
        ? '<img src="' . $url . '/uploads/clientes/' . htmlspecialchars($cliente['imagen_perfil']) . '"
               class="rounded-circle me-3" style="width:70px;height:70px;object-fit:cover;">'
        : '') . '
    <div>
      <h5 class="mb-0">' . htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']) . '</h5>
      <small class="text-muted">' . htmlspecialchars($cliente['identificacion']) . '</small>
    </div>
  </div>
  <hr class="my-2">
  <p class="mb-1"><strong>Estado del cliente:</strong> ' . ucfirst($cliente['estado']) . '</p>
  ' . $infoBadge . '
  <p class="mb-1"><strong>Plan actual:</strong> ' . htmlspecialchars($planNombre) . '</p>
  <p class="mb-1"><strong>Valor del plan:</strong> ' . cop($precioBase) . '</p>
  <p class="mb-1"><strong>Fecha de pago:</strong> ' . (!empty($cliente['pago_plan']) ? htmlspecialchars($cliente['pago_plan']) : '-') . '</p>
  <p class="mb-2"><strong>Vencimiento:</strong> ' . (!empty($cliente['vencimiento_plan']) ? htmlspecialchars($cliente['vencimiento_plan']) : '-') . '</p>
  <div class="alert alert-' . $color . ' mt-2 mb-2">
    <strong>' . strtoupper($estado) . ':</strong> ' . $mensaje . '
  </div>';

if ($mostrarBotonPago && $precioBase > 0) {
    $html .= '
  <div class="text-center mt-3">
    <button
      class="btn btn-success btn-lg"
      id="btnPagar"
      data-id="'    . (int)$cliente['id']          . '"
      data-plan="'  . htmlspecialchars($planNombre) . '"
      data-valor="' . $precioBase                   . '"
      data-final="' . $valorFinal                   . '"
    >
      <i class="bi bi-credit-card me-1"></i> ' . $labelBoton . '
    </button>
  </div>';
}

$html .= '</div>';

echo json_encode([
    'status' => 'success',
    'html'   => $html,
    'data'   => [
        'id'          => (int)$cliente['id'],
        'nombres'     => $cliente['nombres'],
        'apellidos'   => $cliente['apellidos'],
        'plan'        => $planNombre,
        'valor'       => $precioBase,
        'valor_final' => $valorFinal,
        'adicional'   => $adicional,
        'es_tiquetera'=> $esTiquetera,
    ],
]);
