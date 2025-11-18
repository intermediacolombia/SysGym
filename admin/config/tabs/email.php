<div class="tab-pane fade" id="smtp" role="tabpanel" aria-labelledby="smtp-tab">
  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
      
      <h5 class="mb-3">
        <i class="fas fa-envelope me-2 text-primary"></i>Configuración SMTP
      </h5>
      <p class="text-muted">
        Ajusta los parámetros necesarios para el envío de correos del sistema (recuperación, notificaciones, facturación).
      </p>

      <div class="row g-4">

        <!-- === USERNAME === -->
        <div class="col-lg-6">
          <div class="mb-4">
            <label class="form-label"><strong>SMTP Username</strong></label>
            <input type="text" name="smtp_username" class="form-control"
              value="<?= htmlspecialchars($settings['smtp_username'] ?? $Username) ?>"
              placeholder="Ej: usuario@smtp-brevo.com">
            <small class="text-muted">Usuario asignado por el proveedor SMTP.</small>
          </div>
        </div>

        <!-- === HOST === -->
        <div class="col-lg-6">
          <div class="mb-4">
            <label class="form-label"><strong>SMTP Host</strong></label>
            <input type="text" name="smtp_host" class="form-control"
              value="<?= htmlspecialchars($settings['smtp_host'] ?? $Host) ?>"
              placeholder="Ej: smtp-relay.brevo.com">
            <small class="text-muted">Servidor SMTP para enviar correos.</small>
          </div>
        </div>

        <!-- === PASSWORD === -->
        <div class="col-lg-6">
          <div class="mb-4">
            <label class="form-label"><strong>SMTP Password</strong></label>
            <input type="password" name="smtp_password" class="form-control"
              value="<?= htmlspecialchars($settings['smtp_password'] ?? $Password) ?>"
              placeholder="Contraseña SMTP">
            <small class="text-muted">Contraseña o clave de aplicación generada.</small>
          </div>
        </div>

        <!-- === PUERTO SMTP === -->
        <div class="col-lg-6">
          <div class="mb-4">
            <label class="form-label"><strong>Puerto SMTP</strong></label>
            <select name="smtp_port" class="form-select">
              <?php 
                $puertos = [587, 465, 25];
                $portValue = $settings['smtp_port'] ?? $Port;
                foreach ($puertos as $p):
              ?>
                <option value="<?= $p ?>" <?= $p == $portValue ? 'selected' : '' ?>>
                  <?= $p ?> (<?= $p == 587 ? 'TLS' : ($p == 465 ? 'SSL' : 'Alternativo') ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Selecciona el puerto según la configuración recomendada por el proveedor.</small>
          </div>
        </div>

      </div>

      <hr class="my-4">

      <!-- === REMITENTE === -->
      <h6 class="fw-bold text-secondary">
        <i class="fas fa-user-tag me-2"></i> Remitente del Correo
      </h6>

      <div class="row g-4 mt-2">

        <!-- FROM -->
        <div class="col-lg-6">
          <div class="mb-3">
            <label class="form-label"><strong>Correo del Remitente (From)</strong></label>
            <input type="email" name="smtp_from" class="form-control"
              value="<?= htmlspecialchars($settings['smtp_from'] ?? $from) ?>"
              placeholder="Ej: no-reply@activgym.com.co">
            <small class="text-muted">Correo que verán los usuarios como remitente.</small>
          </div>
        </div>

        <!-- NAME -->
        <div class="col-lg-6">
          <div class="mb-3">
            <label class="form-label"><strong>Nombre del Remitente</strong></label>
            <input type="text" name="smtp_from_name" class="form-control"
              value="<?= htmlspecialchars($settings['smtp_from_name'] ?? $name) ?>"
              placeholder="Ej: ActivGym">
            <small class="text-muted">Nombre que aparecerá en los correos enviados.</small>
          </div>
        </div>

      </div>

      <div class="alert alert-info small mt-4 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Asegúrate de que los datos coincidan con la configuración de tu proveedor SMTP para evitar errores de envío.
      </div>

    </div>
  </div>
</div>

