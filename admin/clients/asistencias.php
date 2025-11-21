<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Asistencias';
include('../login/restriction.php');

require_once __DIR__ . '/../../inc/config.php';

// Fecha de hoy (para el filtro inicial)
$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Listado de Asistencias</title>
<?php include('../inc/header.php'); ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body>

<div class="container" style="padding:0;background:rgba(0,0,0,.00)">
  <div class="portada">
    <h1>Listado de Asistencias</h1>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container mt-4">

  <!-- Filtro por fecha -->
  <div class="d-flex align-items-center mb-3">
    <label for="filtroFecha" class="me-2 mb-0">
      <i class="fa fa-calendar"></i> Fecha:
    </label>
    <input type="text" id="filtroFecha" class="form-control" style="max-width:180px"
           value="<?= $hoy ?>">
  </div>

  <!-- Tabla -->
  <table id="asist-global" class="table table-striped table-bordered w-100">
    <thead class="table-dark">
      <tr>
        <th>Fecha</th>
        <th>Día</th>
        <th>Hora</th>
        <th>Identificación</th>
        <th>Nombre</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script>
$(async function(){

  /* === 1. Cargar JSON === */
  let data = [];
  try {
    data = await $.getJSON("asistencias.json"); // ← archivo estático
  } catch (error) {
    console.error("Error cargando asistencias.json", error);
    Swal.fire("Error", "No se pudo cargar el listado de asistencias.", "error");
    return;
  }

  /* === 2. Inicializar DataTable === */
  const tabla = $('#asist-global').DataTable({
    ajax: {
        url: "get_asistencias_all.php",
        dataSrc: "data"
    },
    columns: [
      { data: 'fecha' },
      { data: 'dia' },
      { 
        data: 'hora',
        render: function (h) {
          const [HH,MM] = h.split(":");
          const ampm = (HH >= 12 ? 'pm' : 'am');
          const g = ((HH % 12) || 12);
          return g + ":" + MM + " " + ampm;
        }
      },
      { data: 'identificacion' },
      { data: null, render: r => r.nombres + " " + r.apellidos }
    ],
    order: [[0,'desc'],[2,'desc']],
    pageLength: 50,
    language:{url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'}
});


  /* === Click → detalle cliente === */
  $('#asist-global tbody').on('click','tr',function(){
    const row = tabla.row(this).data();
    if (row?.idCliente) {
      window.location.href = "detail.php?id="+row.idCliente;
    }
  });

  /* === 3. Filtro por fecha === */
  flatpickr('#filtroFecha',{
    dateFormat:'Y-m-d',
    locale:'es',
    defaultDate:'<?= $hoy ?>',
    maxDate : 'today',
    onChange: function(sel){
      const fecha = sel[0].toISOString().slice(0,10);
      tabla.column(0).search('^'+fecha+'$', true, false).draw();
    }
  });

});
</script>

</body>
</html>

