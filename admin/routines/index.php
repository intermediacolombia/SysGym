<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
$permisopage = 'Ver Rutinas';
include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';



if(isset($_GET['action']) && $_GET['action'] == "fetch"){
    $stmt = db()->prepare("
        SELECT r.id, r.nombre, r.descripcion, r.estado,
               COUNT(re.id) AS num_ejercicios
        FROM rutinas r
        LEFT JOIN rutina_ejercicio re ON re.rutina_id = r.id
        WHERE r.borrado = 0
        GROUP BY r.id
        ORDER BY r.id DESC
    ");
    $stmt->execute();
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'get_ejercicios'){
    $stmt = db()->query("SELECT id, nombre FROM ejercicios ORDER BY nombre ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'get_rutina' && isset($_GET['id'])){
    $id = $_GET['id'];
    $stmt = db()->prepare("SELECT id, nombre, descripcion, estado FROM rutinas WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $rutina = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtEj = db()->prepare("SELECT ejercicio_id AS id, repeticiones, series, duracion, descanso, orden FROM rutina_ejercicio WHERE rutina_id = :id ORDER BY orden ASC");
    $stmtEj->execute([':id' => $id]);
    $ejercicios = $stmtEj->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['rutina' => $rutina, 'ejercicios' => $ejercicios]);
    exit;
}

if(isset($_POST['action'])){
    $action = $_POST['action'];

    if($action == "add"){
        $stmt = db()->prepare("INSERT INTO rutinas (nombre, descripcion, estado) VALUES (:nombre, :descripcion, :estado)");
        $success = $stmt->execute([
            ':nombre' => $_POST['nombre'],
            ':descripcion' => $_POST['descripcion'],
            ':estado' => $_POST['estado']
        ]);
        $rutina_id = db()->lastInsertId();
		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'rutina_id'   => $rutina_id,
    'nombre'      => $_POST['nombre'],
    'descripcion' => $_POST['descripcion'],
    'estado'      => $_POST['estado'],
    'ejercicios'  => json_decode($_POST['ejercicios'] ?? '[]', true)
], JSON_UNESCAPED_UNICODE);

log_action('Crear rutina', $desc, 'Rutinas');
// END LOGS


        if($success && isset($_POST['ejercicios'])){
            $ejercicios = json_decode($_POST['ejercicios'], true);
            foreach ($ejercicios as $ej) {
                $stmtEj = db()->prepare("INSERT INTO rutina_ejercicio (rutina_id, ejercicio_id, repeticiones, series, duracion, descanso, orden) VALUES (:rutina_id, :ejercicio_id, :repeticiones, :series, :duracion, :descanso, :orden)");
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
        $stmt = db()->prepare("UPDATE rutinas SET nombre = :nombre, descripcion = :descripcion, estado = :estado WHERE id = :id");
        $success = $stmt->execute([
            ':nombre' => $_POST['nombre'],
            ':descripcion' => $_POST['descripcion'],
            ':estado' => $_POST['estado'],
            ':id' => $_POST['id']
        ]);
		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'rutina_id'   => $_POST['id'],
    'nombre'      => $_POST['nombre'],
    'descripcion' => $_POST['descripcion'],
    'estado'      => $_POST['estado'],
    'ejercicios'  => json_decode($_POST['ejercicios'] ?? '[]', true)
], JSON_UNESCAPED_UNICODE);

log_action('Editar rutina', $desc, 'Rutinas');
// END LOGS


        if ($success) {
            db()->prepare("DELETE FROM rutina_ejercicio WHERE rutina_id = :id")->execute([':id' => $_POST['id']]);

            if(isset($_POST['ejercicios'])){
                $ejercicios = json_decode($_POST['ejercicios'], true);
                foreach ($ejercicios as $ej) {
                    $stmtEj = db()->prepare("INSERT INTO rutina_ejercicio (rutina_id, ejercicio_id, repeticiones, series, duracion, descanso, orden) VALUES (:rutina_id, :ejercicio_id, :repeticiones, :series, :duracion, :descanso, :orden)");
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
        $stmt = db()->prepare("UPDATE rutinas SET borrado = 1 WHERE id = :id");
        $success = $stmt->execute([':id' => $_POST['id']]);
		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'rutina_id' => $_POST['id'],
    'accion'    => 'Rutina marcada como borrada'
], JSON_UNESCAPED_UNICODE);

log_action('Eliminar rutina', $desc, 'Rutinas');
// END LOGS

		
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
  <style>
  /* ═══════════════════════════════
     MÓDULO RUTINAS — diseño cards
  ═══════════════════════════════ */
  .rut-topbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 10px 0 18px;
  }
  .rut-search-box {
    display: flex;
    align-items: center;
    gap: 0;
    flex: 1;
    max-width: 340px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 0 12px;
    transition: border-color .2s, background .2s;
  }
  .rut-search-box:focus-within {
    border-color: var(--system-color-primary);
    background: #fff;
  }
  .rut-search-box i { color: #94a3b8; }
  .rut-search-box input {
    border: none;
    background: none;
    padding: 10px 8px;
    font-size: .88rem;
    width: 100%;
    outline: none;
    color: #334155;
  }
  .rut-counter {
    font-size: .8rem;
    color: #94a3b8;
    margin-left: auto;
  }

  /* Card grid */
  .rut-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
    gap: 20px;
    padding-bottom: 90px;
  }

  /* Card */
  .rut-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: transform .2s, box-shadow .2s, border-color .2s;
    position: relative;
  }
  .rut-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 32px rgba(0,0,0,.13);
    border-color: var(--system-color-primary);
  }
  .rut-card-stripe { height: 7px; }
  .rut-card-inner  { padding: 18px 20px 16px; }
  .rut-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
  }
  .rut-initial {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 900;
    color: #fff;
    flex-shrink: 0;
    text-shadow: 0 1px 4px rgba(0,0,0,.18);
  }
  .rut-badge {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    padding: 4px 11px;
    border-radius: 20px;
  }
  .rut-badge-activo   { background: #dcfce7; color: #15803d; }
  .rut-badge-inactivo { background: #fee2e2; color: #b91c1c; }
  .rut-card-name {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 5px;
    line-height: 1.3;
  }
  .rut-card-desc {
    font-size: .82rem;
    color: #64748b;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .rut-card-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
  }
  .rut-ej-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .76rem;
    font-weight: 600;
    color: var(--system-color-primary);
    background: color-mix(in srgb, var(--system-color-primary) 10%, transparent);
    padding: 3px 10px;
    border-radius: 20px;
  }
  .rut-hint { font-size: .72rem; color: #94a3b8; }

  /* Empty state */
  .rut-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 64px 20px;
    color: #94a3b8;
  }
  .rut-empty .rut-empty-icon {
    font-size: 3.5rem;
    margin-bottom: 14px;
    display: block;
    opacity: .4;
  }

  /* Skeleton loader */
  .rut-skeleton {
    height: 180px;
    border-radius: 20px;
    background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
    background-size: 200% 100%;
    animation: skel 1.4s infinite;
  }
  @keyframes skel {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
  }

  /* FAB */
  .rut-fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--system-color-primary), var(--system-color-primary-dark, #15803d));
    color: #fff;
    border: none;
    font-size: 1.55rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 22px color-mix(in srgb, var(--system-color-primary) 40%, transparent);
    cursor: pointer;
    transition: transform .2s, box-shadow .2s;
    z-index: 1050;
  }
  .rut-fab:hover {
    transform: scale(1.1) translateY(-2px);
    box-shadow: 0 12px 30px color-mix(in srgb, var(--system-color-primary) 50%, transparent);
  }

  /* Modal */
  #modalAddRutina .modal-content { border: none; border-radius: 20px; overflow: hidden; }
  #modalAddRutina .modal-header  {
    background: linear-gradient(135deg, var(--system-color-primary), var(--system-color-primary-dark, #15803d));
    color: #fff;
    padding: 20px 24px;
  }
  #modalAddRutina .modal-header .btn-close { filter: brightness(0) invert(1); }
  #modalAddRutina .modal-footer  { background: #f8fafc; }

  /* Exercise table */
  #tabla-ejercicios { font-size: .82rem; }
  #tabla-ejercicios thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: .73rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    white-space: nowrap;
  }
  #tabla-ejercicios .form-control,
  #tabla-ejercicios .form-select {
    border-radius: 8px;
    font-size: .82rem;
    border: 1.5px solid #e2e8f0;
    padding: 5px 8px;
  }
  #tabla-ejercicios .form-control:focus,
  #tabla-ejercicios .form-select:focus {
    border-color: var(--system-color-primary);
    box-shadow: none;
  }
  </style>
</head>
<body>

<div class="container" style="padding:0;background:rgba(0,0,0,0)">
  <div class="portada">
    <h1>Gestión de Rutinas</h1>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container">
  <div class="rut-topbar">
    <div class="rut-search-box">
      <i class="fa fa-search"></i>
      <input type="text" id="rutSearch" placeholder="Buscar rutina…">
    </div>
    <span class="rut-counter" id="rutCounter"></span>
  </div>

  <div class="rut-grid" id="rutGrid">
    <div class="rut-skeleton"></div>
    <div class="rut-skeleton"></div>
    <div class="rut-skeleton"></div>
    <div class="rut-skeleton"></div>
    <div class="rut-skeleton"></div>
    <div class="rut-skeleton"></div>
  </div>
</div>

<!-- FAB: Nueva Rutina -->
<button class="rut-fab" id="btnAddRutina" title="Nueva Rutina">
  <i class="fa fa-plus"></i>
</button>

<!-- Modal Agregar / Editar -->
<div class="modal fade" id="modalAddRutina" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form id="formAddRutina">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" style="color:#fff">
            <i class="material-icons" style="vertical-align:middle;font-size:20px">fitness_center</i>
            Rutina
            <span id="duracion-estimada" class="fw-normal ms-2" style="font-size:.85rem;opacity:.8"></span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body px-4 py-3">
          <div class="row g-3 mb-1">
            <div class="col-md-7">
              <label class="form-label fw-semibold">Nombre</label>
              <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Estado</label>
              <select name="estado" class="form-control">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Descripción</label>
              <textarea name="descripcion" class="form-control" rows="2" required></textarea>
            </div>
          </div>

          <hr class="my-3">

          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0">
              <i class="fa fa-list-ul me-1 text-muted"></i> Ejercicios
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnAddEjercicio">
              <i class="fa fa-plus me-1"></i>Agregar
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle" id="tabla-ejercicios">
              <thead>
                <tr>
                  <th>Ejercicio</th>
                  <th>Reps</th>
                  <th>Series</th>
                  <th>Dur (s)</th>
                  <th>Desc (min)</th>
                  <th>Orden</th>
                  <th></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-danger me-auto" id="btnDeleteRutina" style="display:none">
            <i class="fa fa-trash me-1"></i>Eliminar
          </button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="fa fa-save me-1"></i>Guardar
          </button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../inc/menu-footer.php'); ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function(){

  /* ── Paleta de colores para iniciales ── */
  const PALETTE = ['#4f8ef7','#34d399','#a78bfa','#fb923c','#f87171','#22d3ee','#f472b6','#fbbf24','#10b981','#6366f1'];
  function cardColor(name) {
    return PALETTE[(name || '?').charCodeAt(0) % PALETTE.length];
  }

  let allRutinas          = [];
  let ejerciciosDisponibles = [];

  /* ── Cargar lista de rutinas ── */
  function loadRutinas() {
    $.getJSON('index.php?action=fetch', function(resp){
      allRutinas = resp.data || [];
      renderGrid(allRutinas);
      actualizarContador(allRutinas.length);
    });
  }

  function actualizarContador(n) {
    $('#rutCounter').text(n + ' rutina' + (n !== 1 ? 's' : ''));
  }

  /* ── Render de cards ── */
  function renderGrid(list) {
    const grid = $('#rutGrid').empty();
    if (!list.length) {
      grid.html(`<div class="rut-empty">
        <span class="rut-empty-icon material-icons">fitness_center</span>
        <p class="fw-semibold mb-1">No hay rutinas</p>
        <small>Usa el botón + para crear la primera rutina.</small>
      </div>`);
      return;
    }
    list.forEach(function(r) {
      const col   = cardColor(r.nombre);
      const badge = r.estado === 'activo'
        ? '<span class="rut-badge rut-badge-activo">Activo</span>'
        : '<span class="rut-badge rut-badge-inactivo">Inactivo</span>';
      const numEj  = parseInt(r.num_ejercicios) || 0;
      const desc   = (r.descripcion || '').substring(0, 110) + ((r.descripcion||'').length > 110 ? '…' : '');

      grid.append(`
        <div class="rut-card" data-id="${r.id}">
          <div class="rut-card-stripe" style="background:${col}"></div>
          <div class="rut-card-inner">
            <div class="rut-card-top">
              <div class="rut-initial" style="background:${col}">${r.nombre.charAt(0).toUpperCase()}</div>
              ${badge}
            </div>
            <div class="rut-card-name">${r.nombre}</div>
            <div class="rut-card-desc">${desc}</div>
            <div class="rut-card-foot">
              <span class="rut-ej-pill"><i class="fa fa-bolt"></i> ${numEj} ejercicio${numEj !== 1 ? 's' : ''}</span>
              <span class="rut-hint">clic para editar</span>
            </div>
          </div>
        </div>
      `);
    });
  }

  /* ── Buscar ── */
  $('#rutSearch').on('input', function(){
    const q = $(this).val().toLowerCase().trim();
    if (!q) { renderGrid(allRutinas); actualizarContador(allRutinas.length); return; }
    const filtradas = allRutinas.filter(r =>
      r.nombre.toLowerCase().includes(q) || (r.descripcion||'').toLowerCase().includes(q)
    );
    renderGrid(filtradas);
    actualizarContador(filtradas.length);
  });

  /* ── Abrir edit al hacer clic en card ── */
  $('#rutGrid').on('click', '.rut-card', function(){
    const id = $(this).data('id');
    $.getJSON('index.php?action=get_rutina&id=' + id, function(r){
      if (!r.rutina) return;
      $('input[name="nombre"]').val(r.rutina.nombre);
      $('textarea[name="descripcion"]').val(r.rutina.descripcion);
      $('select[name="estado"]').val(r.rutina.estado);
      $('#formAddRutina').data('mode','edit').data('id', r.rutina.id);
      $('#tabla-ejercicios tbody').empty();
      $('#btnDeleteRutina').show();
      r.ejercicios.forEach((ej, i) => crearFilaEjercicio(i, ej));
      $('#modalAddRutina').modal('show');
      calcularDuracion();
    });
  });

  /* ── Ejercicios disponibles ── */
  function cargarEjercicios() {
    $.get('index.php?action=get_ejercicios', data => ejerciciosDisponibles = data, 'json');
  }

  /* ── Duración estimada ── */
  function calcularDuracion() {
    let total = 0;
    $('#tabla-ejercicios tbody tr').each(function(){
      const dur  = parseInt($(this).find('input[name*="[duracion]"]').val())  || 0;
      const desc = parseFloat($(this).find('input[name*="[descanso]"]').val())|| 0;
      const ser  = parseInt($(this).find('input[name*="[series]"]').val())    || 1;
      total += (dur + desc * 60) * ser;
    });
    const min = Math.floor(total / 60), sec = total % 60;
    $('#duracion-estimada').text(total > 0 ? `~${min}m ${sec}s` : '');
  }

  /* ── Fila ejercicio ── */
  function crearFilaEjercicio(i, ej = null) {
    const opts = ejerciciosDisponibles.map(e =>
      `<option value="${e.id}" ${ej && ej.id == e.id ? 'selected':''}>${e.nombre}</option>`
    ).join('');
    const fila = `<tr>
      <td><select class="form-select form-select-sm" name="ejercicios[${i}][id]">${opts}</select></td>
      <td><input type="number" class="form-control form-control-sm" name="ejercicios[${i}][repeticiones]" value="${ej?.repeticiones ?? 10}" min="0"></td>
      <td><input type="number" class="form-control form-control-sm" name="ejercicios[${i}][series]" value="${ej?.series ?? 3}" min="1"></td>
      <td><input type="number" class="form-control form-control-sm" name="ejercicios[${i}][duracion]" value="${ej?.duracion ?? 30}" min="0"></td>
      <td><input type="number" class="form-control form-control-sm" name="ejercicios[${i}][descanso]" value="${ej?.descanso ?? 1}" min="0"></td>
      <td><input type="number" class="form-control form-control-sm" name="ejercicios[${i}][orden]" value="${ej?.orden ?? i+1}" min="1"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger btnRemoveEj"><i class="fa fa-times"></i></button></td>
    </tr>`;
    $('#tabla-ejercicios tbody').append(fila);
    calcularDuracion();
  }

  /* ── Inicialización ── */
  cargarEjercicios();
  loadRutinas();

  /* ── FAB: abrir modal vacío ── */
  $('#btnAddRutina').click(function(){
    $('#formAddRutina')[0].reset();
    $('#formAddRutina').removeData('mode').removeData('id');
    $('#tabla-ejercicios tbody').empty();
    $('#btnDeleteRutina').hide();
    $('#duracion-estimada').text('');
    $('#modalAddRutina').modal('show');
  });

  /* ── Agregar ejercicio ── */
  $('#btnAddEjercicio').click(function(){
    crearFilaEjercicio($('#tabla-ejercicios tbody tr').length);
  });

  $(document).on('input',  '#tabla-ejercicios input', calcularDuracion);
  $(document).on('click', '.btnRemoveEj', function(){
    $(this).closest('tr').remove();
    calcularDuracion();
  });

  /* ── Guardar rutina ── */
  $('#formAddRutina').submit(function(e){
    e.preventDefault();
    const data = $(this).serializeArray();
    const ejercicios = [];
    $('#tabla-ejercicios tbody tr').each(function(i){
      ejercicios.push({
        id:           $(this).find('select').val(),
        repeticiones: $(this).find('input[name*="[repeticiones]"]').val(),
        series:       $(this).find('input[name*="[series]"]').val(),
        duracion:     $(this).find('input[name*="[duracion]"]').val(),
        descanso:     $(this).find('input[name*="[descanso]"]').val(),
        orden:        $(this).find('input[name*="[orden]"]').val(),
      });
    });
    const isEdit = $(this).data('mode') === 'edit';
    data.push({ name:'action', value: isEdit ? 'edit' : 'add' });
    if (isEdit) data.push({ name:'id', value: $(this).data('id') });
    data.push({ name:'ejercicios', value: JSON.stringify(ejercicios) });

    $.post('index.php', data, function(resp){
      if (resp.status === 'success') {
        Swal.fire({ icon:'success', title:'Guardado', timer:1400, showConfirmButton:false });
        $('#modalAddRutina').modal('hide');
        loadRutinas();
      } else {
        Swal.fire('Error','','error');
      }
    }, 'json');
  });

  /* ── Eliminar rutina ── */
  $('#btnDeleteRutina').click(function(){
    const id = $('#formAddRutina').data('id');
    Swal.fire({
      title:'¿Eliminar rutina?',
      text:'Esta acción no se puede deshacer.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Sí, eliminar',
      cancelButtonText:'Cancelar'
    }).then(res => {
      if (res.isConfirmed) {
        $.post('index.php', { action:'delete', id }, function(resp){
          if (resp.status === 'success') {
            Swal.fire({ icon:'success', title:'Eliminada', timer:1200, showConfirmButton:false });
            $('#modalAddRutina').modal('hide');
            loadRutinas();
          } else {
            Swal.fire('Error','','error');
          }
        }, 'json');
      }
    });
  });

});
</script>
</body>
</html>

