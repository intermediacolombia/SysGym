<?php require_once __DIR__ . '/../login/session.php';?>

<?php
$permisopage = 'Ver Tiqueteras';
include('../login/restriction.php');?>
<?php
require_once __DIR__ . '/../../inc/config.php';

if(isset($_GET['action']) && $_GET['action'] == "fetch"){
    $stmt = db()->prepare("SELECT id, nombre, precio, limite_entradas, vigencia, estado FROM tiqueteras WHERE borrado = 0");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data'=>$data]);
    exit;
}

if(isset($_POST['action'])){
    $action = $_POST['action'];

    if($action == "add"){
        $nombre           = trim($_POST['nombre']);
        $precio           = trim($_POST['precio']);
        $limite_entradas  = trim($_POST['limite_entradas']);
        $vigencia         = trim($_POST['vigencia']);
        $estado           = trim($_POST['estado']);

        $stmtCheck = db()->prepare("SELECT id, borrado FROM tiqueteras WHERE nombre = :nombre LIMIT 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if($existing){
            if($existing['borrado'] == 0){
                echo json_encode(['status'=>'error', 'message'=>'La tiquetera ya existe']);
                exit;
            } else {
                $stmtUpdate = db()->prepare("UPDATE tiqueteras SET precio = :precio, limite_entradas = :limite_entradas, vigencia = :vigencia, estado = :estado, borrado = 0 WHERE id = :id");
                if($stmtUpdate->execute([':precio'=>$precio, ':limite_entradas'=>$limite_entradas, ':vigencia'=>$vigencia, ':estado'=>$estado, ':id'=>$existing['id']])){
                    require_once __DIR__ . '/../inc/log_action.php';
                    log_action('Agregar Tiqueteras', json_encode(['nombre'=>$nombre,'precio'=>$precio,'limite_entradas'=>$limite_entradas,'vigencia'=>$vigencia,'estado'=>$estado,'id'=>$existing['id']], JSON_UNESCAPED_UNICODE), 'Tiqueteras');
                    echo json_encode(['status'=>'success', 'message'=>'Tiquetera reactivada y actualizada correctamente']);
                } else {
                    echo json_encode(['status'=>'error', 'message'=>'Error al reactivar la tiquetera']);
                }
                exit;
            }
        }

        $stmt = db()->prepare("INSERT INTO tiqueteras (nombre, precio, limite_entradas, vigencia, estado, borrado) VALUES (:nombre, :precio, :limite_entradas, :vigencia, :estado, 0)");
        if($stmt->execute([':nombre'=>$nombre, ':precio'=>$precio, ':limite_entradas'=>$limite_entradas, ':vigencia'=>$vigencia, ':estado'=>$estado])){
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Agregar Tiqueteras', json_encode(['nombre'=>$nombre,'precio'=>$precio,'limite_entradas'=>$limite_entradas,'vigencia'=>$vigencia,'estado'=>$estado], JSON_UNESCAPED_UNICODE), 'Tiqueteras');
            echo json_encode(['status'=>'success', 'message'=>'Tiquetera agregada correctamente']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Error al agregar la tiquetera']);
        }
        exit;

    } elseif($action == "edit"){
        $id              = trim($_POST['id']);
        $nombre          = trim($_POST['nombre']);
        $precio          = trim($_POST['precio']);
        $limite_entradas = trim($_POST['limite_entradas']);
        $vigencia        = trim($_POST['vigencia']);
        $estado          = trim($_POST['estado']);

        $stmt = db()->prepare("UPDATE tiqueteras SET nombre = :nombre, precio = :precio, limite_entradas = :limite_entradas, vigencia = :vigencia, estado = :estado WHERE id = :id");
        if($stmt->execute([':nombre'=>$nombre, ':precio'=>$precio, ':limite_entradas'=>$limite_entradas, ':vigencia'=>$vigencia, ':estado'=>$estado, ':id'=>$id])){
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Editar Tiqueteras', json_encode(['id'=>$id,'nombre'=>$nombre,'precio'=>$precio,'limite_entradas'=>$limite_entradas,'vigencia'=>$vigencia,'estado'=>$estado], JSON_UNESCAPED_UNICODE), 'Tiqueteras');
            echo json_encode(['status'=>'success', 'message'=>'Tiquetera actualizada correctamente']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Error al actualizar la tiquetera']);
        }
        exit;

    } elseif($action == "delete"){
        $id = trim($_POST['id']);
        $stmt = db()->prepare("UPDATE tiqueteras SET borrado = 1 WHERE id = :id");
        if($stmt->execute([':id'=>$id])){
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Borrar Tiqueteras', json_encode(['id'=>$id], JSON_UNESCAPED_UNICODE), 'Tiqueteras');
            echo json_encode(['status'=>'success', 'message'=>'Tiquetera borrada correctamente']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Error al borrar la tiquetera']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Tiqueteras</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Gestión de Tiqueteras</h1>
    <button class="btn btn-success float-end" id="btnAddTiquetera"><i class="fa fa-plus"></i> Agregar Nueva Tiquetera</button>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
  <table id="tiqueteras-table" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Precio</th>
        <th>Límite de Entradas</th>
        <th>Vigencia (días)</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal Agregar Tiquetera -->
<div class="modal fade" id="modalAddTiquetera" tabindex="-1" aria-labelledby="modalAddTiqueteraLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAddTiquetera">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAddTiqueteraLabel">Agregar Nueva Tiquetera</h5>
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
              <span class="input-group-text">$</span>
            </div>
            <input type="number" class="form-control" id="add_precio" name="precio" required step="0.01" min="0">
          </div>
          <div class="mb-3">
            <label for="add_limite_entradas" class="form-label">Límite de Entradas</label>
            <input type="number" class="form-control" id="add_limite_entradas" name="limite_entradas" required min="1" value="10">
          </div>
          <div class="mb-3">
            <label for="add_vigencia" class="form-label">Vigencia (días)</label>
            <input type="number" class="form-control" id="add_vigencia" name="vigencia" required min="1" value="30">
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

<!-- Modal Editar Tiquetera -->
<div class="modal fade" id="modalEditTiquetera" tabindex="-1" aria-labelledby="modalEditTiqueteraLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEditTiquetera">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditTiqueteraLabel">Editar Tiquetera</h5>
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
              <span class="input-group-text">$</span>
            </div>
            <input type="number" class="form-control" id="edit_precio" name="precio" required step="0.01" min="0">
          </div>
          <div class="mb-3">
            <label for="edit_limite_entradas" class="form-label">Límite de Entradas</label>
            <input type="number" class="form-control" id="edit_limite_entradas" name="limite_entradas" required min="1">
          </div>
          <div class="mb-3">
            <label for="edit_vigencia" class="form-label">Vigencia (días)</label>
            <input type="number" class="form-control" id="edit_vigencia" name="vigencia" required min="1">
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
          <button type="button" class="btn btn-danger" id="btnDeleteTiquetera"><i class="fa fa-trash-o"></i> Borrar Tiquetera</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
  $(document).ready(function(){
    var table = $('#tiqueteras-table').DataTable({
      "ajax": "index.php?action=fetch",
      "columns": [
        { "data": "nombre" },
        {
          "data": "precio",
          "render": function(data) {
            var num = Math.round(parseFloat(data));
            return isNaN(num) ? data : "$" + num.toLocaleString('es-CO');
          }
        },
        { "data": "limite_entradas" },
        { "data": "vigencia" },
        { "data": "estado" }
      ],
      "pageLength": 50,
      "language": {
        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
      }
    });

    $("#btnAddTiquetera").click(function(){
      $("#formAddTiquetera")[0].reset();
      $("#modalAddTiquetera").modal("show");
    });

    $("#formAddTiquetera").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "index.php",
        method: "POST",
        data: {
          action: "add",
          nombre: $("#add_nombre").val(),
          precio: $("#add_precio").val(),
          limite_entradas: $("#add_limite_entradas").val(),
          vigencia: $("#add_vigencia").val(),
          estado: $("#add_estado").val()
        },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalAddTiquetera").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $('#tiqueteras-table tbody').on('click', 'tr', function(){
      var data = table.row(this).data();
      if(data){
        $("#edit_id").val(data.id);
        $("#edit_nombre").val(data.nombre);
        $("#edit_precio").val(data.precio);
        $("#edit_limite_entradas").val(data.limite_entradas);
        $("#edit_vigencia").val(data.vigencia);
        $("#edit_estado").val(data.estado);
        $("#modalEditTiquetera").modal("show");
      }
    });

    $("#formEditTiquetera").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "index.php",
        method: "POST",
        data: {
          action: "edit",
          id: $("#edit_id").val(),
          nombre: $("#edit_nombre").val(),
          precio: $("#edit_precio").val(),
          limite_entradas: $("#edit_limite_entradas").val(),
          vigencia: $("#edit_vigencia").val(),
          estado: $("#edit_estado").val()
        },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalEditTiquetera").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $("#btnDeleteTiquetera").on("click", function(){
      var id = $("#edit_id").val();
      Swal.fire({
        title: "¿Está seguro?",
        text: "Esta acción borrará la tiquetera.",
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
                $("#modalEditTiquetera").modal("hide");
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
