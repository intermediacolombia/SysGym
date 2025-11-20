<?php
// list_valoraciones.php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Manejar Valoraciones';
include('../login/restriction.php');

require_once __DIR__ . '/../../inc/config.php';



// Traer todas las valoraciones con datos básicos del cliente
$stmt = db()->query("
    SELECT 
  v.id,
  CONCAT(c.nombres, ' ', c.apellidos) AS cliente,
  DATE(v.created_at)         AS fecha_creacion,
  v.fecha                    AS fecha_valoracion,
  v.peso,
  v.estatura
FROM valoraciones v
JOIN clientes c 
  ON c.id = v.cliente_id
WHERE v.borrado = 0
ORDER BY v.id DESC;
");
$valoraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Valoraciones</title>
  <?php include('../inc/header.php'); ?>
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <style>
    /* Hover y puntero para filas clicables */
    #valoraciones-table tbody tr:hover {
      background-color: #f1f1f1;
      cursor: pointer;
    }
  </style>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
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
      <tbody>
        <?php foreach($valoraciones as $v): ?>
          <tr data-id="<?= $v['id'] ?>">
            
            <td><?= htmlspecialchars($v['cliente']) ?></td>
            <td><?= htmlspecialchars($v['fecha_valoracion']) ?></td>
            <td><?= htmlspecialchars($v['peso']) ?></td>
            <td><?= htmlspecialchars($v['estatura']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php include('../inc/menu-footer.php'); ?>

  <!-- jQuery (necesario para DataTables) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function(){
      // Inicializar DataTable
      var table = $('#valoraciones-table').DataTable({
        pageLength: 50,
		  order: [[1, 'desc']],
        language: {
          url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
      });

      // Al hacer clic en la fila, redirige a editar
      $('#valoraciones-table tbody').on('click', 'tr', function() {
        var id = $(this).data('id');
        if (id) {
          window.location = 'edit.php?id=' + encodeURIComponent(id);
        }
      });
    });
  </script>
	
	<script>
    <?php if(!empty($_SESSION['success'])): ?>
    Swal.fire({
      icon: 'success',
      title: '¡Listo!',
      text: '<?= $_SESSION['success'] ?>'
    });
    <?php unset($_SESSION['success']); endif; ?>
    <?php if(!empty($_SESSION['error'])): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '<?= $_SESSION['error'] ?>'
    });
    <?php unset($_SESSION['error']); endif; ?>
  </script>
</body>
</html>

