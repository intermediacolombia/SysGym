<?php if (isset($_SESSION["user_permissions"]) && in_array('Manejar Valoraciones', $_SESSION["user_permissions"])): ?>
<!-- Sección: Historial de Valoraciones -->

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">
    <i class="fas fa-heartbeat"></i>
    Historial de Valoraciones
  </h3>
<!-- NUEVO botón COMPARACIÓN -->
  <button type="button" class="btn btn-primary" data-bs-toggle="modal"
          data-bs-target="#comparisonModal">
    <i class="fa fa-exchange"></i> Comparación
  </button>

  <a href="/admin/valoraciones/new.php?cliente_id=<?php echo $cliente['id'];?>" class="btn btn-success">
    <i class="fa fa-plus me-1"></i>
    Nueva Valoración
  </a>
	
</div>	
<hr>
<table id="valoraciones-table" class="table table-striped table-bordered">
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Peso (Kg)</th>
      <th>Estatura (m)</th>
      <th>IMC</th>
      <th>% Grasa</th>
      <th>% Músculo</th>
      <th>MB (kcal)</th>
      <th>Edad</th>
      <th>Visceral</th>
      
    </tr>
  </thead>
  <tbody></tbody>
</table>
<?php endif; ?>



<!-- Modal Comparación de Valoraciones -->
<div class="modal fade" id="comparisonModal" tabindex="-1"
     aria-labelledby="comparisonModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="comparisonModalLabel">
          Comparar Valoraciones
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"
                aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <!-- Selectores de fecha -->
        <form id="comparisonForm" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Valoración A</label>
            <select id="valA" class="form-select" required></select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Valoración B</label>
            <select id="valB" class="form-select" required></select>
          </div>
        </form>

        <!-- Resultado -->
        <hr>
        <div id="comparisonResult" style="overflow-x:auto;">
          <!-- Se rellena por JS -->
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary"
                data-bs-dismiss="modal">Cerrar</button>
        <button type="button" id="compareBtn" class="btn btn-primary">
          Comparar
        </button>
      </div>
    </div>
  </div>
</div>
