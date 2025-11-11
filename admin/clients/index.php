<?php 

require_once __DIR__ . '/../login/session.php';

$permisopage = 'Ver Clientes';
include('../login/restriction.php');

// Incluir la configuración de la base de datos
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Traer todos los clientes no borrados
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE borrado = 0 ORDER BY identificacion DESC");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Clientes</title>
  
  <!-- ✅ ORDEN CORRECTO: Primero header.php -->
  <?php include('../inc/header.php'); ?>
  
  <!-- ✅ Solo cargar si header.php NO incluye Bootstrap 5 -->
  <!-- Verifica esto y comenta estas líneas si ya están en header.php -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- DataTables Bootstrap 5 CSS -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>

<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Listado de Clientes</h1>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Agregar Clientes', $_SESSION["user_permissions"])): ?>
      <button class="btn btn-success float-end ms-2" onclick="window.location='new.php'">
        <i class="fa fa-plus"></i> Nuevo Cliente
      </button>
    <?php endif; ?>

    <button class="btn btn-primary float-end" onclick="window.location='client_search.php'">
      <i class="fa fa-search me-1"></i> Búsqueda Avanzada
    </button>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container mt-3">
  <div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" id="toggleInactiveSwitch">
    <label class="form-check-label" for="toggleInactiveSwitch">
      Ocultar clientes inactivos (<span id="inactiveCount">0</span>)
    </label>
  </div>

  <table id="clients-table" class="table table-striped table-bordered">
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
    <tbody>
      <?php foreach ($clientes as $cliente): ?>
      <tr data-id="<?= htmlspecialchars($cliente['id']) ?>">
        <td><?= htmlspecialchars($cliente['id']) ?></td>
        <td><?= htmlspecialchars($cliente['identificacion']) ?></td>
        <td><?= htmlspecialchars($cliente['nombres']) ?></td>
        <td><?= htmlspecialchars($cliente['apellidos']) ?></td>
        <td>+<?= htmlspecialchars($cliente['dialCode']) ?> <?= htmlspecialchars($cliente['telefono']) ?></td>
        <td>
          <?php
            if (!empty($cliente['plan'])) {
              $stmtPlan = $pdo->prepare("SELECT nombre FROM planes WHERE id = :plan AND borrado = 0");
              $stmtPlan->execute([':plan' => $cliente['plan']]);
              echo htmlspecialchars($stmtPlan->fetchColumn());
            }
            if ($cliente['congelado'] == 1) {
              echo ' <i class="fa fa-snowflake-o text-info"></i>';
            }
          ?>
        </td>
        <td>
          <?= !empty($cliente['vencimiento_plan'])
               ? htmlspecialchars($cliente['vencimiento_plan'])
               : '—' ?>
        </td>
        <td>
          <?php if ($cliente['estado'] === 'activo'): ?>
            <span class="badge bg-success">Activo</span>
          <?php else: ?>
            <span class="badge bg-danger">Inactivo</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- ✅ ORDEN CORRECTO DE SCRIPTS -->
<!-- 1. jQuery PRIMERO -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- 2. Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- 3. DataTables -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- 4. SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- 5. TU SCRIPT (debe ir AL FINAL) -->
<script>
$(document).ready(function() {
  console.log('🔍 Verificación inicial:');
  console.log('jQuery:', typeof jQuery !== 'undefined');
  console.log('Bootstrap:', typeof bootstrap !== 'undefined');
  console.log('Swal:', typeof Swal !== 'undefined');

  // 1) Inicializa DataTable
  var table = $('#clients-table').DataTable({
    pageLength: 50,
    language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
  });

  // 2) Función que cuenta cuántos "Inactivo" hay
  function updateInactiveCount() {
    var allStatus = table
      .column(6, { search: 'none' })
      .data()
      .toArray();
    var count = allStatus.filter(function(v){
      return v.indexOf('Inactivo') !== -1;
    }).length;
    $('#inactiveCount').text(count);
  }

  // Ejecutar al inicio
  updateInactiveCount();

  // 3) Al cambiar el switch
  $('#toggleInactiveSwitch').on('change', function() {
    if (this.checked) {
      table.column(6).search('^Activo$', true, false).draw();
    } else {
      table.column(6).search('', false, true).draw();
    }
  });

  // 4) Click en fila → detalle
  $('#clients-table tbody').on('click', 'tr', function() {
    var id = $(this).data('id');
    if (id) {
      window.location.href = 'detail.php?id=' + encodeURIComponent(id);
    }
  });

  // 5) SweetAlerts de sesión
  <?php if(isset($_SESSION['success'])): ?>
    Swal.fire({ 
      icon: 'success', 
      title: 'Éxito', 
      text: '<?= addslashes($_SESSION['success']) ?>' 
    });
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if(isset($_SESSION['error'])): ?>
    Swal.fire({ 
      icon: 'error', 
      title: 'Error', 
      text: '<?= addslashes($_SESSION['error']) ?>' 
    });
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
});
</script>

</body>
</html>





