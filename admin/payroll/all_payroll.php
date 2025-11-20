<?php
// Sesiones y restricciones (ajusta a tu gusto)
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Nominas';
include('../login/restriction.php');

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Nóminas Pagadas</title>
  <?php include('../inc/header.php'); ?>
  <!-- Estilos personalizados -->
  <style>
    /* Estilo de la tabla */
    #tablaNominas tbody tr {
      cursor: pointer !important;
      transition: background-color 0.1s ease;
    }
    #tablaNominas thead th {
      background-color: #214A82;
      color: white;
    }
    #tablaNominas tbody tr:hover {
      background-color: #4972AA !important;
    }
    #tablaNominas tbody tr:hover td {
      color: white !important;
    }
  </style>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
    <h1>Listado de Nóminas Pagadas</h1>
	</div>
	</div>
<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
  

  <!-- Alertas de Bootstrap para mensajes de sesión -->
  <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  <?php endif; ?>
  <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  <?php endif; ?>

  <!-- Tabla de nóminas pagadas -->
  <table id="tablaNominas" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>ID Nómina</th>
        <th>Empleado</th>
        <th>Fecha de Generación</th>
        <th>Fecha de Inicio</th>
        <th>Fecha de Fin</th>
        <th>Días Laborados</th>
        <th>Valor Pagado</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Conexión a la BD
      require_once __DIR__ . '/../../inc/config.php';
      try {
         
          // Consultar nóminas pagadas
          $sql = "SELECT 
                      id_nomina,
                      nombre_empleado,
                      fecha_generacion,
                      fecha_inicio_pago,
                      fecha_fin_pago,
                      total_dias_pagados,
                      valor_pagado
                  FROM nomina
                  ORDER BY fecha_generacion DESC";
          $stmt = db()->query($sql);
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<tr class="nomina-row"
                  data-id="' . htmlspecialchars($row['id_nomina']) . '"
                  data-empleado="' . htmlspecialchars($row['nombre_empleado']) . '"
                  data-fecha-generacion="' . htmlspecialchars($row['fecha_generacion']) . '"
                  data-fecha-inicio="' . htmlspecialchars($row['fecha_inicio_pago']) . '"
                  data-fecha-fin="' . htmlspecialchars($row['fecha_fin_pago']) . '"
                  data-dias-laborados="' . htmlspecialchars($row['total_dias_pagados']) . '"
                  data-valor-pagado="' . htmlspecialchars($row['valor_pagado']) . '">
              ';
              echo '<td>' . htmlspecialchars($row['id_nomina']) . '</td>';
              echo '<td>' . htmlspecialchars($row['nombre_empleado']) . '</td>';
              echo '<td>' . htmlspecialchars($row['fecha_generacion']) . '</td>';
              echo '<td>' . htmlspecialchars($row['fecha_inicio_pago']) . '</td>';
              echo '<td>' . htmlspecialchars($row['fecha_fin_pago']) . '</td>';
              echo '<td>' . htmlspecialchars($row['total_dias_pagados']) . '</td>';
              echo '<td>$' . number_format($row['valor_pagado'], 0, ',', '.') . '</td>';
              echo '</tr>';
          }
      } catch (PDOException $e) {
          echo '<tr><td colspan="7">Error: ' . $e->getMessage() . '</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>

<!-- Modal para Detalles de Nómina -->
<div class="modal fade" id="detalleNominaModal" tabindex="-1" role="dialog" aria-labelledby="detalleNominaModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detalleNominaModalLabel">Detalles de la Nómina</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <ul class="list-unstyled">
          <li><strong>ID Nómina:</strong> <span id="modalId"></span></li>
          <li><strong>Empleado:</strong> <span id="modalEmpleado"></span></li>
          <li><strong>Fecha de Generación:</strong> <span id="modalFechaGeneracion"></span></li>
          <li><strong>Fecha de Inicio:</strong> <span id="modalFechaInicio"></span></li>
          <li><strong>Fecha de Fin:</strong> <span id="modalFechaFin"></span></li>
          <li><strong>Días Laborados:</strong> <span id="modalDiasLaborados"></span></li>
          <li><strong>Valor Pagado:</strong> $<span id="modalValorPagado"></span></li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php include('../inc/menu-footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function() {
  // Inicializar DataTables
  var table = $('#tablaNominas').DataTable({
    "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
    "order": [[0, "desc"]],
    "pageLength": 10
  });

  // Al hacer clic en una fila, abrir el modal con los detalles
  $('#tablaNominas tbody').on('click', 'tr.nomina-row', function() {
    var id = $(this).data('id');
    var empleado = $(this).data('empleado');
    var fechaGeneracion = $(this).data('fecha-generacion');
    var fechaInicio = $(this).data('fecha-inicio');
    var fechaFin = $(this).data('fecha-fin');
    var diasLaborados = $(this).data('dias-laborados');
    var valorPagado = $(this).data('valor-pagado');

    // Rellenar el modal con los datos
    $('#modalId').text(id);
    $('#modalEmpleado').text(empleado);
    $('#modalFechaGeneracion').text(fechaGeneracion);
    $('#modalFechaInicio').text(fechaInicio);
    $('#modalFechaFin').text(fechaFin);
    $('#modalDiasLaborados').text(diasLaborados);
    $('#modalValorPagado').text(valorPagado);

    // Abrir el modal
    $('#detalleNominaModal').modal('show');
  });

  // Asegurarse de que el modal se cierre correctamente
  $('#detalleNominaModal .close, #detalleNominaModal .btn-secondary').on('click', function() {
    $('#detalleNominaModal').modal('hide');
  });
});
</script>
</body>
</html>