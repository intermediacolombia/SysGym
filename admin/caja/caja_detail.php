<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$caja_id_detail_detail = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($caja_id_detail_detail <= 0) {
    die("ID de caja inválido.");
}

// 1. Obtener datos de la caja (incluyendo base) y del usuario que la abrió
$stmtCaja = db()->prepare("SELECT c.*, CONCAT(u.nombre, ' ', u.apellido) AS usuario 
                           FROM cajas c 
                           JOIN usuarios u ON u.id = c.usuario_id 
                           WHERE c.id = :id LIMIT 1");
$stmtCaja->execute([':id' => $caja_id_detail_detail]);
$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
if (!$caja) {
    die("Caja no encontrada.");
}

// 2. Obtener ventas asociadas a la caja
$stmtVentas = db()->prepare("SELECT v.*, p.nombre AS producto 
                             FROM ventas v 
                             LEFT JOIN productos p ON p.id = v.producto_id 
                             WHERE v.caja_id = :caja_id
                             ORDER BY v.fecha ASC, v.hora ASC");
$stmtVentas->execute([':caja_id' => $caja_id_detail_detail]);
$ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

// 3. Totales
$base = floatval($caja['monto_inicial']);

$stmtTotal = db()->prepare("SELECT IFNULL(SUM(valor), 0) as total FROM ventas WHERE caja_id = :caja_id");
$stmtTotal->execute([':caja_id' => $caja_id_detail_detail]);
$rowTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);
$totalVentas = $rowTotal ? floatval($rowTotal['total']) : 0.0;

$stmtEfectivo = db()->prepare("SELECT IFNULL(SUM(valor), 0) as efectivo FROM ventas WHERE caja_id = :caja_id AND payment_method = 'Efectivo'");
$stmtEfectivo->execute([':caja_id' => $caja_id_detail_detail]);
$rowEfectivo = $stmtEfectivo->fetch(PDO::FETCH_ASSOC);
$totalEfectivo = $rowEfectivo ? floatval($rowEfectivo['efectivo']) : 0.0;

// === BANCOS DINÁMICOS ===
$bancosDisponibles = getBancosDisponibles();

// Inicializar todos los bancos en 0
$transferByBank = [];
foreach ($bancosDisponibles as $banco) {
    $transferByBank[$banco] = 0;
}

// Obtener transferencias por banco
$stmtTrans = db()->prepare("SELECT bank, IFNULL(SUM(valor), 0) as total 
                            FROM ventas 
                            WHERE caja_id = :caja_id AND payment_method = 'Transferencia' 
                            GROUP BY bank");
$stmtTrans->execute([':caja_id' => $caja_id_detail_detail]);
$transferencias = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

$totalTransferencias = 0;
foreach ($transferencias as $trans) {
    $bank = $trans['bank'] ? $trans['bank'] : 'Otro';
    $amount = floatval($trans['total']);
    
    // Solo agregar si está en la lista de bancos configurados
    if (array_key_exists($bank, $transferByBank)) {
        $transferByBank[$bank] = $amount;
    } else {
        // Si el banco no está en la configuración, agregarlo como "Otro"
        if (!isset($transferByBank['Otro'])) {
            $transferByBank['Otro'] = 0;
        }
        $transferByBank['Otro'] += $amount;
    }
    $totalTransferencias += $amount;
}

// Eliminar bancos con valor 0 para no mostrarlos si no tienen transacciones
// (Opcional: comenta estas líneas si quieres mostrar todos los bancos siempre)
// $transferByBank = array_filter($transferByBank, function($v) { return $v > 0; });

// === FIN BANCOS DINÁMICOS ===

// Total de egresos
$stmtEgresos = db()->prepare("SELECT IFNULL(SUM(valor), 0) as total_egresos FROM ventas WHERE caja_id = :caja_id AND payment_method = 'Egreso'");
$stmtEgresos->execute([':caja_id' => $caja_id_detail_detail]);
$rowEgresos = $stmtEgresos->fetch(PDO::FETCH_ASSOC);
$totalEgresos = $rowEgresos ? abs(floatval($rowEgresos['total_egresos'])) : 0.0;

$totalCaja = $totalEfectivo - $totalEgresos + $totalTransferencias;

// 4. Consulta para obtener los totales de ventas por bolsillo
$stmtBolsillos = db()->prepare("SELECT IFNULL(b.nombre, 'Facturas') AS bolsillo, IFNULL(SUM(v.valor), 0) AS total 
  FROM ventas v 
  LEFT JOIN productos p ON v.producto_id = p.id 
  LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
  WHERE v.caja_id = :caja_id 
    AND v.payment_method = 'Efectivo'
  GROUP BY IFNULL(b.nombre, 'Facturas')");
$stmtBolsillos->execute([':caja_id' => $caja_id_detail_detail]);
$bolsillos = $stmtBolsillos->fetchAll(PDO::FETCH_ASSOC);

if ($caja['usuario_id'] != $id_user && 
    !(isset($_SESSION["user_permissions"]) && in_array('Ver Todas las Cajas', $_SESSION["user_permissions"]))) {
    $_SESSION['error'] = "No tiene permisos para ver esta caja.";
    header("Location: $url/admin/caja/cajas_list.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle de Caja #<?php echo htmlspecialchars($caja['id']); ?></title>
  <?php include('../inc/header.php'); ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
  <style>
    .card { margin-bottom: 20px; }
  </style>
</head>
<body>
<?php include('../inc/menu.php'); ?>

<?php if (isset($_SESSION["user_permissions"]) && in_array('Cerrar Otras Cajas', $_SESSION["user_permissions"]) && $caja['estado'] == 1): ?>
  <button class="btn btn-danger" id="btnCerrarCajaAdmin"><i class="material-icons" style="font-size:16px">money_off</i>Cerrar Caja</button>
<?php endif; ?>
	
<?php if (isset($_SESSION["user_permissions"]) && in_array('Transferir Cajas', $_SESSION["user_permissions"]) && $caja['estado'] == 1): ?>
  <button class="btn btn-warning" id="btnTransferirCaja">
    <i class="fas fa-random"></i> Transferir Caja
  </button>
<?php endif; ?>

<?php if (isset($_SESSION["user_permissions"]) && in_array('Reabrir Caja', $_SESSION["user_permissions"]) && $caja['estado'] == 0): ?>
    <button class="btn btn-primary" id="btnAbrirCajaEstado">
        <i class="material-icons" style="font-size:16px">attach_money</i> Abrir Caja
    </button>
<?php endif; ?>	

  <div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
      <h4 class="card-title mb-0">Detalle de Caja #<?php echo htmlspecialchars($caja['id']); ?></h4>
    </div>
    <div class="card-body">
      <p class="mb-2">
        <strong>Usuario:</strong> <?php echo htmlspecialchars($caja['usuario']); ?>
      </p>
      <p class="mb-2">
        <strong>Apertura:</strong> <?php echo htmlspecialchars($caja['fecha_apertura']); ?> 
        <strong>Hora: </strong><?php echo date("g:i:s A", strtotime($caja['hora_apertura'])); ?>
      </p>
      <p class="mb-0">
        <strong>Cierre:</strong> 
        <?php if($caja['fecha_cierre']): ?>
          <?php echo htmlspecialchars($caja['fecha_cierre']); ?> 
          <strong>Hora: </strong><?php echo date("g:i:s A", strtotime($caja['hora_cierre'])); ?>
        <?php else: ?>
          No registrado
        <?php endif; ?>
      </p>
    </div>
  </div>

  <!-- Resumen de Caja -->
<div class="card border-0 mb-4" style="border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10)">
  <div class="card-header border-0 d-flex align-items-center gap-2 py-3" style="background:var(--system-color-primary)">
    <i class="fas fa-cash-register fa-lg text-white"></i>
    <h5 class="card-title mb-0 text-white fw-bold">Resumen de Caja</h5>
  </div>
  <div class="card-body p-4" style="background:#fafafa">
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="rounded-3 p-3 text-center h-100" style="background:#f0fdf4;border:1.5px solid #86efac">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#15803d"><i class="fas fa-coins me-1"></i>Base</div>
          <div class="mt-1" style="font-size:1.35rem;font-weight:800;color:#166534">$<?php echo number_format($base, 0, '', '.'); ?></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="rounded-3 p-3 text-center h-100" style="background:#eff6ff;border:1.5px solid #93c5fd">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#1d4ed8"><i class="fas fa-hand-holding-usd me-1"></i>Ventas Efectivo</div>
          <div class="mt-1" style="font-size:1.35rem;font-weight:800;color:#1e3a8a">$<?php echo number_format($totalEfectivo, 0, '', '.'); ?></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="rounded-3 p-3 text-center h-100" style="background:#fff7ed;border:1.5px solid #fdba74">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#c2410c"><i class="fas fa-minus-circle me-1"></i>Egresos</div>
          <div class="mt-1" style="font-size:1.35rem;font-weight:800;color:#9a3412">-$<?php echo number_format($totalEgresos, 0, '', '.'); ?></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="rounded-3 p-3 text-center h-100" style="background:#fdf4ff;border:1.5px solid #d8b4fe">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#7e22ce"><i class="fas fa-wallet me-1"></i>Total Efectivo</div>
          <div class="mt-1" style="font-size:1.35rem;font-weight:800;color:#581c87">$<?php echo number_format($totalEfectivo - $totalEgresos, 0, '', '.'); ?></div>
        </div>
      </div>
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="rounded-3 p-3 h-100" style="background:#fff;border:1.5px solid #e2e8f0">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fab fa-get-pocket" style="color:#64748b"></i>
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b">Bolsillos</span>
          </div>
          <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:11px">Solo referencia de efectivo — no se suma al total.</div>
          <?php if(count($bolsillos) > 0): ?>
            <?php foreach($bolsillos as $b): ?>
              <div class="d-flex justify-content-between" style="font-size:13px"><span><?php echo htmlspecialchars($b['bolsillo']); ?></span><strong>$<?php echo number_format($b['total'], 0, '', '.'); ?></strong></div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted mb-0" style="font-size:13px">Sin ventas por bolsillo.</p>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="rounded-3 p-3 h-100" style="background:#fff;border:1.5px solid #e2e8f0">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa fa-bank" style="color:#64748b"></i>
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b">Transferencias</span>
            <span class="ms-auto fw-bold" style="color:#1e40af">$<?php echo number_format($totalTransferencias, 0, '', '.'); ?></span>
          </div>
          <?php if(count($transferByBank) > 0): ?>
          <ul class="list-group list-group-flush">
            <?php foreach($transferByBank as $bank => $amount): ?>
            <li class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center" style="font-size:13px;border-color:#f1f5f9">
              <?php echo htmlspecialchars($bank); ?>
              <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:12px">$<?php echo number_format($amount, 0, '', '.'); ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php else: ?>
            <p class="text-muted mb-0" style="font-size:13px">Sin transferencias.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer border-0 d-flex align-items-center justify-content-center py-4 px-4" style="background:linear-gradient(135deg,#1e293b,#334155)">
    <div class="text-center">
      <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.7px;color:#94a3b8">Total Turno</div>
      <div style="font-size:2rem;font-weight:900;color:#fff">$<?php echo number_format($totalCaja, 0, '', '.'); ?></div>
    </div>
  </div>
</div>
  
  <!-- Tabla de ventas realizadas -->
  <h3>Ventas Realizadas</h3>
  <table id="ventasDetailTable" class="table table-striped">
    <thead>
      <tr>
        <th>Detalle</th>
        <th>Cantidad</th>
        <th>Valor</th>
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Coste y Ganancias en Caja', $_SESSION["user_permissions"])): ?>
        <th>Coste</th>
        <th>Ganancia</th>
        <?php endif; ?>
        <th>Medio de Pago</th>
        <th>Banco</th>
        <th>Fecha</th>
        <th>Hora</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($ventas)): ?>
        <?php foreach ($ventas as $v): ?>
          <tr>
            <td><?= htmlspecialchars($v['detalle'] ?? '') ?></td>
            <td><?= htmlspecialchars($v['cantidad'] ?? '') ?></td>
            <td>$<?= number_format($v['valor'] ?? 0, 0, '', '.') ?></td>
            <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Coste y Ganancias en Caja', $_SESSION["user_permissions"])): ?>
            <td>$<?= number_format($v['coste'] * $v['cantidad'] ?? 0, 0, '', '.')  ?></td>
            <td>
              <?php
              $valor = $v['valor'] ?? 0;
              $coste = $v['coste'] ?? 0;
			$cantidad = $v['cantidad'] ?? 0;
				
              $method = $v['payment_method'] ?? '';
              echo ($method === 'Egreso') ? '$0' : '$' . number_format($valor - ($coste * $cantidad), 0, '', '.');
				
              ?>
            </td>
            <?php endif; ?>
            <td><?= htmlspecialchars($v['payment_method'] ?? '') ?></td>
            <td><?= htmlspecialchars($v['bank'] ?? '') ?></td>
            <td><?= htmlspecialchars($v['fecha'] ?? '') ?></td>
            <td data-order="<?= htmlspecialchars($v['hora'] ?? '') ?>">
              <?= !empty($v['hora']) ? date("g:i:s A", strtotime($v['hora'])) : '' ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  
  <a href="cajas_list.php" class="btn btn-secondary mt-3">Volver al listado de Cajas</a>

<?php if (isset($_SESSION["user_permissions"]) && in_array('Transferir Cajas', $_SESSION["user_permissions"]) && $caja['estado'] == 1): ?>	
<div class="modal fade" id="transferirCajaModal" tabindex="-1" aria-labelledby="transferirCajaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Transferir Caja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label for="nuevoUsuario" class="form-label">Selecciona el nuevo usuario:</label>
        <select class="form-select mb-2" id="nuevoUsuario">
          <option value="">Cargando...</option>
        </select>
        <div class="alert alert-info" style="font-size: 13px;">
          <i class="fas fa-info-circle me-1"></i>
          Las facturas ya generadas en esta caja no cambiarán el usuario que las emitió, pero el total de ventas se asignará al nuevo responsable.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" id="confirmarTransferenciaCaja">Transferir</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include('../inc/menu-footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
  $('#ventasDetailTable').DataTable({
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Coste y Ganancias en Caja', $_SESSION["user_permissions"])): ?>
    order: [[8, 'desc']],
    <?php else: ?>
    order: [[6, 'desc']],
    <?php endif; ?>
    "pageLength": 50,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
    emptyTable: "No se han registrado ventas."
  });
});

$('#btnCerrarCajaAdmin').on('click', function(){
  Swal.fire({
    title: 'Cerrar Caja',
    text: '¿Desea cerrar esta caja?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, cerrar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if(result.isConfirmed){
      $.get('close_id.php', { id: <?php echo $caja['id']; ?> }, function(res){
        if(res.status === 'success'){
          Swal.fire('Caja Cerrada', res.message, 'success').then(() => location.reload());
        } else {
          Swal.fire('Error', res.message, 'error');
        }
      }, 'json').fail(function(){
        Swal.fire('Error', 'No se pudo cerrar la caja (error de red).', 'error');
      });
    }
  });
});

$('#btnAbrirCajaEstado').click(function(){
  Swal.fire({
    title: 'Reabrir Caja',
    text: '¿Desea reabrir esta caja?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, abrir',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if(result.isConfirmed){
      $.get('open_id.php', { id: <?php echo $caja['id']; ?> }, function(res){
        if(res.status === 'success'){
          Swal.fire('Caja Abierta', res.message, 'success').then(() => location.reload());
        } else {
          Swal.fire('Error', res.message, 'error');
        }
      }, 'json').fail(function(){
        Swal.fire('Error', 'No se pudo abrir la caja (error de red).', 'error');
      });
    }
  });
});

$('#btnTransferirCaja').on('click', function(){
  $('#transferirCajaModal').modal('show');
  $.get('get_usuarios_disponibles.php', function(data){
    const select = $('#nuevoUsuario');
    select.empty().append('<option value="">Selecciona un usuario</option>');
    data.forEach(u => {
      select.append(`<option value="${u.id}">${u.nombre} ${u.apellido}</option>`);
    });
  }, 'json');
});

$('#confirmarTransferenciaCaja').on('click', function(){
  const nuevoUsuario = $('#nuevoUsuario').val();
  if (!nuevoUsuario) {
    Swal.fire('Atención', 'Debes seleccionar un usuario.', 'warning');
    return;
  }
  Swal.fire({
    title: '¿Transferir Caja?',
    text: 'Se cambiará el propietario de esta caja.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, transferir',
    cancelButtonText: 'Cancelar'
  }).then((res) => {
    if (res.isConfirmed) {
      $.post('transferir_caja.php', {
        caja_id: <?= $caja['id'] ?>,
        nuevo_usuario_id: nuevoUsuario
      }, function(response){
        if (response.status === 'success') {
          Swal.fire('Éxito', response.message, 'success').then(() => location.reload());
        } else {
          Swal.fire('Error', response.message, 'error');
        }
      }, 'json');
    }
  });
});
</script>

</body>
</html>





