<div class="container-fluid py-4">
  <div class="row g-4 align-items-start">

    <!-- COLUMNA IZQUIERDA (Foto + Datos básicos) -->
    <div class="col-lg-4 col-md-5 text-center">
      <div class="card border-0 shadow-sm p-4 h-100">
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
        <h3 class="fw-bold mb-1">
          <?php echo htmlspecialchars($cliente['nombres'] . " " . $cliente['apellidos']); ?>
        </h3>

        <!-- Estado -->
        <span class="badge fs-6 px-3 py-2 mt-2 <?php echo ($cliente['congelado'] == 1) ? '' : (($cliente['estado'] === 'activo') ? 'bg-success' : 'bg-danger'); ?>"
          <?php echo ($cliente['congelado'] == 1) ? 'style="background-color: #31D2F0;"' : ''; ?>>
          <?php echo ($cliente['congelado'] == 1) ? 'Congelado' : (($cliente['estado'] === 'activo') ? 'Activo' : 'Inactivo'); ?>
        </span>

        <hr class="my-4">

        <!-- Contacto -->
        <div class="text-start px-3">
          <p class="mb-2"><i class="fa fa-envelope text-success me-2"></i> <?php echo htmlspecialchars($cliente['email']); ?></p>
          <p class="mb-2"><i class="fa fa-phone text-success me-2"></i> +<?php echo htmlspecialchars($cliente['dialCode']); ?> <?php echo htmlspecialchars($cliente['telefono']); ?></p>
          <p class="mb-2"><i class="fa fa-map-marker text-success me-2"></i> <?php echo htmlspecialchars($cliente['direccion']); ?></p>
        </div>
      </div>
    </div>

    <!-- COLUMNA DERECHA (Tarjetas informativas) -->
    <div class="col-lg-8 col-md-7">
      <div class="row g-3">

        <!-- Datos personales -->
        <div class="col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white fw-bold">Datos Personales</div>
            <div class="card-body small">
              <p><strong>Identificación:</strong> <?php echo htmlspecialchars($cliente['identificacion']); ?></p>
              <p><strong>Género:</strong> <?php echo htmlspecialchars($cliente['genero']); ?></p>
              <p><strong>Fecha Nac.:</strong>
                <?php
                  $fechaNacimiento = new DateTime($cliente['fecha_nacimiento']);
                  echo strftime("%d de %B de %Y", $fechaNacimiento->getTimestamp());
                ?>
              </p>
              <?php if($edad !== ""): ?>
                <p><strong>Edad:</strong> <?php echo $edad; ?> años</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Información médica -->
        <div class="col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white fw-bold">Información Médica</div>
            <div class="card-body small">
              <p><strong>RH:</strong> <?php echo htmlspecialchars($cliente['rh']); ?></p>
              <p><strong>EPS:</strong> <?php echo htmlspecialchars($cliente['eps']); ?></p>
              <p><strong>Fracturas:</strong> <?php echo htmlspecialchars($cliente['fracturas']); ?></p>
              <p><strong>Alergias:</strong> <?php echo htmlspecialchars($cliente['alergias']); ?></p>
            </div>
          </div>
        </div>

        <!-- Plan -->
        <div class="col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white fw-bold">Plan Actual</div>
            <div class="card-body small">
              <?php if ($planInfo): ?>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($planInfo['nombre']); ?></p>
                <p><strong>Precio:</strong> $<?php echo htmlspecialchars($planInfo['precio']); ?></p>
                <p><strong>Pago:</strong> <?php echo htmlspecialchars($cliente['pago_plan']); ?></p>
                <p><strong>Vence:</strong>
                  <?php if (!empty($cliente['vencimiento_plan'])): ?>
                    <span class="<?php echo ($vencido) ? 'text-danger' : ''; ?>">
                      <?php echo htmlspecialchars($cliente['vencimiento_plan']); ?>
                    </span>
                  <?php else: ?>
                    No registrado
                  <?php endif; ?>
                </p>
              <?php else: ?>
                <p>Sin plan asignado</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Consentimiento y notificaciones -->
        <div class="col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white fw-bold">Consentimiento y Notificaciones</div>
            <div class="card-body small">
              <p><strong>Notificaciones:</strong> <?php echo ($cliente['notificaciones'] == 0) ? 'NO' : 'SÍ'; ?></p>
              <p><strong>Consentimiento:</strong>
                <?php if ($formId): ?>
                  <a href="/pdf/?type=consent&id=<?php echo urlencode($formId); ?>" class="text-success fw-bold">
                    <i class="fa fa-file-pdf-o"></i> Descargar
                  </a>
                <?php else: ?>
                  <span>No disponible</span>
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>

      </div> <!-- /row -->
    </div> <!-- /col derecha -->

  </div> <!-- /row general -->
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
              <option value="">Seleccione banco</option>
              <option value="Bancolombia">Bancolombia</option>
              <option value="Daviplata">Daviplata</option>
              <option value="Nequi">Nequi</option>
              <option value="Davivienda">Davivienda</option>
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
                <option value="">Seleccione banco</option>
                <option value="Bancolombia">Bancolombia</option>
                <option value="Daviplata">Daviplata</option>
                <option value="Nequi">Nequi</option>
                <option value="Davivienda">Davivienda</option>
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
                <input type="date" id="fecha_plazo" class="form-control">
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
