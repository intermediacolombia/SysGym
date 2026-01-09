<div class="tab-pane fade" id="pasarela" role="tabpanel" aria-labelledby="pasarela-tab">
  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
      <h5 class="mb-3"><i class="fas fa-credit-card me-2 text-primary"></i>Configuración de Pasarela de Pago</h5>
      <p class="text-muted">Define los parámetros de conexión y ajustes financieros para los pagos en línea.</p>
      
      <!-- === BANCOS DISPONIBLES === -->
      <div class="row g-4 mb-4">
        <div class="col-12">
          <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2">
              <strong><i class="fas fa-university me-2 text-success"></i>Bancos Disponibles</strong>
            </label>
            <input type="text" name="bancos_disponibles" class="form-control" 
                   value="<?= htmlspecialchars($settings['bancos_disponibles'] ?? 'Bancolombia,Daviplata,Nequi,Davivienda') ?>" 
                   placeholder="Ej: Bancolombia,Daviplata,Nequi,Davivienda">
            <small class="text-muted">
              <i class="fas fa-info-circle me-1"></i>
              Lista de bancos separados por coma. Estos aparecerán en los selectores de método de pago por transferencia.
            </small>
          </div>
        </div>
      </div>
      
      <hr class="my-4">
      
      <h6 class="fw-bold text-secondary"><i class="fas fa-key me-2"></i> Credenciales Mercado Pago</h6>
      <div class="row g-4 mt-2">
        <!-- === PUBLIC KEY === -->
        <div class="col-lg-6">
          <div class="mb-4">
            <label class="form-label"><strong>Public Key (Mercado Pago)</strong></label>
            <input type="text" name="mp_public_key" class="form-control" 
                   value="<?= htmlspecialchars($settings['mp_public_key'] ?? MP_PUBLIC_KEY) ?>" 
                   placeholder="Ej: APP_USR-xxxxxxxxxxxxxxxxxxxxxxx">
            <small class="text-muted">Clave pública utilizada para integrar el checkout en el frontend.</small>
          </div>
        </div>
        <!-- === ACCESS TOKEN === -->
        <div class="col-lg-6">
          <div class="mb-4">
            <label class="form-label"><strong>Access Token (Mercado Pago)</strong></label>
            <input type="text" name="mp_access_token" class="form-control" 
                   value="<?= htmlspecialchars($settings['mp_access_token'] ?? MP_ACCESS_TOKEN) ?>" 
                   placeholder="Ej: APP_USR-xxxxxxxxxxxxxxxxxxxxxxx">
            <small class="text-muted">Token privado del servidor. Se usa para verificar pagos desde el webhook.</small>
          </div>
        </div>
      </div>
      
      <hr class="my-4">
      
      <!-- === AJUSTES FINANCIEROS === -->
      <h6 class="fw-bold text-secondary"><i class="fas fa-percentage me-2"></i> Ajustes Financieros</h6>
      <div class="row g-4 mt-2">
        
        <!-- PORCENTAJE ADICIONAL -->
        <div class="col-lg-6">
          <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2">
              <strong>Porcentaje Adicional</strong>
              <span class="text-muted small">(ej: 8.5 para 8.5%)</span>
            </label>
            <input type="number" step="0.1" min="0" max="100" name="additional_percentage_payment"
                   class="form-control" 
                   value="<?= htmlspecialchars($settings['additional_percentage_payment'] ?? '0') ?>">
            <small class="text-muted">Porcentaje adicional aplicado automáticamente al valor del plan al pagar por pasarela.</small>
          </div>
        </div>
        <!-- DÍAS PERMITIDOS -->
        <div class="col-lg-6">
          <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2">
              <strong>Días para permitir el pago</strong>
              <span class="text-muted small">(ej: 5)</span>
            </label>
            <input type="number" min="0" name="days_allowed_before_due"
                   class="form-control" 
                   value="<?= htmlspecialchars($settings['days_allowed_before_due'] ?? '0') ?>">
            <small class="text-muted">Número de días antes del vencimiento en los que el cliente puede realizar el pago en línea.</small>
          </div>
        </div>
      </div>
      
      <div class="alert alert-info small mt-4 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        El porcentaje adicional se usa para cubrir comisiones de la pasarela, y los días de margen controlan cuándo el cliente puede pagar anticipadamente su plan.
      </div>
    </div>
  </div>
</div>
