<div class="tab-pane fade show active" id="sistema" role="tabpanel" aria-labelledby="sistema-tab">
  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
		
		<!-- NOMBRE -->
		<div class="mb-3">
        <label class="form-label"><strong>Nombre del Gimnasio</strong></label>
        <input type="text" class="form-control" name="name_gym" value="<?= htmlspecialchars($settings['name_gym'] ?? '') ?>">
      </div>
		
		<!-- LOGO -->
      <div class="mb-4">
        <label class="form-label"><strong>Logo del Sistema</strong></label>
        <?php if (!empty($settings['system_logo'])): ?>
          <div class="mb-2">
            <img src="../uploads/<?= htmlspecialchars($settings['system_logo']) ?>" alt="Logo actual" style="max-height: 80px;" class="border rounded shadow-sm p-1 bg-white">
          </div>
        <?php endif; ?>
        <input type="file" class="form-control" name="system_logo" accept="image/*">
        <small class="text-muted">Formatos permitidos: PNG, JPG. Tamaño recomendado: 300x100 px.</small>
      </div>
	<!-- FAVICON -->
      <div class="mb-4">
        <label class="form-label"><strong>Favicon</strong></label>
        <?php if (!empty($settings['system_favicon'])): ?>
          <div class="mb-2">
            <img src="../uploads/<?= htmlspecialchars($settings['system_favicon']) ?>" alt="Favicon actual" style="max-height: 32px;" class="border rounded bg-white p-1">
          </div>
        <?php endif; ?>
        <input type="file" class="form-control" name="system_favicon" accept="image/x-icon,image/png">
        <small class="text-muted">Formato permitido: .ico o .png (32x32 px)</small>
      </div>
		
		<!-- EMAIL -->
		<div class="mb-3">
        <label class="form-label"><strong>Email del Gimnasio</strong></label>
        <input type="email" class="form-control" name="email_gym" value="<?= htmlspecialchars($settings['email_gym'] ?? '') ?>">
		<small class="text-muted">Se imprimirá en la información de la factura.</small>
      </div>
		
		<!-- NIT -->
		<div class="mb-3">
        <label class="form-label"><strong>NIT del Gimnasio</strong></label>
        <input type="text" class="form-control" name="nit_gym" value="<?= htmlspecialchars($settings['nit_gym'] ?? '') ?>">
		<small class="text-muted">Se imprimirá en la información de la factura.</small>
      </div>
		
		<!-- TELEFONO -->
		<div class="mb-3">
        <label class="form-label"><strong>Teléfono del Gimnasio</strong></label>
        <input type="text" class="form-control" name="tel_gym" value="<?= htmlspecialchars($settings['tel_gym'] ?? '') ?>">
		<small class="text-muted">Se imprimirá en la información de la factura.</small>
      </div>

      <hr class="my-4">     
<!-- ===== COLORES DEL SISTEMA ===== -->
<h5><i class="fas fa-palette me-2"></i>Colores del Sistema</h5>
<div class="card border-0 shadow-sm">
  <div class="card-body">

    <p class="text-muted mb-4">Configura los colores base del sistema. Los tonos del modo oscuro se aplican automáticamente cuando el usuario activa el tema oscuro.</p>

    <div class="row g-4">

      <!-- ===== PALETA MODO CLARO ===== -->
      <div class="col-lg-6">
        <div class="border rounded p-3 h-100 bg-light">
          <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-sun me-1 text-warning"></i> Modo Claro</h6>

          <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2">
              <strong>Color Primario</strong>
              <span id="previewPrimary" class="border rounded-circle shadow-sm" 
                    style="width: 28px; height: 28px; background: <?= htmlspecialchars(SYSTEM_COLOR_PRIMARY) ?>;"></span>
            </label>
            <input type="color" class="form-control form-control-color w-100" id="colorPrimary"
                   name="system_color_primary" value="<?= htmlspecialchars(SYSTEM_COLOR_PRIMARY) ?>">
          </div>

          <div>
            <label class="form-label d-flex align-items-center gap-2">
              <strong>Color Secundario</strong>
              <span id="previewSecondary" class="border rounded-circle shadow-sm" 
                    style="width: 28px; height: 28px; background: <?= htmlspecialchars(SYSTEM_COLOR_SECONDARY) ?>;"></span>
            </label>
            <input type="color" class="form-control form-control-color w-100" id="colorSecondary"
                   name="system_color_secondary" value="<?= htmlspecialchars(SYSTEM_COLOR_SECONDARY) ?>">
          </div>
        </div>
      </div>

      <!-- ===== PALETA MODO OSCURO ===== -->
      <div class="col-lg-6">
        <div class="border rounded p-3 h-100 bg-dark text-white">
          <h6 class="fw-bold mb-3"><i class="fas fa-moon me-1 text-info"></i> Modo Oscuro</h6>

          <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2">
              <strong>Color Primario (Oscuro)</strong>
              <span id="previewPrimaryDark" class="border rounded-circle shadow-sm" 
                    style="width: 28px; height: 28px; background: <?= htmlspecialchars(SYSTEM_COLOR_PRIMARY_DARK) ?>;"></span>
            </label>
            <input type="color" class="form-control form-control-color w-100" id="colorPrimaryDark"
                   name="system_color_primary_dark" value="<?= htmlspecialchars(SYSTEM_COLOR_PRIMARY_DARK) ?>">
          </div>

          <div>
            <label class="form-label d-flex align-items-center gap-2">
              <strong>Color Secundario (Oscuro)</strong>
              <span id="previewSecondaryDark" class="border rounded-circle shadow-sm" 
                    style="width: 28px; height: 28px; background: <?= htmlspecialchars(SYSTEM_COLOR_SECONDARY_DARK) ?>;"></span>
            </label>
            <input type="color" class="form-control form-control-color w-100" id="colorSecondaryDark"
                   name="system_color_secondary_dark" value="<?= htmlspecialchars(SYSTEM_COLOR_SECONDARY_DARK) ?>">
          </div>
        </div>
      </div>

    </div>

    <div class="alert alert-info small mt-4 mb-0">
      <i class="fas fa-info-circle me-1"></i>Estos colores definen el aspecto general del panel, incluyendo menús, encabezados y botones.
    </div>

  </div>
</div>
</div>
</div>
</div>
<!-- Script para vistas previas -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const updates = [
    ['colorPrimary', 'previewPrimary'],
    ['colorSecondary', 'previewSecondary'],
    ['colorPrimaryDark', 'previewPrimaryDark'],
    ['colorSecondaryDark', 'previewSecondaryDark']
  ];
  updates.forEach(([inputId, previewId]) => {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (input && preview) {
      input.addEventListener('input', e => preview.style.background = e.target.value);
    }
  });
});
</script>

