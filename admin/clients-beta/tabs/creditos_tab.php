<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
<!-- Sección: Créditos Activos -->
  <h3><i class="fa fa-credit-card"></i> Créditos Activos</h3>
	<hr>
  <table id="creditos-table" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Valor</th>
        <th>Fecha Límite</th>
        <th>Descripción</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
<?php endif;?>

<!-- Modal para Editar Crédito -->
<div class="modal fade" id="editCreditModal" tabindex="-1" aria-labelledby="editCreditModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editCreditForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editCreditModalLabel">Editar Crédito</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <!-- Campo oculto para el ID del crédito -->
          <input type="hidden" id="editCreditId" name="credit_id">
          <!-- Mostrar detalle y valor original (solo lectura) -->
          <div class="mb-3">
            <label class="form-label">Deuda Actual</label>
            <h2><p id="originalCreditValue" class="form-control-plaintext"></p></h2>
          </div>
          <div class="mb-3">
            <label for="editCreditFecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="editCreditFecha" name="fecha" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Detalle</label>
            <p id="creditDetail" class="form-control-plaintext"></p>
          </div>
			
			<!-- Campo para ingresar el pago -->
          <div class="mb-3">
            <label for="editPago" class="form-label">Pago</label>
            <input type="number" id="editPago" name="pago" class="form-control" step="any" min="0" placeholder="Ingrese el pago" required>
          </div>
			
		<!-- Mostrar el valor restante (calculado en tiempo real) -->
          <div class="mb-3">
            <h2><p id="remainingValueDisplay" class="form-control-plaintext"></p></h2>
          </div>
			
          <!-- Nuevo: Selección de Medio de Pago -->
          <div class="mb-3">
            <label for="editCreditPaymentMethod" class="form-label">Medio de Pago</label>
            <select name="paymentMethod" id="editCreditPaymentMethod" class="form-select" required>
              <option value="">Seleccione medio de pago</option>
              <option value="Efectivo">Efectivo</option>
              <option value="Transferencia">Transferencia</option>
            </select>
          </div>
          <!-- Nuevo: Selección de Banco (se muestra solo si es Transferencia) -->
          <div class="mb-3" id="editCreditBankDiv" style="display: none;">
            <label for="editCreditBankSelection" class="form-label">Seleccione Banco</label>
            <select name="bank" id="editCreditBankSelection" class="form-select">
              <option value="">Seleccione banco</option>
              <option value="Bancolombia">Bancolombia</option>
              <option value="Daviplata">Daviplata</option>
              <option value="Nequi">Nequi</option>
              <option value="Davivienda">Davivienda</option>
            </select>
          </div>
          
          
          <!-- Otros campos editables -->
          <div class="mb-3">
            <label for="editCreditFechaLimite" class="form-label">Fecha Límite</label>
            <input type="date" class="form-control" id="editCreditFechaLimite" name="fecha_limite">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
			
			<?php if(!$caja_id): ?>
				<div class="alert alert-danger">
      			No tienes una caja abierta. Por favor abre la caja para continuar con las ventas.
    			</div>
			<?php else: ?>
          		<button type="submit" class="btn btn-primary">Aplicar Pago</button>			
			<?php endif; ?>
			
			
			
        </div>
      </form>
    </div>
  </div>
</div>


