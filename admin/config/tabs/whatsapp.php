<!-- ===== TAB WHATSAPP ===== -->
<div class="tab-pane fade" id="whatsapp" role="tabpanel" aria-labelledby="whatsapp-tab">

  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
     

      <!-- API -->
      <div class="mb-3">
        <label class="form-label"><strong>API WhatsApp</strong></label>
        <input type="text" class="form-control" name="wa_api" value="<?= htmlspecialchars($settings['wa_api'] ?? '') ?>">
      </div>

      <!-- MENSAJES -->
      <div class="row">
        <?php
        // Definir mensajes y campos disponibles
        $campos = [
          'wa_consent' => [
            'label' => 'Mensaje de consentimiento informado',
            'campos' => '{nombres}, {apellidos}'
          ],'wa_consent_pending' => [
            'label' => 'Mensaje de consentimiento informado Pendiente',
            'campos' => '{nombres}, {apellidos}, {cedula}, {link}'
          ],
          'wa_client_pay' => [
            'label' => 'Mensaje de pago mensualidad del cliente',
            'campos' => '{nombres}, {apellidos}, {fecha_pago}, {fecha_vencimiento}'
          ],
          'wa_client_pay_general' => [
            'label' => 'Mensaje de otros pagos del cliente',
            'campos' => '{nombres}, {apellidos}, {fecha_pago}, {valor}'
          ],
          'wa_hbd' => [
            'label' => 'Mensaje de cumpleaños para el cliente',
            'campos' => '{nombres}, {apellidos}'
          ],
          'wa_notify_expired' => [
            'label' => 'Mensaje de vencimiento membresía del cliente',
            'campos' => '{nombres}, {apellidos}, {fecha_vencimiento}, {url_pago}'
          ],
          'wa_paymentReminder' => [
            'label' => 'Mensaje recordatorio de pago membresía',
            'campos' => '{nombres}, {apellidos}, {fecha_vencimiento}, {plan}, {valor_pago}, {url_pago}'
          ],
          'wa_creditReminder' => [
            'label' => 'Mensaje recordatorio de pagos de créditos',
            'campos' => '{nombres}, {apellidos}, {total_credito}, {detalle_creditos}'
          ],
          'wa_valoracion' => [
            'label' => 'Mensaje de envío de valoración',
            'campos' => '{nombres}, {apellidos}'
          ]
        ];

        foreach ($campos as $key => $info): ?>
          <div class="col-md-6 mb-4">
            <label class="form-label">
              <strong><?= $info['label'] ?></strong>
              <hr class="my-2">
              <span class="text-muted small">Campos disponibles: <?= htmlspecialchars($info['campos']) ?></span>
            </label>
            <textarea class="form-control" name="<?= $key ?>"><?= htmlspecialchars($settings[$key] ?? '') ?></textarea>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- DÍA Y HORA DE RECORDATORIO -->
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

      <div class="alert alert-info small mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Usa los marcadores disponibles entre llaves para personalizar los mensajes.  
        Por ejemplo: <code>Hola {nombres}, tu pago vence el {fecha_vencimiento}.</code>
      </div>
		
		
		
		
		<!-- CONFIGURACIÓN ENVÍOS MASIVOS -->
<div class="mt-4">
    <h5 class="text-danger"><i class="fas fa- paper-plane me-2"></i>Configuración Envíos Masivos</h5>
    <hr>

    <!-- Advertencia de Uso -->
    <div class="alert alert-warning border-start border-warning border-4 shadow-sm">
        <div class="d-flex">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <div>
                <strong>¡Advertencia de Seguridad!</strong><br>
                El uso inadecuado de envíos masivos puede resultar en el <strong>bloqueo permanente</strong> de su número por parte de WhatsApp. 
                Para reducir riesgos, siga las configuraciones recomendadas:
                <ul class="mb-0 mt-1">
                    <li>Días: Lunes a Viernes.</li>
                    <li>Horario: 7:00 AM a 9:00 PM.</li>
                    <li>Volumen: Máximo 50 mensajes diarios.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Días de Envío -->
        <div class="col-md-12 mb-3">
            <label class="form-label d-block"><strong>Días permitidos para envíos masivos</strong></label>
            <div class="btn-group w-100" role="group" aria-label="Días de la semana">
                <?php
                $dias_semana = [
                    1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'
                ];
                // Asumiendo que guardas los días como una cadena separada por comas, ej: "1,2,3,4,5"
                $dias_configurados = explode(',', $settings['wa_mass_days'] ?? '1,2,3,4,5');
                
                foreach ($dias_semana as $num => $nombre): 
                    $checked = in_array($num, $dias_configurados) ? 'checked' : '';
                ?>
                    <input type="checkbox" class="btn-check" name="wa_mass_days_array[]" id="day_<?= $num ?>" value="<?= $num ?>" <?= $checked ?>>
                    <label class="btn btn-outline-primary" for="day_<?= $num ?>"><?= $nombre ?></label>
                <?php endforeach; ?>
            </div>
            <!-- Campo oculto para procesar el array como string si tu script de guardado no maneja arrays -->
            <input type="hidden" name="wa_mass_days" id="wa_mass_days_hidden" value="<?= htmlspecialchars($settings['wa_mass_days'] ?? '1,2,3,4,5') ?>">
        </div>

        <!-- Hora Inicio -->
        <div class="col-md-4 mb-3">
            <label class="form-label"><strong>Hora Inicio (00-23)</strong></label>
            <select class="form-select" name="wa_mass_hour_start">
                <?php
                $start_sel = (int)($settings['wa_mass_hour_start'] ?? 7);
                for ($i = 0; $i <= 23; $i++) {
                    $label = ($i == 0) ? "12 AM" : (($i < 12) ? "$i AM" : (($i == 12) ? "12 PM" : ($i - 12) . " PM"));
                    $selected = ($i === $start_sel) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>$label ($i:00)</option>";
                }
                ?>
            </select>
        </div>

        <!-- Hora Fin -->
        <div class="col-md-4 mb-3">
            <label class="form-label"><strong>Hora Fin (00-23)</strong></label>
            <select class="form-select" name="wa_mass_hour_end">
                <?php
                $end_sel = (int)($settings['wa_mass_hour_end'] ?? 21);
                for ($i = 0; $i <= 23; $i++) {
                    $label = ($i == 0) ? "12 AM" : (($i < 12) ? "$i AM" : (($i == 12) ? "12 PM" : ($i - 12) . " PM"));
                    $selected = ($i === $end_sel) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>$label ($i:00)</option>";
                }
                ?>
            </select>
        </div>

        <!-- Máximo Mensajes -->
        <div class="col-md-4 mb-3">
            <label class="form-label"><strong>Máx. Mensajes Diarios</strong></label>
            <input type="number" class="form-control" name="wa_mass_limit" value="<?= htmlspecialchars($settings['wa_mass_limit'] ?? 50) ?>" min="1" max="500">
        </div>
    </div>
</div>

<script>
// Script opcional para convertir los checkboxes en una cadena separada por comas antes de enviar, 
// ya que tu PHP de guardado inserta directamente lo que viene en $_POST
document.querySelectorAll('.btn-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        let selected = Array.from(document.querySelectorAll('.btn-check:checked')).map(cb => cb.value);
        document.getElementById('wa_mass_days_hidden').value = selected.join(',');
    });
});
</script>

    </div>
  </div>

</div>
