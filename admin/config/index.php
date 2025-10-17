<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php 

$permisopage = 'Configurar Sistema';
include('../login/restriction.php'); ?>
<?php
try {
    // Conexión a la base de datos usando PDO con utf8mb4 para soportar emojis
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8mb4'");

    // Consulta de todas las columnas (suponiendo que solo existe una fila)
    $query = "SELECT * FROM system_settings LIMIT 1";
    $stmt = $pdo->query($query);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Configuraciones</title>
  <?php include('../inc/header.php'); ?>
  <style>
      /* Estilos personalizados para los textareas de configuración */
      textarea.form-control {
          height: 220px; /* mayor altura para textos largos */
          font-family: 'Roboto', sans-serif;
          font-size: 14px;
          border: 1px solid #ced4da;
          border-radius: 0.375rem;
          padding: 12px;
          background-color: #fff;
          box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
          transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      }
      textarea.form-control:focus {
          border-color: #86b7fe;
          outline: 0;
          box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
      }
  </style>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Editar Configuraciones</h1>
  </div>
</div>
  <?php include('../inc/menu.php'); ?>
  <div class="container my-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
          
          <!-- Enlace para abrir el modal con las instrucciones -->
          <a href="#" data-bs-toggle="modal" data-bs-target="#usageModal" class="btn btn-link">
              <i class="fas fa-question-circle"></i> ¿Como Usar?
          </a>
      </div>
      
      <?php if (isset($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
      
      <!-- Se asigna id="configForm" para el manejo con AJAX -->
      <form id="configForm" action="save_config.php" method="POST">
          <!-- Campo de texto para wa_api -->
          <div class="mb-3">
              <label for="wa_api" class="form-label"><strong>API WhatsApp</strong></label>
              <input type="text" class="form-control" id="wa_api" name="wa_api" value="<?php echo htmlspecialchars($settings['wa_api'] ?? ''); ?>">
          </div>
          <!-- Organización en dos columnas para los textareas -->
          <div class="row">
              <div class="col-md-6 mb-3">
                  <label for="wa_consent" class="form-label">
                      <strong>Mensaje de consentimiento Informado</strong>
                      <hr>Campos disponibles: {nombres}, {apellidos}
                  </label>
                  <textarea class="form-control" id="wa_consent" name="wa_consent"><?php echo htmlspecialchars($settings['wa_consent'] ?? ''); ?></textarea>
              </div>
              <div class="col-md-6 mb-3">
                  <label for="wa_client_pay" class="form-label">
                      <strong>Mensaje de pago mensualidad del cliente</strong>
                      <hr>Campos disponibles: {nombres}, {apellidos}, {fecha_pago}, {fecha_vencimiento}
                  </label>
                  <textarea class="form-control" id="wa_client_pay" name="wa_client_pay"><?php echo htmlspecialchars($settings['wa_client_pay'] ?? ''); ?></textarea>
              </div>
              <div class="col-md-6 mb-3">
                  <label for="wa_client_pay_general" class="form-label">
                      <strong>Mensaje otros pagos del cliente</strong>
                      <hr>Campos disponibles: {nombres}, {apellidos}, {fecha_pago}, {valor}
                  </label>
                  <textarea class="form-control" id="wa_client_pay_general" name="wa_client_pay_general"><?php echo htmlspecialchars($settings['wa_client_pay_general'] ?? ''); ?></textarea>
              </div>
              <div class="col-md-6 mb-3">
                  <label for="wa_hbd" class="form-label">
                      <strong>Mensaje cumpleaños para el cliente</strong>
                      <hr>Campos disponibles: {nombres}, {apellidos}
                  </label>
                  <textarea class="form-control" id="wa_hbd" name="wa_hbd"><?php echo htmlspecialchars($settings['wa_hbd'] ?? ''); ?></textarea>
              </div>
              <div class="col-md-6 mb-3">
                  <label for="wa_notify_expired" class="form-label">
                      <strong>Mensaje vencimiento cliente</strong>
                      <hr>Campos disponibles: {nombres}, {apellidos}, {fecha_vencimiento}
                  </label>
                  <textarea class="form-control" id="wa_notify_expired" name="wa_notify_expired"><?php echo htmlspecialchars($settings['wa_notify_expired'] ?? ''); ?></textarea>
              </div>
              <div class="col-md-6 mb-3">
                  <label for="wa_paymentReminder" class="form-label">
                      <strong>Mensaje recordatorio de pago</strong>
                      <hr>Campos disponibles: {nombres}, {apellidos}, {fecha_vencimiento}, {plan}, $ {valor_pago}
                  </label>
                  <textarea class="form-control" id="wa_paymentReminder" name="wa_paymentReminder"><?php echo htmlspecialchars($settings['wa_paymentReminder'] ?? ''); ?></textarea>
              </div>
			  
			  <div class="col-md-6 mb-3">
    <label for="wa_creditReminder" class="form-label">
        <strong>Mensaje recordatorio pagos de créditos</strong>
        <hr class="my-1">
        Campos disponibles: {nombres} {apellidos}, {total_credito}, {detalle_creditos}
    </label>

    <!-- Texto del mensaje -->
    <textarea
        class="form-control mb-3"
        id="wa_creditReminder"
        name="wa_creditReminder"
        rows="6"><?php echo htmlspecialchars($settings['wa_creditReminder'] ?? ''); ?></textarea>

    <!-- Día de envío -->
    <label for="wa_creditReminder_day" class="form-label mb-1"><strong>Día de envío recordatorio pagos de créditos</strong></label>
    <select class="form-select mb-3" id="wa_creditReminder_day" name="wa_creditReminder_day">
        <?php
            $dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
            $diaSel = (int)($settings['wa_creditReminder_day'] ?? 4);   // Jueves por defecto
            foreach ($dias as $num => $nombre) {
                $sel = ($num === $diaSel) ? 'selected' : '';
                echo "<option value=\"$num\" $sel>$nombre</option>";
            }
        ?>
    </select>

    <!-- Hora de envío -->
    <label for="wa_creditReminder_hour" class="form-label mb-1"><strong>Hora de envío recordatorio pagos de créditos</strong></label>
    <input
        type="time"
        class="form-control"
        id="wa_creditReminder_hour"
        name="wa_creditReminder_hour"
        value="<?php echo htmlspecialchars($settings['wa_creditReminder_hour']); ?>">
</div>

			  
			  
			  <div class="col-md-6 mb-3">
                  <label for="wa_valoracion" class="form-label">
                      <strong>Mensaje de envio de valoración</strong>
                      <hr>Campos disponibles: {nombres}, {apellidos}
                  </label>
                  <textarea class="form-control" id="wa_valoracion" name="wa_valoracion"><?php echo htmlspecialchars($settings['wa_valoracion'] ?? ''); ?></textarea>
              </div>
			  
			  
			  
			  
          </div>
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
      </form>
  </div>
  
  <!-- Modal con las instrucciones -->
  <div class="modal fade" id="usageModal" tabindex="-1" aria-labelledby="usageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="usageModalLabel">Instrucciones de Configuración para WhatsApp</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p>Esta sección le permite editar los mensajes de WhatsApp que se enviarán a sus clientes. Cada campo corresponde a un mensaje distinto y puede personalizarlo utilizando los siguientes marcadores dinámicos:</p>
          <ul>
              <li><strong>{nombres}</strong>: Se reemplaza por el nombre del cliente.</li>
              <li><strong>{apellidos}</strong>: Se reemplaza por el apellido del cliente.</li>
              <li><strong>{fecha_pago}</strong>: Se reemplaza por la fecha de pago.</li>
              <li><strong>{fecha_vencimiento}</strong>: Se reemplaza por la fecha de vencimiento.</li>
              <li><strong>{valor}</strong>: Se reemplaza por el valor (según el mensaje).</li>
              <li><strong>{plan}</strong>: Se reemplaza por el plan del cliente.</li>
              <li><strong>{valor_pago}</strong>: Se reemplaza por el valor del pago.</li>
          </ul>
          <p><strong>Formato de texto para WhatsApp:</strong></p>
          <ul>
              <li><strong>Negrita:</strong> En WhatsApp se utiliza asteriscos. Ejemplo: <code>*texto*</code></li>
              <li><strong>Cursiva:</strong> En WhatsApp se utiliza guiones bajos. Ejemplo: <code>_texto_</code></li>
              <li><strong>Tachado:</strong> En WhatsApp se utiliza la virgulilla. Ejemplo: <code>~texto~</code></li>
          </ul>
          <p>Para agregar emojis en Windows, presione <strong>Windows + . (punto)</strong> para abrir el selector de emojis.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  
  <?php include('../inc/menu-footer.php'); ?>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap 5 JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  
  <!-- Script AJAX para guardar la configuración -->
  <script>
    $(document).ready(function(){
      $("#configForm").submit(function(e){
        e.preventDefault();
        $.ajax({
          url: $(this).attr("action"),
          type: $(this).attr("method"),
          data: $(this).serialize(),
          dataType: "json",
          success: function(response) {
            if(response.success) {
              Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: response.message
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message
              });
            }
          },
          error: function() {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Ocurrió un error al guardar la configuración.'
            });
          }
        });
      });
    });
  </script>
</body>
</html>










