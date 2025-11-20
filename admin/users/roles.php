<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php 
$permisopage = 'Gestionar Roles';
include('../login/restriction.php'); ?>
<?php
require_once __DIR__ . '/../../inc/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Roles</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
     <h1 class="mb-4">Gestión de Roles</h1>
	  <button class="btn btn-success float-end" id="btnAddRole"><i class="fa fa-plus"></i> Agregar Nuevo Rol</button>
  </div>
</div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
   
    
    <table id="roles-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Descripción</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>

  <!-- Modal para Agregar/Editar Rol -->
  <div class="modal fade" id="modalAddRole" tabindex="-1" aria-labelledby="modalAddRoleLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formAddRole">
          <div class="modal-header">
            <h5 class="modal-title" id="modalAddRoleLabel">Agregar/Editar Rol</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_role_id" name="id">
            <div class="mb-3">
              <label for="role_name" class="form-label">Nombre del Rol</label>
              <input type="text" class="form-control" id="role_name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="role_description" class="form-label">Descripción</label>
              <textarea class="form-control" id="role_description" name="description"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label"><h5>Permisos</h5></label>
<hr>
              <?php
// Consulta para obtener todos los permisos disponibles con su categoría
try {
    // Consulta SQL: Agrupar permisos por categoría
    $stmtPermissions = db()->query("SELECT category, id, name FROM permissions ORDER BY category ASC");
    $permissions = $stmtPermissions->fetchAll(PDO::FETCH_ASSOC);

    // Variable para rastrear la categoría actual
    $currentCategory = null;

    foreach ($permissions as $permission) {
        // Verificar si la categoría ha cambiado
        if ($currentCategory !== $permission['category']) {
            // Si ya hay una categoría anterior, cerrar el bloque anterior
            if ($currentCategory !== null) {
                echo "<hr>";
            }

            // Mostrar el nombre de la nueva categoría en negrita
            echo "<strong>" . htmlspecialchars($permission['category']) . "</strong><br>";

            // Actualizar la categoría actual
            $currentCategory = $permission['category'];
        }

        // Mostrar el permiso como un interruptor (switch)
        echo "<div class='form-check form-switch'>"; // Clase Bootstrap para el switch
        echo "<input class='form-check-input' type='checkbox' name='permissions[]' value='{$permission['id']}' id='permission_{$permission['id']}'>";
        echo "<label class='form-check-label' for='permission_{$permission['id']}'>{$permission['name']}</label>";
        echo "</div>";
    }
} catch (Exception $ex) {
    echo "<p>Error al cargar los permisos.</p>";
}
?>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="btnDeleteRole"><i class="fa fa-trash"></i> Borrar Rol</button>
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
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
    $(document).ready(function () {
      // Inicializar DataTable
      var table = $('#roles-table').DataTable({
        "ajax": "get_roles.php?action=fetch",
        "columns": [
          { "data": "name" },
          { "data": "description" }
        ],
        "language": {
          "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
      });

      // Abrir modal para agregar un nuevo rol
      $("#btnAddRole").click(function () {
        $("#formAddRole")[0].reset();
        $("#edit_role_id").val("");
        $(".form-check-input").prop("checked", false);
        $("#modalAddRole").modal("show");
      });

      // Agregar/Editar rol vía Ajax
      $("#formAddRole").on("submit", function (e) {
        e.preventDefault();
        const roleId = $("#edit_role_id").val();
        const action = roleId ? "edit" : "add";
        $.ajax({
          url: "get_roles.php",
          method: "POST",
          data: {
            action: action,
            id: roleId,
            name: $("#role_name").val(),
            description: $("#role_description").val(),
            permissions: $("input[name='permissions[]']:checked").map(function () {
              return $(this).val();
            }).get()
          },
          dataType: "json",
          success: function (response) {
            if (response.status === "success") {
              Swal.fire("Éxito", response.message, "success");
              $("#modalAddRole").modal("hide");
              table.ajax.reload();
            } else {
              Swal.fire("Error", response.message, "error");
            }
          }
        });
      });

      // Borrar rol vía Ajax
      $("#btnDeleteRole").on("click", function() {
        const roleId = $("#edit_role_id").val();
        if (!roleId) {
          Swal.fire("Error", "No se encontró el ID del rol", "error");
          return;
        }
        
        Swal.fire({
          title: "¿Está seguro?",
          text: "Este rol se marcará como borrado y no se mostrará en la lista.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          confirmButtonText: "Sí, borrar"
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              url: "get_roles.php",
              method: "POST",
              data: { action: "delete", id: roleId },
              dataType: "json",
              success: function(response) {
                if (response.status === "success") {
                  Swal.fire("Borrado", response.message, "success");
                  $("#modalAddRole").modal("hide");
                  table.ajax.reload();
                } else {
                  Swal.fire("Error", response.message, "error");
                }
              }
            });
          }
        });
      });

      // Editar rol (al hacer clic en una fila)
      $('#roles-table tbody').on('click', 'tr', function () {
        var data = table.row(this).data(); // Obtener los datos de la fila seleccionada
        if (data) {
          // Cargar los datos del rol en el formulario
          $("#edit_role_id").val(data.id);
          $("#role_name").val(data.name);
          $("#role_description").val(data.description);

          // Desmarcar todas las casillas de permisos
          $(".form-check-input").prop("checked", false);

          // Marcar las casillas correspondientes a los permisos del rol
          $.ajax({
            url: "get_roles.php",
            method: "POST",
            data: { action: "get", id: data.id },
            dataType: "json",
            success: function (response) {
              if (response.status === "success") {
                response.data.permissions.forEach(function (permissionId) {
                  $(`#permission_${permissionId}`).prop("checked", true);
                });
                $("#modalAddRole").modal("show"); // Abrir el modal
              } else {
                Swal.fire("Error", response.message, "error");
              }
            }
          });
        }
      });
    });
  </script>
</body>
</html>