<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Manejar Valoraciones';
include('../login/restriction.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Valoraciones</title>

  <?php include('../inc/header.php'); ?>

  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <style>
    #valoraciones-table tbody tr:hover {
      background-color: #f1f1f1;
      cursor: pointer;
    }
  </style>
</head>

<body>

<div class="container" style="padding:0;background:rgba(0,0,0,0)">
  <div class="portada">
    <h1>Valoraciones</h1>
    <button class="btn btn-success float-end" onclick="window.location='new.php'">
      <i class="fa fa-plus"></i> Nueva Valoración
    </button>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
  <table id="valoraciones-table" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>Cliente</th>
        <th>Fecha</th>
        <th>Peso (Kg)</th>
        <th>Estatura (m)</th>
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

  // Inicializar DataTable con AJAX
  var table = $('#valoraciones-table').DataTable({
    ajax: {
      url: "gets/get_valoraciones_all.php",
      dataSrc: "data"
    },
    pageLength: 50,
    order: [[1, 'desc']],
    columns: [
      { data: 'cliente' },
      { data: 'fecha_valoracion' },
      { data: 'peso' },
      { data: 'estatura' }
    ],
    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
    }
  });

  // Click → editar
  $('#valoraciones-table tbody').on('click', 'tr', function() {
    var row = table.row(this).data();
    if (row && row.id) {
      window.location = 'edit.php?id=' + encodeURIComponent(row.id);
    }
  });

  // Alertas de sesión
  <?php if(!empty($_SESSION['success'])): ?>
    Swal.fire({
      icon: 'success',
      title: 'Listo',
      text: '<?= $_SESSION['success'] ?>'
    });
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if(!empty($_SESSION['error'])): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '<?= $_SESSION['error'] ?>'
    });
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

});
</script>

</body>
</html>


