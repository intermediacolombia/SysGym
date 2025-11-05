<?php
require_once __DIR__ . '/../inc/config.php';
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';

if (!$token) die('<h3>Token no proporcionado</h3>');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar token válido
    $stmt = $pdo->prepare("SELECT tc.*, c.nombres, c.apellidos, c.id AS cliente_id 
                           FROM tokens_consent tc
                           JOIN clientes c ON c.id = tc.cliente_id
                           WHERE tc.token = :token AND tc.usado = 0
                           AND tc.fecha_expiracion > NOW()
                           LIMIT 1");
    $stmt->execute([':token' => $token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        die('<h3>El enlace ha expirado o no es válido.</h3>');
    }

    // Verificar si ya firmó consentimiento
    $stmtForm = $pdo->prepare("SELECT id FROM formularios WHERE cliente_id = :id LIMIT 1");
    $stmtForm->execute([':id' => $tokenData['cliente_id']]);
    $form = $stmtForm->fetch();

    if ($form) {
        echo '<h3>Ya existe un consentimiento firmado para este cliente.</h3>';
        exit;
    }

} catch (Exception $e) {
    die('<h3>Error al procesar el token: ' . $e->getMessage() . '</h3>');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consentimiento Informado - SysGym</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
  background-color: #f5f5f5;
  font-family: 'Segoe UI', sans-serif;
}
.card {
  border: none;
  border-radius: 10px;
}
canvas {
  border: 1px solid #ccc;
  border-radius: 5px;
  cursor: crosshair;
  background-color: white;
}
.modal-header {
  background-color: <?= SYSTEM_COLOR_PRIMARY; ?>;
  color: white;
}
.btn-primary-custom {
  background-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
  border-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
  color: white !important;
}
.btn-primary-custom:hover {
  opacity: 0.9;
  background-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
  border-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
}
.btn-outline-primary-custom {
  color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
  border-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
}
.btn-outline-primary-custom:hover {
  background-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
  border-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
  color: white !important;
}
.text-primary-custom {
  color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
}
</style>
</head>
<body class="py-5">
<div class="container">
  <div class="card shadow mx-auto" style="max-width:600px;">
    <div class="card-body">
      <h4 class="text-center mb-4 text-primary-custom">Consentimiento Informado</h4>
      <p>Hola <strong><?= htmlspecialchars($tokenData['nombres'] . ' ' . $tokenData['apellidos']) ?></strong>,</p>
      <p>Por favor lee atentamente el siguiente consentimiento y firma para confirmar tu aceptación.</p>
      <div class="border p-3 mb-3 bg-light rounded" style="max-height:250px; overflow-y:auto;">
        <?= defined('CONSENT') ? CONSENT : '<p>No se encontró el texto del consentimiento.</p>'; ?>
      </div>
      <form id="consentForm" method="POST" action="submit.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="hidden" name="firma_digital_cliente" id="firma_digital_cliente">
        
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="acepto" id="acepto" required>
          <label class="form-check-label" for="acepto">
            He leído y acepto los términos del consentimiento informado.
          </label>
        </div>
        <div class="mb-3 text-center">
          <button type="button" class="btn btn-outline-primary-custom" data-bs-toggle="modal" data-bs-target="#modalFirma">
            <i class="fa fa-pen"></i> Firmar Digitalmente
          </button>
        </div>
        <div class="text-center">
          <img id="firma_preview" src="" alt="Firma del cliente" class="img-fluid mb-3" style="display:none; max-height:120px;">
        </div>
        <button type="submit" class="btn btn-primary-custom w-100" disabled>Enviar Consentimiento</button>
      </form>
    </div>
  </div>
</div>
<!-- Modal Firma -->
<div class="modal fade" id="modalFirma" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Firma Digital del Clientesss</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <canvas id="canvasFirma" width="350" height="150"></canvas>
        <div class="mt-3">
          <button type="button" id="clearFirma" class="btn btn-danger btn-sm">Borrar</button>
          <button type="button" id="saveFirma" class="btn btn-primary-custom btn-sm">Guardar Firma</button>
        </div>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// === Lógica de firma en canvas (CORREGIDA) ===
const canvas = document.getElementById('canvasFirma');
const ctx = canvas.getContext('2d');
let drawing = false;
let lastX = 0;
let lastY = 0;

// Configuración del estilo de dibujo
ctx.lineWidth = 2;
ctx.lineCap = 'round';
ctx.strokeStyle = '#000';

// Función para obtener coordenadas correctas
function getMousePos(e) {
  const rect = canvas.getBoundingClientRect();
  return {
    x: e.clientX - rect.left,
    y: e.clientY - rect.top
  };
}

function getTouchPos(e) {
  const rect = canvas.getBoundingClientRect();
  return {
    x: e.touches[0].clientX - rect.left,
    y: e.touches[0].clientY - rect.top
  };
}

// Eventos para mouse
canvas.addEventListener('mousedown', (e) => {
  drawing = true;
  const pos = getMousePos(e);
  lastX = pos.x;
  lastY = pos.y;
});

canvas.addEventListener('mousemove', (e) => {
  if (!drawing) return;
  const pos = getMousePos(e);
  
  ctx.beginPath();
  ctx.moveTo(lastX, lastY);
  ctx.lineTo(pos.x, pos.y);
  ctx.stroke();
  
  lastX = pos.x;
  lastY = pos.y;
});

canvas.addEventListener('mouseup', () => {
  drawing = false;
});

canvas.addEventListener('mouseout', () => {
  drawing = false;
});

// Eventos para touch (móviles/tablets)
canvas.addEventListener('touchstart', (e) => {
  e.preventDefault();
  drawing = true;
  const pos = getTouchPos(e);
  lastX = pos.x;
  lastY = pos.y;
});

canvas.addEventListener('touchmove', (e) => {
  e.preventDefault();
  if (!drawing) return;
  const pos = getTouchPos(e);
  
  ctx.beginPath();
  ctx.moveTo(lastX, lastY);
  ctx.lineTo(pos.x, pos.y);
  ctx.stroke();
  
  lastX = pos.x;
  lastY = pos.y;
});

canvas.addEventListener('touchend', (e) => {
  e.preventDefault();
  drawing = false;
});

// Limpiar firma
$('#clearFirma').on('click', function(){
  ctx.clearRect(0, 0, canvas.width, canvas.height);
});

// Guardar firma
$('#saveFirma').on('click', function(){
  const dataURL = canvas.toDataURL('image/png');
  if (dataURL.length < 3000) {
    Swal.fire('Atención', 'Por favor realiza tu firma antes de guardar.', 'warning');
    return;
  }
  $('#firma_digital_cliente').val(dataURL);
  $('#firma_preview').attr('src', dataURL).show();
  $('#modalFirma').modal('hide');
  $('#consentForm button[type="submit"]').prop('disabled', false);
});

// Confirmación visual
$('#consentForm').on('submit', function(e){
  if (!$('#firma_digital_cliente').val()) {
    e.preventDefault();
    Swal.fire('Falta la firma', 'Debes firmar antes de enviar el consentimiento.', 'warning');
  }
});
</script>
</body>
</html>

