<div class="tab-pane fade" id="pasarela" role="tabpanel" aria-labelledby="pasarela-tab">
  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
      <h5 class="mb-3"><i class="fas fa-dumbbell"></i>Configuración de Planes</h5>
      <p class="text-muted">Define los bparamentros de configuracion en el sistema para operaciones con los planes.</p>
      
    
		
		     
      <!-- === AJUSTES FINANCIEROS === -->
      <h6 class="fw-bold text-secondary"><i class="fas fa-percentage me-2"></i> Ajustes Financieros</h6>
      <div class="row g-4 mt-2">
        
        
        <!-- DÍAS PERMITIDOS -->
        <div class="col-lg-6">
          <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2">
              <strong>Días para permitir el congelamiento</strong>
              <span class="text-muted small">(ej: 7)</span>
            </label>
            <input type="number" min="0" name="days_allowed_frozen"
                   class="form-control" 
                   value="<?= htmlspecialchars($settings['days_allowed_frozen'] ?? '0') ?>">
            <small class="text-muted">Número de días permitidos para congelar los planes de los clientes.</small>
          </div>
        </div>
      </div>
      
      
    </div>
  </div>
</div>
