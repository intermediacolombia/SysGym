<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Asistencias';
include('../login/restriction.php');
session_start();
require_once __DIR__ . '/../../inc/config.php';

/* -------- consulta (día en español) -------- */
try {
    $pdo->exec("SET lc_time_names = 'es_ES';");        // «lunes», «martes»…

    $stmt = $pdo->query("
        SELECT  a.fecha,
                a.hora,
                DATE_FORMAT(a.fecha,'%W')      AS dia,
                c.id                           AS idCliente,
                c.identificacion,
                c.nombres,
                c.apellidos
        FROM    asistencias a
        JOIN    clientes c ON c.id = a.idCliente
        WHERE   c.borrado = 0
        ORDER BY a.fecha DESC, a.hora DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error DB: '.$e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Asistencias</title>

  <?php include('../inc/header.php'); ?>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables Bootstrap 5 CSS -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">


</head>
<body>

<div class="container" style="padding:0;background:rgba(0,0,0,.00)">
  <div class="portada">
    <h1>Listado de Asistencias</h1>
  </div>
</div>

<?php include('../inc/menu.php'); ?>
	
<!-- ---------------  SELECTOR DE FECHA --------------- -->


  


<div class="container mt-4">
	
	<div class="d-flex align-items-center mb-3">
  <label for="filtroFecha" class="me-2 mb-0">
    <i class="fa fa-calendar"></i> Fecha:
  </label>
  <input type="text" id="filtroFecha" class="form-control" style="max-width:180px"
         value="<?= $hoy ?>">
</div>
  <!-- ---------------  TABLA --------------- -->
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
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr data-id="<?= $r['idCliente'] ?>">
        <td><?= htmlspecialchars($r['fecha']) ?></td>
        <td><?= ucfirst($r['dia']) ?></td>
        <td data-order="<?= $r['hora'] ?>">
          <?php
            $h = DateTime::createFromFormat('H:i:s',$r['hora']);
            echo $h->format('g:i a');
          ?>
        </td>
        <td><?= htmlspecialchars($r['identificacion']) ?></td>
        <td><?= htmlspecialchars($r['nombres'].' '.$r['apellidos']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<!-- jQuery, Bootstrap, DataTables, SweetAlert2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<!-- ---------------  SCRIPTS --------------- -->

<script>
$(function () {

  /* === 1. DataTable === */
  const tabla = $('#asist-global').DataTable({
    order: [[0,'desc'],[2,'desc']],
    pageLength: 50,
    language:{url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'}
  });

  /*  fila → perfil cliente  */
  $(document).on('click','#asist-global tbody tr',function(){
    const id = $(this).data('id');
    if (id) window.location.href = "detail.php?id="+encodeURIComponent(id);
  });

  /* === 2. Calendario Flatpickr === */
  flatpickr('#filtroFecha',{
    dateFormat:'Y-m-d',
    locale:'es',
    defaultDate:'<?= $hoy ?>',
	  maxDate : 'today',
    onChange: function(sel){
      const fecha = sel[0].toISOString().slice(0,10);   // YYYY-MM-DD
      tabla.column(0).search('^'+fecha+'$', true, false).draw();
    }
  });
});
</script>
	</body>
</html>
