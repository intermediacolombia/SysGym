<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
$permisopage = 'Administrar Almacén';
include('../login/restriction.php'); ?>
<?php
require_once __DIR__ . '/../../inc/config.php';

// Lista fija de unidades de medida: clave guardada en BD => etiqueta visible
$unidades_medida = [
    'unidad'      => 'Unidad',
    'caja'        => 'Caja',
    'paquete'     => 'Paquete',
    'rollo'       => 'Rollo',
    'litro'       => 'Litro (L)',
    'mililitro'   => 'Mililitro (ml)',
    'galon'       => 'Galón',
    'kilogramo'   => 'Kilogramo (kg)',
    'gramo'       => 'Gramo (g)',
    'metro'       => 'Metro (m)',
    'centimetro'  => 'Centímetro (cm)',
];

// =====================================================
// FETCH: Obtener elementos
// =====================================================
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $stmt = db()->prepare("SELECT e.id, e.nombre, e.categoria_id, c.nombre AS categoria_nombre,
                                   e.unidad_medida, e.stock_actual, e.stock_minimo, e.alerta_stock, e.estado
                            FROM almacen_elementos e
                            LEFT JOIN almacen_categorias c ON e.categoria_id = c.id AND c.borrado = 0
                            WHERE e.borrado = 0
                            ORDER BY e.nombre ASC");
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
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $unidad_medida = trim($_POST['unidad_medida']);
        $stock_actual = isset($_POST['stock_actual']) && $_POST['stock_actual'] !== '' ? (float)$_POST['stock_actual'] : 0;
        $alerta_stock = (!empty($_POST['alerta_stock']) && $_POST['alerta_stock'] == '1') ? 1 : 0;
        $stock_minimo = $alerta_stock && $_POST['stock_minimo'] !== '' ? (float)$_POST['stock_minimo'] : null;
        $estado = trim($_POST['estado']);

        if ($nombre === '' || $unidad_medida === '' || !array_key_exists($unidad_medida, $unidades_medida)) {
            echo json_encode(['status' => 'error', 'message' => 'Nombre y unidad de medida son obligatorios']);
            exit;
        }

        $stmtCheck = db()->prepare("SELECT id, borrado FROM almacen_elementos WHERE nombre = :nombre LIMIT 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing && $existing['borrado'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ya existe un elemento con ese nombre']);
            exit;
        }

        if ($existing && $existing['borrado'] == 1) {
            $stmtUpdate = db()->prepare("
                UPDATE almacen_elementos
                SET categoria_id = :categoria_id, unidad_medida = :unidad_medida, stock_actual = :stock_actual,
                    stock_minimo = :stock_minimo, alerta_stock = :alerta_stock, estado = :estado, borrado = 0
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':categoria_id' => $categoria_id,
                ':unidad_medida' => $unidad_medida,
                ':stock_actual' => $stock_actual,
                ':stock_minimo' => $stock_minimo,
                ':alerta_stock' => $alerta_stock,
                ':estado' => $estado,
                ':id' => $existing['id']
            ]);

            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Reactivar Elemento Almacén', json_encode(['id' => $existing['id'], 'nombre' => $nombre], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Elemento reactivado y actualizado correctamente']);
            exit;
        }

        $stmt = db()->prepare("
            INSERT INTO almacen_elementos
            (nombre, categoria_id, unidad_medida, stock_actual, stock_minimo, alerta_stock, estado, borrado)
            VALUES
            (:nombre, :categoria_id, :unidad_medida, :stock_actual, :stock_minimo, :alerta_stock, :estado, 0)
        ");
        if ($stmt->execute([
            ':nombre' => $nombre,
            ':categoria_id' => $categoria_id,
            ':unidad_medida' => $unidad_medida,
            ':stock_actual' => $stock_actual,
            ':stock_minimo' => $stock_minimo,
            ':alerta_stock' => $alerta_stock,
            ':estado' => $estado
        ])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Agregar Elemento Almacén', json_encode([
                'nombre' => $nombre, 'categoria_id' => $categoria_id, 'unidad_medida' => $unidad_medida,
                'stock_actual' => $stock_actual, 'stock_minimo' => $stock_minimo, 'alerta_stock' => $alerta_stock, 'estado' => $estado
            ], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Elemento agregado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el elemento']);
        }
        exit;
    }

    elseif ($action == "edit") {
        $id = trim($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $unidad_medida = trim($_POST['unidad_medida']);
        $alerta_stock = (!empty($_POST['alerta_stock']) && $_POST['alerta_stock'] == '1') ? 1 : 0;
        $stock_minimo = $alerta_stock && $_POST['stock_minimo'] !== '' ? (float)$_POST['stock_minimo'] : null;
        $estado = trim($_POST['estado']);

        if ($nombre === '' || $unidad_medida === '' || !array_key_exists($unidad_medida, $unidades_medida)) {
            echo json_encode(['status' => 'error', 'message' => 'Nombre y unidad de medida son obligatorios']);
            exit;
        }

        $stmt = db()->prepare("
            UPDATE almacen_elementos
            SET nombre = :nombre, categoria_id = :categoria_id, unidad_medida = :unidad_medida,
                stock_minimo = :stock_minimo, alerta_stock = :alerta_stock, estado = :estado
            WHERE id = :id
        ");
        if ($stmt->execute([
            ':nombre' => $nombre,
            ':categoria_id' => $categoria_id,
            ':unidad_medida' => $unidad_medida,
            ':stock_minimo' => $stock_minimo,
            ':alerta_stock' => $alerta_stock,
            ':estado' => $estado,
            ':id' => $id
        ])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Editar Elemento Almacén', json_encode([
                'id' => $id, 'nombre' => $nombre, 'categoria_id' => $categoria_id, 'unidad_medida' => $unidad_medida,
                'stock_minimo' => $stock_minimo, 'alerta_stock' => $alerta_stock, 'estado' => $estado
            ], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Elemento actualizado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el elemento']);
        }
        exit;
    }

    elseif ($action == "delete") {
        $id = trim($_POST['id']);
        $stmt = db()->prepare("UPDATE almacen_elementos SET borrado = 1 WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Borrar Elemento Almacén', json_encode(['id' => $id], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Elemento borrado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar el elemento']);
        }
        exit;
    }
}

// Categorías activas para los selects
$categorias = db()->query("SELECT id, nombre FROM almacen_categorias WHERE borrado = 0 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Almacén - Elementos</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
  <div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
    <div class="portada">
      <h1 class="mb-4">Almacén - Elementos</h1>
      <button class="btn btn-success float-end" id="btnAddElemento"><i class="fa fa-plus"></i> Agregar Elemento</button>
    </div>
  </div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    <div class="mb-3">
      <label for="filtroCategoria" class="form-label">Filtrar por categoría</label>
      <select id="filtroCategoria" class="form-control" style="max-width:300px;">
        <option value="">Todas</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <table id="elementos-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Unidad</th>
          <th>Stock actual</th>
          <th>Stock mínimo</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- Modal Agregar -->
  <div class="modal fade" id="modalAddElemento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formAddElemento">
          <div class="modal-header">
            <h5 class="modal-title">Agregar Elemento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="add_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="add_nombre" name="nombre" required>
            </div>
            <div class="mb-3">
              <label for="add_categoria_id" class="form-label">Categoría</label>
              <select class="form-control" id="add_categoria_id" name="categoria_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categorias as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="add_unidad_medida" class="form-label">Unidad de medida</label>
              <select class="form-control" id="add_unidad_medida" name="unidad_medida" required>
                <option value="">Seleccione</option>
                <?php foreach ($unidades_medida as $key => $label): ?>
                  <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="add_stock_actual" class="form-label">Stock inicial</label>
              <input type="number" step="0.01" min="0" class="form-control" id="add_stock_actual" name="stock_actual" value="0" required>
            </div>
            <div class="mb-3">
              <label for="add_estado" class="form-label">Estado</label>
              <select class="form-control" id="add_estado" name="estado" required>
                <option value="1" selected>Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="add_alerta_stock" name="alerta_stock">
              <label class="form-check-label" for="add_alerta_stock">Activar alerta por stock bajo</label>
            </div>
            <div class="mb-3" id="add_minimo_container" style="display:none;">
              <label for="add_stock_minimo" class="form-label">Stock mínimo para alerta</label>
              <input type="number" step="0.01" min="0" class="form-control" id="add_stock_minimo" name="stock_minimo">
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
  <div class="modal fade" id="modalEditElemento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formEditElemento">
          <div class="modal-header">
            <h5 class="modal-title">Editar Elemento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_id" name="id">
            <div class="mb-3">
              <label for="edit_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
            </div>
            <div class="mb-3">
              <label for="edit_categoria_id" class="form-label">Categoría</label>
              <select class="form-control" id="edit_categoria_id" name="categoria_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categorias as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_unidad_medida" class="form-label">Unidad de medida</label>
              <select class="form-control" id="edit_unidad_medida" name="unidad_medida" required>
                <?php foreach ($unidades_medida as $key => $label): ?>
                  <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Stock actual</label>
              <input type="text" class="form-control" id="edit_stock_actual_display" disabled>
              <small class="text-muted">El stock se ajusta desde Almacén &gt; Movimientos.</small>
            </div>
            <div class="mb-3">
              <label for="edit_estado" class="form-label">Estado</label>
              <select class="form-control" id="edit_estado" name="estado" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="edit_alerta_stock">
              <label class="form-check-label" for="edit_alerta_stock">Activar alerta por stock bajo</label>
            </div>
            <div class="mb-3" id="edit_minimo_container" style="display:none;">
              <label for="edit_stock_minimo" class="form-label">Stock mínimo para alerta</label>
              <input type="number" step="0.01" min="0" class="form-control" id="edit_stock_minimo" name="stock_minimo">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="btnDeleteElemento"><i class="fa fa-trash-o"></i> Borrar</button>
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
  var UNIDADES = <?= json_encode($unidades_medida, JSON_UNESCAPED_UNICODE) ?>;

  $(document).ready(function(){
    var table = $('#elementos-table').DataTable({
      "ajax": "index.php?action=fetch",
      "columns": [
        { "data": "nombre" },
        { "data": "categoria_nombre", "defaultContent": "Sin categoría" },
        { "data": "unidad_medida",
          "render": function(data){ return UNIDADES[data] || data; }
        },
        { "data": "stock_actual" },
        { "data": "stock_minimo", "defaultContent": "-" },
        { "data": "estado",
          "render": function(data){
            return data == 1
              ? '<span class="badge bg-success">Activo</span>'
              : '<span class="badge bg-danger">Inactivo</span>';
          }
        }
      ],
      "createdRow": function(row, data){
        if (data.alerta_stock == 1 && data.stock_minimo !== null && parseFloat(data.stock_actual) <= parseFloat(data.stock_minimo)) {
          $(row).addClass('table-danger');
        }
      },
      "pageLength": 50,
      "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
    });

    $('#filtroCategoria').on('change', function(){
      var val = $(this).val();
      if (val === '') {
        table.column(1).search('').draw();
      } else {
        var nombre = $(this).find('option:selected').text();
        table.column(1).search('^' + $.fn.dataTable.util.escapeRegex(nombre) + '$', true, false).draw();
      }
    });

    $("#btnAddElemento").click(function(){
      $("#formAddElemento")[0].reset();
      $("#add_minimo_container").hide();
      $("#modalAddElemento").modal("show");
    });

    $("#formAddElemento").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "index.php",
        method: "POST",
        data: {
          action: "add",
          nombre: $("#add_nombre").val(),
          categoria_id: $("#add_categoria_id").val(),
          unidad_medida: $("#add_unidad_medida").val(),
          stock_actual: $("#add_stock_actual").val(),
          estado: $("#add_estado").val(),
          alerta_stock: $("#add_alerta_stock").is(":checked") ? 1 : 0,
          stock_minimo: $("#add_alerta_stock").is(":checked") ? $("#add_stock_minimo").val() : ''
        },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalAddElemento").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $('#elementos-table tbody').on('click', 'tr', function(){
      var data = table.row(this).data();
      if(data){
        $("#edit_id").val(data.id);
        $("#edit_nombre").val(data.nombre);
        $("#edit_categoria_id").val(data.categoria_id || '');
        $("#edit_unidad_medida").val(data.unidad_medida);
        $("#edit_stock_actual_display").val(data.stock_actual + ' ' + (UNIDADES[data.unidad_medida] || data.unidad_medida));
        $("#edit_estado").val(data.estado);

        if (data.alerta_stock == 1) {
          $("#edit_alerta_stock").prop('checked', true);
          $("#edit_minimo_container").show();
          $("#edit_stock_minimo").val(data.stock_minimo);
        } else {
          $("#edit_alerta_stock").prop('checked', false);
          $("#edit_minimo_container").hide();
          $("#edit_stock_minimo").val('');
        }

        $("#modalEditElemento").modal("show");
      }
    });

    $("#formEditElemento").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "index.php",
        method: "POST",
        data: {
          action: "edit",
          id: $("#edit_id").val(),
          nombre: $("#edit_nombre").val(),
          categoria_id: $("#edit_categoria_id").val(),
          unidad_medida: $("#edit_unidad_medida").val(),
          estado: $("#edit_estado").val(),
          alerta_stock: $("#edit_alerta_stock").is(":checked") ? 1 : 0,
          stock_minimo: $("#edit_alerta_stock").is(":checked") ? $("#edit_stock_minimo").val() : ''
        },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalEditElemento").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $("#btnDeleteElemento").on("click", function(){
      var id = $("#edit_id").val();
      Swal.fire({
        title: "¿Está seguro?",
        text: "Esta acción borrará el elemento.",
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
                $("#modalEditElemento").modal("hide");
                table.ajax.reload();
              } else {
                Swal.fire("Error", response.message, "error");
              }
            }
          });
        }
      });
    });

    $("#add_alerta_stock").on("change", function(){ $("#add_minimo_container").toggle(this.checked); });
    $("#edit_alerta_stock").on("change", function(){ $("#edit_minimo_container").toggle(this.checked); });
  });
  </script>
</body>
</html>
