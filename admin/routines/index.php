<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
$permisopage = 'Ver Rutinas';
include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

if(isset($_GET['action']) && $_GET['action'] == "fetch"){
    $stmt = $pdo->prepare("SELECT id, nombre, descripcion, estado FROM rutinas WHERE borrado = 0");
    $stmt->execute();
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'get_ejercicios'){
    $stmt = $pdo->query("SELECT id, nombre FROM ejercicios ORDER BY nombre ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'get_rutina' && isset($_GET['id'])){
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT id, nombre, descripcion, estado FROM rutinas WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $rutina = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtEj = $pdo->prepare("SELECT ejercicio_id AS id, repeticiones, series, duracion, descanso, orden FROM rutina_ejercicio WHERE rutina_id = :id ORDER BY orden ASC");
    $stmtEj->execute([':id' => $id]);
    $ejercicios = $stmtEj->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['rutina' => $rutina, 'ejercicios' => $ejercicios]);
    exit;
}

if(isset($_POST['action'])){
    $action = $_POST['action'];

    if($action == "add"){
        $stmt = $pdo->prepare("INSERT INTO rutinas (nombre, descripcion, estado) VALUES (:nombre, :descripcion, :estado)");
        $success = $stmt->execute([
            ':nombre' => $_POST['nombre'],
            ':descripcion' => $_POST['descripcion'],
            ':estado' => $_POST['estado']
        ]);
        $rutina_id = $pdo->lastInsertId();

        if($success && isset($_POST['ejercicios'])){
            $ejercicios = json_decode($_POST['ejercicios'], true);
            foreach ($ejercicios as $ej) {
                $stmtEj = $pdo->prepare("INSERT INTO rutina_ejercicio (rutina_id, ejercicio_id, repeticiones, series, duracion, descanso, orden) VALUES (:rutina_id, :ejercicio_id, :repeticiones, :series, :duracion, :descanso, :orden)");
                $stmtEj->execute([
                    ':rutina_id' => $rutina_id,
                    ':ejercicio_id' => $ej['id'],
                    ':repeticiones' => $ej['repeticiones'],
                    ':series' => $ej['series'],
                    ':duracion' => $ej['duracion'],
                    ':descanso' => $ej['descanso'],
                    ':orden' => $ej['orden']
                ]);
            }
        }

        echo json_encode(['status' => $success ? 'success' : 'error']);
        exit;
    }

    if($action == "edit" && isset($_POST['id'])){
        $stmt = $pdo->prepare("UPDATE rutinas SET nombre = :nombre, descripcion = :descripcion, estado = :estado WHERE id = :id");
        $success = $stmt->execute([
            ':nombre' => $_POST['nombre'],
            ':descripcion' => $_POST['descripcion'],
            ':estado' => $_POST['estado'],
            ':id' => $_POST['id']
        ]);

        if ($success) {
            $pdo->prepare("DELETE FROM rutina_ejercicio WHERE rutina_id = :id")->execute([':id' => $_POST['id']]);

            if(isset($_POST['ejercicios'])){
                $ejercicios = json_decode($_POST['ejercicios'], true);
                foreach ($ejercicios as $ej) {
                    $stmtEj = $pdo->prepare("INSERT INTO rutina_ejercicio (rutina_id, ejercicio_id, repeticiones, series, duracion, descanso, orden) VALUES (:rutina_id, :ejercicio_id, :repeticiones, :series, :duracion, :descanso, :orden)");
                    $stmtEj->execute([
                        ':rutina_id' => $_POST['id'],
                        ':ejercicio_id' => $ej['id'],
                        ':repeticiones' => $ej['repeticiones'],
                        ':series' => $ej['series'],
                        ':duracion' => $ej['duracion'],
                        ':descanso' => $ej['descanso'],
                        ':orden' => $ej['orden']
                    ]);
                }
            }
        }

        echo json_encode(['status' => $success ? 'success' : 'error']);
        exit;
    }

    if($action == "delete"){
        $stmt = $pdo->prepare("UPDATE rutinas SET borrado = 1 WHERE id = :id");
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
  <title>Gestión de Rutinas</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
	<h1>Gestión de Rutinas</h1>
	
	<button class="btn btn-success float-end" id="btnAddRutina"><i class="fa fa-plus"></i> Agregar Rutina</button>
	</div>
</div>
<?php include('../inc/menu.php'); ?>
<div class="container">
 
  
  <table id="rutinas-table" class="table table-striped">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal -->
<div class="modal fade" id="modalAddRutina" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form id="formAddRutina">
        <div class="modal-header">
          <h5 class="modal-title">Rutina <span id="duracion-estimada" class="text-primary small fw-normal"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label>Nombre</label>
          <input type="text" name="nombre" class="form-control mb-2" required>
          <label>Descripción</label>
          <textarea name="descripcion" class="form-control mb-2" required></textarea>
          <label>Estado</label>
          <select name="estado" class="form-control mb-2">
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
          </select>

          <hr>
          <h6>Ejercicios</h6>
          <table class="table table-sm" id="tabla-ejercicios">
            <thead>
              <tr>
                <th>Ejercicio</th>
                <th>Reps</th>
                <th>Series</th>
                <th>Duración (s)</th>
                <th>Descanso (min)</th>
                <th>Orden</th>
                <th></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <button type="button" class="btn btn-outline-primary" id="btnAddEjercicio">+ Agregar Ejercicio</button>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger me-auto" id="btnDeleteRutina" style="display:none"><i class="fa fa-trash"></i> Eliminar</button>
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
$(function(){
  let table = $('#rutinas-table').DataTable({
    ajax: 'index.php?action=fetch',
    columns: [
      { data: 'nombre' },
      { data: 'descripcion' },
      { data: 'estado' }
    ]
  });

  let ejerciciosDisponibles = [];

  function cargarEjerciciosDisponibles() {
    $.get('index.php?action=get_ejercicios', data => ejerciciosDisponibles = data, 'json');
  }

  function calcularDuracionTotal() {
    let total = 0;
    $('#tabla-ejercicios tbody tr').each(function(){
      let duracion = parseInt($(this).find('input[name*="[duracion]"]').val()) || 0;
      let descanso = parseFloat($(this).find('input[name*="[descanso]"]').val()) || 0;
      let series = parseInt($(this).find('input[name*="[series]"]').val()) || 1;
      total += (duracion + descanso * 60) * series;
    });
    let min = Math.floor(total / 60), sec = total % 60;
    $('#duracion-estimada').text(` - Duración estimada: ${min}m ${sec}s`);
  }

  function crearFilaEjercicio(i, ej = null) {
    let options = ejerciciosDisponibles.map(e => `<option value="${e.id}" ${ej && ej.id == e.id ? 'selected' : ''}>${e.nombre}</option>`).join('');
    let fila = `<tr>
      <td><select class="form-select" name="ejercicios[${i}][id]">${options}</select></td>
      <td><input type="number" class="form-control" name="ejercicios[${i}][repeticiones]" value="${ej?.repeticiones ?? 10}"></td>
      <td><input type="number" class="form-control" name="ejercicios[${i}][series]" value="${ej?.series ?? 3}"></td>
      <td><input type="number" class="form-control" name="ejercicios[${i}][duracion]" value="${ej?.duracion ?? 30}"></td>
      <td><input type="number" class="form-control" name="ejercicios[${i}][descanso]" value="${ej?.descanso ?? 1}"></td>
      <td><input type="number" class="form-control" name="ejercicios[${i}][orden]" value="${ej?.orden ?? i+1}"></td>
      <td><button class="btn btn-sm btn-danger btnRemoveEj"><i class="fa fa-trash"></i></button></td>
    </tr>`;
    $('#tabla-ejercicios tbody').append(fila);
    calcularDuracionTotal();
  }

  cargarEjerciciosDisponibles();

  $('#btnAddRutina').click(function(){
    $('#formAddRutina')[0].reset();
    $('#formAddRutina').removeData('mode').removeData('id');
    $('#tabla-ejercicios tbody').empty();
    $('#btnDeleteRutina').hide();
    $('#modalAddRutina').modal('show');
    $('#duracion-estimada').text('');
  });

  $('#btnAddEjercicio').click(function(){
    let i = $('#tabla-ejercicios tbody tr').length;
    crearFilaEjercicio(i);
  });

  $(document).on('input', '#tabla-ejercicios input', calcularDuracionTotal);
  $(document).on('click', '.btnRemoveEj', function(){
    $(this).closest('tr').remove();
    calcularDuracionTotal();
  });

  $('#rutinas-table tbody').on('click', 'tr', function(){
    let data = table.row(this).data();
    $.get('index.php?action=get_rutina&id=' + data.id, function(r){
      if(r.rutina){
        $('input[name="nombre"]').val(r.rutina.nombre);
        $('textarea[name="descripcion"]').val(r.rutina.descripcion);
        $('select[name="estado"]').val(r.rutina.estado);
        $('#formAddRutina').data('mode','edit').data('id', r.rutina.id);
        $('#tabla-ejercicios tbody').empty();
        $('#btnDeleteRutina').show();
        r.ejercicios.forEach((ej, i) => crearFilaEjercicio(i, ej));
        $('#modalAddRutina').modal('show');
      }
    }, 'json');
  });

  $('#formAddRutina').submit(function(e){
    e.preventDefault();
    let data = $(this).serializeArray();
    let ejercicios = [];
    $('#tabla-ejercicios tbody tr').each(function(i){
      ejercicios.push({
        id: $(this).find('select').val(),
        repeticiones: $(this).find('input[name*="[repeticiones]"]').val(),
        series: $(this).find('input[name*="[series]"]').val(),
        duracion: $(this).find('input[name*="[duracion]"]').val(),
        descanso: $(this).find('input[name*="[descanso]"]').val(),
        orden: $(this).find('input[name*="[orden]"]').val()
      });
    });
    data.push({ name: 'action', value: $(this).data('mode') === 'edit' ? 'edit' : 'add' });
    if($(this).data('mode') === 'edit') data.push({ name: 'id', value: $(this).data('id') });
    data.push({ name: 'ejercicios', value: JSON.stringify(ejercicios) });

    $.post('index.php', data, function(resp){
      if(resp.status === 'success'){
        Swal.fire('Guardado', '', 'success');
        $('#modalAddRutina').modal('hide');
        table.ajax.reload();
      } else {
        Swal.fire('Error', '', 'error');
      }
    }, 'json');
  });

  $('#btnDeleteRutina').click(function(){
    let id = $('#formAddRutina').data('id');
    Swal.fire({
      title: '¿Eliminar rutina?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar'
    }).then(res => {
      if(res.isConfirmed){
        $.post('index.php', { action: 'delete', id: id }, function(resp){
          if(resp.status === 'success'){
            Swal.fire('Eliminada', '', 'success');
            $('#modalAddRutina').modal('hide');
            table.ajax.reload();
          } else {
            Swal.fire('Error', '', 'error');
          }
        }, 'json');
      }
    });
  });
});
</script>
</body>
</html>

