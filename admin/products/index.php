<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php 
$permisopage = 'Ver y Editar Productos';
include('../login/restriction.php'); ?>
<?php
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

// =====================================================
// FETCH: Obtener productos
// =====================================================
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $stmt = $pdo->prepare("SELECT p.id, p.nombre, p.precio, p.coste, p.stock, p.estado, p.id_bolsillo, b.nombre AS bolsillo, b.borrado,
                              p.alerta_stock, p.minimo_stock
                       FROM productos p 
                       LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
                       WHERE p.borrado = 0");

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

    // =====================================================
    // AGREGAR PRODUCTO
    // =====================================================
    if ($action == "add") {
        $nombre = trim($_POST['nombre']);
        $precio = trim($_POST['precio']);
        $coste = trim($_POST['coste']);
        $stock = trim($_POST['stock']);
        $estado = trim($_POST['estado']);
        $id_bolsillo = trim($_POST['id_bolsillo']);
        $alerta_stock = isset($_POST['alerta_stock']) ? 1 : 0;
        $minimo_stock = isset($_POST['minimo_stock']) && $_POST['minimo_stock'] !== '' ? (int)$_POST['minimo_stock'] : null;

        // Verificar si ya existe un producto con el mismo nombre
        $stmtCheck = $pdo->prepare("SELECT id, borrado FROM productos WHERE nombre = :nombre LIMIT 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['borrado'] == 0) {
                echo json_encode(['status' => 'error', 'message' => 'El producto ya existe']);
                exit;
            } else {
                // Reactivar producto borrado
                $stmtUpdate = $pdo->prepare("
                    UPDATE productos 
                    SET precio = :precio, coste = :coste, stock = :stock, estado = :estado, id_bolsillo = :id_bolsillo,
                        alerta_stock = :alerta_stock, minimo_stock = :minimo_stock, borrado = 0
                    WHERE id = :id
                ");
                if ($stmtUpdate->execute([
                    ':precio' => $precio,
                    ':coste' => $coste,
                    ':stock' => $stock,
                    ':estado' => $estado,
                    ':id_bolsillo' => $id_bolsillo,
                    ':alerta_stock' => $alerta_stock,
                    ':minimo_stock' => $minimo_stock,
                    ':id' => $existing['id']
                ])) {

                    require_once __DIR__ . '/../inc/log_action.php';
                    $desc = json_encode([
                        'precio' => $precio,
                        'coste' => $coste,
                        'stock' => $stock,
                        'estado' => $estado,
                        'id_bolsillo' => $id_bolsillo,
                        'alerta_stock' => $alerta_stock,
                        'minimo_stock' => $minimo_stock,
                        'id' => $existing['id']
                    ], JSON_UNESCAPED_UNICODE);
                    log_action('Reactivar Producto', $desc, 'Productos');

                    echo json_encode(['status' => 'success', 'message' => 'Producto reactivado y actualizado correctamente']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al reactivar el producto']);
                }
                exit;
            }
        }

        // Insertar nuevo producto
        $stmt = $pdo->prepare("
            INSERT INTO productos 
            (nombre, precio, coste, stock, estado, id_bolsillo, alerta_stock, minimo_stock, borrado)
            VALUES
            (:nombre, :precio, :coste, :stock, :estado, :id_bolsillo, :alerta_stock, :minimo_stock, 0)
        ");
        if ($stmt->execute([
            ':nombre' => $nombre,
            ':precio' => $precio,
            ':coste' => $coste,
            ':stock' => $stock,
            ':estado' => $estado,
            ':id_bolsillo' => $id_bolsillo,
            ':alerta_stock' => $alerta_stock,
            ':minimo_stock' => $minimo_stock
        ])) {

            require_once __DIR__ . '/../inc/log_action.php';
            $desc = json_encode([
                'nombre' => $nombre,
                'precio' => $precio,
                'coste' => $coste,
                'stock' => $stock,
                'estado' => $estado,
                'id_bolsillo' => $id_bolsillo,
                'alerta_stock' => $alerta_stock,
                'minimo_stock' => $minimo_stock
            ], JSON_UNESCAPED_UNICODE);
            log_action('Agregar Producto', $desc, 'Productos');

            echo json_encode(['status' => 'success', 'message' => 'Producto agregado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el producto']);
        }
        exit;
    }

    // =====================================================
    // EDITAR PRODUCTO
    // =====================================================
    elseif ($action == "edit") {
        $id = trim($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $precio = trim($_POST['precio']);
        $coste = trim($_POST['coste']);
        $stock = trim($_POST['stock']);
        $estado = trim($_POST['estado']);
        $id_bolsillo = trim($_POST['id_bolsillo']);
        $alerta_stock = isset($_POST['alerta_stock']) ? 1 : 0;
        $minimo_stock = isset($_POST['minimo_stock']) && $_POST['minimo_stock'] !== '' ? (int)$_POST['minimo_stock'] : null;

        $stmt = $pdo->prepare("
            UPDATE productos 
            SET nombre = :nombre, precio = :precio, coste = :coste, stock = :stock, estado = :estado,
                id_bolsillo = :id_bolsillo, alerta_stock = :alerta_stock, minimo_stock = :minimo_stock
            WHERE id = :id
        ");
        if ($stmt->execute([
            ':nombre' => $nombre,
            ':precio' => $precio,
            ':coste' => $coste,
            ':stock' => $stock,
            ':estado' => $estado,
            ':id_bolsillo' => $id_bolsillo,
            ':alerta_stock' => $alerta_stock,
            ':minimo_stock' => $minimo_stock,
            ':id' => $id
        ])) {

            require_once __DIR__ . '/../inc/log_action.php';
            $desc = json_encode([
                'id' => $id,
                'nombre' => $nombre,
                'precio' => $precio,
                'coste' => $coste,
                'stock' => $stock,
                'estado' => $estado,
                'id_bolsillo' => $id_bolsillo,
                'alerta_stock' => $alerta_stock,
                'minimo_stock' => $minimo_stock
            ], JSON_UNESCAPED_UNICODE);
            log_action('Editar Producto', $desc, 'Productos');

            echo json_encode(['status' => 'success', 'message' => 'Producto actualizado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el producto']);
        }
        exit;
    }

    // =====================================================
    // BORRAR PRODUCTO
    // =====================================================
    elseif ($action == "delete") {
        $id = trim($_POST['id']);
        $stmt = $pdo->prepare("UPDATE productos SET borrado = 1 WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {

            require_once __DIR__ . '/../inc/log_action.php';
            $desc = json_encode(['id' => $id], JSON_UNESCAPED_UNICODE);
            log_action('Borrar Producto', $desc, 'Productos');

            echo json_encode(['status' => 'success', 'message' => 'Producto borrado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar el producto']);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Productos</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
	<div class="portada">
	 <h1 class="mb-4">Gestión de Productos</h1>
		<button class="btn btn-success float-end" id="btnAddProducto"><i class="fa fa-plus"></i> Agregar Nuevo Producto</button>
	</div>
	</div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    
    
    <table id="productos-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Precio</th>
		  <th>Coste Unidad</th>
		  <th>Ganancia X Un.</th>
		  <th>% Ganancia X Un.</th>
          <th>Stock</th>
          <th>Estado</th>
          <th>Bolsillo</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>

  <!-- Modal para Agregar Producto -->
  <!-- (Se mantiene sin cambios) -->
 <!-- Modal para Agregar Producto -->
<div class="modal fade" id="modalAddProducto" tabindex="-1" aria-labelledby="modalAddProductoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAddProducto">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAddProductoLabel">Agregar Nuevo Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="add_nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="add_nombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label for="add_precio" class="form-label">Precio</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">$</span>
              </div>
              <input type="number" class="form-control" id="add_precio" name="precio" required step="0.01">
            </div>
          </div>
			
		<div class="mb-3">
            <label for="add_coste" class="form-label">Coste Unidad</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">$</span>
              </div>
              <input type="number" class="form-control" id="add_coste" name="coste" required step="0.01">
            </div>
          </div>
			
          <div class="mb-3">
            <label for="add_stock" class="form-label">Stock</label>
            <input type="number" class="form-control" id="add_stock" name="stock" required>
          </div>
          <div class="mb-3">
            <label for="add_estado" class="form-label">Estado</label>
            <select class="form-control" id="add_estado" name="estado" required>
              <option value="1" selected>Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
          <!-- Nuevo campo para seleccionar el bolsillo -->
          <div class="mb-3">
            <label for="add_id_bolsillo" class="form-label">Bolsillo</label>
			  
            <select class="form-control" id="add_id_bolsillo" name="id_bolsillo" required>
			<option value="">Seleccione</option>
              <?php 
              // Consulta para obtener todos los bolsillos activos
              try {
                  $stmtB = $pdo->prepare("SELECT id, nombre FROM bolsillos WHERE borrado = 0");
                  $stmtB->execute();
                  $bolsillos = $stmtB->fetchAll(PDO::FETCH_ASSOC);
                  foreach($bolsillos as $b){
                      echo "<option value='{$b['id']}'>{$b['nombre']}</option>";
                  }
              } catch(Exception $ex){
                  echo "<option value=''>Error al cargar</option>";
              }
              ?>
            </select>
          </div>
			
			<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="add_alerta_stock">
  <label class="form-check-label" for="add_alerta_stock">Activar alerta por stock bajo</label>
</div>
<div class="mb-3" id="add_minimo_container" style="display:none;">
  <label for="add_minimo_stock" class="form-label">Stock mínimo para alerta</label>
  <input type="number" class="form-control" id="add_minimo_stock" name="minimo_stock">
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

  
  <!-- Modal para Editar Producto -->
  <div class="modal fade" id="modalEditProducto" tabindex="-1" aria-labelledby="modalEditProductoLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formEditProducto">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditProductoLabel">Editar Producto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_id" name="id">
            <div class="mb-3">
              <label for="edit_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
            </div>
            <div class="mb-3">
              <label for="edit_precio" class="form-label">Precio</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">$</span>
                </div>
                <input type="number" class="form-control" id="edit_precio" name="precio" required step="0.01">
              </div>
            </div>
			 
			  <div class="mb-3">
              <label for="edit_coste" class="form-label">Coste Unidad</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">$</span>
                </div>
                <input type="number" class="form-control" id="edit_coste" name="coste" required step="0.01">
              </div>
            </div>
			  
			 
            <div class="mb-3">
              <label for="edit_stock" class="form-label">Stock</label>
              <input type="number" class="form-control" id="edit_stock" name="stock" required>
            </div>
            <div class="mb-3">
              <label for="edit_estado" class="form-label">Estado</label>
              <select class="form-control" id="edit_estado" name="estado" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <!-- Nuevo campo para seleccionar el bolsillo -->
            <div class="mb-3">
              <label for="edit_id_bolsillo" class="form-label">Bolsillo</label>
              <select class="form-control" id="edit_id_bolsillo" name="id_bolsillo" required>
			<option value="">Seleccione</option>
                <?php 
                try {
                    $stmtB = $pdo->prepare("SELECT id, nombre FROM bolsillos WHERE borrado = 0");
                    $stmtB->execute();
                    $bolsillos = $stmtB->fetchAll(PDO::FETCH_ASSOC);
                    foreach($bolsillos as $b){
                        echo "<option value='{$b['id']}'>{$b['nombre']}</option>";
                    }
                } catch(Exception $ex){
                    echo "<option value=''>Error al cargar</option>";
                }
                ?>
              </select>
            </div>
			  
			  <div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="edit_alerta_stock">
  <label class="form-check-label" for="edit_alerta_stock">Activar alerta por stock bajo</label>
</div>
<div class="mb-3" id="edit_minimo_container" style="display:none;">
  <label for="edit_minimo_stock" class="form-label">Stock mínimo para alerta</label>
  <input type="number" class="form-control" id="edit_minimo_stock" name="minimo_stock">
</div>

			  
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="btnDeleteProducto"><i class="fa fa-trash-o"></i> Borrar Producto</button>
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
  // Inicializar DataTable con columna adicional "Bolsillo"
  var table = $('#productos-table').DataTable({
    "ajax": "index.php?action=fetch",
    "columns": [
      { "data": "nombre" },
      { "data": "precio",
        "render": function(data, type, row) {
          var num = parseFloat(data);
          if (isNaN(num)) return data;
          num = Math.round(num);
          return "$" + num.toLocaleString('es-CO');
        }
      },
      { "data": "coste",
        "render": function(data, type, row) {
          var num = parseFloat(data);
          if (isNaN(num)) return data;
          num = Math.round(num);
          return "$" + num.toLocaleString('es-CO');
        }
      },
      { 
        "data": null,
        "title": "Ganancia",
        "render": function(data, type, row) {
          var precio = parseFloat(row.precio) || 0;
          var coste = parseFloat(row.coste) || 0;
          var ganancia = precio - coste;
          return "$" + ganancia.toLocaleString('es-CO', { maximumFractionDigits: 0 });
        }
      },
      { 
        "data": null,
        "title": "% Ganancia",
        "render": function(data, type, row) {
          var precio = parseFloat(row.precio) || 0;
          var coste = parseFloat(row.coste) || 0;
          if(precio > 0){
            var porcentaje = ((precio - coste) / precio) * 100;
            return porcentaje.toFixed(2) + "%";
          } else {
            return "N/A";
          }
        }
      },
      { "data": "stock" },
      { "data": "estado",
        "render": function(data, type, row) {
          return data == 1 
            ? '<span class="badge bg-success">Activo</span>' 
            : '<span class="badge bg-danger">Inactivo</span>';
        }
      },
      { "data": "bolsillo", "defaultContent": "Sin Asignar" },
      { "data": "alerta_stock", "visible": false },
      { "data": "minimo_stock", "visible": false }
    ],
    "pageLength": 50,
    "language": {
      "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
    }
  });

  // Abrir modal para agregar un nuevo producto
  $("#btnAddProducto").click(function(){
    $("#formAddProducto")[0].reset();
    $("#add_minimo_container").hide();
    $("#modalAddProducto").modal("show");
  });

  // Agregar producto vía Ajax
  $("#formAddProducto").on("submit", function(e){
    e.preventDefault();
    $.ajax({
      url: "index.php",
      method: "POST",
      data: {
        action: "add",
        nombre: $("#add_nombre").val(),
        precio: $("#add_precio").val(),
        coste: $("#add_coste").val(),
        stock: $("#add_stock").val(),
        estado: $("#add_estado").val(),
        id_bolsillo: $("#add_id_bolsillo").val(),
        alerta_stock: $("#add_alerta_stock").is(":checked") ? 1 : 0,
        minimo_stock: $("#add_alerta_stock").is(":checked") ? $("#add_minimo_stock").val() : null
      },
      dataType: "json",
      success: function(response){
        if(response.status === "success"){
          Swal.fire("Éxito", response.message, "success");
          $("#modalAddProducto").modal("hide");
          table.ajax.reload();
        } else {
          Swal.fire("Error", response.message, "error");
        }
      }
    });
  });

  // Al hacer click en una fila, abrir modal para editar
  $('#productos-table tbody').on('click', 'tr', function(){
    var data = table.row(this).data();
    if(data){
      $("#edit_id").val(data.id);
      $("#edit_nombre").val(data.nombre);
      $("#edit_precio").val(data.precio);
      $("#edit_coste").val(data.coste);
      $("#edit_stock").val(data.stock);
      $("#edit_estado").val(data.estado);  
      $("#edit_id_bolsillo").val(data.id_bolsillo);
      
      // CORRECCIÓN: Configurar el checkbox correctamente
      if (data.alerta_stock == 1) {
        $("#edit_alerta_stock").prop('checked', true);
        $("#edit_minimo_container").show();
        $("#edit_minimo_stock").val(data.minimo_stock);
      } else {
        $("#edit_alerta_stock").prop('checked', false);
        $("#edit_minimo_container").hide();
        $("#edit_minimo_stock").val('');
      }
      
      $("#modalEditProducto").modal("show");
    }
  });

  // Editar producto vía Ajax
  $("#formEditProducto").on("submit", function(e){
    e.preventDefault();
    $.ajax({
      url: "index.php",
      method: "POST",
      data: {
        action: "edit",
        id: $("#edit_id").val(),
        nombre: $("#edit_nombre").val(),
        precio: $("#edit_precio").val(),
        coste: $("#edit_coste").val(),
        stock: $("#edit_stock").val(),
        estado: $("#edit_estado").val(),
        id_bolsillo: $("#edit_id_bolsillo").val(),
        alerta_stock: $("#edit_alerta_stock").is(":checked") ? 1 : 0,
        minimo_stock: $("#edit_alerta_stock").is(":checked") ? $("#edit_minimo_stock").val() : null
      },
      dataType: "json",
      success: function(response){
        if(response.status === "success"){
          Swal.fire("Éxito", response.message, "success");
          $("#modalEditProducto").modal("hide");
          table.ajax.reload();
        } else {
          Swal.fire("Error", response.message, "error");
        }
      }
    });
  });

  // Borrar producto vía Ajax (desde el modal de edición)
  $("#btnDeleteProducto").on("click", function(){
    var id = $("#edit_id").val();
    Swal.fire({
      title: "¿Está seguro?",
      text: "Esta acción borrará el producto.",
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
              $("#modalEditProducto").modal("hide");
              table.ajax.reload();
            } else {
              Swal.fire("Error", response.message, "error");
            }
          }
        });
      }
    });
  });

  // Mostrar/ocultar en Agregar
  $("#add_alerta_stock").on("change", function() {
    $("#add_minimo_container").toggle(this.checked);
  });

  // Mostrar/ocultar en Editar
  $("#edit_alerta_stock").on("change", function() {
    $("#edit_minimo_container").toggle(this.checked);
  });
});
	</script>
</body>
</html>



