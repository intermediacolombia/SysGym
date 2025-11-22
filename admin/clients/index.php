<?php 
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Clientes';
include('../login/restriction.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Clientes</title>

  <?php include('../inc/header.php'); ?>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>

<div class="container" style="padding: 0; background:rgba(0,0,0,0)">
  <div class="portada">
    <h1>Listado de Clientes</h1>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Agregar Clientes', $_SESSION["user_permissions"])): ?>
      <button class="btn btn-success float-end ms-2" onclick="window.location='new.php'">
        <i class="fa fa-plus"></i> Nuevo Cliente
      </button>
    <?php endif; ?>

    <button class="btn btn-primary float-end" onclick="window.location='client_search.php'">
      <i class="fa fa-search me-1"></i> Búsqueda Avanzada
    </button>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container mt-3">

  <div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" id="toggleInactiveSwitch">
    <label class="form-check-label" for="toggleInactiveSwitch">
      Ocultar clientes inactivos (<span id="inactiveCount">0</span>)
    </label>
  </div>

  <table id="clients-table" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>ID</th>
        <th>Identificación</th>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>Teléfono</th>
        <th>Plan</th>
        <th>Vencimiento Plan</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<?php include('../inc/menu-footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(function() {

  var table = $('#clients-table').DataTable({
    ajax: {
      url: "gets/get_clientes_all.php",
      dataSrc: "data"
    },
    pageLength: 50,
    columns: [
      { data: 'id' },
      { data: 'identificacion' },
      { data: 'nombres' },
      { data: 'apellidos' },
      { 
        data: null,
        render: r => "+" + r.dialCode + " " + r.telefono
      },
      { 
        data: null,
        render: r => {
          let html = "";
          if (r.nombre_plan) html += r.nombre_plan;
          if (r.congelado == 1) html += ' <i class="fa fa-snowflake-o text-info"></i>';
          return html || "—";
        }
      },
      { 
        data: 'vencimiento_plan',
        render: v => v ? v : "—"
      },
      { 
        data: 'estado',
        render: e => e === "activo" 
            ? '<span class="badge bg-success">Activo</span>' 
            : '<span class="badge bg-danger">Inactivo</span>'
      },
      {   // COLUMNA OCULTA PARA FILTRO REAL
        data: 'estado',
        visible: false
      }
    ],
    language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
  });

  // Recuento de clientes inactivos
  table.on('xhr', function() {
    let rows = table.ajax.json().data || [];
    let count = rows.filter(r => r.estado !== "activo").length;
    $('#inactiveCount').text(count);
  });

  // Filtro de clientes inactivos
  $('#toggleInactiveSwitch').on('change', function() {
    if (this.checked) {
      table.column(8).search('Activo', true, false).draw(); 
    } else {
      table.column(8).search('', false, true).draw();
    }
  });

  // Click → detalle
  $('#clients-table tbody').on('click', 'tr', function() {
    const row = table.row(this).data();
    if (row?.id) {
      window.location.href = "detail.php?id=" + row.id;
    }
  });

});
</script>

</body>
</html>







