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
    <?php
    try {
        $stmtCreditos = $pdo->prepare("
            SELECT id, fecha, valor, fecha_limite, descripcion
            FROM creditos
            WHERE cliente_id = :cliente_id
              AND estado = 'pendiente'
            ORDER BY fecha DESC
        ");
        $stmtCreditos->execute([':cliente_id' => $cliente_id]);
        $creditos = $stmtCreditos->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo '<tr><td colspan="5" class="text-danger">Error al cargar créditos: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
        $creditos = [];
    }

    if ($creditos):
      foreach ($creditos as $credito): ?>
        <tr>
          <td><input type="checkbox" class="credit-checkbox" data-id="<?= $credito['id'] ?>"></td>
          <td><?= htmlspecialchars($credito['fecha']) ?></td>
          <td>$<?= number_format($credito['valor'], 0, ',', '.') ?></td>
          <td><?= htmlspecialchars($credito['fecha_limite']) ?></td>
          <td><?= htmlspecialchars($credito['descripcion']) ?></td>
        </tr>
      <?php endforeach;
    else: ?>
      <tr><td colspan="5" class="text-center text-muted">No hay créditos pendientes</td></tr>
    <?php endif; ?>
  </tbody>
</table>
<?php endif; ?>


<!-- ======================================================
     Modal: Pago Masivo de Créditos
====================================================== -->
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
            <div class="alert alert-danger mb-0">
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


<!-- ======================================================
     Modal: Editar Crédito
====================================================== -->
<div class="modal fade" id="editCreditModal" tabindex="-1" aria-labelledby="editCreditModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editCreditForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editCreditModalLabel">Editar Crédito</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editCreditId" name="credit_id">
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
          <div class="mb-3">
            <label for="editPago" class="form-label">Pago</label>
            <input type="number" id="editPago" name="pago" class="form-control" step="any" min="0" placeholder="Ingrese el pago" required>
          </div>
          <div class="mb-3">
            <h2><p id="remainingValueDisplay" class="form-control-plaintext"></p></h2>
          </div>
          <div class="mb-3">
            <label for="editCreditPaymentMethod" class="form-label">Medio de Pago</label>
            <select name="paymentMethod" id="editCreditPaymentMethod" class="form-select" required>
              <option value="">Seleccione medio de pago</option>
              <option value="Efectivo">Efectivo</option>
              <option value="Transferencia">Transferencia</option>
            </select>
          </div>
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


<!-- ======================================================
     SCRIPT PRINCIPAL
====================================================== -->
<!--script>
$(document).ready(function(){

  // Seleccionar/deseleccionar todos
  $('#selectAllCredits').on('change', function(){
    const checked = this.checked;
    $('#creditos-table tbody input[type="checkbox"]').prop('checked', checked);
    togglePagoMasivoButton();
  });

  // Mostrar botón solo si hay más de 1 seleccionado
  $(document).on('change', '#creditos-table tbody input[type="checkbox"]', togglePagoMasivoButton);

  function togglePagoMasivoButton(){
    const count = $('#creditos-table tbody input[type="checkbox"]:checked').length;
    $('#btnPagarCreditosWrapper').toggle(count >= 2);
  }

  // Abrir modal de pago masivo
  $('#btnPagarCreditos').on('click', function(e){
    e.preventDefault();
    const seleccionados = [];

    $('#creditos-table tbody input[type="checkbox"]:checked').each(function(){
      const tr = $(this).closest('tr');
      const id = $(this).data('id');
      const fecha = tr.find('td:eq(1)').text().trim();
      const valorTxt = tr.find('td:eq(2)').text().replace(/[^\d,.-]/g, '').trim();
      const valor = parseFloat(valorTxt.replace(/\./g, '').replace(',', '.')) || 0;
      const limite = tr.find('td:eq(3)').text().trim();
      const desc = tr.find('td:eq(4)').text().trim();
      seleccionados.push({ id, fecha, valor, limite, desc });
    });

    if (seleccionados.length < 2) {
      Swal.fire('Atención', 'Debes seleccionar al menos 2 créditos.', 'warning');
      return;
    }

    // Generar tabla dentro del modal
    let html = `<table class="table table-sm table-bordered mb-0">
      <thead class="table-success">
        <tr><th>ID</th><th>Fecha</<th><th>Valor</th><th>Fecha Límite</th><th>Descripción</th></tr>
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
    window.creditosSeleccionadosIds = seleccionados.map(c => c.id);

    // Abrir modal
    const modalElement = document.getElementById('pagoMasivoModal');
    let modal = bootstrap.Modal.getInstance(modalElement);
    if (!modal) modal = new bootstrap.Modal(modalElement);
    modal.show();
  });

  // Mostrar banco si es transferencia
  $('#pagoMasivoMetodo').on('change', function(){
    const isTransferencia = $(this).val() === 'Transferencia';
    $('#pagoMasivoBancoDiv').toggle(isTransferencia);
    if (!isTransferencia) $('#pagoMasivoBanco').val('');
  });

  // Enviar pago masivo
  $('#formPagoMasivo').on('submit', function(e){
    e.preventDefault();
    const metodo = $('#pagoMasivoMetodo').val();
    const banco = $('#pagoMasivoBanco').val();
    const ids = window.creditosSeleccionadosIds;

    if (!metodo || !ids || ids.length < 2) {
      Swal.fire('Error', 'Debe seleccionar al menos 2 créditos y un método de pago.', 'error');
      return;
    }

    if (metodo === 'Transferencia' && !banco) {
      Swal.fire('Error', 'Debe seleccionar un banco para transferencia.', 'error');
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
              const modal = bootstrap.Modal.getInstance(document.getElementById('pagoMasivoModal'));
              if (modal) modal.hide();
              location.reload();
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
</script-->
<script>
	$(document).ready(function(){
  // ===== DEBUG SETUP =====
  const DEBUG = true;
  const TAG = '[Creditos]';
  function log(){ if(DEBUG) console.log(TAG, ...arguments); }
  function warn(){ if(DEBUG) console.warn(TAG, ...arguments); }
  function err(){ console.error(TAG, ...arguments); }

  // Reportar errores JS globales
  window.onerror = function(message, source, lineno, colno, error){
    err('window.onerror =>', {message, source, lineno, colno, error});
  };
  window.addEventListener('unhandledrejection', e => {
    err('unhandledrejection =>', e.reason || e);
  });

  // Chequeos rápidos de entorno
  if (typeof $ === 'undefined') { err('jQuery NO está cargado'); return; } else { log('jQuery OK'); }
  if (typeof bootstrap === 'undefined' || !bootstrap.Modal) { err('Bootstrap 5 JS o Modal NO está cargado'); }
  if (typeof Swal === 'undefined') { warn('SweetAlert2 NO está cargado (solo afecta alertas bonitas)'); }

  // Verificar existencia de elementos clave
  const table = document.getElementById('creditos-table');
  if (!table) { err('No existe #creditos-table en DOM'); }
  const btnPagar = document.getElementById('btnPagarCreditos');
  if (!btnPagar) { err('No existe #btnPagarCreditos en DOM'); }
  const modalEl = document.getElementById('pagoMasivoModal');
  if (!modalEl) { err('No existe #pagoMasivoModal en DOM'); }

  // ===== Seleccionar/deseleccionar todos =====
  $('#selectAllCredits').on('change', function(){
    const checked = this.checked;
    log('#selectAllCredits change =>', checked);
    $('#creditos-table tbody input[type="checkbox"]').prop('checked', checked);
    togglePagoMasivoButton();
    log('Checkboxes marcados:', $('#creditos-table tbody input[type="checkbox"]:checked').length);
  });

  // ===== Mostrar botón solo si hay más de 1 seleccionado =====
  $(document).on('change', '#creditos-table tbody input[type="checkbox"]', function(){
    log('Cambio en checkbox individual. Marcados:',
        $('#creditos-table tbody input[type="checkbox"]:checked').length);
    togglePagoMasivoButton();
  });

  function togglePagoMasivoButton(){
    const count = $('#creditos-table tbody input[type="checkbox"]:checked').length;
    log('togglePagoMasivoButton => count:', count);
    $('#btnPagarCreditosWrapper').toggle(count >= 2);
  }

  // ===== Helper: parsear $xxx.xxx,yy a float =====
  function parseValorCO(txt) {
    const limpio = (txt || '').replace(/[^\d,.-]/g, '').trim();
    const val = parseFloat(limpio.replace(/\./g, '').replace(',', '.')) || 0;
    return val;
  }

  // ===== Helper: obtener instancia Modal (BS5) =====
  function getBsModal(modalId) {
    const el = document.getElementById(modalId);
    if (!el) { err('getBsModal: no existe #' + modalId); return null; }
    let modal = bootstrap.Modal.getInstance(el);
    if (!modal) modal = new bootstrap.Modal(el);
    return modal;
  }

  // Hook para verificar si el handler se enlaza
  $('#btnPagarCreditos').off('click.debug').on('click.debug', function(){
    log('#btnPagarCreditos: handler DEBUG enlazado OK');
  });

  // ===== Abrir modal de pago masivo (con logs) =====
  $('#btnPagarCreditos').off('click.main').on('click.main', function(e){
    e.preventDefault();
    log('#btnPagarCreditos click');

    const seleccionados = [];
    const $checks = $('#creditos-table tbody input[type="checkbox"]:checked');

    log('Checks seleccionados:', $checks.length);
    if ($checks.length === 0) {
      warn('No hay créditos marcados');
    }

    $checks.each(function(){
      const $tr    = $(this).closest('tr');
      const id     = $(this).data('id');
      const fecha  = $tr.find('td:eq(1)').text().trim();
      const valTxt = $tr.find('td:eq(2)').text();
      const valor  = parseValorCO(valTxt);
      const limite = $tr.find('td:eq(3)').text().trim();
      const desc   = $tr.find('td:eq(4)').text().trim();
      seleccionados.push({ id, fecha, valor, limite, desc });
    });

    log('Seleccionados =>', seleccionados);
    if (console.table && seleccionados.length) console.table(seleccionados);

    if (seleccionados.length < 2) {
      const msg = 'Debes seleccionar al menos 2 créditos.';
      log(msg);
      if (typeof Swal !== 'undefined') Swal.fire('Atención', msg, 'warning'); else alert(msg);
      return;
    }

    // Construir HTML
    let total = 0;
    let html = `
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-success">
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Valor</th>
            <th>Fecha Límite</th>
            <th>Descripción</th>
          </tr>
        </thead>
        <tbody>
    `;

    seleccionados.forEach(c => {
      total += c.valor;
      html += `
        <tr>
          <td>${c.id}</td>
          <td>${c.fecha}</td>
          <td>$${c.valor.toLocaleString('es-CO')}</td>
          <td>${c.limite}</td>
          <td>${c.desc}</td>
        </tr>
      `;
    });

    html += `
        </tbody>
      </table>
      <div class="text-end mt-3">
        <h4>Total a Pagar: <span class="text-success">$${total.toLocaleString('es-CO')}</span></h4>
      </div>
    `;

    $('#creditosSeleccionadosContainer').html(html);
    window.creditosSeleccionadosIds = seleccionados.map(c => c.id);
    log('IDs seleccionados =>', window.creditosSeleccionadosIds);

    // Abrir modal con try/catch
    try {
      const modal = getBsModal('pagoMasivoModal');
      if (!modal) {
        err('No se obtuvo instancia de #pagoMasivoModal');
        return;
      }

      // Reset selects antes de abrir
      $('#pagoMasivoMetodo').val('');
      $('#pagoMasivoBanco').val('');
      $('#pagoMasivoBancoDiv').hide();

      log('Mostrando modal #pagoMasivoModal…');
      modal.show();

      // Verificar evento de mostrado
      $(modalEl).one('shown.bs.modal', function(){
        log('#pagoMasivoModal => shown.bs.modal');
      });

    } catch (ex) {
      err('Excepción al abrir modal:', ex);
      if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudo abrir el modal de pago.', 'error');
    }
  });

  // ===== Mostrar banco si es transferencia =====
  $('#pagoMasivoMetodo').on('change', function(){
    const isTransferencia = $(this).val() === 'Transferencia';
    log('#pagoMasivoMetodo change =>', $(this).val());
    $('#pagoMasivoBancoDiv').toggle(isTransferencia);
    if (!isTransferencia) $('#pagoMasivoBanco').val('');
  });

  // ===== Enviar pago masivo =====
  $('#formPagoMasivo').on('submit', function(e){
    e.preventDefault();
    const metodo = $('#pagoMasivoMetodo').val();
    const banco  = $('#pagoMasivoBanco').val();
    const ids    = window.creditosSeleccionadosIds;

    log('#formPagoMasivo submit =>', { metodo, banco, ids });

    if (!metodo || !ids || ids.length < 2) {
      const msg = 'Debe seleccionar al menos 2 créditos y un método de pago.';
      log(msg);
      if (typeof Swal !== 'undefined') Swal.fire('Error', msg, 'error'); else alert(msg);
      return;
    }
    if (metodo === 'Transferencia' && !banco) {
      const msg = 'Debe seleccionar un banco para transferencia.';
      log(msg);
      if (typeof Swal !== 'undefined') Swal.fire('Error', msg, 'error'); else alert(msg);
      return;
    }

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Confirmar Pago',
        text: `Se aplicará el pago a ${ids.length} crédito(s) seleccionados.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        log('Confirm dialog result =>', result);
        if (!result.isConfirmed) return;
        enviarPagoMasivo(metodo, banco, ids);
      });
    } else {
      if (confirm(`Se aplicará el pago a ${ids.length} crédito(s) seleccionados. ¿Confirmar?`)) {
        enviarPagoMasivo(metodo, banco, ids);
      }
    }
  });

  function enviarPagoMasivo(metodo, banco, ids){
    log('AJAX -> pagar_creditos_masivo.php', { metodo, banco, ids });
    $.ajax({
      url: 'pagar_creditos_masivo.php',
      method: 'POST',
      data: { ids: ids, paymentMethod: metodo, bank: banco },
      dataType: 'json'
    })
    .done(function(res){
      log('AJAX OK', res);
      if (res && res.status === 'success') {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Éxito', res.message || 'Pago aplicado.', 'success').then(()=>{
            const modal = bootstrap.Modal.getInstance(document.getElementById('pagoMasivoModal'));
            if (modal) modal.hide();
            location.reload();
          });
        } else {
          alert('Pago aplicado.');
          const modal = bootstrap.Modal.getInstance(document.getElementById('pagoMasivoModal'));
          if (modal) modal.hide();
          location.reload();
        }
      } else {
        const msg = (res && res.message) ? res.message : 'Error desconocido del servidor.';
        if (typeof Swal !== 'undefined') Swal.fire('Error', msg, 'error'); else alert(msg);
      }
    })
    .fail(function(xhr, status, error){
      err('AJAX FAIL', {status, error, response: xhr && xhr.responseText});
      if (typeof Swal !== 'undefined') Swal.fire('Error', 'Error en la comunicación con el servidor.', 'error');
      else alert('Error en la comunicación con el servidor.');
    });
  }
});

</script>



