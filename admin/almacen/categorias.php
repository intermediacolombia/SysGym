<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
$permisopage = 'Administrar Almacén';
include('../login/restriction.php'); ?>
<?php
require_once __DIR__ . '/../../inc/config.php';

// =====================================================
// FETCH: Obtener categorías
// =====================================================
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $stmt = db()->prepare("SELECT c.id, c.nombre,
                                   (SELECT COUNT(*) FROM almacen_elementos e WHERE e.categoria_id = c.id AND e.borrado = 0) AS total_elementos
                            FROM almacen_categorias c
                            WHERE c.borrado = 0
                            ORDER BY c.nombre ASC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

// =====================================================
// CRUD: Agregar / Editar / Eliminar
// =====================================================
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == "add") {
        $nombre = trim($_POST['nombre']);

        if ($nombre === '') {
            echo json_encode(['status' => 'error', 'message' => 'El nombre es obligatorio']);
            exit;
        }

        $stmtCheck = db()->prepare("SELECT id, borrado FROM almacen_categorias WHERE nombre = :nombre LIMIT 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing && $existing['borrado'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ya existe una categoría con ese nombre']);
            exit;
        }

        if ($existing && $existing['borrado'] == 1) {
            $stmtUpdate = db()->prepare("UPDATE almacen_categorias SET borrado = 0 WHERE id = :id");
            $stmtUpdate->execute([':id' => $existing['id']]);

            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Reactivar Categoría Almacén', json_encode(['id' => $existing['id'], 'nombre' => $nombre], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Categoría reactivada correctamente']);
            exit;
        }

        $stmt = db()->prepare("INSERT INTO almacen_categorias (nombre, borrado) VALUES (:nombre, 0)");
        if ($stmt->execute([':nombre' => $nombre])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Agregar Categoría Almacén', json_encode(['nombre' => $nombre], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Categoría agregada correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar la categoría']);
        }
        exit;
    }

    elseif ($action == "edit") {
        $id = trim($_POST['id']);
        $nombre = trim($_POST['nombre']);

        if ($nombre === '') {
            echo json_encode(['status' => 'error', 'message' => 'El nombre es obligatorio']);
            exit;
        }

        $stmt = db()->prepare("UPDATE almacen_categorias SET nombre = :nombre WHERE id = :id");
        if ($stmt->execute([':nombre' => $nombre, ':id' => $id])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Editar Categoría Almacén', json_encode(['id' => $id, 'nombre' => $nombre], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Categoría actualizada correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la categoría']);
        }
        exit;
    }

    elseif ($action == "delete") {
        $id = trim($_POST['id']);
        $stmt = db()->prepare("UPDATE almacen_categorias SET borrado = 1 WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Borrar Categoría Almacén', json_encode(['id' => $id], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Categoría borrada correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar la categoría']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Categorías de Almacén</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
  <div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
    <div class="portada">
      <h1 class="mb-4">Categorías de Almacén</h1>
      <button class="btn btn-success float-end" id="btnAddCategoria"><i class="fa fa-plus"></i> Agregar Categoría</button>
    </div>
  </div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    <table id="categorias-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Elementos asociados</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- Modal Agregar -->
  <div class="modal fade" id="modalAddCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formAddCategoria">
          <div class="modal-header">
            <h5 class="modal-title">Agregar Categoría</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

  <!-- Modal Editar -->
  <div class="modal fade" id="modalEditCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formEditCategoria">
          <div class="modal-header">
            <h5 class="modal-title">Editar Categoría</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_id" name="id">
            <div class="mb-3">
              <label for="edit_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="btnDeleteCategoria"><i class="fa fa-trash-o"></i> Borrar</button>
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
    var table = $('#categorias-table').DataTable({
      "ajax": "categorias.php?action=fetch",
      "columns": [
        { "data": "nombre" },
        { "data": "total_elementos" }
      ],
      "pageLength": 50,
      "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
    });

    $("#btnAddCategoria").click(function(){
      $("#formAddCategoria")[0].reset();
      $("#modalAddCategoria").modal("show");
    });

    $("#formAddCategoria").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "categorias.php",
        method: "POST",
        data: { action: "add", nombre: $("#add_nombre").val() },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalAddCategoria").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $('#categorias-table tbody').on('click', 'tr', function(){
      var data = table.row(this).data();
      if(data){
        $("#edit_id").val(data.id);
        $("#edit_nombre").val(data.nombre);
        $("#modalEditCategoria").modal("show");
      }
    });

    $("#formEditCategoria").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "categorias.php",
        method: "POST",
        data: { action: "edit", id: $("#edit_id").val(), nombre: $("#edit_nombre").val() },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalEditCategoria").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $("#btnDeleteCategoria").on("click", function(){
      var id = $("#edit_id").val();
      Swal.fire({
        title: "¿Está seguro?",
        text: "Esta acción borrará la categoría.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, borrar",
        cancelButtonText: "Cancelar"
      }).then((result) => {
        if(result.isConfirmed){
          $.ajax({
            url: "categorias.php",
            method: "POST",
            data: { action: "delete", id: id },
            dataType: "json",
            success: function(response){
              if(response.status === "success"){
                Swal.fire("Borrado", response.message, "success");
                $("#modalEditCategoria").modal("hide");
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
