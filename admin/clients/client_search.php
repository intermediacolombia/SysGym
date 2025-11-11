<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Clientes';
include('../login/restriction.php');
session_start();

// Configuración de base de datos
require_once __DIR__ . '/../../inc/config.php';

// Obtener planes para el dropdown
try {
    $pdoPlanes = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdoPlanes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $planStmt = $pdoPlanes->query("SELECT id, nombre FROM planes WHERE borrado = 0 ORDER BY nombre ASC");
    $planes = $planStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $planes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Búsqueda Avanzada de Clientes</title>
  <?php include('../inc/header.php'); ?>
  
</head>
<body>

<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Búsqueda Avanzada de Clientes</h1>
	   <?php if (isset($_SESSION["user_permissions"]) && in_array('Agregar Clientes', $_SESSION["user_permissions"])): ?>
    <button class="btn btn-success float-end ms-2" onclick="window.location='new.php'">
      <i class="fa fa-plus"></i> Nuevo Cliente
    </button>
  <?php endif; ?>

  <button class="btn btn-secondary float-end" onclick="window.location='index.php'">
  <i class="fa fa-arrow-left me-1"></i> Volver al listado general
</button>

  </div>
</div>
<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
 
	
	<div class="alert alert-info">
  <h5 class="mb-1"><i class="fa fa-info-circle me-2"></i>Cómo funciona la búsqueda</h5>
  <ul class="mb-0">
    <li>Escribe uno o varios términos en cualquiera de los campos y la tabla se actualizará automáticamente.</li>
    <li>Usa <code>*</code> en cualquier campo de texto para ver todos los registros que tengan ese campo no vacío.</li>
    <li>Sólo verás los clientes que cumplan <em>todos</em> los filtros seleccionados.</li>
    <li>Para restablecer todos los filtros, pulsa el botón <strong>Reset</strong>.</li>
  </ul>
</div>


  <form id="filtrosCliente" class="row g-3 mb-3">
    <!--div class="col-md-2 form-group">
      <label for="identificacion" class="form-label">Identificación</label>
      <input id="identificacion" name="identificacion" class="form-control" placeholder="Identificación">
    </div-->
    <div class="col-md-2 form-group">
      <label for="nombres" class="form-label">Nombres</label>
      <input id="nombres" name="nombres" class="form-control">
    </div>
    <div class="col-md-2 form-group">
      <label for="apellidos" class="form-label">Apellidos</label>
      <input id="apellidos" name="apellidos" class="form-control">
    </div>
	  
	  <div class="col-md-2 form-group">
      <label for="fecha_nacimiento" class="form-label">Nacimiento</label>
      <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="form-control">
    </div>
	  
	  <div class="col-md-2 form-group">
      <label for="genero" class="form-label">Género</label>
      <select id="genero" name="genero" class="form-select">
        <option value="">---</option>
        <option value="masculino">Masculino</option>
        <option value="femenino">Femenino</option>
        <option value="otro">Otro</option>
      </select>
    </div>
	  
    <div class="col-md-2 form-group">
      <label for="telefono" class="form-label">Teléfono</label>
      <input id="telefono" name="telefono" class="form-control" placeholder="Teléfono">
    </div>
    <div class="col-md-2 form-group">
      <label for="direccion" class="form-label">Dirección</label>
      <input id="direccion" name="direccion" class="form-control">
    </div>
    <div class="col-md-2 form-group">
      <label for="email" class="form-label">Correo</label>
      <input id="email" name="email" class="form-control">
    </div>

    <div class="col-md-2 form-group">
      <label for="estado" class="form-label">Estado</label>
      <select id="estado" name="estado" class="form-select">
        <option value="">---</option>
        <option value="activo">Activo</option>
        <option value="inactivo">Inactivo</option>
      </select>
    </div>
    

    <div class="col-md-2 form-group">
      <label for="plan" class="form-label">Plan</label>
      <select id="plan" name="plan" class="form-select">
        <option value="">---</option>
        <?php foreach ($planes as $pl): ?>
          <option value="<?= $pl['id'] ?>"><?= htmlspecialchars($pl['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
	  
	  <div class="col-md-2 form-group">
  <label for="vencimiento_plan" class="form-label">Vencimiento Plan</label>
  <input id="vencimiento_plan" name="vencimiento_plan" type="date" class="form-control">
</div>
<div class="col-md-2 form-group">
      <label for="congelado" class="form-label">¿Congelado?</label>
      <select id="congelado" name="congelado" class="form-select">
        <option value="">---</option>
        <option value="1">Sí</option>
        <option value="0">No</option>
      </select>
    </div>

    
    <div class="col-md-2 form-group">
  <label for="rh" class="form-label">RH</label>
  <select id="rh" name="rh" class="form-select">
    <option value="">---</option>
    <option value="A+">A+</option>
    <option value="A-">A-</option>
    <option value="B+">B+</option>
    <option value="B-">B-</option>
    <option value="AB+">AB+</option>
    <option value="AB-">AB-</option>
    <option value="O+">O+</option>
    <option value="O-">O-</option>
  </select>
</div>

    <div class="col-md-2 form-group">
      <label for="eps" class="form-label">EPS</label>
      <input id="eps" name="eps" class="form-control">
    </div>
    <div class="col-md-2 form-group">
      <label for="fracturas" class="form-label">Fracturas</label>
      <input id="fracturas" name="fracturas" class="form-control">
    </div>
    <div class="col-md-2 form-group">
      <label for="alergias" class="form-label">Alergias</label>
      <input id="alergias" name="alergias" class="form-control">
    </div>
    <div class="col-md-2 form-group">
      <label for="enfermedades_actuales" class="form-label">Enfermedades</label>
      <input id="enfermedades_actuales" name="enfermedades_actuales" class="form-control">
    </div>
    <!--div class="col-md-2 form-group">
      <label for="observaciones" class="form-label">Observaciones</label>
      <input id="observaciones" name="observaciones" class="form-control">
    </div-->
    <div class="col-md-2 form-group">
      <label for="contacto_emergencia" class="form-label">Contacto Emergencia</label>
      <input id="contacto_emergencia" name="contacto_emergencia" class="form-control">
    </div>
    <div class="col-md-2 form-group">
      <label for="numero_emergencia" class="form-label">Tel. Emergencia</label>
      <input id="numero_emergencia" name="numero_emergencia" class="form-control">
    </div>
    

    <div class="col-md-2 d-grid">
  <button type="button" id="resetFilters" class="btn btn-secondary mt-4">
    <i class="fas fa-undo-alt me-2"></i> Reset
  </button>
</div>

  </form>

  <table id="clients-table" class="table table-striped table-bordered w-100">
    <thead>
      <tr>
        <th>ID</th>
        <th>Identificación</th>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>Teléfono</th>
        <th>Plan</th>
        <th>Vencimiento Plan</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<?php include('../inc/menu-footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!--script>
$(document).ready(function () {
  const $form = $('#filtrosCliente');

  // 1. Recuperar filtros guardados
  const saved = JSON.parse(localStorage.getItem('filtrosCliente') || '{}');
  Object.entries(saved).forEach(([k, v]) => {
    $form.find(`[name="${k}"]`).val(v);
  });

  // 2. Inicializar DataTable con Ajax y filtros
  const table = $('#clients-table').DataTable({
    ajax: {
      url: 'clientes_fetch.php',
      data: function () {
        const d = {};
        $form.serializeArray().forEach(f => d[f.name] = f.value);
        // Guardar en localStorage
        localStorage.setItem('filtrosCliente', JSON.stringify(d));
        return d;
      },
      dataSrc: 'data'
    },
    columns: [
      { data: 'identificacion' },
      { data: 'nombres' },
      { data: 'apellidos' },
      { data: d => '+' + (d.dialCode||'') + ' ' + (d.telefono||'') },
      { data: 'plan_nombre',     defaultContent: '—' },
      { data: 'vencimiento_plan', defaultContent: '—' },
      {
        data: 'estado',
        render: e => e === 'activo'
          ? '<span class="badge bg-success">Activo</span>'
          : '<span class="badge bg-danger">Inactivo</span>'
      }
    ],
    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
    },
    pageLength: 50
  });

  // 3. Recargar resultados al cambiar cualquier filtro
  $form.on('input change', 'input, select', () => {
    table.ajax.reload();
  });

  // 4. Redirigir al detalle al hacer clic en una fila
  $('#clients-table tbody').on('click', 'tr', function () {
    const d = table.row(this).data();
    if (d && d.id) {
      window.location.href = 'detail.php?id=' + encodeURIComponent(d.id);
    }
  });

  // 5. Botón Reset: limpiar filtros y localStorage
  $('#resetFilters').on('click', function () {
    localStorage.removeItem('filtrosCliente');
    $form[0].reset();
    table.ajax.reload();
  });
});
</script-->
	
	<script>
$(document).ready(function () {
  const $form = $('#filtrosCliente');

  // 1. Inicializar DataTable con Ajax y filtros
  const table = $('#clients-table').DataTable({
    ajax: {
      url: 'clientes_fetch.php',
      data: function () {
        // Enviar todos los filtros al servidor
        return $form.serialize();
      },
      dataSrc: 'data'
    },
    columns: [
      { data: 'id' },
      { data: 'identificacion' },
      { data: 'nombres' },
      { data: 'apellidos' },
      { data: d => '+' + (d.dialCode||'') + ' ' + (d.telefono||'') },
      { data: 'plan_nombre',     defaultContent: '—' },
      { data: 'vencimiento_plan', defaultContent: '—' },
      {
        data: 'estado',
        render: e => e === 'activo'
          ? '<span class="badge bg-success">Activo</span>'
          : '<span class="badge bg-danger">Inactivo</span>'
      }
    ],
    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
    },
    pageLength: 50
  });

  // 2. Recargar resultados al cambiar cualquier filtro
  $form.on('input change', 'input, select', () => {
    table.ajax.reload();
  });

  // 3. Redirigir al detalle al hacer clic en una fila
  $('#clients-table tbody').on('click', 'tr', function () {
    const d = table.row(this).data();
    if (d && d.id) {
      window.location.href = 'detail.php?id=' + encodeURIComponent(d.id);
    }
  });

  // 4. Botón Reset: limpiar filtros
  $('#resetFilters').on('click', function () {
    $form[0].reset();
    table.ajax.reload();
  });
});
</script>


</body>
</html>




