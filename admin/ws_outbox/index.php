<?php require_once __DIR__ . '/../login/session.php'; ?>

<?php
$permisopage = 'Ver Mensajes Pendientes';
include('../login/restriction.php');
?>

<?php
// admin/ws_outbox/index.php
require_once __DIR__ . '/../../inc/config.php';
ws_outbox_migrate();


/* ======================= ENDPOINTS AJAX ======================= */
if (isset($_GET['action']) && $_GET['action'] === 'count') {
    header('Content-Type: application/json');
    $counts = db()->query("SELECT status, COUNT(*) c FROM ws_outbox GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode([
        'pending' => (int)($counts['pending'] ?? 0),
        'invalid' => (int)($counts['invalid'] ?? 0),
        'total'   => (int)($counts['pending'] ?? 0) + (int)($counts['invalid'] ?? 0)
    ]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $status = ($_GET['status'] ?? 'pending') === 'invalid' ? 'invalid' : 'pending';
    $stmt = db()->prepare("SELECT id, phonenumber, text, url, created_at, status, last_error FROM ws_outbox WHERE status = :s ORDER BY id DESC");
    $stmt->execute([':s' => $status]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $counts = db()->query("SELECT status, COUNT(*) c FROM ws_outbox GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode([
        'data' => $data,
        'counts' => ['pending' => (int)($counts['pending'] ?? 0), 'invalid' => (int)($counts['invalid'] ?? 0)]
    ]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'requeue') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) $ids = [$ids];
    $ids = array_filter(array_map('intval', $ids));
    if (!$ids) { echo json_encode(['status'=>'error','message'=>'Sin IDs']); exit; }
    $in = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = db()->prepare("UPDATE ws_outbox SET status='pending', last_error=NULL WHERE id IN ($in)");
    $stmt->execute($ids);
    echo json_encode(['status'=>'success','updated'=>$stmt->rowCount()]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = db()->prepare("SELECT id, phonenumber, text, url, created_at, status, last_error FROM ws_outbox WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Mensaje no encontrado']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare("DELETE FROM ws_outbox WHERE id = :id");
    if ($stmt->execute([':id' => $id])) {
        echo json_encode(['status' => 'success', 'message' => 'Mensaje cancelado (eliminado)']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'resend') {
    $id = (int)($_POST['id'] ?? 0);

    // Traer el mensaje
    $stmt = db()->prepare("SELECT id, phonenumber, text, url FROM ws_outbox WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Mensaje no encontrado']); exit;
    }

    // Preparar payload
    $payload = [
        'phonenumber' => $row['phonenumber'],
        'text'        => $row['text'],
    ];
    if (!empty($row['url'])) $payload['url'] = $row['url'];

    $urlEndpoint = rtrim(WA_API_URL, '/') . '/send';
    $apiKey      = $api_ws;

    // cURL
    $ch = curl_init($urlEndpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_SSL_VERIFYPEER => true,
        // CURLOPT_CAINFO      => '/etc/ssl/certs/ca-certificates.crt',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
    $error    = curl_error($ch) ?: null;
    curl_close($ch);

    $successFlag = false;
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        $successFlag = ws_sent_ok($decoded);
    }

    if ($successFlag) {
        $del = db()->prepare("DELETE FROM ws_outbox WHERE id = :id");
        $del->execute([':id' => $id]);
        echo json_encode([
            'status'   => 'success',
            'message'  => 'Mensaje reenviado y eliminado de la cola',
            'response' => $response,
            'httpCode' => $httpCode
        ]);
    } else {
        $dec = json_decode((string)$response, true);
        if (is_array($dec) && array_key_exists('success', $dec) && $dec['success'] === false) {
            $errMsg = $dec['error'] ?? 'success:false';
            db()->prepare("UPDATE ws_outbox SET status='invalid', last_error=:err WHERE id=:id")
                ->execute([':err' => mb_substr($errMsg, 0, 255), ':id' => $id]);
        }
        echo json_encode([
            'status'   => 'error',
            'message'  => $error ? $error : "HTTP $httpCode",
            'response' => $response,
            'httpCode' => $httpCode
        ]);
    }
    exit;
}

/* ============ BULK DELETE ============ */
if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(['status'=>'error', 'message'=>'Sin IDs']); exit;
    }
    $ids = array_map('intval', $ids);

    $in  = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = db()->prepare("DELETE FROM ws_outbox WHERE id IN ($in)");
    $ok = $stmt->execute($ids);
    $deleted = $stmt->rowCount();

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'deleted' => $deleted,
        'failed'  => count($ids) - $deleted
    ]);
    exit;
}

/* ============ BULK RESEND ============ */
if (isset($_POST['action']) && $_POST['action'] === 'bulk_resend') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(['status'=>'error', 'message'=>'Sin IDs']); exit;
    }
    $ids = array_map('intval', $ids);

    $urlEndpoint = rtrim(WA_API_URL, '/') . '/send';
    $apiKey      = $api_ws;

    $ok = 0; $fail = 0;
    $results = [];

    $fetchStmt = db()->prepare("SELECT id, phonenumber, text, url FROM ws_outbox WHERE id = :id LIMIT 1");
    $delStmt   = db()->prepare("DELETE FROM ws_outbox WHERE id = :id");
    $invStmt   = db()->prepare("UPDATE ws_outbox SET status='invalid', last_error=:err WHERE id=:id");

    foreach ($ids as $id) {
        $fetchStmt->execute([':id' => $id]);
        $row = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $fail++;
            $results[] = ['id'=>$id, 'status'=>'not_found'];
            continue;
        }

        $payload = [
            'phonenumber' => $row['phonenumber'],
            'text'        => $row['text']
        ];
        if (!empty($row['url'])) $payload['url'] = $row['url'];

        $ch = curl_init($urlEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        $error    = curl_error($ch) ?: null;
        curl_close($ch);

        $successFlag = false;
        if (!$error && $httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            $successFlag = ws_sent_ok($decoded);
        }

        if ($successFlag) {
            $delStmt->execute([':id' => $id]);
            $ok++;
            $results[] = ['id'=>$id, 'status'=>'sent', 'httpCode'=>$httpCode];
        } else {
            $dec = json_decode((string)$response, true);
            if (is_array($dec) && array_key_exists('success', $dec) && $dec['success'] === false) {
                $invStmt->execute([':err' => mb_substr($dec['error'] ?? 'success:false', 0, 255), ':id' => $id]);
            }
            $fail++;
            $results[] = ['id'=>$id, 'status'=>'fail', 'httpCode'=>$httpCode, 'error'=>$error, 'response'=>$response];
        }
    }

    echo json_encode([
        'status'  => 'success',
        'ok'      => $ok,
        'fail'    => $fail,
        'results' => $results
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mensajes Pendientes de Envío</title>
  <?php include('../inc/header.php'); ?>
	<style>
/* Efecto tipo collapse, evitando que se vea “un filo” al cerrar */
.collapse-custom{
  overflow: hidden;
  height: 0;                 /* cerrado por defecto */
  padding-top: 0 !important; /* quitar padding vertical al cerrar */
  padding-bottom: 0 !important;
  margin-bottom: 0;          /* evitar espacio bajo la alerta cerrada */
  border-width: 0;           /* ocultar borde cuando está cerrada */
  opacity: 0;                /* desvanecer */
  transition:
    height 240ms ease,
    padding 240ms ease,
    margin 240ms ease,
    opacity 180ms ease;
}
.collapse-custom.show{
  /* Al abrir, restauramos padding/borde/opacity;
     el height lo gestiona JS con scrollHeight */
  padding-top: .75rem !important;
  padding-bottom: .75rem !important;
  margin-bottom: 1rem;
  border-width: 1px;
  opacity: 1;
}
@media (prefers-reduced-motion: reduce){
  .collapse-custom{ transition: none; }
}
</style>
</head>
<body>
<div class="container" style="padding:0; background:rgba(0,0,0,0.00)">
  <div class="portada">
    
    <div class="d-flex justify-content-between align-items-center px-3 pb-2">
      <div><h1>Mensajes Pendientes de Envío</h1></div>
      <div id="bulk-actions" class="d-none">
        <button id="btnBulkRequeue" class="btn btn-warning me-2 d-none">
          <i class="fa fa-refresh"></i> Reencolar
        </button>
        <button id="btnBulkResend" class="btn btn-primary me-2">
          <i class="fa fa-send"></i> Reintentar envíos
        </button>
        <button id="btnBulkDelete" class="btn btn-danger">
          <i class="fa fa-trash-o"></i> Cancelar envíos
        </button>
      </div>
    </div>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
<!-- Botón -->
<div class="mb-3">
  <button id="btnInfo" class="btn btn-outline-info btn-sm" type="button">
    <i class="fa fa-info-circle"></i> Acerca de estos mensajes
  </button>
</div>

<!-- Caja informativa (estilo alert) -->
<div id="infoPendientes" class="alert alert-info collapse-custom" aria-hidden="true">
  <h5><i class="fa fa-info-circle"></i> Información importante</h5>
  <ul class="mb-0">
    <li>Estos mensajes están <strong>pendientes de envío</strong>.</li>
    <li>El sistema intentará enviarlos <strong>automáticamente</strong> de forma periódica.</li>
    <li>Si alguno falla, puedes intentar reenviarlo de forma individual o mediante <strong>acciones masivas</strong>.</li>
    <li>Cuando el envío sea exitoso, el mensaje se <strong>eliminará automáticamente</strong> de esta lista.</li>
  </ul>
</div>




  <style>
    .tab-count-badge{display:inline-block;background:#9e9e9e;color:#fff;border-radius:10px;padding:1px 8px;font-size:12px;font-weight:700;min-width:20px;text-align:center}
    .tab-count-badge.alert{background:#e53935;color:#fff;animation:tabBadgePulse 1s ease-in-out infinite;padding:1px 8px}
    @keyframes tabBadgePulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(229,57,53,.7)}50%{transform:scale(1.15);box-shadow:0 0 0 6px rgba(229,57,53,0)}}
  </style>
  <ul class="nav nav-tabs mb-3" id="ws-tabs">
    <li class="nav-item"><a class="nav-link active" data-status="pending" href="#">Pendientes <span class="tab-count-badge ms-1" id="tab-count-pending">0</span></a></li>
    <li class="nav-item"><a class="nav-link" data-status="invalid" href="#">Números inválidos <span class="tab-count-badge ms-1" id="tab-count-invalid">0</span></a></li>
  </ul>

  <table id="outbox-table" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th style="width:36px;">
          <input type="checkbox" id="select-all">
        </th>
        <th>Teléfono</th>
        <th>Mensaje</th>
        <th>Adjunto</th>
        <th>Error</th>
        <th>Creado</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formDetalle">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDetalleLabel">Detalle del Mensaje Pendiente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="msg_id">
          <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="msg_phone" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Adjunto (si aplica)</label>
            <div class="form-text"><a href="#" target="_blank" id="msg_url_link">Abrir</a></div>
          </div>
          <div class="mb-3">
            <label class="form-label">Mensaje</label>
            <textarea class="form-control" id="msg_text" rows="6" readonly></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Creado</label>
            <input type="text" class="form-control" id="msg_created" readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="btnCancel" class="btn btn-danger">
            <i class="fa fa-trash-o"></i> Cancelar envío
          </button>
          <button type="button" id="btnResend" class="btn btn-primary">
            <i class="fa fa-send"></i> Reintentar reenvío
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(function(){
  /* ========= DataTable ========= */
  var currentStatus = 'pending';
  var table = $('#outbox-table').DataTable({
    ajax: function(d, cb){
      $.getJSON('index.php?action=fetch&status=' + currentStatus, function(res){
        if (res && res.counts) {
          $('#tab-count-pending').text(res.counts.pending).toggleClass('alert', res.counts.pending > 0);
          $('#tab-count-invalid').text(res.counts.invalid).toggleClass('alert', res.counts.invalid > 0);
        }
        cb(res);
      });
    },
    columns: [
      {
      data: null,
      orderable: false,
      render: function(data, type, row) {
        return '<input type="checkbox" class="row-select" value="' + row.id + '">';
      }
      },

      { data: "phonenumber" },
      { data: "text",
        render: function(data,type,row){
          if(type === 'display'){
            const max = 70;
            const s = (data || '').toString();
            return s.length > max ? s.substring(0,max) + '…' : s;
          }
          return data;
        }
      },
      { data: "url",
        render: function(data,type,row){
          if (!data) return '';
          if (type === 'display') {
            return '<a href="'+data+'" target="_blank">Abrir</a>';
          }
          return data;
        }
      },
      { data: "last_error",
        render: function(data,type,row){
          if (!data) return '';
          if (type === 'display') {
            const s = String(data);
            return '<span class="text-danger" title="'+s.replace(/"/g,'&quot;')+'">'+(s.length>60?s.substring(0,60)+'…':s)+'</span>';
          }
          return data;
        }
      },
      {
        data: "created_at",
        render: function(data, type, row) {
          if (!data) return '';
          let date = new Date(data + ' UTC'); // si la BD está en UTC
          return date.toLocaleString('es-CO', { 
            timeZone: 'America/Bogota',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
          });
        }
      }
    ],
    pageLength: 50,
	  order: [],
    language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
  });

  /* Evita que el click en el checkbox abra el modal */
  $('#outbox-table tbody').on('click', 'input.row-select', function(e){
    e.stopPropagation();
  });

  /* ========= Abrir modal al hacer click en la fila ========= */
  $('#outbox-table tbody').on('click', 'tr', function(e){
    var target = $(e.target);
    if (target.is('input.row-select')) return; // ya lo manejamos arriba

    var row = table.row(this).data();
    if(!row) return;

    $.getJSON('index.php', {action:'get', id: row.id}, function(res){
      if(res.status === 'success'){
        const d = res.data;
        $('#msg_id').val(d.id);
        $('#msg_phone').val(d.phonenumber);
        $('#msg_text').val(d.text);
        if (d.created_at) {
          let date = new Date(d.created_at + ' UTC');
          $('#msg_created').val(date.toLocaleString('es-CO', { 
            timeZone: 'America/Bogota',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
          }));
        } else {
          $('#msg_created').val('');
        }
        $('#msg_url_link').attr('href', d.url || '#').text(d.url ? 'Abrir' : 'Sin URL');
        $('#modalDetalle').modal('show');
      } else {
        Swal.fire('Error', res.message || 'No se pudo cargar el detalle', 'error');
      }
    });
  });

  /* ========= Cancelar envío (individual) ========= */
  $('#btnCancel').on('click', function(){
    const id = $('#msg_id').val();
    if(!id) return;

    Swal.fire({
      title: '¿Cancelar (eliminar) este envío?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'No'
    }).then(function(result){
      if(result.isConfirmed){
        $.ajax({
          url: 'index.php',
          method: 'POST',
          dataType: 'json',
          data: { action:'delete', id:id },
          success: function(res){
            if(res.status === 'success'){
              Swal.fire('Listo', res.message, 'success');
              $('#modalDetalle').modal('hide');
              table.ajax.reload(null,false);
            } else {
              Swal.fire('Error', res.message || 'No se pudo eliminar', 'error');
            }
          }
        });
      }
    });
  });

  /* ========= Reintentar reenvío (individual) ========= */
  $('#btnResend').on('click', function(){
    const id = $('#msg_id').val();
    if(!id) return;

    $('#btnResend').prop('disabled', true);
    $.ajax({
      url: 'index.php',
      method: 'POST',
      dataType: 'json',
      data: { action:'resend', id:id },
      success: function(res){
        $('#btnResend').prop('disabled', false);
        if(res.status === 'success'){
          Swal.fire('Enviado', 'Mensaje reenviado y eliminado de la cola', 'success');
          $('#modalDetalle').modal('hide');
          table.ajax.reload(null,false);
        } else {
          const msg = (res.message ? res.message + '. ' : '') + (res.httpCode ? 'HTTP ' + res.httpCode : '');
          Swal.fire('No enviado', msg, 'warning');
        }
      },
      error: function(){
        $('#btnResend').prop('disabled', false);
        Swal.fire('Error', 'No se pudo reintentar el envío', 'error');
      }
    });
  });

  /* ================= Selección múltiple y acciones masivas ================= */

  function getSelectedIds() {
    var ids = [];
    $('#outbox-table tbody input.row-select:checked').each(function(){
      ids.push($(this).val());
    });
    return ids;
  }

  function toggleBulkActions(){
    const count = getSelectedIds().length;
    if (count > 0) {
      $('#bulk-actions').removeClass('d-none');
      $('#btnBulkResend, #btnBulkDelete').prop('disabled', false);
    } else {
      $('#bulk-actions').addClass('d-none');
      $('#btnBulkResend, #btnBulkDelete').prop('disabled', true);
    }
  }

  // Seleccionar todo
  $('#select-all').on('change', function(){
    const checked = $(this).is(':checked');
    $('#outbox-table tbody input.row-select').prop('checked', checked);
    toggleBulkActions();
  });

  // Cambios en checks individuales
  $('#outbox-table tbody').on('change', 'input.row-select', function(){
    if (!$(this).is(':checked')) $('#select-all').prop('checked', false);
    const total = $('#outbox-table tbody input.row-select').length;
    const marcados = $('#outbox-table tbody input.row-select:checked').length;
    if (total > 0 && total === marcados) $('#select-all').prop('checked', true);
    toggleBulkActions();
  });

  // Al recargar la tabla
  table.on('draw', function(){
    $('#select-all').prop('checked', false);
    toggleBulkActions();
  });

  // Acciones masivas: eliminar
  $('#btnBulkDelete').on('click', function(){
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    Swal.fire({
      title: '¿Cancelar (eliminar) los envíos seleccionados?',
      text: ids.length + ' mensaje(s) serán eliminados',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'No'
    }).then(function(result){
      if (result.isConfirmed){
        $.ajax({
          url: 'index.php',
          method: 'POST',
          dataType: 'json',
          data: { action: 'bulk_delete', ids: ids },
          success: function(res){
            if (res.status === 'success'){
              Swal.fire('Listo', 'Eliminados: ' + res.deleted + ', Fallidos: ' + res.failed, 'success');
              table.ajax.reload(null,false);
            } else {
              Swal.fire('Error', res.message || 'No se completó la eliminación', 'error');
            }
          },
          error: function(){
            Swal.fire('Error', 'No se pudo ejecutar la acción', 'error');
          }
        });
      }
    });
  });

  // Acciones masivas: reintentar
  $('#btnBulkResend').on('click', function(){
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    $('#btnBulkResend, #btnBulkDelete').prop('disabled', true);

    $.ajax({
      url: 'index.php',
      method: 'POST',
      dataType: 'json',
      data: { action: 'bulk_resend', ids: ids },
      success: function(res){
        $('#btnBulkResend, #btnBulkDelete').prop('disabled', false);
        if (res.status === 'success'){
          const ok = res.ok || 0;
          const fail = res.fail || 0;
          Swal.fire('Resultado', `Reenviados: ${ok}, Fallidos: ${fail}`, ok>0 ? 'success' : 'warning');
          table.ajax.reload(null,false);
        } else {
          Swal.fire('Error', res.message || 'No se completó el reenvío', 'error');
        }
      },
      error: function(){
        $('#btnBulkResend, #btnBulkDelete').prop('disabled', false);
        Swal.fire('Error', 'No se pudo ejecutar la acción', 'error');
      }
    });
  });

  /* ========= Pestañas pending/invalid ========= */
  $('#ws-tabs .nav-link').on('click', function(e){
    e.preventDefault();
    $('#ws-tabs .nav-link').removeClass('active');
    $(this).addClass('active');
    currentStatus = $(this).data('status');
    // Mostrar/ocultar botones segun pestaña
    if (currentStatus === 'invalid') {
      $('#btnBulkRequeue').removeClass('d-none');
      $('#btnBulkResend').addClass('d-none');
    } else {
      $('#btnBulkRequeue').addClass('d-none');
      $('#btnBulkResend').removeClass('d-none');
    }
    table.ajax.reload();
  });

  /* ========= Reencolar (invalid -> pending) ========= */
  $('#btnBulkRequeue').on('click', function(){
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    Swal.fire({
      title: '¿Mover a Pendientes?',
      text: ids.length + ' mensaje(s) volveran a reintentarse automaticamente',
      icon: 'question', showCancelButton: true,
      confirmButtonText: 'Si, reencolar', cancelButtonText: 'No'
    }).then(function(r){
      if (!r.isConfirmed) return;
      $.ajax({
        url:'index.php', method:'POST', dataType:'json',
        data:{action:'requeue', ids:ids},
        success:function(res){
          if (res.status==='success'){
            Swal.fire('Listo', 'Reencolados: '+res.updated, 'success');
            table.ajax.reload(null,false);
          } else {
            Swal.fire('Error', res.message||'No se completo', 'error');
          }
        }
      });
    });
  });

});
</script>

<!-- Script para abrir/cerrar -->
<script>
(function () {
  const btn = document.getElementById('btnInfo');
  const box = document.getElementById('infoPendientes');
  let isOpen = false;

  function slideDown(el){
    // Preparar: aplicar .show para que recupere padding/borde/opacity
    el.classList.add('show');
    el.setAttribute('aria-hidden','false');

    // Medir altura destino
    el.style.height = 'auto';
    const target = el.scrollHeight + 'px';

    // Iniciar desde 0
    el.style.height = '0px';
    // Reflow para que el browser calcule el cambio
    // eslint-disable-next-line no-unused-expressions
    el.offsetHeight;

    // Animar a la altura real
    el.style.height = target;

    function onEnd(e){
      if (e.propertyName === 'height'){
        el.style.height = 'auto'; // limpiar inline
        el.removeEventListener('transitionend', onEnd);
      }
    }
    el.addEventListener('transitionend', onEnd);
  }

  function slideUp(el){
    el.setAttribute('aria-hidden','true');

    // Fijar altura actual antes de colapsar
    el.style.height = el.scrollHeight + 'px';
    // Reflow
    // eslint-disable-next-line no-unused-expressions
    el.offsetHeight;

    // Quitar .show para que quite padding/borde/opacity mientras colapsa
    el.classList.remove('show');
    // Animar a 0
    el.style.height = '0px';

    function onEnd(e){
      if (e.propertyName === 'height'){
        // Al terminar, queda completamente cerrado
        el.removeEventListener('transitionend', onEnd);
      }
    }
    el.addEventListener('transitionend', onEnd);
  }

  btn.addEventListener('click', function(){
    if (isOpen){
      slideUp(box);
      btn.innerHTML = '<i class="fa fa-info-circle"></i> Acerca de estos mensajes';
    } else {
      slideDown(box);
      btn.innerHTML = '<i class="fa fa-info-circle"></i> Ocultar información';
    }
    isOpen = !isOpen;
  });
})();
</script>

</body>
</html>

