<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Logs del Sistema';
include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';

/* ======================= CONEXIÓN ======================= */
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['data'=>[], 'error'=>'Error conexión: '.$e->getMessage()]);
        exit;
    }
    die("Error en la conexión: " . $e->getMessage());
}

/* ======================= ENDPOINTS AJAX ======================= */
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $stmt = $pdo->query("SELECT id, fecha, hora, usuario_nombre, accion, modulo, ip FROM system_logs ORDER BY id DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['data'=>$data], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        echo json_encode(['data'=>[], 'error'=>$e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM system_logs WHERE id = :id LIMIT 1");
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status'=>'error','message'=>'Registro no encontrado']);
        }
    } catch(PDOException $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Logs del Sistema</title>
<?php include('../inc/header.php'); ?>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
	<h1>Logs del Sistema</h1>
	
	<button class="btn btn-success float-end" id="btnAddPlan"><i class="fa fa-plus"></i> Agregar Nuevo Plan</button>
	</div>
	</div>

<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
  <table id="logs-table" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Hora</th>
        <th>Usuario</th>
        <th>Acción</th>
        <th>Módulo</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDetalleLabel">Detalle del Log</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <strong>Usuario:</strong> <span id="log_usuario"></span><br>
          <strong>Acción:</strong> <span id="log_accion"></span><br>
          <strong>Módulo:</strong> <span id="log_modulo"></span><br>
          <strong>Fecha:</strong> <span id="log_fecha"></span> <span id="log_hora"></span><br>
          <strong>IP:</strong> <span id="log_ip"></span><br>
          <strong>Navegador:</strong> <span id="log_user_agent"></span>
        </div>
        <hr>
        <h6>Descripción detallada</h6>
        <div id="descripcion_contenido"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- jQuery, Bootstrap y DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function(){
  /* ========== DataTable ========== */
  var table = $('#logs-table').DataTable({
    ajax: "logs.php?action=fetch", // ✅ ruta correcta
    columns: [
      { data: "id" },
      { data: "fecha" },
      { data: "hora" },
      { data: "usuario_nombre" },
      { data: "accion" },
      { data: "modulo" },
      { data: "ip" }
    ],
    order: [[0, "desc"]],
    pageLength: 50,
    language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
  });

  /* ========== Modal detalle ========== */
  $('#logs-table tbody').on('click', 'tr', function(){
    var row = table.row(this).data();
    if (!row) return;
    $.getJSON('logs.php', { action:'get', id: row.id }, function(res){
      if(res.status === 'success'){
        const d = res.data;
        $('#log_usuario').text(d.usuario_nombre || 'Desconocido');
        $('#log_accion').text(d.accion);
        $('#log_modulo').text(d.modulo || 'General');
        $('#log_fecha').text(d.fecha);
        $('#log_hora').text(d.hora);
        $('#log_ip').text(d.ip);
        $('#log_user_agent').text(d.user_agent || 'N/A');

        let html = '';
        try {
          const json = JSON.parse(d.descripcion);
          html += '<table class="table table-sm table-bordered">';
          html += '<thead><tr><th>Campo</th><th>Valor</th></tr></thead><tbody>';
          for (const [k, v] of Object.entries(json)) {
            let val = typeof v === 'object' ? JSON.stringify(v) : v;
            html += `<tr><td>${k}</td><td>${val}</td></tr>`;
          }
          html += '</tbody></table>';
        } catch(e){
          html = `<pre>${d.descripcion}</pre>`;
        }
        $('#descripcion_contenido').html(html);
        $('#modalDetalle').modal('show');
      } else {
        Swal.fire('Error', res.message || 'No se pudo obtener el detalle', 'error');
      }
    });
  });
});
</script>
</body>
</html>

