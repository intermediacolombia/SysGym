<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php 
$permisopage = 'Ver y Editar Bolsillos';
include('../login/restriction.php'); ?>
<?php
// pocket.php para Bolsillos

require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

// Procesar peticiones Ajax para CRUD
if(isset($_GET['action']) && $_GET['action'] == "fetch"){
    // Obtener todos los bolsillos no borrados
    $stmt = $pdo->prepare("SELECT id, nombre FROM bolsillos WHERE borrado = 0");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

if(isset($_POST['action'])){
    $action = $_POST['action'];
    if($action == "add"){
        $nombre = trim($_POST['nombre']);
        
        // Verificar si ya existe un bolsillo con el mismo nombre
        $stmtCheck = $pdo->prepare("SELECT id, borrado FROM bolsillos WHERE nombre = :nombre LIMIT 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if($existing){
            if($existing['borrado'] == 0){
                echo json_encode(['status' => 'error', 'message' => 'El bolsillo ya existe']);
                exit;
            } else {
                // Reactivar el bolsillo que estaba borrado
                $stmtUpdate = $pdo->prepare("UPDATE bolsillos SET borrado = 0 WHERE id = :id");
                if($stmtUpdate->execute([':id' => $existing['id']])){
                    echo json_encode(['status' => 'success', 'message' => 'Bolsillo reactivado correctamente']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al reactivar el bolsillo']);
                }
                exit;
            }
        }
        
        // Insertar nuevo bolsillo
        $stmt = $pdo->prepare("INSERT INTO bolsillos (nombre, borrado) VALUES (:nombre, 0)");
        if($stmt->execute([':nombre' => $nombre])){
            echo json_encode(['status' => 'success', 'message' => 'Bolsillo agregado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el bolsillo']);
        }
        exit;
    } elseif($action == "edit"){
        $id = trim($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $stmt = $pdo->prepare("UPDATE bolsillos SET nombre = :nombre WHERE id = :id");
        if($stmt->execute([':nombre' => $nombre, ':id' => $id])){
            echo json_encode(['status' => 'success', 'message' => 'Bolsillo actualizado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el bolsillo']);
        }
        exit;
    } elseif($action == "delete"){
        $id = trim($_POST['id']);
        // Marcar el bolsillo como borrado
        $stmt = $pdo->prepare("UPDATE bolsillos SET borrado = 1 WHERE id = :id");
        if($stmt->execute([':id' => $id])){
            echo json_encode(['status' => 'success', 'message' => 'Bolsillo borrado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar el bolsillo']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Bolsillos</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
  
  	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
	<div class="portada">
    <h1>Gestión de Bolsillos</h1>
		<button class="btn btn-success float-end" id="btnAddBolsillo"><i class="fa fa-plus"></i> Agregar Nuevo Bolsillo</button>
	</div>
	</div>
	<?php include('../inc/menu.php'); ?>
	<div class="container mt-4">
    
    <table id="bolsillos-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Nombre</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>

  <!-- Modal para Agregar Bolsillo -->
  <div class="modal fade" id="modalAddBolsillo" tabindex="-1" aria-labelledby="modalAddBolsilloLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formAddBolsillo">
          <div class="modal-header">
            <h5 class="modal-title" id="modalAddBolsilloLabel">Agregar Nuevo Bolsillo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="add_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="add_nombre" name="nombre" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Modal para Editar Bolsillo -->
  <div class="modal fade" id="modalEditBolsillo" tabindex="-1" aria-labelledby="modalEditBolsilloLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formEditBolsillo">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditBolsilloLabel">Editar Bolsillo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_id" name="id">
            <div class="mb-3">
              <label for="edit_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="btnDeleteBolsillo"><i class="fa fa-trash-o"></i> Borrar Bolsillo</button>
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php include('../inc/menu-footer.php'); ?>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap 5 JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
    $(document).ready(function(){
      // Inicializar DataTable
      var table = $('#bolsillos-table').DataTable({
        "ajax": "pocket.php?action=fetch",
        "columns": [
          { "data": "nombre" }
        ],
        "pageLength": 50,
        "language": {
          "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
      });

      // Abrir modal para agregar un nuevo bolsillo
      $("#btnAddBolsillo").click(function(){
        $("#formAddBolsillo")[0].reset();
        $("#modalAddBolsillo").modal("show");
      });

      // Agregar bolsillo vía Ajax
      $("#formAddBolsillo").on("submit", function(e){
        e.preventDefault();
        $.ajax({
          url: "pocket.php",
          method: "POST",
          data: {
            action: "add",
            nombre: $("#add_nombre").val()
          },
          dataType: "json",
          success: function(response){
            if(response.status === "success"){
              Swal.fire("Éxito", response.message, "success");
              $("#modalAddBolsillo").modal("hide");
              table.ajax.reload();
            } else {
              Swal.fire("Error", response.message, "error");
            }
          }
        });
      });

      // Al hacer click en una fila, abrir modal para editar
      $('#bolsillos-table tbody').on('click', 'tr', function(){
        var data = table.row(this).data();
        if(data){
          $("#edit_id").val(data.id);
          $("#edit_nombre").val(data.nombre);
          $("#modalEditBolsillo").modal("show");
        }
      });

      // Editar bolsillo vía Ajax
      $("#formEditBolsillo").on("submit", function(e){
        e.preventDefault();
        $.ajax({
          url: "pocket.php",
          method: "POST",
          data: {
            action: "edit",
            id: $("#edit_id").val(),
            nombre: $("#edit_nombre").val()
          },
          dataType: "json",
          success: function(response){
            if(response.status === "success"){
              Swal.fire("Éxito", response.message, "success");
              $("#modalEditBolsillo").modal("hide");
              table.ajax.reload();
            } else {
              Swal.fire("Error", response.message, "error");
            }
          }
        });
      });

      // Borrar bolsillo vía Ajax (desde el modal de edición)
      $("#btnDeleteBolsillo").on("click", function(){
        var id = $("#edit_id").val();
        Swal.fire({
          title: "¿Está seguro?",
          text: "Esta acción borrará el bolsillo.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Sí, borrar",
          cancelButtonText: "Cancelar"
        }).then((result) => {
          if(result.isConfirmed){
            $.ajax({
              url: "pocket.php",
              method: "POST",
              data: { action: "delete", id: id },
              dataType: "json",
              success: function(response){
                if(response.status === "success"){
                  Swal.fire("Borrado", response.message, "success");
                  $("#modalEditBolsillo").modal("hide");
                  table.ajax.reload();
                } else {
                  Swal.fire("Error", response.message, "error");
                }
              }
            });
          }
        });
      });
    });
  </script>
</body>
</html>
