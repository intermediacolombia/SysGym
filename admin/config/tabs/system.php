<!-- ===== TAB SISTEMA ===== -->
<div class="tab-pane fade show active" id="sistema" role="tabpanel" aria-labelledby="sistema-tab">

  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">

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

      <hr class="my-4">

      <!-- COLORES DEL SISTEMA -->
      <h5 class="text-primary mb-3"><i class="fas fa-palette me-2"></i>Colores del Sistema</h5>

      <div class="row">
        <!-- COLOR PRINCIPAL CLARO -->
        <div class="col-md-6 mb-4">
          <label class="form-label d-flex align-items-center gap-2">
            <strong>Color Principal (modo claro)</strong>
            <span id="previewPrimary" class="border rounded"
                  style="display:inline-block;width:25px;height:25px;background:<?= htmlspecialchars($settings['system_color_primary'] ?? '#0d6efd') ?>;"></span>
          </label>
          <input type="color" class="form-control form-control-color" id="colorPrimary" name="system_color_primary"
                 value="<?= htmlspecialchars($settings['system_color_primary'] ?? '#0d6efd') ?>">
          <small class="text-muted">Usado en botones, encabezados y acentos principales.</small>
        </div>

        <!-- COLOR SECUNDARIO CLARO -->
        <div class="col-md-6 mb-4">
          <label class="form-label d-flex align-items-center gap-2">
            <strong>Color Secundario (modo claro)</strong>
            <span id="previewSecondary" class="border rounded"
                  style="display:inline-block;width:25px;height:25px;background:<?= htmlspecialchars($settings['system_color_secondary'] ?? '#6c757d') ?>;"></span>
          </label>
          <input type="color" class="form-control form-control-color" id="colorSecondary" name="system_color_secondary"
                 value="<?= htmlspecialchars($settings['system_color_secondary'] ?? '#6c757d') ?>">
          <small class="text-muted">Usado en textos y fondos secundarios.</small>
        </div>
      </div>

      <hr class="my-4">

      <h5 class="text-primary mb-3"><i class="fas fa-moon me-2"></i>Colores del Modo Oscuro</h5>

      <div class="row">
        <!-- COLOR PRINCIPAL OSCURO -->
        <div class="col-md-6 mb-4">
          <label class="form-label d-flex align-items-center gap-2">
            <strong>Color Principal (modo oscuro)</strong>
            <span id="previewPrimaryDark" class="border rounded"
                  style="display:inline-block;width:25px;height:25px;background:<?= htmlspecialchars($settings['system_color_primary_dark'] ?? '#1e90ff') ?>;"></span>
          </label>
          <input type="color" class="form-control form-control-color" id="colorPrimaryDark" name="system_color_primary_dark"
                 value="<?= htmlspecialchars($settings['system_color_primary_dark'] ?? '#1e90ff') ?>">
          <small class="text-muted">Color principal para modo oscuro.</small>
        </div>

        <!-- COLOR SECUNDARIO OSCURO -->
        <div class="col-md-6 mb-4">
          <label class="form-label d-flex align-items-center gap-2">
            <strong>Color Secundario (modo oscuro)</strong>
            <span id="previewSecondaryDark" class="border rounded"
                  style="display:inline-block;width:25px;height:25px;background:<?= htmlspecialchars($settings['system_color_secondary_dark'] ?? '#999999') ?>;"></span>
          </label>
          <input type="color" class="form-control form-control-color" id="colorSecondaryDark" name="system_color_secondary_dark"
                 value="<?= htmlspecialchars($settings['system_color_secondary_dark'] ?? '#999999') ?>">
          <small class="text-muted">Color secundario para modo oscuro.</small>
        </div>
      </div>

      <div class="alert alert-info small mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Estos colores afectan el tema global tanto en modo claro como en modo oscuro.
      </div>
    </div>
  </div>
</div>

<!-- Script de vista previa -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const colors = [
    ['colorPrimary', 'previewPrimary', '--system-color-primary'],
    ['colorSecondary', 'previewSecondary', '--system-color-secondary'],
    ['colorPrimaryDark', 'previewPrimaryDark', '--system-color-primary-dark'],
    ['colorSecondaryDark', 'previewSecondaryDark', '--system-color-secondary-dark']
  ];

  const root = document.documentElement;
  colors.forEach(([inputId, previewId, variable]) => {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (input && preview) {
      input.addEventListener('input', e => {
        const color = e.target.value;
        preview.style.background = color;
        root.style.setProperty(variable, color);
      });
    }
  });
});
</script>

