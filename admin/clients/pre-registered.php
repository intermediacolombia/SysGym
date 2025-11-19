<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Clientes Pre-inscritos';
include('../login/restriction.php');
session_start();
require_once __DIR__ . '/../../inc/config.php';

/* ======================================================
   1.  BLOQUE PARA ELIMINAR – debe ir ANTES de imprimir HTML
   ======================================================*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    header('Content-Type: application/json');
    try {
        db();

        $stmt = db()->prepare("DELETE FROM clientes_preinscritos WHERE id = ?");
        $stmt->execute([intval($_POST['eliminar_id'])]);

        echo json_encode(['status'=>'success','message'=>'El cliente ha sido eliminado.']);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error','message'=>'Error al eliminar: '.$e->getMessage()]);
    }
    exit;                // ← MUY IMPORTANTE
}

/* ======================================================
   2.  A partir de aquí se genera la página normal (GET)
   ======================================================*/
try {
    db();

    $stmt = db()->query("SELECT * FROM clientes_preinscritos ORDER BY created_at DESC");
    $preinscritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Clientes Preinscritos</title>
  <?php include('../inc/header.php'); ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Listado de Clientes Preinscritos</h1>
  </div>
</div>
<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
  

  <table id="preinscritos-table" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Identificación</th>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>Teléfono</th>
        <th>Email</th>
        <th>Fecha Registro</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($preinscritos as $cli): ?>
        <tr data-cliente='<?= json_encode($cli, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
          <td><?= htmlspecialchars($cli['identificacion']) ?></td>
          <td><?= htmlspecialchars($cli['nombres']) ?></td>
          <td><?= htmlspecialchars($cli['apellidos']) ?></td>
          <td>+<?= htmlspecialchars($cli['dialCode']) ?> <?= htmlspecialchars($cli['telefono']) ?></td>
          <td><?= htmlspecialchars($cli['email']) ?></td>
          <td><?= htmlspecialchars(date('Y-m-d', strtotime($cli['created_at']))) ?></td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="detalleLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="detalleLabel">Detalles del Preinscrito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="detalleCliente"></div>
      </div>
      <div class="modal-footer">
        <button id="btnEliminar" class="btn btn-danger">Eliminar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
  const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
  let currentCliente = null;

  $('#preinscritos-table').DataTable({
    language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
  });

  $('#preinscritos-table tbody').on('click', 'tr', function() {
    currentCliente = $(this).data('cliente');
    if (currentCliente) {
      const detalleHtml = `
        <div class="row">
          <div class="col-md-6"><strong>Identificación:</strong> ${currentCliente.identificacion}</div>
          <div class="col-md-6"><strong>Nombre:</strong> ${currentCliente.nombres} ${currentCliente.apellidos}</div>
          <div class="col-md-6"><strong>Teléfono:</strong> +${currentCliente.dialCode} ${currentCliente.telefono}</div>
          <div class="col-md-6"><strong>Email:</strong> ${currentCliente.email}</div>
          <div class="col-md-6"><strong>Género:</strong> ${currentCliente.genero}</div>
          <div class="col-md-6"><strong>Fecha Nacimiento:</strong> ${currentCliente.fecha_nacimiento}</div>
          <div class="col-md-12"><strong>Fecha de Registro:</strong> ${currentCliente.created_at}</div>
        </div>`;
      $('#detalleCliente').html(detalleHtml);
      modal.show();
    }
  });

  $('#btnEliminar').on('click', function() {
    if (!currentCliente || !currentCliente.id) return;

    Swal.fire({
      title: '¿Eliminar preinscrito?',
      text: "Esta acción no se puede deshacer.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('', { eliminar_id: currentCliente.id }, function(resp) {
          if (resp.status === 'success') {
            Swal.fire('Eliminado', resp.message, 'success').then(() => location.reload());
          } else {
            Swal.fire('Error', resp.message, 'error');
          }
        }, 'json');
      }
    });
  });
});
</script>

</body>
</html>

<?php
// Manejo de eliminación desde POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    $id = intval($_POST['eliminar_id']);
    try {
        $stmtDel = db()->prepare("DELETE FROM clientes_preinscritos WHERE id = ?");
        $stmtDel->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'El cliente ha sido eliminado.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}
?>
