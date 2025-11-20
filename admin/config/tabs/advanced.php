<div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">

      <h5 class="mb-3"><i class="fas fa-tools me-2"></i>Configuraciones Avanzadas</h5>
      <p class="text-muted">
        Estas opciones están dirigidas a tareas de desarrollo o pruebas. Utilízalas con precaución para evitar registros excesivos o exposición de información interna.
      </p>

      <hr class="my-4">

      <!-- SWITCH: Modo depuración -->
      <div class="mb-4">
        <label class="form-label fw-bold d-flex align-items-center gap-2">
          <i class="fas fa-bug"></i> Mostrar informes de depuración
        </label>

        <!-- Switch Bootstrap -->
        <div class="form-check form-switch">
          <input 
            class="form-check-input"
            type="checkbox"
            id="debugMode"
            name="debug_mode"
            value="1"
            <?= !empty($settings['debug_mode']) && $settings['debug_mode'] == 1 ? 'checked' : '' ?>
          >
          <label class="form-check-label" for="debugMode">
            Activar informes de depuración en el sistema.
          </label>
        </div>

        <small class="text-danger">
          Esta opción solo debe utilizarse en entornos de prueba. Habilitarla en producción puede exponer datos internos o afectar el rendimiento.
        </small>
      </div>

      <hr class="my-4">

      <p class="text-muted small mb-0">
        Más opciones avanzadas se agregarán próximamente.
      </p>

    </div>
  </div>
</div>

