<div class="tab-pane fade" id="consent" role="tabpanel" aria-labelledby="consent-tab">

  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
      

      <div class="mb-3">
        <label class="form-label"><strong>Contenido del consentimiento</strong></label>
        <textarea id="wa_consent_html" name="wa_consent_html"><?= htmlspecialchars($settings['wa_consent_html'] ?? '') ?></textarea>
      </div>

      <div class="alert alert-info small mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Puedes incluir texto, imágenes, listas y formato avanzado. Este texto se usará para mostrar o imprimir el consentimiento informado.
      </div>

</div>
  </div>

</div>
