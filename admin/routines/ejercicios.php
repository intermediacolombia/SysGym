<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
$permisopage = 'Ver Ejercicios';
include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

if(isset($_GET['action']) && $_GET['action'] == "fetch"){
    $stmt = $pdo->prepare("SELECT id, nombre, descripcion, url_video FROM ejercicios ORDER BY nombre ASC");
    $stmt->execute();
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if(isset($_POST['action'])){
    $action = $_POST['action'];

    if($action == "add"){
        $stmt = $pdo->prepare("INSERT INTO ejercicios (nombre, descripcion, url_video) VALUES (:nombre, :descripcion, :url_video)");
        $success = $stmt->execute([
            ':nombre' => $_POST['nombre'],
            ':descripcion' => $_POST['descripcion'],
            ':url_video' => $_POST['url_video']
        ]);
        echo json_encode(['status' => $success ? 'success' : 'error']);
        exit;
    }

    if($action == "edit"){
        $stmt = $pdo->prepare("UPDATE ejercicios SET nombre = :nombre, descripcion = :descripcion, url_video = :url_video WHERE id = :id");
        $success = $stmt->execute([
            ':nombre' => $_POST['nombre'],
            ':descripcion' => $_POST['descripcion'],
            ':url_video' => $_POST['url_video'],
            ':id' => $_POST['id']
        ]);
        echo json_encode(['status' => $success ? 'success' : 'error']);
        exit;
    }

    if($action == "delete"){
        $stmt = $pdo->prepare("DELETE FROM ejercicios WHERE id = :id");
        $success = $stmt->execute([':id' => $_POST['id']]);
        echo json_encode(['status' => $success ? 'success' : 'error']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Ejercicios</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
	<h1>Gestión de Ejercicios</h1>
	 <button class="btn btn-success float-end" id="btnAddEjercicio"><i class="fa fa-plus"></i> Agregar Ejercicio</button>
	</div>
</div>
<?php include('../inc/menu.php'); ?>
<div class="container">
 
 
  <table id="ejercicios-table" class="table table-striped">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Video</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal Agregar/Editar Ejercicio -->
<div class="modal fade" id="modalEjercicio" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEjercicio">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloModal">Nuevo Ejercicio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="ejercicio_id">
          <label>Nombre</label>
          <input type="text" name="nombre" id="nombre" class="form-control mb-2" required>
          <label>Descripción</label>
          <textarea name="descripcion" id="descripcion" class="form-control mb-2"></textarea>
          <label>URL Video (YouTube)</label>
          <input type="url" name="url_video" id="url_video" class="form-control mb-2">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../inc/menu-footer.php'); ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
  var table = $('#ejercicios-table').DataTable({
    ajax: 'ejercicios.php?action=fetch',
    columns: [
      { data: 'nombre' },
      { data: 'descripcion' },
      { 
        data: 'url_video', 
        render: function(data){
          return data ? `<a href="${data}" target="_blank">Ver video</a>` : '';
        }
      },
      {
        data: null,
        orderable: false,
        render: function(data, type, row){
          return `
            <button class="btn btn-sm btn-primary btnEdit" data-id="${row.id}"><i class="fa fa-edit"></i></button>
            <button class="btn btn-sm btn-danger btnDelete" data-id="${row.id}"><i class="fa fa-trash"></i></button>
          `;
        }
      }
    ]
  });

  $('#btnAddEjercicio').click(function(){
    $('#formEjercicio')[0].reset();
    $('#ejercicio_id').val('');
    $('#tituloModal').text('Nuevo Ejercicio');
    $('#modalEjercicio').modal('show');
  });

  // Editar ejercicio
  $('#ejercicios-table tbody').on('click', '.btnEdit', function(){
    var data = table.row($(this).closest('tr')).data();
    $('#ejercicio_id').val(data.id);
    $('#nombre').val(data.nombre);
    $('#descripcion').val(data.descripcion);
    $('#url_video').val(data.url_video);
    $('#tituloModal').text('Editar Ejercicio');
    $('#modalEjercicio').modal('show');
  });

  // Eliminar ejercicio
  $('#ejercicios-table tbody').on('click', '.btnDelete', function(){
    var id = $(this).data('id');
    Swal.fire({
      title: '¿Eliminar ejercicio?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('ejercicios.php', { action: 'delete', id: id }, function(resp){
          if(resp.status === 'success'){
            Swal.fire('Eliminado', '', 'success');
            table.ajax.reload();
          } else {
            Swal.fire('Error', 'No se pudo eliminar.', 'error');
          }
        }, 'json');
      }
    });
  });

  $('#formEjercicio').on('submit', function(e){
    e.preventDefault();
    let formData = $(this).serialize();
    let action = $('#ejercicio_id').val() ? 'edit' : 'add';
    formData += '&action=' + action;
    $.post('ejercicios.php', formData, function(resp){
      if(resp.status === 'success'){
        Swal.fire('Guardado', '', 'success');
        $('#modalEjercicio').modal('hide');
        table.ajax.reload();
      } else {
        Swal.fire('Error', '', 'error');
      }
    }, 'json');
  });
});
</script>
</body>
</html>

