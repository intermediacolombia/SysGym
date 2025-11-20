<?php require_once __DIR__ . '/../login/session.php';?>

<?php
$permisopage = 'Ver Planes';
include('../login/restriction.php');?>
<?php
// index.php

require_once __DIR__ . '/../../inc/config.php';


// Procesar peticiones Ajax para CRUD
if(isset($_GET['action']) && $_GET['action'] == "fetch"){
    // Obtener todos los planes no borrados (incluyendo frecuencia)
    // Cambiar la consulta para incluir "dias"
	$stmt = db()->prepare("SELECT id, nombre, precio, frecuencia, dias, estado FROM planes WHERE borrado = 0");

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data'=>$data]);
    exit;
}

if(isset($_POST['action'])){
    $action = $_POST['action'];
    if($action == "add"){
    $nombre = trim($_POST['nombre']);
    $precio = trim($_POST['precio']);
    $frecuencia = trim($_POST['frecuencia']);
    $dias = trim($_POST['dias']); // Nuevo campo: días adicionales
    $estado = trim($_POST['estado']); // Este valor se usará solo si el plan es nuevo

    // Verificar si ya existe un plan con el mismo nombre
    $stmtCheck = db()->prepare("SELECT id, borrado FROM planes WHERE nombre = :nombre LIMIT 1");
    $stmtCheck->execute([':nombre' => $nombre]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if($existing){
        if($existing['borrado'] == 0){
            echo json_encode(['status'=>'error', 'message'=>'El plan ya existe']);
            exit;
        } else {
            // Reactivar y actualizar si está borrado
            $stmtUpdate = db()->prepare("UPDATE planes SET precio = :precio, frecuencia = :frecuencia, dias = :dias, estado = :estado, borrado = 0 WHERE id = :id");
            if($stmtUpdate->execute([':precio'=>$precio, ':frecuencia'=>$frecuencia, ':dias'=>$dias, ':estado'=>$estado, ':id'=>$existing['id']])) {
				
				// LOGS
		require_once __DIR__ . '/../inc/log_action.php';		
		$desc = json_encode([
				'precio'=>$precio, 
				'frecuencia'=>$frecuencia, 
				'dias'=>$dias, 
				'estado'=>$estado, 
				'id'=>$existing['id']		
		], JSON_UNESCAPED_UNICODE);
		log_action('Agregar Planes', $desc, 'Planes');
		// END LOGS	
				
                echo json_encode(['status'=>'success', 'message'=>'Plan reactivado y actualizado correctamente']);
            } else {
                echo json_encode(['status'=>'error', 'message'=>'Error al reactivar el plan']);
            }
            exit;
        }
    }
    // Insertar nuevo plan
    $stmt = db()->prepare("INSERT INTO planes (nombre, precio, frecuencia, dias, estado, borrado) VALUES (:nombre, :precio, :frecuencia, :dias, :estado, 0)");
    if($stmt->execute([':nombre'=>$nombre, ':precio'=>$precio, ':frecuencia'=>$frecuencia, ':dias'=>$dias, ':estado'=>$estado])){
        echo json_encode(['status'=>'success', 'message'=>'Plan agregado correctamente']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Error al agregar el plan']);
    }
    exit;
    } elseif($action == "edit"){
    $id = trim($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $precio = trim($_POST['precio']);
    $frecuencia = trim($_POST['frecuencia']);
    $dias = trim($_POST['dias']); // Nuevo campo: días adicionales
    $estado = trim($_POST['estado']);
    $stmt = db()->prepare("UPDATE planes SET nombre = :nombre, precio = :precio, frecuencia = :frecuencia, dias = :dias, estado = :estado WHERE id = :id");
    if($stmt->execute([':nombre'=>$nombre, ':precio'=>$precio, ':frecuencia'=>$frecuencia, ':dias'=>$dias, ':estado'=>$estado, ':id'=>$id])){
        
		// LOGS
		require_once __DIR__ . '/../inc/log_action.php';		
		$desc = json_encode([
				'nombre'=>$nombre, 
				'precio'=>$precio, 
				'frecuencia'=>$frecuencia, 
				'dias'=>$dias, 
				'estado'=>$estado, 
				'id'=>$id
			
		], JSON_UNESCAPED_UNICODE);
		log_action('Agregar Planes', $desc, 'Planes');
		// END LOGS	
		
		echo json_encode(['status'=>'success', 'message'=>'Plan actualizado correctamente']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Error al actualizar el plan']);
    }
    exit;
    } elseif($action == "delete"){
        $id = trim($_POST['id']);
        // Marcar el plan como borrado (borrado = 1)
        $stmt = db()->prepare("UPDATE planes SET borrado = 1 WHERE id = :id");
        if($stmt->execute([':id'=>$id])){
			
			// LOGS
		require_once __DIR__ . '/../inc/log_action.php';		
		$desc = json_encode([
				'id'=>$id,			
		], JSON_UNESCAPED_UNICODE);
		log_action('Borrar Planes', $desc, 'Planes');
		// END LOGS	
			
            echo json_encode(['status'=>'success', 'message'=>'Plan borrado correctamente']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Error al borrar el plan']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Planes</title>
 <?php include('../inc/header.php'); ?>
  
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
	<h1>Gestión de Planes</h1>
	
	<button class="btn btn-success float-end" id="btnAddPlan"><i class="fa fa-plus"></i> Agregar Nuevo Plan</button>
	</div>
	</div>
<?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    
    <!--button class="btn btn-success mb-3" id="btnAddPlan"><i class="fa fa-plus"></i> Agregar Nuevo Plan</button-->
    <table id="planes-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Precio</th>
          <th>Frecuencia (en Meses)</th>
			<th>Frecuencia (en Dias)</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>

  <!-- Modal para Agregar Plan -->
<div class="modal fade" id="modalAddPlan" tabindex="-1" aria-labelledby="modalAddPlanLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAddPlan">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAddPlanLabel">Agregar Nuevo Plan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="add_nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="add_nombre" name="nombre" required>
          </div>
			
			<label for="add_precio" class="form-label">Precio</label>
          <div class="input-group mb-3">			  
			  <div class="input-group-prepend">
    		<span class="input-group-text" id="basic-addon1">$</span>
  			</div>            
            <input type="number" class="form-control" id="add_precio" name="precio" required step="0.01">
          </div>
			
          <div class="mb-3">
            <label for="add_frecuencia" class="form-label">Frecuencia (meses)</label>
            <input type="number" class="form-control" id="add_frecuencia" name="frecuencia" required>
          </div>
          <div class="mb-3">
            <label for="add_dias" class="form-label">Frecuencia (dias)</label>
            <input type="number" class="form-control" id="add_dias" name="dias" required value="0">
          </div>
          <div class="mb-3">
            <label for="add_estado" class="form-label">Estado</label>
            <select class="form-control" id="add_estado" name="estado" required>
              <option value="activo" selected>Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
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
	
  <!-- Modal para Editar Plan -->
  <!-- Modal para Editar Plan -->
<div class="modal fade" id="modalEditPlan" tabindex="-1" aria-labelledby="modalEditPlanLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEditPlan">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditPlanLabel">Editar Plan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id" name="id">
          <div class="mb-3">
            <label for="edit_nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
          </div>
        
            <label for="edit_precio" class="form-label">Precio</label>
			  <div class="input-group mb-3">
  <div class="input-group-prepend">
    <span class="input-group-text" id="basic-addon1">$</span>
  </div>
            <input type="number" class="form-control" id="edit_precio" name="precio" required step="0.01">
          </div>
          <div class="mb-3">
            <label for="edit_frecuencia" class="form-label">Frecuencia (meses)</label>
            <input type="number" class="form-control" id="edit_frecuencia" name="frecuencia" required>
          </div>
          <div class="mb-3">
            <label for="edit_dias" class="form-label">Días</label>
            <input type="number" class="form-control" id="edit_dias" name="dias" required value="0">
          </div>
          <div class="mb-3">
            <label for="edit_estado" class="form-label">Estado</label>
            <select class="form-control" id="edit_estado" name="estado" required>
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" id="btnDeletePlan"><i class="fa fa-trash-o"></i> Borrar Plan</button>
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
      var table = $('#planes-table').DataTable({
        "ajax": "index.php?action=fetch",
        "columns": [
          { "data": "nombre" },
          { "data": "precio",
        "render": function(data, type, row) {
          var num = parseFloat(data);
          if (isNaN(num)) return data;
          num = Math.round(num); // Quita los decimales
          return "$" + num.toLocaleString('es-CO'); // Usa formato es-CO (puntos para miles)
        } },
          { "data": "frecuencia" },
		{ "data": "dias" },
          { "data": "estado" }
        ],
		  "pageLength": 50,
        "language": {
          "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
      });

      // Abrir modal para agregar un nuevo plan
      $("#btnAddPlan").click(function(){
        $("#formAddPlan")[0].reset();
        $("#modalAddPlan").modal("show");
      });

      // Agregar plan vía Ajax
      $("#formAddPlan").on("submit", function(e){
  e.preventDefault();
  $.ajax({
    url: "index.php",
    method: "POST",
    data: {
      action: "add",
      nombre: $("#add_nombre").val(),
      precio: $("#add_precio").val(),
      frecuencia: $("#add_frecuencia").val(),
      dias: $("#add_dias").val(),  // Nuevo campo días
      estado: $("#add_estado").val()
    },
    dataType: "json",
    success: function(response){
      if(response.status === "success"){
        Swal.fire("Éxito", response.message, "success");
        $("#modalAddPlan").modal("hide");
        table.ajax.reload();
      } else {
        Swal.fire("Error", response.message, "error");
      }
    }
  });
});


      // Al hacer click en una fila, abrir modal para editar
      $('#planes-table tbody').on('click', 'tr', function(){
        var data = table.row(this).data();
        if(data){
          $("#edit_id").val(data.id);
          $("#edit_nombre").val(data.nombre);
          $("#edit_precio").val(data.precio);
          $("#edit_frecuencia").val(data.frecuencia);
			 $("#edit_dias").val(data.dias);
          $("#edit_estado").val(data.estado);
          $("#modalEditPlan").modal("show");
        }
      });

      // Editar plan vía Ajax
      $("#formEditPlan").on("submit", function(e){
  e.preventDefault();
  $.ajax({
    url: "index.php",
    method: "POST",
    data: {
      action: "edit",
      id: $("#edit_id").val(),
      nombre: $("#edit_nombre").val(),
      precio: $("#edit_precio").val(),
      frecuencia: $("#edit_frecuencia").val(),
      dias: $("#edit_dias").val(),  // Nuevo campo días
      estado: $("#edit_estado").val()
    },
    dataType: "json",
    success: function(response){
      if(response.status === "success"){
        Swal.fire("Éxito", response.message, "success");
        $("#modalEditPlan").modal("hide");
        table.ajax.reload();
      } else {
        Swal.fire("Error", response.message, "error");
      }
    }
  });
});

      // Borrar plan vía Ajax (desde el modal de edición)
      $("#btnDeletePlan").on("click", function(){
        var id = $("#edit_id").val();
        Swal.fire({
          title: "¿Está seguro?",
          text: "Esta acción borrará el plan.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Sí, borrar",
          cancelButtonText: "Cancelar"
        }).then((result) => {
          if(result.isConfirmed){
            $.ajax({
              url: "index.php",
              method: "POST",
              data: { action: "delete", id: id },
              dataType: "json",
              success: function(response){
                if(response.status === "success"){
                  Swal.fire("Borrado", response.message, "success");
                  $("#modalEditPlan").modal("hide");
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
