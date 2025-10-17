<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

// Inicialización de variables
$totalVentas = 0;
$totalCoste = 0;
$totalEgresos = 0;
$totalGananciaAjustada = 0;
$fecha_inicio = '';
$fecha_fin = '';
$error = '';
$cajas_reporte = [];

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
                $totalGananciaAjustada = ($totalVentas - $totalCoste) - $totalEgresos;
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

        } catch (PDOException $e) {
            $error = "Error en la consulta: " . $e->getMessage();
        }
    }
}
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas por Rango de Fechas</title>
    <?php include('../inc/header.php'); ?>
    <style>.card { margin-bottom: 20px; }</style>
</head>
<body>
<?php include('../inc/menu.php'); ?>

<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <h4 class="card-title mb-0">Reporte de Ventas por Rango de Fechas</h4>
    </div>
    <div class="card-body">
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio:</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= $fecha_inicio ?>" required>
                </div>
                <div class="col-md-5 mb-3">
                    <label for="fecha_fin" class="form-label">Fecha de Fin:</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= $fecha_fin ?>" required>
                </div>
                <div class="col-md-2 align-self-end mb-3">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
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
                        <div class="col-md-3 border-end"><strong>Total de Ventas:</strong><br>$<?= number_format($totalVentas, 0, '', '.') ?></div>
                        <div class="col-md-3 border-end"><strong>Total de Costos:</strong><br>$<?= number_format($totalCoste, 0, '', '.') ?></div>
                        <div class="col-md-3 border-end"><strong>Total de Egresos:</strong><br>$<?= number_format($totalEgresos, 0, '', '.') ?></div>
                        <div class="col-md-3"><strong>Total de Ganancia:</strong><br>$<?= number_format($totalGananciaAjustada, 0, '', '.') ?></div>
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

    // Evento click para abrir el detalle
    $('#tablaCajas tbody').on('click', 'tr', function() {
        var cajaId = $(this).data('id');
        if (cajaId) {
            window.open('/admin/caja/caja_detail.php?id=' + cajaId, '_blank');
        }
    });
});
</script>

</body>
</html>


