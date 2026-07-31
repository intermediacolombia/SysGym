<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
<h3><i class="fa fa-credit-card"></i> Créditos Activos</h3>


<!-- Botón visible solo si hay créditos seleccionados -->
<div id="btnPagarCreditosWrapper" style="display:none; text-align:right; margin-bottom:10px;">
  <button id="btnPagarCreditos" class="btn btn-success">
    <i class="fa fa-dollar-sign"></i> Pagar Créditos Seleccionados
  </button>
</div>
<hr>


<table id="creditos-table" class="table table-striped table-bordered align-middle">
  <thead style="background-color:#d81f1f; color:white;">
    <tr>
      <th style="width:40px;"><input type="checkbox" id="selectAllCredits"></th>
      <th>Fecha</th>
      <th>Valor</th>
      <th>Fecha Límite</th>
      <th>Descripción</th>
    </tr>
  </thead>
  <tbody>
    <!-- Aquí se cargan dinámicamente tus créditos -->
  </tbody>
</table>
<?php endif; ?>



<!-- Modal: Pago Masivo de Créditos -->
<div class="modal fade" id="pagoMasivoModal" tabindex="-1" aria-labelledby="pagoMasivoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPagoMasivo">
        <div class="modal-header">
          <h5 class="modal-title" id="pagoMasivoModalLabel">Pagar Créditos Seleccionados</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="creditosSeleccionadosContainer"></div>
          <div class="mb-3 mt-4">
            <label for="pagoMasivoMetodo" class="form-label">Método de Pago</label>
            <select id="pagoMasivoMetodo" name="paymentMethod" class="form-select" required>
              <option value="">Seleccione medio de pago</option>
              <option value="Efectivo">Efectivo</option>
              <option value="Transferencia">Transferencia</option>
            </select>
          </div>
          <div class="mb-3" id="pagoMasivoBancoDiv" style="display:none;">
            <label for="pagoMasivoBanco" class="form-label">Seleccione Banco</label>
            <select id="pagoMasivoBanco" name="bank" class="form-select">
              <?= getBancosOptions() ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
			<?php if(!$caja_id): ?>
				<div class="alert alert-danger">
      			No tienes una caja abierta. Por favor abre la caja para continuar con las ventas.
    			</div>
			<?php else: ?>
          <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Confirmar Pago</button>
			<?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para Editar Crédito -->

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
			
          <!-- Selección de Medio de Pago -->
          <div class="mb-3">
            <label for="editCreditPaymentMethod" class="form-label">Medio de Pago</label>
            <select name="paymentMethod" id="editCreditPaymentMethod" class="form-select" required>
              <option value="">Seleccione medio de pago</option>
              <option value="Efectivo">Efectivo</option>
              <option value="Transferencia">Transferencia</option>
            </select>
          </div>
          <!-- Selección de Banco (se muestra solo si es Transferencia) -->
          <div class="mb-3" id="editCreditBankDiv" style="display: none;">
            <label for="editCreditBankSelection" class="form-label">Seleccione Banco</label>
            <select name="bank" id="editCreditBankSelection" class="form-select">
              <?= getBancosOptions() ?>
            </select>
          </div>
          
          <!-- Otros campos editables -->
          <div class="mb-3">
            <label for="editCreditFechaLimite" class="form-label">Fecha Límite</label>
            <input type="date" class="form-control" id="editCreditFechaLimite" name="fecha_limite" min="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <!-- BOTÓN ELIMINAR A LA IZQUIERDA -->
          <div>
            <?php if (isset($_SESSION["user_permissions"]) && in_array('Eliminar Creditos', $_SESSION["user_permissions"])): ?>
              <button type="button" id="btnDeleteCreditFromModal" class="btn btn-danger">
                <i class="fa fa-trash"></i> Eliminar Crédito
              </button>
            <?php endif; ?>
          </div>
          
          <!-- BOTONES CANCELAR Y GUARDAR A LA DERECHA -->
          <div>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <?php if(!$caja_id): ?>
              <span class="text-danger ms-2"><i class="fa fa-exclamation-circle"></i> Sin caja abierta</span>
            <?php else: ?>
              <button type="submit" class="btn btn-primary">Aplicar Pago</button>			
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para Confirmar Eliminación de Crédito -->
<div class="modal fade" id="deleteCreditModal" tabindex="-1" aria-labelledby="deleteCreditModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteCreditModalLabel">
          <i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="deleteCreditId">
        <p>¿Está seguro que desea eliminar este crédito?</p>
        <div class="alert alert-warning">
          <strong>Cliente:</strong> <span id="deleteCreditCliente"></span><br>
          <strong>Valor:</strong> $<span id="deleteCreditValue"></span><br>
          <strong>Descripción:</strong> <span id="deleteCreditDetail"></span><br>
          <strong>Fecha:</strong> <span id="deleteCreditFecha"></span>
        </div>
        <p class="text-muted">
          <small><i class="fa fa-info-circle"></i> Esta acción marcará el crédito como eliminado y no aparecerá en los listados.</small>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fa fa-times"></i> Cancelar
        </button>
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Eliminar Creditos', $_SESSION["user_permissions"])): ?>
          <button type="button" id="confirmDeleteCredit" class="btn btn-danger">
            <i class="fa fa-trash"></i> Eliminar
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

