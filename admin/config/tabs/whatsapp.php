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

    </div>
  </div>

</div>
