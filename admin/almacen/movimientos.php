<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/check_stock_alert.php';

$permisos = $_SESSION['user_permissions'] ?? [];
$puedeAdministrar = in_array('Administrar Almacén', $permisos);
$puedeEntradas = in_array('Registrar Entradas Almacén', $permisos);
$puedeSalidas = in_array('Registrar Salidas Almacén', $permisos);

// Misma lista fija de unidades de medida usada en index.php (clave BD => etiqueta visible)
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

if (!$puedeAdministrar && !$puedeEntradas && !$puedeSalidas) {
    $_SESSION['error'] = "<center>No tiene permisos para ver esta página.<br> Permiso necesario:<strong> 'Administrar Almacén', 'Registrar Entradas Almacén' o 'Registrar Salidas Almacén'</strong><center>";
    header("Location: $url/admin/");
    exit();
}

// =====================================================
// FETCH: Obtener movimientos (con filtros)
// =====================================================
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $where = ["1=1"];
    $params = [];

    if (!empty($_GET['elemento_id'])) {
        $where[] = "m.elemento_id = :elemento_id";
        $params[':elemento_id'] = (int)$_GET['elemento_id'];
    }
    if (!empty($_GET['tipo']) && in_array($_GET['tipo'], ['entrada', 'salida'])) {
        $where[] = "m.tipo = :tipo";
        $params[':tipo'] = $_GET['tipo'];
    }
    if (!empty($_GET['fecha_inicio'])) {
        $where[] = "DATE(m.created_at) >= :fecha_inicio";
        $params[':fecha_inicio'] = $_GET['fecha_inicio'];
    }
    if (!empty($_GET['fecha_fin'])) {
        $where[] = "DATE(m.created_at) <= :fecha_fin";
        $params[':fecha_fin'] = $_GET['fecha_fin'];
    }
    if (!empty($_GET['usuario_id'])) {
        $where[] = "m.usuario_id = :usuario_id";
        $params[':usuario_id'] = (int)$_GET['usuario_id'];
    }

    $sql = "SELECT m.id, m.elemento_id, e.nombre AS elemento_nombre, e.unidad_medida,
                   m.tipo, m.cantidad, m.proveedor, m.observacion, m.usuario_id,
                   TRIM(CONCAT(u.nombre, ' ', u.apellido)) AS usuario_nombre, m.created_at
            FROM almacen_movimientos m
            JOIN almacen_elementos e ON m.elemento_id = e.id
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY m.created_at DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

// =====================================================
// REGISTRAR ENTRADA
// =====================================================
if (isset($_POST['action']) && $_POST['action'] == "entrada") {
    if (!$puedeEntradas) {
        echo json_encode(['status' => 'error', 'message' => 'No tiene permiso para registrar entradas']);
        exit;
    }

    $elemento_id = (int)$_POST['elemento_id'];
    $cantidad = (float)$_POST['cantidad'];
    $proveedor = trim($_POST['proveedor'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');
    $usuario_id = $_SESSION['user']['id'] ?? null;

    if ($elemento_id <= 0 || $cantidad <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Elemento y cantidad (mayor a 0) son obligatorios']);
        exit;
    }

    $stmtCheck = db()->prepare("SELECT id FROM almacen_elementos WHERE id = :id AND borrado = 0");
    $stmtCheck->execute([':id' => $elemento_id]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'El elemento seleccionado no existe']);
        exit;
    }

    db()->beginTransaction();
    try {
        $stmt = db()->prepare("
            INSERT INTO almacen_movimientos (elemento_id, tipo, cantidad, proveedor, observacion, usuario_id)
            VALUES (:elemento_id, 'entrada', :cantidad, :proveedor, :observacion, :usuario_id)
        ");
        $stmt->execute([
            ':elemento_id' => $elemento_id,
            ':cantidad' => $cantidad,
            ':proveedor' => $proveedor !== '' ? $proveedor : null,
            ':observacion' => $observacion !== '' ? $observacion : null,
            ':usuario_id' => $usuario_id
        ]);

        $stmtUpd = db()->prepare("UPDATE almacen_elementos SET stock_actual = stock_actual + :cantidad WHERE id = :id");
        $stmtUpd->execute([':cantidad' => $cantidad, ':id' => $elemento_id]);

        db()->commit();

        require_once __DIR__ . '/../inc/log_action.php';
        log_action('Registrar Entrada Almacén', json_encode([
            'elemento_id' => $elemento_id, 'cantidad' => $cantidad, 'proveedor' => $proveedor, 'observacion' => $observacion
        ], JSON_UNESCAPED_UNICODE), 'Almacén');

        echo json_encode(['status' => 'success', 'message' => 'Entrada registrada correctamente']);
    } catch (Exception $ex) {
        db()->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar la entrada']);
    }
    exit;
}

// =====================================================
// REGISTRAR SALIDA
// =====================================================
if (isset($_POST['action']) && $_POST['action'] == "salida") {
    if (!$puedeSalidas) {
        echo json_encode(['status' => 'error', 'message' => 'No tiene permiso para registrar salidas']);
        exit;
    }

    $elemento_id = (int)$_POST['elemento_id'];
    $cantidad = (float)$_POST['cantidad'];
    $observacion = trim($_POST['observacion'] ?? '');
    $usuario_id = $_SESSION['user']['id'] ?? null;

    if ($elemento_id <= 0 || $cantidad <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Elemento y cantidad (mayor a 0) son obligatorios']);
        exit;
    }

    $stmtCheck = db()->prepare("SELECT id, stock_actual FROM almacen_elementos WHERE id = :id AND borrado = 0");
    $stmtCheck->execute([':id' => $elemento_id]);
    $elemento = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$elemento) {
        echo json_encode(['status' => 'error', 'message' => 'El elemento seleccionado no existe']);
        exit;
    }

    if ($cantidad > (float)$elemento['stock_actual']) {
        echo json_encode(['status' => 'error', 'message' => 'No hay suficiente stock disponible para esta salida']);
        exit;
    }

    db()->beginTransaction();
    try {
        $stmt = db()->prepare("
            INSERT INTO almacen_movimientos (elemento_id, tipo, cantidad, proveedor, observacion, usuario_id)
            VALUES (:elemento_id, 'salida', :cantidad, NULL, :observacion, :usuario_id)
        ");
        $stmt->execute([
            ':elemento_id' => $elemento_id,
            ':cantidad' => $cantidad,
            ':observacion' => $observacion !== '' ? $observacion : null,
            ':usuario_id' => $usuario_id
        ]);

        $stmtUpd = db()->prepare("UPDATE almacen_elementos SET stock_actual = stock_actual - :cantidad WHERE id = :id");
        $stmtUpd->execute([':cantidad' => $cantidad, ':id' => $elemento_id]);

        db()->commit();

        require_once __DIR__ . '/../inc/log_action.php';
        log_action('Registrar Salida Almacén', json_encode([
            'elemento_id' => $elemento_id, 'cantidad' => $cantidad, 'observacion' => $observacion
        ], JSON_UNESCAPED_UNICODE), 'Almacén');

        $stock_anterior = (float)$elemento['stock_actual'];
        check_almacen_stock_alert($elemento_id, $stock_anterior, $stock_anterior - $cantidad, $api_ws);

        echo json_encode(['status' => 'success', 'message' => 'Salida registrada correctamente']);
    } catch (Exception $ex) {
        db()->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar la salida']);
    }
    exit;
}

// Elementos activos para los selects de los modales
$elementos = db()->query("SELECT id, nombre, unidad_medida, stock_actual FROM almacen_elementos WHERE borrado = 0 AND estado = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Almacén - Movimientos</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
  <div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
    <div class="portada">
      <h1 class="mb-4">Almacén - Movimientos</h1>
      <?php if ($puedeEntradas): ?>
        <button class="btn btn-success float-end ms-2" id="btnEntrada"><i class="fa fa-arrow-down"></i> Registrar Entrada</button>
      <?php endif; ?>
      <?php if ($puedeSalidas): ?>
        <button class="btn btn-warning float-end" id="btnSalida"><i class="fa fa-arrow-up"></i> Registrar Salida</button>
      <?php endif; ?>
    </div>
  </div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    <div class="row mb-3">
      <div class="col-md-3">
        <label for="filtro_elemento" class="form-label">Elemento</label>
        <select id="filtro_elemento" class="form-control">
          <option value="">Todos</option>
          <?php foreach ($elementos as $e): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filtro_tipo" class="form-label">Tipo</label>
        <select id="filtro_tipo" class="form-control">
          <option value="">Todos</option>
          <option value="entrada">Entrada</option>
          <option value="salida">Salida</option>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filtro_fecha_inicio" class="form-label">Desde</label>
        <input type="date" id="filtro_fecha_inicio" class="form-control">
      </div>
      <div class="col-md-2">
        <label for="filtro_fecha_fin" class="form-label">Hasta</label>
        <input type="date" id="filtro_fecha_fin" class="form-control">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button id="btnFiltrar" class="btn btn-primary me-2"><i class="fa fa-filter"></i> Filtrar</button>
        <button id="btnLimpiarFiltro" class="btn btn-secondary"><i class="fa fa-times"></i> Limpiar</button>
      </div>
    </div>

    <table id="movimientos-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Elemento</th>
          <th>Tipo</th>
          <th>Cantidad</th>
          <th>Proveedor</th>
          <th>Observación</th>
          <th>Usuario</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- Modal Registrar Entrada -->
  <div class="modal fade" id="modalEntrada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formEntrada">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Entrada</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="entrada_elemento_id" class="form-label">Elemento</label>
              <select class="form-control" id="entrada_elemento_id" name="elemento_id" required>
                <option value="">Seleccione</option>
                <?php foreach ($elementos as $e): ?>
                  <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($unidades_medida[$e['unidad_medida']] ?? $e['unidad_medida']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="entrada_cantidad" class="form-label">Cantidad</label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="entrada_cantidad" name="cantidad" required>
            </div>
            <div class="mb-3">
              <label for="entrada_proveedor" class="form-label">Proveedor (opcional)</label>
              <input type="text" class="form-control" id="entrada_proveedor" name="proveedor">
            </div>
            <div class="mb-3">
              <label for="entrada_observacion" class="form-label">Observación (opcional)</label>
              <textarea class="form-control" id="entrada_observacion" name="observacion" rows="2"></textarea>
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

  <!-- Modal Registrar Salida -->
  <div class="modal fade" id="modalSalida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formSalida">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Salida</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="salida_elemento_id" class="form-label">Elemento</label>
              <select class="form-control" id="salida_elemento_id" name="elemento_id" required>
                <option value="">Seleccione</option>
                <?php foreach ($elementos as $e): ?>
                  <option value="<?= $e['id'] ?>" data-stock="<?= $e['stock_actual'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($unidades_medida[$e['unidad_medida']] ?? $e['unidad_medida']) ?>) - Disponible: <?= $e['stock_actual'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="salida_cantidad" class="form-label">Cantidad</label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="salida_cantidad" name="cantidad" required>
            </div>
            <div class="mb-3">
              <label for="salida_observacion" class="form-label">Observación / motivo</label>
              <textarea class="form-control" id="salida_observacion" name="observacion" rows="2"></textarea>
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

<?php include('../inc/menu-footer.php'); ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
  var UNIDADES = <?= json_encode($unidades_medida, JSON_UNESCAPED_UNICODE) ?>;

  $(document).ready(function(){
    function buildAjaxUrl(){
      var params = $.param({
        action: 'fetch',
        elemento_id: $('#filtro_elemento').val(),
        tipo: $('#filtro_tipo').val(),
        fecha_inicio: $('#filtro_fecha_inicio').val(),
        fecha_fin: $('#filtro_fecha_fin').val()
      });
      return 'movimientos.php?' + params;
    }

    var table = $('#movimientos-table').DataTable({
      "ajax": buildAjaxUrl(),
      "columns": [
        { "data": "created_at" },
        { "data": "elemento_nombre" },
        { "data": "tipo",
          "render": function(data){
            return data === 'entrada'
              ? '<span class="badge bg-success">Entrada</span>'
              : '<span class="badge bg-warning text-dark">Salida</span>';
          }
        },
        { "data": null,
          "render": function(data, type, row){ return row.cantidad + ' ' + (UNIDADES[row.unidad_medida] || row.unidad_medida); }
        },
        { "data": "proveedor", "defaultContent": "-" },
        { "data": "observacion", "defaultContent": "-" },
        { "data": "usuario_nombre", "defaultContent": "-" }
      ],
      "order": [[0, 'desc']],
      "pageLength": 50,
      "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
    });

    $('#btnFiltrar').on('click', function(){
      table.ajax.url(buildAjaxUrl()).load();
    });

    $('#btnLimpiarFiltro').on('click', function(){
      $('#filtro_elemento, #filtro_tipo').val('');
      $('#filtro_fecha_inicio, #filtro_fecha_fin').val('');
      table.ajax.url(buildAjaxUrl()).load();
    });

    $('#btnEntrada').on('click', function(){
      $('#formEntrada')[0].reset();
      $('#modalEntrada').modal('show');
    });

    $('#formEntrada').on('submit', function(e){
      e.preventDefault();
      $.ajax({
        url: 'movimientos.php',
        method: 'POST',
        data: {
          action: 'entrada',
          elemento_id: $('#entrada_elemento_id').val(),
          cantidad: $('#entrada_cantidad').val(),
          proveedor: $('#entrada_proveedor').val(),
          observacion: $('#entrada_observacion').val()
        },
        dataType: 'json',
        success: function(response){
          if(response.status === 'success'){
            Swal.fire('Éxito', response.message, 'success');
            $('#modalEntrada').modal('hide');
            table.ajax.url(buildAjaxUrl()).load();
          } else {
            Swal.fire('Error', response.message, 'error');
          }
        }
      });
    });

    $('#btnSalida').on('click', function(){
      $('#formSalida')[0].reset();
      $('#modalSalida').modal('show');
    });

    $('#formSalida').on('submit', function(e){
      e.preventDefault();
      $.ajax({
        url: 'movimientos.php',
        method: 'POST',
        data: {
          action: 'salida',
          elemento_id: $('#salida_elemento_id').val(),
          cantidad: $('#salida_cantidad').val(),
          observacion: $('#salida_observacion').val()
        },
        dataType: 'json',
        success: function(response){
          if(response.status === 'success'){
            Swal.fire('Éxito', response.message, 'success');
            $('#modalSalida').modal('hide');
            table.ajax.url(buildAjaxUrl()).load();
          } else {
            Swal.fire('Error', response.message, 'error');
          }
        }
      });
    });
  });
  </script>
</body>
</html>
