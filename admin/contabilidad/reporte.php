<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$permisopage = 'Ver Reportes Contabilidad';
include('../login/restriction.php');

// Inicialización de variables
$totalVentas = 0;
$totalCoste = 0;
$totalEgresos = 0;
$totalEgresosManuales = 0;
$totalNomina = 0;
$totalGananciaAjustada = 0;
$fecha_inicio = '';
$fecha_fin = '';
$error = '';
$cajas_reporte = [];
$egresos_detalle = [];
$nominas_detalle = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];

    if (!$fecha_inicio || !$fecha_fin) {
        $error = "Debe seleccionar ambas fechas.";
    } elseif (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
        $error = "La fecha de inicio no puede ser posterior a la fecha de fin.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Totales generales del periodo
            $stmtTotales = $pdo->prepare("
                SELECT 
                    SUM(v.valor) AS total_ventas,
                    SUM(v.coste) AS total_coste,
                    SUM(CASE WHEN v.payment_method = 'Egreso' THEN ABS(v.valor) ELSE 0 END) AS total_egresos
                FROM ventas v
                JOIN cajas c ON v.caja_id = c.id
                WHERE c.estado = 0 
                  AND DATE(v.fecha) BETWEEN :fecha_inicio AND :fecha_fin
            ");
            $stmtTotales->execute([
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin
            ]);
            $resultTotales = $stmtTotales->fetch(PDO::FETCH_ASSOC);

            if ($resultTotales) {
                $totalVentas = floatval($resultTotales['total_ventas'] ?? 0);
                $totalCoste = floatval($resultTotales['total_coste'] ?? 0);
                $totalEgresos = floatval($resultTotales['total_egresos'] ?? 0);
            }

            // Detalle de cajas en el periodo
            $stmtCajas = $pdo->prepare("
                SELECT 
                    c.id,
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario,
                    c.fecha_apertura,
                    c.fecha_cierre,
                    IFNULL(vs.total_ventas_caja, 0) AS total_ventas_caja,
                    IFNULL(vs.total_coste_caja, 0) AS total_coste_caja,
                    IFNULL(vs.total_egresos_caja, 0) AS total_egresos_caja
                FROM cajas c
                JOIN usuarios u ON c.usuario_id = u.id
                LEFT JOIN (
                    SELECT 
                        caja_id,
                        SUM(valor) AS total_ventas_caja,
                        SUM(coste) AS total_coste_caja,
                        SUM(CASE WHEN payment_method = 'Egreso' THEN ABS(valor) ELSE 0 END) AS total_egresos_caja
                    FROM ventas
                    WHERE DATE(fecha) BETWEEN :fecha_inicio AND :fecha_fin
                    GROUP BY caja_id
                ) vs ON c.id = vs.caja_id
                WHERE c.estado = 0
                  AND c.borrado = 0
                  AND DATE(c.fecha_cierre) BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY c.fecha_cierre DESC, c.id ASC
            ");
            $stmtCajas->execute([
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin
            ]);
            $cajas_reporte = $stmtCajas->fetchAll(PDO::FETCH_ASSOC);

            // Cálculo de ganancia ajustada por caja
            foreach ($cajas_reporte as &$caja) {
                $ventas = $caja['total_ventas_caja'] ?? 0;
                $costes = $caja['total_coste_caja'] ?? 0;
                $egresos = $caja['total_egresos_caja'] ?? 0;

                $caja['total_ganancia_caja_ajustada'] = ($ventas - $costes) - $egresos;
            }

            // Consulta para egresos manuales del periodo
            $stmtEgresos = $pdo->prepare("
                SELECT id, fecha, descripcion, valor 
                FROM egresos 
                WHERE borrado = 0
                  AND DATE(fecha) BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY fecha DESC
            ");
            $stmtEgresos->execute([
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin
            ]);
            $egresos_detalle = $stmtEgresos->fetchAll(PDO::FETCH_ASSOC);

            // Sumar egresos manuales
            foreach ($egresos_detalle as $egreso) {
                $totalEgresosManuales += floatval($egreso['valor']);
            }

            // Consulta para nóminas del periodo
            $stmtNominas = $pdo->prepare("
                SELECT id_nomina, nombre_empleado, fecha_generacion, valor_pagado 
                FROM nomina 
                WHERE DATE(fecha_generacion) BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY fecha_generacion DESC
            ");
            $stmtNominas->execute([
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin
            ]);
            $nominas_detalle = $stmtNominas->fetchAll(PDO::FETCH_ASSOC);

            // Sumar nóminas
            foreach ($nominas_detalle as $nomina) {
                $totalNomina += floatval($nomina['valor_pagado']);
            }

            // Ajustar totales
            $totalEgresos += $totalEgresosManuales + $totalNomina;
            $totalGananciaAjustada = ($totalVentas - $totalCoste) - $totalEgresos;

        } catch (PDOException $e) {
            $error = "Error en la consulta: " . $e->getMessage();
        }
    }
}
?>

<?php
  // construimos el texto que el usuario verá: 2025-06-11 to 2025-06-27
  $rangoValor = ($fecha_inicio && $fecha_fin)
                  ? $fecha_inicio . ' to ' . $fecha_fin
                  : '';
?>

<?php
$rangoFlat = ($fecha_inicio && $fecha_fin)
               ? $fecha_inicio.' to '.$fecha_fin   // EXACTO así: “YYYY-MM-DD to YYYY-MM-DD”
               : '';
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas por Rango de Fechas</title>
    <?php include('../inc/header.php'); ?>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js"></script>

    <style>
        .card { margin-bottom: 20px; }
        .total-egresos-breakdown { font-size: 0.8rem; }
        .section-title { font-size: 1.1rem; font-weight: bold; }
		/* ancho máximo y bordes redondeados algo más suaves */
input.rango{
  max-width: 500px;      /* ajústalo a tu gusto */
  border-radius: .35rem; /* opcional, redondeo leve */
}

    </style>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Reporte de Contabilidad</h1>
  </div>
</div>
<?php include('../inc/menu.php'); ?>

<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <h4 class="card-title mb-0">Reporte de Ventas por Rango de Fechas</h4>
    </div>
    <div class="card-body">
        <!-- ========= FORMULARIO FILTRO DE FECHAS ========= -->
<form method="POST" class="mb-4">
  <div class="row g-2">

    <!-- input visible + label -->
    <div class="col-md-6 col-sm-8">
      <label for="rangoFechas" class="form-label mb-1">Rango de fechas:</label>
      <input
  type="text"
  id="rangoFechas"
  class="form-control rango"
  placeholder="dd de mes de aaaa → dd de mes de aaaa"
  autocomplete="off"
  readonly
  value="<?= $rangoFlat ?>"
>

    </div>

    <!-- botón -->
    <div class="col-md-2 col-sm-4 d-flex align-items-end">
      <button type="submit" class="btn btn-danger w-100">Filtrar</button>
    </div>

    <!-- ocultos para el POST -->
    <!-- campos ocultos que realmente envías -->
<input type="hidden" name="fecha_inicio" id="fecha_inicio">
<input type="hidden" name="fecha_fin"    id="fecha_fin">
  </div>
</form>



        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)): ?>
            <!-- Totales Generales -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Totales del Periodo <?= $fecha_inicio ?> - <?= $fecha_fin ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 border-end">
                            <strong>Total de Ventas:</strong><br>
                            $<?= number_format($totalVentas, 0, '', '.') ?>
                        </div>
                        <div class="col-md-2 border-end">
                            <strong>Total de Costos:</strong><br>
                            $<?= number_format($totalCoste, 0, '', '.') ?>
                        </div>
                        <div class="col-md-3 border-end">
                            <strong>Total de Egresos:</strong><br>
                            <span class="total-egresos-breakdown">
                                (En Cajas: $<?= number_format(floatval($resultTotales['total_egresos'] ?? 0), 0, '', '.') ?>)<br>
                                (Manuales: $<?= number_format($totalEgresosManuales, 0, '', '.') ?>)<br>
                                (Nóminas: $<?= number_format($totalNomina, 0, '', '.') ?>)
                            </span><br>
                            $<?= number_format($totalEgresos, 0, '', '.') ?>
                        </div>
                        <div class="col-md-2 border-end">
                            <strong>Ganancia Bruta:</strong><br>
                            $<?= number_format(($totalVentas - $totalCoste), 0, '', '.') ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Ganancia Neta:</strong><br>
                            $<?= number_format($totalGananciaAjustada, 0, '', '.') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Cajas -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Cajas Cerradas en el Periodo</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($cajas_reporte)): ?>
                        <div class="table-responsive">
                            <table id="tablaCajas" class="table table-striped" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Apertura</th>
            <th>Cierre</th>
            <th>Ventas</th>
            <th>Costos</th>
            <th>Egresos</th>
            <th>Ganancia</th>
        </tr>
    </thead>
    <tbody>
        <!-- Los datos se cargarán via AJAX -->
    </tbody>
</table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No se encontraron cajas cerradas en este periodo.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla de Egresos Manuales -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Egresos Manuales del Periodo (Total: $<?= number_format($totalEgresosManuales, 0, '', '.') ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($egresos_detalle)): ?>
                        <div class="table-responsive">
                            <table id="tablaEgresos" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Descripción</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($egresos_detalle as $egreso): ?>
                                    <tr>
                                        <td><?= $egreso['fecha'] ?></td>
                                        <td><?= htmlspecialchars($egreso['descripcion']) ?></td>
                                        <td>$<?= number_format($egreso['valor'], 0, '', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No se encontraron egresos manuales en este periodo.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla de Nóminas -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Nóminas del Periodo (Total: $<?= number_format($totalNomina, 0, '', '.') ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($nominas_detalle)): ?>
                        <div class="table-responsive">
                            <table id="tablaNominas" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Empleado</th>
                                        <th>Fecha</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($nominas_detalle as $nomina): ?>
                                    <tr>
                                        <td><?= $nomina['id_nomina'] ?></td>
                                        <td><?= htmlspecialchars($nomina['nombre_empleado']) ?></td>
                                        <td><?= $nomina['fecha_generacion'] ?></td>
                                        <td>$<?= number_format($nomina['valor_pagado'], 0, '', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No se encontraron nóminas en este periodo.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Inicialización de DataTables para cajas
   
	// Limpia cualquier instancia previa
    if ($.fn.DataTable.isDataTable('#tablaCajas')) {
        $('#tablaCajas').DataTable().clear().destroy();
    }
    
    // Crea la tabla con configuración optimizada
    var table = $('#tablaCajas').DataTable({
        "data": <?php echo json_encode($cajas_reporte); ?>,
        "columns": [
            { "data": "id" },
            { "data": "usuario" },
            { 
                "data": "fecha_apertura",
                "render": function(data) {
                    return data ? data : 'N/A';
                }
            },
            { 
                "data": "fecha_cierre",
                "render": function(data) {
                    return data ? data : 'No registrada';
                }
            },
            { 
                "data": "total_ventas_caja",
                "render": function(data) {
                    return '$' + Math.floor(data).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }
            },
            { 
                "data": "total_coste_caja",
                "render": function(data) {
                    return '$' + Math.floor(data).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }
            },
            { 
                "data": "total_egresos_caja",
                "render": function(data) {
                    return '$' + Math.floor(data).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }
            },
            { 
                "data": "total_ganancia_caja_ajustada",
                "render": function(data) {
                    return '$' + Math.floor(data).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }
            }
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "destroy": true,
        "responsive": true,
        "autoWidth": false,
        "createdRow": function(row, data, dataIndex) {
            // Añade el atributo data-id a cada fila
            $(row).attr('data-id', data.id);
            // Estilo para indicar que es clickeable
            $(row).css('cursor', 'pointer');
        }
    });

	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Todas las Cajas', $_SESSION["user_permissions"])): ?>
    // Evento click para abrir el detalle
    $('#tablaCajas tbody').on('click', 'tr', function() {
        var cajaId = $(this).data('id');
        if (cajaId) {
            window.open('/admin/caja/caja_detail.php?id=' + cajaId, '_blank');
        }
    });
	<?php endif;?>

    // Inicialización de DataTables para egresos
    $('#tablaEgresos').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "order": [[0, "desc"]],
        "responsive": true
    });

    // Inicialización de DataTables para nóminas
    $('#tablaNominas').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "order": [[2, "desc"]],
        "responsive": true
    });
});
</script>
	
	
	<script>
/* valores que vienen de PHP para repintar el rango */
const rangoIni = "<?= $fecha_inicio ?>";
const rangoFin = "<?= $fecha_fin   ?>";

flatpickr("#rangoFechas",{
  mode         : "range",
  locale       : "es",
  dateFormat   : "Y-m-d",              // valor que guarda en #rangoFechas
  altInput     : true,                 // crea el campo “bonito” (visible)
  altFormat    : "d \\de F \\de Y",    // 27 de junio de 2025
  altSeparator : "  a  ",
  maxDate      : "today",
  defaultDate  : (rangoIni && rangoFin) ? [rangoIni,rangoFin] : null,  // 👈
  onReady      : (_,__,fp) => {        // ← rellena ocultos al recargar
      if (rangoIni && rangoFin){
          document.getElementById('fecha_inicio').value = rangoIni;
          document.getElementById('fecha_fin').value    = rangoFin;
      }
  },
  onChange : (sel) => {
      if(sel.length===2){
          document.getElementById('fecha_inicio').value =
            flatpickr.formatDate(sel[0],"Y-m-d");
          document.getElementById('fecha_fin').value =
            flatpickr.formatDate(sel[1],"Y-m-d");
      }
  }
});
</script>

	
	

</body>
</html>


