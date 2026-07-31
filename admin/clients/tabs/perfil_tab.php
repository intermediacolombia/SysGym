<div class="container-fluid py-4">
  <div class="row g-4 align-items-start">

    <!-- COLUMNA IZQUIERDA (Foto + Nombre + Estado + Contacto) -->
    <div class="col-lg-4 col-md-5">
      <div class="card border-0 shadow-sm p-4 text-center h-100">
        <!-- Foto circular -->
        <div class="position-relative mx-auto mb-3">
          <img
            id="clienteFoto"
            src="<?php echo !empty($cliente['imagen_perfil']) ? '../../uploads/clientes/' . htmlspecialchars($cliente['imagen_perfil']) : '../../assets/img/default-user.png'; ?>"
            alt="Foto de perfil"
            class="foto-perfil shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#fotoModal">
        </div>

        <!-- Nombre -->
        <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($cliente['nombres'] . " " . $cliente['apellidos']); ?></h3>

        <!-- Estado -->
        <span class="badge fs-6 px-3 py-2 mb-3 <?php echo ($cliente['congelado'] == 1) ? '' : (($cliente['estado'] === 'activo') ? 'bg-success' : 'bg-danger'); ?>"
          <?php echo ($cliente['congelado'] == 1) ? 'style="background-color: #31D2F0;"' : ''; ?>>
          <?php echo ($cliente['congelado'] == 1) ? 'Congelado' : (($cliente['estado'] === 'activo') ? 'Activo' : 'Inactivo'); ?>
        </span>

        <hr>

        <!-- Contacto -->
        <div class="text-start px-3 small">
          <p><i class="fa fa-envelope text-success me-2"></i><?php echo htmlspecialchars($cliente['email']); ?></p>
          <p><i class="fa fa-phone text-success me-2"></i>+<?php echo htmlspecialchars($cliente['dialCode']); ?> <?php echo htmlspecialchars($cliente['telefono']); ?></p>
          <p><i class="fa fa-map-marker text-success me-2"></i><?php echo htmlspecialchars($cliente['direccion']); ?></p>
        </div>

        <hr>

        <!-- Datos de emergencia -->
        <div class="text-start px-3 small">
          <p><i class="fa fa-user-md text-success me-2"></i><strong>Contacto Emergencia:</strong> <?php echo htmlspecialchars($cliente['contacto_emergencia']); ?></p>
          <p><i class="fa fa-phone-square text-success me-2"></i>+<?php echo htmlspecialchars($cliente['dialCodeEmergencia']); ?> <?php echo htmlspecialchars($cliente['numero_emergencia']); ?></p>
        </div>
      </div>
    </div>

    <!-- COLUMNA DERECHA (tarjetas de info) -->
    <div class="col-lg-8 col-md-7">
      <div class="row g-3">

        <!-- DATOS PERSONALES -->
        <div class="col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white fw-bold">Datos Personales</div>
            <div class="card-body small">
              <p><strong>Identificación:</strong> <?php echo htmlspecialchars($cliente['identificacion']); ?></p>
              <p><strong>Fecha Nacimiento:</strong>
                <?= fechaBonita($cliente['fecha_nacimiento']); ?>
              </p>
              <?php if($edad !== ""): ?>
                <p><strong>Edad:</strong> <?php echo $edad; ?> años</p>
              <?php endif; ?>
              <p><strong>Género:</strong> <?php echo htmlspecialchars($cliente['genero']); ?></p>
              <p><strong>Registro:</strong> <?= fechaBonita($cliente['created_at']); ?></p>
              <p><strong>Última actualización:</strong> <?= fechaBonita($cliente['updated_at']);?></p>
            </div>
          </div>
        </div>

        <!-- INFORMACIÓN MÉDICA -->
        <div class="col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white fw-bold">Información Médica</div>
            <div class="card-body small">
              <p><strong>RH:</strong> <?php echo htmlspecialchars($cliente['rh']); ?></p>
              <p><strong>EPS:</strong> <?php echo htmlspecialchars($cliente['eps']); ?></p>
              <p><strong>Fracturas:</strong> <?php echo htmlspecialchars($cliente['fracturas']); ?></p>
              <p><strong>Alergias:</strong> <?php echo htmlspecialchars($cliente['alergias']); ?></p>
              <p><strong>Enfermedades:</strong> <?php echo htmlspecialchars($cliente['enfermedades_actuales']); ?></p>
              <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($cliente['observaciones']); ?></p>
            </div>
          </div>
        </div>

        <!-- PLAN -->
<div class="col-12">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-success text-white fw-bold">Plan Actual</div>
    <div class="card-body small">
      <?php if ($planInfo): ?>
        <div class="row">
          <div class="col-md-6">
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($planInfo['nombre']); ?></p>
            <p><strong>Precio:</strong> $<?php echo htmlspecialchars($planInfo['precio']); ?></p>
            <p><strong>Certificado:</strong>
              <?php if ($cliente['estado'] == 'activo' && isset($_SESSION["user_permissions"]) && in_array('Generear Certificados', $_SESSION["user_permissions"])): ?>
                <a href="/pdf/?type=cert&id=<?php echo $cliente['id']; ?>" class="text-success fw-bold"><i class="fa fa-file-pdf-o"></i> Generar</a>
              <?php endif; ?>
            </p>
          </div>
          <div class="col-md-6">
            <p><strong>Pago:</strong> <span id="fecha_pago_display"><?= fechaBonita($cliente['pago_plan']); ?></span></p>
            <p><strong>Vencimiento:</strong>
					<?php if (!empty($cliente['vencimiento_plan'])): ?>
						<span id="fecha_vencimiento_display" class="<?= $vencido ? 'text-danger fw-bold' : '' ?>">
							<?= fechaBonita($cliente['vencimiento_plan']); ?>
						</span>
					<?php else: ?>
						No registrado
					<?php endif; ?>
				</p>

				<?php if ($esTiquetera && $planInfo): ?>
				<p><strong>Entradas:</strong>
					<span class="<?= $entradasConsumidas >= $planInfo['limite_entradas'] ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
						<?= $entradasConsumidas ?>/<?= $planInfo['limite_entradas'] ?>
					</span>
				</p>
				<?php endif; ?>

          </div>
        </div>

        <!-- ALERTA DE CAJA CERRADA -->
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Usar Cajas', $_SESSION["user_permissions"])): ?>
          <?php if(!$caja_id): ?>
            <div class="alert alert-danger mt-3 mb-0">
              No tienes una caja abierta. Por favor abre la caja para continuar con las ventas.
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <!-- BOTONES DEL PLAN -->
        <div class="mt-3 d-flex flex-wrap gap-2">
          <?php if ($cliente['congelado'] == 1): ?>
            <button id="btnUnFreezePlan" class="btn btn-info"><i class="fa fa-thermometer-4"></i> Descongelar Plan</button>
            <p class="mb-0 ms-2"><strong>Fecha de Congelado:</strong> <?= fechaBonita($cliente['fecha_congelado']); ?></p>
          <?php else: ?>
            <?php if (isset($_SESSION["user_permissions"]) && in_array('Usar Cajas', $_SESSION["user_permissions"]) && $caja_id): ?>
              <button id="btnPago" class="btn btn-success"><i class="fa fa-money"></i> Marcar Pago</button>
            <?php endif; ?>

            <?php if ($cliente['estado'] == 'activo'): ?>
              <?php if (isset($_SESSION["user_permissions"]) && in_array('Congelar Planes', $_SESSION["user_permissions"])): ?>
                <button id="btnFreezePlan" class="btn btn-info text-white"><i class="fa fa-snowflake-o"></i> Congelar Plan</button>
              <?php endif; ?>
              <?php if (isset($_SESSION["user_permissions"]) && in_array('Anular Planes', $_SESSION["user_permissions"])): ?>
                <button id="btnNullPlan" class="btn btn-warning"><i class="fa fa-ban"></i> Anular Plan</button>
              <?php endif; ?>
              <?php if (isset($_SESSION["user_permissions"]) && in_array('Transferir Planes', $_SESSION["user_permissions"])): ?>
                <button id="btnTransferPlan" class="btn btn-outline-secondary"><i class="fas fa-random"></i> Transferir Plan</button>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p>Sin plan asignado</p>
      <?php endif; ?>
    </div>
  </div>
</div>

        <!-- CONSENTIMIENTO Y NOTIFICACIONES -->
  <div class="col-12">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-success text-white fw-bold">Consentimiento y Notificaciones</div>
    <div class="card-body small">
      <p><strong>Recibe Notificaciones:</strong> <?php echo ($cliente['notificaciones'] == 0) ? 'NO' : 'SÍ'; ?></p>
      <p><strong>Consentimiento informado:</strong>
        <?php if ($formId): ?>
          <a href="/pdf/?type=consent&id=<?php echo urlencode($formId); ?>" class="text-success fw-bold">
            <i class="fa fa-file-pdf-o"></i> Descargar
          </a>
          <?php if (isset($_SESSION["user_permissions"]) && in_array('Reenviar Consentimiento informado', $_SESSION["user_permissions"])): ?>
            <button id="resendConsent" class="btn btn-link text-success">
              <i class="fa fa-paper-plane"></i> Reenviar
            </button>
          <?php endif; ?>
        <?php else: ?>
          <span class="text-danger fw-bold">No firmado</span>
          <?php if (isset($_SESSION["user_permissions"]) && in_array('Reenviar Consentimiento informado', $_SESSION["user_permissions"])): ?>
            <button id="sendConsentLink" class="btn btn-link text-success">
              <i class="fa fa-whatsapp"></i> Enviar por WhatsApp
            </button>
          <?php endif; ?>
        <?php endif; ?>
      </p>

      <button type="button" class="btn btn-success mt-2"
              data-bs-toggle="modal"
              data-bs-target="#messagesModal"
              <?php echo ($cliente['congelado'] == 1) ? 'disabled' : ''; ?>>
        <i class="fa fa-whatsapp"></i> Ver Mensajes Enviados
      </button>
    </div>
  </div>
</div>


      </div>
    </div>

  </div>
</div>


<!-- Modal Enviados -->
<div class="modal fade" id="messagesModal" tabindex="-1" aria-labelledby="messagesModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="max-width:80%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="messagesModalLabel">
          <i class="fa fa-whatsapp"></i> Mensajes Enviados a <?php echo htmlspecialchars($cliente['nombres']); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Tabla de mensajes -->
        <table id="mensajesTable" class="display" style="width:100%">
          <thead>
            <tr>
              <th>Teléfono</th>
              <th>Texto</th>
              <th>Status</th>
              <th>Status Info</th>
              <th>Delivery</th>
              <th>Fecha Creación</th>
              <th>Fecha Ejecución</th>
              <th>URL</th>
            </tr>
          </thead>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>


<!-- Modal para Selección de Medio de Pago -->	
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalLabel">Seleccione Medio de Pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <p id="valorPagar">
            Valor a Pagar: <strong id="originalTotalDisplay">$<?php echo isset($planInfo['precio']) ? number_format($planInfo['precio'], 0, ',', '.') : '0'; ?></strong>
          </p>
        </div>

        <form id="paymentForm">
          <!-- Primer medio de pago -->
          <div class="mb-3">
            <label for="payment_method" class="form-label">Medio de Pago</label>
            <select name="paymentMethod" id="payment_method" class="form-select" required>
              <option value="">Seleccione medio de pago</option>
              <option value="Efectivo">Efectivo</option>
              <option value="Transferencia">Transferencia</option>
            </select>
          </div>

          <div class="mb-3" id="bankDiv" style="display: none;">
            <label for="bank" class="form-label">Banco</label>
            <select name="bank" id="bank" class="form-select">
             <?= getBancosOptions() ?>
            </select>
          </div>

          <div class="mb-3" id="firstPaymentValueWrapper" style="display: none;">
            <label for="first_payment_value" class="form-label">Valor Primer Pago</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" name="first_payment_value" id="first_payment_value" class="form-control" min="0" step="any" placeholder="Ingrese monto del primer pago">
            </div>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="split_payment_check">
            <label class="form-check-label" for="split_payment_check">Dividir Pago</label>
          </div>

          <div id="secondPaymentWrapper" style="display: none;">
            <div class="mb-3">
              <label for="second_payment_method" class="form-label">Segundo Medio de Pago</label>
              <select name="secondPaymentMethod" id="second_payment_method" class="form-select">
                <option value="">Seleccione segundo medio de pago</option>
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia">Transferencia</option>
              </select>
            </div>

            <div class="mb-3" id="secondBankDiv" style="display: none;">
              <label for="second_bank" class="form-label">Banco para segundo pago</label>
              <select name="secondBank" id="second_bank" class="form-select">
               <?= getBancosOptions() ?>
              </select>
            </div>

            <div class="mb-3" id="secondPaymentValueWrapper">
              <label for="second_payment_value" class="form-label">Valor Segundo Pago</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="second_payment_value" id="second_payment_value" class="form-control" min="0" step="any" placeholder="Ingrese monto del segundo pago">
              </div>
            </div>
          </div>

          <!-- Crédito -->
          <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="credit_check">
              <label class="form-check-label" for="credit_check">Crédito</label>
            </div>

            <div id="creditFields" style="display: none;">
              <div class="mb-3">
                <label for="valor_pagado" class="form-label">Valor Pagado</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" id="valor_pagado" class="form-control" step="any" min="0" placeholder="Ingrese valor pagado">
                </div>
              </div>
              <div class="mb-3">
                <p id="valorRestanteDisplay">
                  Valor Restante: $<?php echo isset($planInfo['precio']) ? number_format($planInfo['precio'], 0, ',', '.') : '0'; ?>
                </p>
              </div>
              <div class="mb-3">
                <label for="fecha_plazo" class="form-label">Fecha de Plazo</label>
                <input type="date" id="fecha_plazo" class="form-control" min="<?= date('Y-m-d') ?>">
              </div>
            </div>
          <?php endif; ?>

          <!-- ✅ NUEVO: Respetar fechas guardadas -->
<div class="mb-3 form-check mt-4">
  <input type="checkbox" class="form-check-input" id="respetarFechas" name="respetarFechas" value="1">
  <label class="form-check-label" for="respetarFechas">
    Respetar fechas guardadas (no recalcular vencimiento)
  </label>
</div>

<!-- Mostrar fechas actuales del cliente -->
<div id="fechasActualesInfo" class="alert alert-light border mt-2 mb-0" style="display: none;">
  <p class="mb-1">
    <strong>Fecha de pago actual:</strong>
    <span id="fechaPagoActual">--</span>
  </p>
  <p class="mb-0">
    <strong>Fecha de vencimiento actual:</strong>
    <span id="fechaVencimientoActual">--</span>
  </p>
</div>


        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="confirmPaymentBtn">Pagar</button>
      </div>
    </div>
  </div>
</div>	
<!--fin modal-->

	 <!-- Modal para mostrar foto en grande -->
<div class="modal fade" id="fotoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark">
      <div class="modal-body text-center p-0">
        <img id="fotoAmpliada" src="" alt="Foto del cliente" class="w-100 h-auto">
      </div>
      <div class="modal-footer justify-content-center bg-dark border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
          <i class="fa fa-times"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>


<!-- ============================================================
     MODAL CONGELAR PLAN — nueva lógica de rango de fechas
     Incluir en la vista del cliente (detail.php o donde estén los botones)
     ============================================================ -->

<style>
  #freezeModal .modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    overflow: hidden;
  }
  #freezeModal .modal-header {
    background: linear-gradient(135deg, #31D2F0 0%, #0ea5e9 100%);
    border: none;
    padding: 1.5rem 1.75rem;
  }
  #freezeModal .modal-title {
    color: #fff;
    font-weight: 700;
    font-size: 1.15rem;
  }
  #freezeModal .modal-header .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
  }
  #freezeModal .modal-body { padding: 1.75rem; }

  .freeze-info-box {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    font-size: 0.875rem;
    color: #0369a1;
    line-height: 1.6;
  }
  .freeze-info-box strong { color: #075985; }
  .freeze-info-box.bloqueado {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
  }
  .freeze-info-box.loading {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #64748b;
  }

  .freeze-date-label {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: #1e293b;
  }
  .freeze-date-input {
    width: 100%;
    padding: 0.7rem 1rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    color: #1e293b;
    background: #f8fafc;
    outline: none;
    transition: border-color 0.2s;
  }
  .freeze-date-input:focus { border-color: #31D2F0; background: #fff; }
  .freeze-date-input:disabled { opacity: 0.5; cursor: not-allowed; }

  .freeze-days-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.8rem;
    background: rgba(49,210,240,0.12);
    color: #0284c7;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    margin-top: 0.6rem;
  }

  #btnConfirmFreeze {
    background: linear-gradient(135deg, #31D2F0, #0ea5e9);
    border: none;
    border-radius: 12px;
    padding: 0.7rem 1.5rem;
    font-weight: 600;
    color: #fff;
    transition: opacity 0.2s;
  }
  #btnConfirmFreeze:hover  { opacity: 0.85; }
  #btnConfirmFreeze:disabled { opacity: 0.4; cursor: not-allowed; }
</style>

<!-- MODAL -->
<div class="modal fade" id="freezeModal" tabindex="-1" aria-labelledby="freezeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="freezeModalLabel">
          <i class="fa fa-snowflake-o me-2"></i>Congelar Plan
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="freezeInfoBox" class="freeze-info-box loading">
          <i class="fa fa-spinner fa-spin me-1"></i> Cargando información...
        </div>

        <div id="freezeFechaWrap" style="display:none;">
          <div class="freeze-date-label">
            <i class="fa fa-calendar me-1"></i>
            ¿Desde qué fecha deseas congelar?
          </div>
          <input type="date" id="freezeFechaInicio" class="freeze-date-input">
          <div id="freezeDaysBadge" class="freeze-days-badge" style="display:none;">
            <i class="fa fa-info-circle"></i>
            <span id="freezeDaysText"></span>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn" id="btnConfirmFreeze" disabled>
          <i class="fa fa-snowflake-o me-1"></i> Congelar Plan
        </button>
      </div>

    </div>
  </div>
</div>


<!-- ============================================================
     SCRIPT
     ============================================================ -->
<script>
(function () {

  // ── Datos PHP → JS ───────────────────────────────────────────
  const DAYS_FROZEN = <?= (int)(DAYS_ALLOWED_FROZEN) ?>;
  const HOY         = '<?= $hoy ?>';
  const PAGO_PLAN   = '<?= $cliente['pago_plan'] ?? $hoy ?>';
  const VENCIMIENTO = '<?= $cliente['vencimiento_plan'] ?? $hoy ?>';
  const CLIENTE_ID  = <?= (int)$cliente['id'] ?>;

  // ── Helpers ──────────────────────────────────────────────────
  function addDays(dateStr, n) {
    const d = new Date(dateStr + 'T00:00:00');
    d.setDate(d.getDate() + n);
    return d.toISOString().slice(0, 10);
  }
  function subDays(dateStr, n) {
    return addDays(dateStr, -n);
  }
  function diffDays(from, to) {
    return Math.round((new Date(to + 'T00:00:00') - new Date(from + 'T00:00:00')) / 86400000);
  }
  function maxDate(...dates) {
    return dates.filter(Boolean).sort().reverse()[0];
  }
  function buildFormData(obj) {
    const fd = new FormData();
    Object.entries(obj).forEach(([k, v]) => fd.append(k, v));
    return fd;
  }
  // ── Abrir modal: carga última asistencia y calcula rango ─────
  document.getElementById('btnFreezePlan')?.addEventListener('click', () => {
    const infoBox    = document.getElementById('freezeInfoBox');
    const fechaWrap  = document.getElementById('freezeFechaWrap');
    const fechaInput = document.getElementById('freezeFechaInicio');
    const badge      = document.getElementById('freezeDaysBadge');
    const btnConfirm = document.getElementById('btnConfirmFreeze');

    // Reset
    infoBox.className  = 'freeze-info-box loading';
    infoBox.innerHTML  = '<i class="fa fa-spinner fa-spin me-1"></i> Cargando información...';
    fechaWrap.style.display = 'none';
    badge.style.display     = 'none';
    btnConfirm.disabled     = true;

    const modal = new bootstrap.Modal(document.getElementById('freezeModal'));
    modal.show();

    // ── Fetch última asistencia del cliente
    fetch(`get_asistencias.php?cliente_id=${CLIENTE_ID}`)
      .then(r => r.json())
      .then(res => {
        const asistencias = res.data || [];

        // Última asistencia dentro del rango del plan actual (pago_plan → vencimiento)
        const ultimaEnRango = asistencias.find(a => a.fecha >= PAGO_PLAN && a.fecha <= VENCIMIENTO);

        // ── Calcular fecha MÍNIMA
        const minFecha = ultimaEnRango
        ? maxDate(HOY, PAGO_PLAN, addDays(ultimaEnRango.fecha, 1))
        : PAGO_PLAN;

        // ── Calcular fecha MÁXIMA: vencimiento - DAYS_FROZEN días
        // Si DAYS_FROZEN = 0 → máximo es el día antes del vencimiento
        const maxFecha = DAYS_FROZEN > 0
          ? subDays(VENCIMIENTO, DAYS_FROZEN)
          : subDays(VENCIMIENTO, 1);

        // ── Verificar que el rango sea válido
        const diasHastaVenc = diffDays(HOY, VENCIMIENTO);

        if (DAYS_FROZEN > 0 && diasHastaVenc < DAYS_FROZEN) {
          infoBox.className = 'freeze-info-box bloqueado';
          infoBox.innerHTML = `
            <i class="fa fa-ban me-1"></i>
            No se puede congelar: quedan <strong>${diasHastaVenc} día(s)</strong>
            para el vencimiento y se requieren al menos <strong>${DAYS_FROZEN} días</strong> restantes.
          `;
          btnConfirm.disabled = true;
          return;
        }

        if (minFecha > maxFecha) {
          infoBox.className = 'freeze-info-box bloqueado';
          infoBox.innerHTML = `
            <i class="fa fa-ban me-1"></i>
            No hay un rango de fechas válido para congelar el plan en este momento.
            ${ultimaEnRango ? `<br>La última asistencia fue el <strong>${ultimaEnRango.fecha}</strong>.` : ''}
          `;
          btnConfirm.disabled = true;
          return;
        }

        // ── Rango válido: configurar input
        const defFecha = (HOY >= minFecha && HOY <= maxFecha) ? HOY : minFecha;

        fechaInput.min   = minFecha;
        fechaInput.max   = maxFecha;
        fechaInput.value = defFecha;
        fechaWrap.style.display = 'block';
        btnConfirm.disabled = false;
        infoBox.className = 'freeze-info-box';

        let infoHTML = `<strong>Rango permitido:</strong> ${minFecha} → ${maxFecha}`;
        if (ultimaEnRango) {
          infoHTML += `<br><i class="fa fa-check-circle me-1 text-success"></i>Última asistencia en el período: <strong>${ultimaEnRango.fecha}</strong> — mínimo desde el día siguiente.`;
        }
        if (DAYS_FROZEN > 0) {
          infoHTML += `
            <br><br><i class="fa fa-info-circle me-1"></i>
            Si el congelamiento dura <strong>menos de ${DAYS_FROZEN} días</strong>,
            el vencimiento <strong>no se correrá</strong> al descongelar.`;
        }
        infoBox.innerHTML = infoHTML;

        actualizarBadge();
      })
      .catch(() => {
        // Si falla el fetch de asistencias, continuar con lógica básica
        const diasHastaVenc = diffDays(HOY, VENCIMIENTO);

        if (DAYS_FROZEN > 0 && diasHastaVenc < DAYS_FROZEN) {
          infoBox.className = 'freeze-info-box bloqueado';
          infoBox.innerHTML = `
            <i class="fa fa-ban me-1"></i>
            No se puede congelar: quedan <strong>${diasHastaVenc} día(s)</strong>get
            para el vencimiento y se requieren al menos <strong>${DAYS_FROZEN} días</strong>.
          `;
          return;
        }

        const minFecha = maxDate(HOY, PAGO_PLAN);
        const maxFecha = DAYS_FROZEN > 0 ? subDays(VENCIMIENTO, DAYS_FROZEN) : subDays(VENCIMIENTO, 1);
        fechaInput.min   = minFecha;
        fechaInput.max   = maxFecha;
        fechaInput.value = minFecha;
        fechaWrap.style.display = 'block';
        btnConfirm.disabled = false;
        infoBox.className = 'freeze-info-box';
        infoBox.innerHTML = `<strong>Rango permitido:</strong> ${minFecha} → ${maxFecha}`;
        actualizarBadge();
      });
  });

  // ── Badge dinámico al cambiar fecha ─────────────────────────
  document.getElementById('freezeFechaInicio')?.addEventListener('change', actualizarBadge);

  function actualizarBadge() {
    const val   = document.getElementById('freezeFechaInicio').value;
    const badge = document.getElementById('freezeDaysBadge');
    const texto = document.getElementById('freezeDaysText');
    if (!val) { badge.style.display = 'none'; return; }
    const diasHastaVenc = diffDays(val, VENCIMIENTO);
    texto.textContent = `Congelamiento desde ${val} · ${diasHastaVenc} día(s) hasta el vencimiento`;
    badge.style.display = 'inline-flex';
  }

  // ── Confirmar congelar ───────────────────────────────────────
  document.getElementById('btnConfirmFreeze')?.addEventListener('click', () => {
    const fechaInicio = document.getElementById('freezeFechaInicio').value;
    if (!fechaInicio) { alert('Por favor selecciona una fecha de inicio.'); return; }

    const btn = document.getElementById('btnConfirmFreeze');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';

    fetch('freeze_plan.php', {
      method: 'POST',
      body: buildFormData({ id: CLIENTE_ID, fecha_inicio: fechaInicio })
    })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') {
        bootstrap.Modal.getInstance(document.getElementById('freezeModal'))?.hide();
        location.reload();
      } else {
        alert(data.message || 'No se pudo congelar el plan.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-snowflake-o me-1"></i> Congelar Plan';
      }
    })
    .catch(() => {
      alert('Error de conexión.');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-snowflake-o me-1"></i> Congelar Plan';
    });
  });

  // ── Descongelar ──────────────────────────────────────────────
  document.getElementById('btnUnFreezePlan')?.addEventListener('click', () => {
    Swal.fire({
      title: '¿Descongelar plan?',
      text: 'Se descongelará el plan del cliente y se recalculará el vencimiento.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, descongelar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#31D2F0',
    }).then(result => {
      if (!result.isConfirmed) return;

      const btn = document.getElementById('btnUnFreezePlan');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';

      fetch('unfreeze_plan.php', {
        method: 'POST',
        body: buildFormData({ id: CLIENTE_ID })
      })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') {
          Swal.fire('Éxito', data.message, 'success').then(() => location.reload());
        } else {
          Swal.fire('Error', data.message || 'No se pudo descongelar el plan.', 'error');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-thermometer-4 me-1"></i> Descongelar Plan';
        }
      })
      .catch(() => {
        Swal.fire('Error', 'Error de conexión.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-thermometer-4 me-1"></i> Descongelar Plan';
      });
    });
  });

})();

console.log('asistencias:', res.data);
console.log('PAGO_PLAN:', PAGO_PLAN, 'VENCIMIENTO:', VENCIMIENTO);
console.log('ultimaEnRango:', asistencias.find(a => a.fecha >= PAGO_PLAN && a.fecha <= VENCIMIENTO));
</script>