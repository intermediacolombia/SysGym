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
$stmtVentas =  db()->prepare("SELECT v.*, p.nombre AS producto 
                             FROM ventas v 
                             LEFT JOIN productos p ON p.id = v.producto_id 
                             WHERE v.caja_id = :caja_id
                             ORDER BY v.fecha ASC, v.hora ASC");
$stmtVentas->execute([':caja_id' => $caja_id_detail_detail]);
$ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

// 3. Totales
// Base (monto_inicial)
$base = floatval($caja['monto_inicial']);

// Total vendido global (suma de todas las ventas de la caja)
$stmtTotal =  db()->prepare("SELECT IFNULL(SUM(valor), 0) as total FROM ventas WHERE caja_id = :caja_id");
$stmtTotal->execute([':caja_id' => $caja_id_detail_detail]);
$rowTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);
$totalVentas = $rowTotal ? floatval($rowTotal['total']) : 0.0;

// Total en efectivo: ventas con payment_method = 'Efectivo'
$stmtEfectivo =  db()->prepare("SELECT IFNULL(SUM(valor), 0) as efectivo FROM ventas WHERE caja_id = :caja_id AND payment_method = 'Efectivo'");
$stmtEfectivo->execute([':caja_id' => $caja_id_detail_detail]);
$rowEfectivo = $stmtEfectivo->fetch(PDO::FETCH_ASSOC);
$totalEfectivo = $rowEfectivo ? floatval($rowEfectivo['efectivo']) : 0.0;

// Total de transferencias agrupado por banco (payment_method = 'Transferencia')
$stmtTrans =  db()->prepare("SELECT bank, IFNULL(SUM(valor), 0) as total FROM ventas WHERE caja_id = :caja_id AND payment_method = 'Transferencia' GROUP BY bank");
$stmtTrans->execute([':caja_id' => $caja_id_detail_detail]);
$transferencias = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);
$totalTransferencias = 0;
$transferByBank = [];
foreach ($transferencias as $trans) {
    $bank = $trans['bank'] ? $trans['bank'] : 'Otro';
    $amount = floatval($trans['total']);
    $transferByBank[$bank] = $amount;
    $totalTransferencias += $amount;
}

// Total de egresos (payment_method = 'Egreso')
$stmtEgresos =  db()->prepare("SELECT IFNULL(SUM(valor), 0) as total_egresos FROM ventas WHERE caja_id = :caja_id AND payment_method = 'Egreso'");
$stmtEgresos->execute([':caja_id' => $caja_id_detail_detail]);
$rowEgresos = $stmtEgresos->fetch(PDO::FETCH_ASSOC);
$totalEgresos = $rowEgresos ? abs(floatval($rowEgresos['total_egresos'])) : 0.0;


// Total en caja final = base + totalVentas
$totalCaja = $base + $totalVentas;


// 4. Consulta para obtener los totales de ventas por bolsillo
// Se excluyen las ventas sin producto (producto_id IS NOT NULL)
$stmtBolsillos =  db()->prepare("SELECT IFNULL(b.nombre, 'Facturas') AS bolsillo, IFNULL(SUM(v.valor), 0) AS total 
  FROM ventas v 
  LEFT JOIN productos p ON v.producto_id = p.id 
  LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
  WHERE v.caja_id = :caja_id 
    AND v.payment_method = 'Efectivo'
  GROUP BY IFNULL(b.nombre, 'Facturas')");
$stmtBolsillos->execute([':caja_id' => $caja_id_detail_detail]);
$bolsillos = $stmtBolsillos->fetchAll(PDO::FETCH_ASSOC);
/*$stmtBolsillos =  db()->prepare("SELECT b.nombre AS bolsillo, IFNULL(SUM(v.valor), 0) AS total 
                               FROM ventas v 
                               LEFT JOIN productos p ON v.producto_id = p.id 
                               LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
                               WHERE v.caja_id = :caja_id 
                                 AND v.producto_id IS NOT NULL 
                                 AND v.payment_method = 'Efectivo'
                                 AND v.detalle LIKE '%Factura%'
                               GROUP BY b.nombre");
$stmtBolsillos->execute([':caja_id' => $caja_id_detail_detail]);
$bolsillos = $stmtBolsillos->fetchAll(PDO::FETCH_ASSOC);*/

/*$stmtBolsillos =  db()->prepare("SELECT b.nombre AS bolsillo, IFNULL(SUM(v.valor), 0) AS total 
                               FROM ventas v 
                               LEFT JOIN productos p ON v.producto_id = p.id 
                               LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
                               WHERE v.caja_id = :caja_id 
                                 AND v.producto_id IS NOT NULL 
                                 AND v.payment_method = 'Efectivo'
                               GROUP BY b.nombre");
$stmtBolsillos->execute([':caja_id' => $caja_id_detail_detail]);
$bolsillos = $stmtBolsillos->fetchAll(PDO::FETCH_ASSOC);*/

/*$stmtBolsillos =  db()->prepare("SELECT b.nombre AS bolsillo, IFNULL(SUM(v.valor), 0) AS total 
                                FROM ventas v 
                                LEFT JOIN productos p ON v.producto_id = p.id 
                                LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
                                WHERE v.caja_id = :caja_id AND v.producto_id IS NOT NULL
                                GROUP BY b.nombre");
$stmtBolsillos->execute([':caja_id' => $caja_id_detail_detail]);
$bolsillos = $stmtBolsillos->fetchAll(PDO::FETCH_ASSOC);*/



if ($caja['usuario_id'] != $id_user && 
    !(isset($_SESSION["user_permissions"]) && in_array('Ver Todas las Cajas', $_SESSION["user_permissions"]))) {
    
    // Si el usuario no tiene permisos, establecer mensaje de error y redirigir
    $_SESSION['error'] = "No tiene permisos para ver esta caja.";
    header("Location: $url/admin/caja/cajas_list.php"); // Cambia a la URL del dashboard
    exit();
}



?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle de Caja #<?php echo htmlspecialchars($caja['id']); ?></title>
  <?php include('../inc/header.php'); ?>
  <!-- DataTables CSS -->
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
      <h4 class="card-title mb-0" >Detalle de Caja #<?php echo htmlspecialchars($caja['id']); ?></h4>
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


  <!-- Resumen Detallado de la Caja -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-danger text-white">
      <h5 class="card-title mb-0">Resumen de Caja</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <!-- Columna Efectivo -->
        <div class="col-md-6 border-end">
          <h6 class="text-uppercase text-muted mb-3"><i class='fas fa-coins'></i> Efectivo</h6>
          <p class="mb-2">
            <strong>Base:</strong> $<?php echo number_format($base, 0, '', '.'); ?>
          </p>
          <p class="mb-2">
            <strong>Total Vendido (Efectivo):</strong> $<?php echo number_format($totalEfectivo, 0, '', '.'); ?>
          </p>
			
			<p class="mb-2">
  <strong>Egresos:</strong> $-<?php echo number_format($totalEgresos, 0, '', '.'); ?>
</p>
			
          <p class="mb-2">
            <strong>Total Base + Efectivo<?php if($totalEgresos > 0):?> - Egresos<?php endif; ?>:</strong> $<?php echo number_format($base + $totalEfectivo - $totalEgresos, 0, '', '.'); ?>
          </p>
			
		
		
			<hr>
			<h6 class="text-uppercase text-muted mb-3" title=""><i class='fab fa-get-pocket'></i> Bolsillos</h6>
		   <div class="alert alert-danger" role="alert" style="font-size: 12px;"><strong style="color: red;">Nota:</strong> Esta información no se suma, solo se identifica cuanto debe ir en cada bolsillo y solo contempla el dinero en efectivo en la caja.</div>
			
			<?php if(count($bolsillos) > 0): ?>
     
        <?php foreach($bolsillos as $b): ?>
          
            <strong><?php echo htmlspecialchars($b['bolsillo']); ?>:</strong>
            $<?php echo number_format($b['total'], 0, '', '.'); ?><br>
          
        <?php endforeach; ?>
      
      <?php else: ?>
        <p>No se registraron ventas por bolsillo.</p>
      <?php endif; ?>
			
			

        </div>
        <!-- Columna Transferencias -->
        <div class="col-md-6">
          <h6 class="text-uppercase text-muted mb-3"><i class="fa fa-bank"></i> Transferencias</h6>
          <p class="mb-2">
            <strong>Total Transferencias:</strong> $<?php echo number_format($totalTransferencias, 0, '', '.'); ?>
          </p>
          <?php if(count($transferByBank) > 0): ?>
          <ul class="list-group list-group-flush">
            <?php foreach($transferByBank as $bank => $amount): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?php echo htmlspecialchars($bank); ?>
                <span>$<?php echo number_format($amount, 0, '', '.'); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <?php else: ?>
            <p>No se registraron transferencias.</p>
          <?php endif; ?>
        </div>
      </div>
      <hr class="my-4">
      <div class="text-center">
        <h4 class="mb-3">Total Turno: $<?php echo number_format($totalCaja, 0, '', '.'); ?></h4>
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
  <?php if(count($ventas) > 0): ?>
    <?php foreach($ventas as $v): ?>
      <tr>
        <td><?php echo htmlspecialchars($v['detalle']); ?></td>
        <td><?php echo $v['cantidad']; ?></td>
        <td>$<?php echo number_format($v['valor'], 0, '', '.'); ?></td>
		  
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Coste y Ganancias en Caja', $_SESSION["user_permissions"])): ?>
		<td>$<?php echo number_format($v['coste'] , 0, '', '.'); ?></td>
		<!--td>$<?php echo number_format($v['valor'] - $v['coste'] , 0, '', '.'); ?></td-->
		<td><?php
if ($v['payment_method'] === 'Egreso') {
    echo '$0';
} else {
    echo '$' . number_format($v['valor'] - $v['coste'], 0, '', '.');
}
?>
</td>
		<?php endif; ?>
		  
        <td><?php echo htmlspecialchars($v['payment_method']); ?></td>
        <td><?php echo htmlspecialchars($v['bank']); ?></td>
        <td><?php echo htmlspecialchars($v['fecha']); ?></td>
        <td data-order="<?php echo htmlspecialchars($v['hora']); ?>">
  <?php echo date("g:i:s A", strtotime($v['hora'])); ?>
</td>

      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</tbody>

  </table>
  
  <a href="cajas_list.php" class="btn btn-secondary mt-3">Volver al listado de Cajas</a>
</div>
	
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
<!-- jQuery y DataTables JS -->
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
	  <?php endif;?>
	  "pageLength": 50,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
	  emptyTable: "No se han registrado ventas."
  });
});
	
	


	
</script>
	
	<script>
$(document).ready(function(){
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
            Swal.fire('Caja Cerrada', res.message, 'success')
              .then(() => location.reload());
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json').fail(function(){
          Swal.fire('Error', 'No se pudo cerrar la caja (error de red).', 'error');
        });
      }
    });
  });
});
</script>

	
<script>
$(document).ready(function(){
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
            Swal.fire('Caja Abierta', res.message, 'success')
              .then(() => location.reload());
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json').fail(function(){
          Swal.fire('Error', 'No se pudo abrir la caja (error de red).', 'error');
        });
      }
    });
  });
});
</script>
	
	
<script>
$(document).ready(function(){
  $('#btnTransferirCaja').on('click', function(){
    // Abrimos el modal y cargamos los usuarios disponibles
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
            Swal.fire('Éxito', response.message, 'success')
              .then(() => location.reload());
          } else {
            Swal.fire('Error', response.message, 'error');
          }
        }, 'json');
      }
    });
  });
});
</script>

	
</body>
</html>





