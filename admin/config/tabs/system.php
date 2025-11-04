 <!-- ===== TAB SISTEMA ===== -->
      <div class="tab-pane fade show active" id="sistema" role="tabpanel" aria-labelledby="sistema-tab">

        <div class="mb-4">
          <label class="form-label"><strong>Logo del Sistema</strong></label>
          <?php if (!empty($settings['system_logo'])): ?>
              <div class="mb-2">
                  <img src="../uploads/<?= htmlspecialchars($settings['system_logo']) ?>" alt="Logo actual" style="max-height: 80px;">
              </div>
          <?php endif; ?>
          <input type="file" class="form-control" name="system_logo" accept="image/*">
          <small class="text-muted">Formatos permitidos: PNG, JPG. Tamaño recomendado: 300x100 px.</small>
        </div>

        <div class="mb-4">
          <label class="form-label"><strong>Favicon</strong></label>
          <?php if (!empty($settings['system_favicon'])): ?>
              <div class="mb-2">
                  <img src="../uploads/<?= htmlspecialchars($settings['system_favicon']) ?>" alt="Favicon actual" style="max-height: 32px;">
              </div>
          <?php endif; ?>
          <input type="file" class="form-control" name="system_favicon" accept="image/x-icon,image/png">
          <small class="text-muted">Formato permitido: .ico o .png (32x32 px)</small>
        </div>

      </div>