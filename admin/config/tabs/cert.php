<div class="tab-pane fade" id="certificado" role="tabpanel" aria-labelledby="certificado-tab">
  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">

      <p class="text-muted small mb-3">
        Usa los siguientes campos dinámicos en el texto del certificado:<br>
        <code>{nombres}</code> <code>{apellidos}</code> <code>{nombre_completo}</code> <code>{identificacion}</code>
        <code>{nombre_gym}</code> <code>{fecha_vinculacion}</code> <code>{plan_activo}</code>
        <code>{fecha_inicio_plan}</code> <code>{fecha_fin_plan}</code> <code>{fecha_expedicion}</code>
      </p>

      <div class="mb-3">
        <label class="form-label"><strong>Texto del Certificado</strong></label>
        <textarea id="cert_template" name="cert_template"><?= htmlspecialchars($settings['cert_template'] ?? '') ?></textarea>
      </div>

      <hr class="my-4">

      <div class="mb-3">
        <label class="form-label"><strong>Nombre del Firmante</strong></label>
        <input type="text" class="form-control" name="cert_firmante" value="<?= htmlspecialchars($settings['cert_firmante'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Cargo del Firmante</strong></label>
        <input type="text" class="form-control" name="cert_cargo" value="<?= htmlspecialchars($settings['cert_cargo'] ?? '') ?>">
        <small class="text-muted">Ej: Gerente, Director, Administrador</small>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Imagen de Firma</strong></label>
        <?php if (!empty($settings['cert_firma_img'])): ?>
          <div class="mb-2">
            <img src="../uploads/<?= htmlspecialchars($settings['cert_firma_img']) ?>" alt="Firma actual" style="max-height: 80px;" class="border rounded bg-white p-1">
          </div>
        <?php endif; ?>
        <input type="file" class="form-control" name="cert_firma_img" accept="image/*">
        <small class="text-muted">PNG con fondo transparente recomendado.</small>
      </div>

    </div>
  </div>
</div>

