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