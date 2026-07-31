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

      <hr class="my-4">

      <div class="mb-3">
        <label class="form-label"><strong>Nombre del Firmante</strong></label>
        <input type="text" class="form-control" name="consent_firmante" value="<?= htmlspecialchars($settings['consent_firmante'] ?? '') ?>">
        <small class="text-muted">Nombre que aparece al pie del consentimiento como responsable.</small>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Imagen de Firma</strong></label>
        <?php if (!empty($settings['consent_firma_img'])): ?>
          <div class="mb-2">
            <img src="../uploads/<?= htmlspecialchars($settings['consent_firma_img']) ?>" alt="Firma actual" style="max-height: 80px;" class="border rounded bg-white p-1">
          </div>
        <?php endif; ?>
        <input type="file" class="form-control" name="consent_firma_img" accept="image/*">
        <small class="text-muted">PNG con fondo transparente recomendado. Se imprimirá en el PDF del consentimiento.</small>
      </div>

    </div>
  </div>

</div>
