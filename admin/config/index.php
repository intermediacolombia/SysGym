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
  <title>Editar Configuraciones</title>
  <?php include('../inc/header.php'); ?>
  <style>
      textarea.form-control {
          height: 220px;
          font-family: 'Roboto', sans-serif;
          font-size: 14px;
          border: 1px solid #ced4da;
          border-radius: 0.375rem;
          padding: 12px;
          background-color: #fff;
          box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
      }
      textarea.form-control:focus {
          border-color: #86b7fe;
          outline: 0;
          box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
      }
  </style>
</head>
<body>
<div class="container" style="padding: 0px;">
  <div class="portada">
    <h1>Editar Configuraciones del Sistema</h1>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container my-5">
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form id="configForm" action="save_config.php" method="POST">
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

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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
    $.ajax({
      url: $(this).attr("action"),
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? 'success' : 'error',
          title: res.success ? 'Éxito' : 'Error',
          text: res.message
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











