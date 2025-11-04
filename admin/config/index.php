<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Configurar Sistema';
include('../login/restriction.php');

try {
    // Conexión PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("SET NAMES 'utf8mb4'");

    // Cargar todas las configuraciones
    $stmt = $pdo->query("SELECT setting_name, value FROM system_settings WHERE enabled = 1");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_name']] = $row['value'];
    }
} catch (PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Configuraciones del Sistema</title>
  <?php include('../inc/header.php'); ?>
  <style>
      body {
          background-color: #f8f9fa;
      }
      .nav-tabs .nav-link.active {
          background-color: #E21F0C
          color: #fff !important;
          border: none;
      }
      .nav-tabs .nav-link {
          color: #495057;
          border: none;
          font-weight: 500;
      }
      .tab-content {
          background-color: #fff;
          border-radius: 0.5rem;
          box-shadow: 0 0 10px rgba(0,0,0,0.1);
          padding: 2rem;
          margin-top: 1rem;
      }
      textarea.form-control {
          height: 180px;
          resize: vertical;
      }
      label strong {
          color: #0d6efd;
      }
  </style>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
    <h1>Configuraciones del Sistema</h1>
  </div>
  </div>

  <?php include('../inc/menu.php'); ?>
<div class="container mt-4">
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form id="configForm" action="save_config.php" method="POST" enctype="multipart/form-data">

    <!-- ======== TABS ======== -->
    <ul class="nav nav-tabs" id="configTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema" type="button" role="tab">⚙️ Sistema</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#whatsapp" type="button" role="tab">💬 WhatsApp</button>
      </li>
    </ul>

    <!-- ======== CONTENIDO DE TABS ======== -->
    <div class="tab-content" id="configTabsContent">

      <!-- ===== TAB SISTEMA ===== -->
      <div class="tab-pane fade show active" id="sistema" role="tabpanel" aria-labelledby="sistema-tab">

        <div class="mb-4">
          <label class="form-label"><strong>Logo del Sistema</strong></label>
          <?php if (!empty($settings['system_logo'])): ?>
              <div class="mb-2">
                  <img src="../uploads/<?= htmlspecialchars($settings['system_logo']) ?>" alt="Logo actual" style="max-height: 80px;">
              </div>
          <?php endif; ?>
          <input type="file" class="form-control" name="system_logo" accept="image/*">
          <small class="text-muted">Formatos permitidos: PNG, JPG. Tamaño recomendado: 300x100 px.</small>
        </div>

        <div class="mb-4">
          <label class="form-label"><strong>Favicon</strong></label>
          <?php if (!empty($settings['system_favicon'])): ?>
              <div class="mb-2">
                  <img src="../uploads/<?= htmlspecialchars($settings['system_favicon']) ?>" alt="Favicon actual" style="max-height: 32px;">
              </div>
          <?php endif; ?>
          <input type="file" class="form-control" name="system_favicon" accept="image/x-icon,image/png">
          <small class="text-muted">Formato permitido: .ico o .png (32x32 px)</small>
        </div>

      </div>

      <!-- ===== TAB WHATSAPP ===== -->
      <div class="tab-pane fade" id="whatsapp" role="tabpanel" aria-labelledby="whatsapp-tab">

        <div class="mb-3">
          <label class="form-label"><strong>API WhatsApp</strong></label>
          <input type="text" class="form-control" name="wa_api" value="<?= htmlspecialchars($settings['wa_api'] ?? '') ?>">
        </div>

        <div class="row">
          <?php
          $campos = [
              'wa_consent' => 'Mensaje de consentimiento informado',
              'wa_client_pay' => 'Mensaje de pago mensualidad del cliente',
              'wa_client_pay_general' => 'Mensaje de otros pagos del cliente',
              'wa_hbd' => 'Mensaje cumpleaños para el cliente',
              'wa_notify_expired' => 'Mensaje vencimiento cliente',
              'wa_paymentReminder' => 'Mensaje recordatorio de pago',
              'wa_creditReminder' => 'Mensaje recordatorio de pagos de créditos',
              'wa_valoracion' => 'Mensaje de envío de valoración'
          ];
          foreach ($campos as $key => $label): ?>
              <div class="col-md-6 mb-3">
                  <label class="form-label"><strong><?= $label ?></strong></label>
                  <textarea class="form-control" name="<?= $key ?>"><?= htmlspecialchars($settings[$key] ?? '') ?></textarea>
              </div>
          <?php endforeach; ?>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
              <label class="form-label"><strong>Día de envío recordatorio pagos de créditos</strong></label>
              <select class="form-select" name="wa_creditReminder_day">
                  <?php
                  $dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
                  $diaSel = (int)($settings['wa_creditReminder_day'] ?? 4);
                  foreach ($dias as $num => $nombre) {
                      $sel = ($num === $diaSel) ? 'selected' : '';
                      echo "<option value=\"$num\" $sel>$nombre</option>";
                  }
                  ?>
              </select>
          </div>

          <div class="col-md-6 mb-3">
              <label class="form-label"><strong>Hora de envío recordatorio pagos de créditos</strong></label>
              <input type="time" class="form-control" name="wa_creditReminder_hour" value="<?= htmlspecialchars($settings['wa_creditReminder_hour'] ?? '') ?>">
          </div>
        </div>

      </div>
    </div>

    <!-- Botón de guardado -->
    <div class="text-end mt-4">
      <button type="submit" class="btn btn-primary btn-lg px-4">
        <i class="fa fa-save me-2"></i>Guardar Cambios
      </button>
    </div>
  </form>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- Librerías -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){
  $("#configForm").submit(function(e){
    e.preventDefault();

    var formData = new FormData(this);
    $.ajax({
      url: $(this).attr("action"),
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? 'success' : 'error',
          title: res.success ? 'Éxito' : 'Error',
          text: res.message
        }).then(() => {
          if(res.success) location.reload();
        });
      },
      error: function(){
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo guardar la configuración.'
        });
      }
    });
  });
});
</script>

</body>
</html>












