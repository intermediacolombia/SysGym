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
              <option value="">Seleccione banco</option>
              <option value="Bancolombia">Bancolombia</option>
              <option value="Daviplata">Daviplata</option>
              <option value="Nequi">Nequi</option>
              <option value="Davivienda">Davivienda</option>
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
<script>
$(document).ready(function(){

  /*───────────────────────────────────────────────
   * 1. SELECCIONAR / DESELECCIONAR TODOS
   *───────────────────────────────────────────────*/
  $('#selectAllCredits').on('change', function(){
    const checked = this.checked;
    $('#creditos-table tbody input[type="checkbox"]').prop('checked', checked);
    togglePagoMasivoButton();
  });

  /*───────────────────────────────────────────────
   * 2. MOSTRAR BOTÓN SOLO SI HAY 2+ SELECCIONADOS
   *───────────────────────────────────────────────*/
  $(document).on('change', '#creditos-table tbody input[type="checkbox"]', function(){
    togglePagoMasivoButton();
  });

  function togglePagoMasivoButton(){
    const count = $('#creditos-table tbody input[type="checkbox"]:checked').length;
    $('#btnPagarCreditosWrapper').toggle(count >= 2);
  }

  /*───────────────────────────────────────────────
   * 3. ABRIR MODAL DE PAGO MASIVO
   *───────────────────────────────────────────────*/
  $('#btnPagarCreditos').on('click', function(){
    const seleccionados = [];
    $('#creditos-table tbody input[type="checkbox"]:checked').each(function(){
      const tr = $(this).closest('tr');
      const id = $(this).data('id');
      const fecha = tr.find('td:eq(1)').text().trim();
      const valorTxt = tr.find('td:eq(2)').text().replace(/[^\d,.-]/g, '').trim();
      const valor = parseFloat(valorTxt.replace('.', '').replace(',', '.')) || 0;
      const limite = tr.find('td:eq(3)').text().trim();
      const desc = tr.find('td:eq(4)').text().trim();
      seleccionados.push({ id, fecha, valor, limite, desc });
    });

    if (seleccionados.length < 2) {
      Swal.fire('Atención', 'Debes seleccionar al menos 2 créditos.', 'warning');
      return;
    }

    // Generar tabla HTML dentro del modal
    let html = `<table class="table table-sm table-bordered mb-0">
      <thead class="table-success">
        <tr><th>ID</th><th>Fecha</th><th>Valor</th><th>Fecha Límite</th><th>Descripción</th></tr>
      </thead><tbody>`;
    let total = 0;
    seleccionados.forEach(c => {
      total += c.valor;
      html += `<tr>
        <td>${c.id}</td>
        <td>${c.fecha}</td>
        <td>$${c.valor.toLocaleString('es-CO')}</td>
        <td>${c.limite}</td>
        <td>${c.desc}</td>
      </tr>`;
    });
    html += `</tbody></table>
      <div class="text-end mt-3">
        <h4>Total a Pagar: <span class="text-success">$${total.toLocaleString('es-CO')}</span></h4>
      </div>`;

    $('#creditosSeleccionadosContainer').html(html);

    // Guardar IDs seleccionados globalmente
    window.creditosSeleccionadosIds = seleccionados.map(c => c.id);

    const modal = new bootstrap.Modal(document.getElementById('pagoMasivoModal'));
    modal.show();
  });

  /*───────────────────────────────────────────────
   * 4. MOSTRAR BANCO SOLO SI ES TRANSFERENCIA
   *───────────────────────────────────────────────*/
  $('#pagoMasivoMetodo').on('change', function(){
    $('#pagoMasivoBancoDiv').toggle($(this).val() === 'Transferencia');
  });

  /*───────────────────────────────────────────────
   * 5. ENVIAR PAGO MASIVO POR AJAX
   *───────────────────────────────────────────────*/
  $('#formPagoMasivo').on('submit', function(e){
    e.preventDefault();

    const metodo = $('#pagoMasivoMetodo').val();
    const banco = $('#pagoMasivoBanco').val();
    const ids = window.creditosSeleccionadosIds;

    if (!metodo || !ids || ids.length < 2) {
      Swal.fire('Error', 'Debe seleccionar al menos 2 créditos y un método de pago.', 'error');
      return;
    }

    Swal.fire({
      title: 'Confirmar Pago',
      text: `Se aplicará el pago a ${ids.length} crédito(s) seleccionados.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, confirmar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (!result.isConfirmed) return;

      $.ajax({
        url: 'pagar_creditos_masivo.php',
        method: 'POST',
        data: { ids: ids, paymentMethod: metodo, bank: banco },
        dataType: 'json',
        success: function(res){
          if (res.status === 'success') {
            Swal.fire('Éxito', res.message, 'success').then(()=>{
              $('#pagoMasivoModal').modal('hide');
              $('#creditos-table').DataTable().ajax.reload(null, false);
            });
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        },
        error: function(){
          Swal.fire('Error', 'Error en la comunicación con el servidor.', 'error');
        }
      });
    });
  });
});

</script>

