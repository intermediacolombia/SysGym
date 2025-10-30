<div class="card shadow">
      <!-- Encabezado: Nombre y Estado -->
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">
          <i class="fa fa-user"></i> <?php echo htmlspecialchars($cliente['nombres'] . " " . $cliente['apellidos']); ?>
        </h3>	  
        <span class="badge <?php echo ($cliente['congelado'] == 1) ? '' : (($cliente['estado'] === 'activo') ? 'bg-success' : 'bg-danger'); ?>" <?php echo ($cliente['congelado'] == 1) ? 'style="background-color: #31D2F0;"' : ''; ?>>
  <?php echo ($cliente['congelado'] == 1) ? 'Congelado' : (($cliente['estado'] === 'activo') ? 'Activo' : 'Inactivo'); ?>
</span>		  
      </div>
      <div class="card-body">
		  
		   <!-- Foto de perfil del cliente -->
<div class="text-center my-4">
  <div class="position-relative d-inline-block">
    <img 
      id="clienteFoto"
      src="<?php echo !empty($cliente['imagen_perfil']) ? '../../uploads/clientes/' . htmlspecialchars($cliente['imagen_perfil']) : '../../assets/img/default-user.png'; ?>"
      alt="Foto de perfil"
      class="foto-perfil shadow-sm"
      data-bs-toggle="modal"
      data-bs-target="#fotoModal">
  </div>
</div>
		  
        <!-- Sección: Datos Personales -->
        <div class="section-title"><i class="fa fa-id-card-o"></i> Datos Personales</div>
        <div class="row">
          <div class="col-md-6">
			  <p class="detail-item"><span class="detail-label">Identificación:</span> <?php echo htmlspecialchars($cliente['identificacion']); ?></p>
            <p class="detail-item"><span class="detail-label">Dirección:</span> <?php echo htmlspecialchars($cliente['direccion']); ?></p>
            <p class="detail-item"><span class="detail-label">Teléfono:</span> +<?php echo htmlspecialchars($cliente['dialCode']); ?> <?php echo htmlspecialchars($cliente['telefono']); ?></p>			  
			 <p class="detail-item"><span class="detail-label">Contacto de Emergencia:</span> <?php echo htmlspecialchars($cliente['contacto_emergencia']); ?></p>	  
			  <p class="detail-item"><span class="detail-label">Tel. Contacto de Emergencia:</span> +<?php echo htmlspecialchars($cliente['dialCodeEmergencia']); ?> <?php echo htmlspecialchars($cliente['numero_emergencia']); ?></p>			  
			  <p class="detail-item"><span class="detail-label">Registro:</span> <?php
				// Configura la localización a español (esto puede variar según tu sistema)
				setlocale(LC_TIME, 'es_ES.UTF-8');

				// Convierte la cadena de fecha a un objeto DateTime
				$fecha = new DateTime($cliente['created_at']);

				// Formatea la fecha: día, nombre del mes y año
				echo strftime("%d de %B de %Y", $fecha->getTimestamp());
				?></p>

							  <p class="detail-item"><span class="detail-label">Ultima Actualización:</span> <?php
				// Configura la localización a español (esto puede variar según tu sistema)
				setlocale(LC_TIME, 'es_ES.UTF-8');

				// Convierte la cadena de fecha a un objeto DateTime
				$fecha = new DateTime($cliente['updated_at']);

				// Formatea la fecha: día, nombre del mes y año
				echo strftime("%d de %B de %Y", $fecha->getTimestamp());
				?></p>
			</div>
          <div class="col-md-6">
            <p class="detail-item"><span class="detail-label">Email:</span> <?php echo htmlspecialchars($cliente['email']); ?></p>
			  <?php
// Configura la localización a español
setlocale(LC_TIME, 'es_ES.UTF-8');

$fechaNacimiento = new DateTime($cliente['fecha_nacimiento']);
?>
<p class="detail-item">
    <span class="detail-label">Fecha de Nacimiento:</span> 
    <?php echo strftime("%d de %B de %Y", $fechaNacimiento->getTimestamp()); ?>
</p>

			  
			  
            <?php if($edad !== ""): ?>
              <p class="detail-item"><span class="detail-label">Edad:</span> <?php echo $edad; ?> años</p>
            <?php endif; ?>
            <p class="detail-item" style="text-transform: capitalize"><span class="detail-label">Género:</span> <?php echo htmlspecialchars($cliente['genero']); ?></p>
			
			  
			  
			  <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Consentimiento informado', $_SESSION["user_permissions"])): ?>
<p class="detail-item">
    <span class="detail-label">Consentimiento informado:</span>
    <?php if ($formId): ?>
        <a href="/pdf/?type=consent&id=<?php echo urlencode($formId); ?>" class="btn btn-link <?php echo ($cliente['congelado'] == 1) ? 'disabled' : ''; ?>" style="text-decoration: none; color: #E21F0C;" >
            <i class="fa fa-file-pdf-o"></i> Descargar
        </a>

        <!-- Agregamos el nuevo permiso para el botón Reenviar -->
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Reenviar Consentimiento informado', $_SESSION["user_permissions"])): ?>
            <button id="resendConsent" class="btn btn-link <?php echo ($cliente['congelado'] == 1) ? 'disabled' : ''; ?>" style="text-decoration: none; color: #E21F0C;">
                <i class="fa fa-paper-plane"></i> Reenviar
            </button>
        <?php endif; ?>

    <?php else: ?>
        <a href="#" class="btn btn-link disabled" title="No hay consentimiento informado disponible" style="text-decoration: none; color: #E21F0C;"> Descargar</a>
    <?php endif; ?>
</p>
<?php endif; ?>
			  
			  
			
			  
			  
			
			  
			<p class="detail-item">
  <span class="detail-label">Recibe Notificaciones?:</span>
  <?php echo ($cliente['notificaciones'] == 0) ? 'NO' : 'SI'; ?><br>
			<!-- Botón para abrir el modal -->
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#messagesModal" <?php echo ($cliente['congelado'] == 1) ? 'disabled' : ''; ?>>
  <i class="fa fa-whatsapp"></i> Ver Mensajes Enviados </button>
				
				
	


<!-- Modal -->
<div class="modal fade" id="messagesModal" tabindex="-1" aria-labelledby="messagesModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="max-width:90%;">
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

<!-- Incluir jQuery, Bootstrap y DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- Inicialización de DataTables para la tabla dentro del modal -->
<script>
  $(document).ready(function(){
    $('#mensajesTable').DataTable({
      ajax: {
        url: 'get_all_send.php', // Este endpoint debe recibir el id del cliente por parámetro si es necesario
        type: 'GET',
        data: { id: "<?php echo $id; ?>" }, // Enviar el id del cliente
        dataSrc: 'data.data' // Ajusta según la estructura de tu JSON
      },
      columns: [
        { data: 'phonenumber' },
        { 
          data: 'text',
          render: function(data) {
            return data.replace(/\n/g, "<br>");
          }
        },
        { data: 'status' },
        { data: 'statusInfo' },
        { data: 'delivery' },
        { data: 'createdAt' },
        { data: 'executedAt' },
        { 
          data: 'url',
          render: function(data) {
            return data ? '<a href="'+data+'" target="_blank">Ver PDF</a>' : 'Sin URL';
          }
        }
      ],
      order: [[5, 'desc']],
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
      }
    });
  });
</script>

</p>

          </div>
        </div>

        <!-- Sección: Información Médica -->
        <div class="section-title"><i class="fa fa-user-md"></i> Información Médica</div>
        <div class="row">
          <div class="col-md-12">
		<p class="detail-item"><span class="detail-label">RH:</span> <?php echo htmlspecialchars($cliente['rh']); ?></p>
            <p class="detail-item"><span class="detail-label">EPS:</span> <?php echo htmlspecialchars($cliente['eps']); ?></p>
            <p class="detail-item"><span class="detail-label">Fracturas:</span> <?php echo htmlspecialchars($cliente['fracturas']); ?></p>
            <p class="detail-item"><span class="detail-label">Alergias:</span> <?php echo htmlspecialchars($cliente['alergias']); ?></p>
            <p class="detail-item"><span class="detail-label">Enfermedades Actuales:</span> <?php echo htmlspecialchars($cliente['enfermedades_actuales']); ?></p>
            <p class="detail-item"><span class="detail-label">Observaciones:</span> <?php echo htmlspecialchars($cliente['observaciones']); ?></p>
          </div>
        </div>

        <!-- Sección: Plan -->
        <div class="section-title"><i class='fas fa-fire-alt'></i> Plan</div>
        <?php if ($planInfo): ?>
          <div class="row">
            <div class="col-md-6">
              <p class="detail-item">
                <span class="detail-label">Plan:</span>
                <?php echo htmlspecialchars($planInfo['nombre']); ?>
                <?php if ($vencido): ?>
                  <span class="badge-vencido">Vencido</span>
                <?php endif; ?>
              </p>
              <p class="detail-item"><span class="detail-label">Precio del Plan:</span> $<?php echo htmlspecialchars($planInfo['precio']); ?></p>
				
			<?php if ($cliente['estado'] == 'activo'): ?>
			<?php if (isset($_SESSION["user_permissions"]) && in_array('Generear Certificados', $_SESSION["user_permissions"])): ?>
				
			<p class="detail-item"><span class="detail-label">Certificado Inscripción:</span> <a href="/pdf/?type=cert&id=<?php echo $cliente['id']; ?>" class="btn btn-link <?php echo ($cliente['congelado'] == 1) ? 'disabled' : ''; ?>" style="text-decoration: none; color: #138496;" >
            <i class="fa fa-file-pdf-o"></i> Generar
				</a></p>
			<?php endif;?>
			<?php endif;?>
				
				
            </div>
            <div class="col-md-6">
              <p class="detail-item">
                <span class="detail-label">Fecha de Pago:</span>
                <span id="fecha_pago_display"><?php echo !empty($cliente['pago_plan']) ? htmlspecialchars($cliente['pago_plan']) : 'No registrado'; ?></span>
              </p>

              <!--<p class="detail-item">
                <span class="detail-label">Fecha de Vencimiento:</span>
                <?php if (!empty($cliente['vencimiento_plan'])): ?>
                  <?php
                    $vencimientoDate = new DateTime($cliente['vencimiento_plan']);
                    $today = new DateTime();
                  ?>
                  <?php if ($today > $vencimientoDate): ?>
                    <span id="fecha_vencimiento_display" style="color:red;"><?php echo htmlspecialchars($cliente['vencimiento_plan']); ?></span>
                  <?php else: ?>
                    <span id="fecha_vencimiento_display"><?php echo htmlspecialchars($cliente['vencimiento_plan']); ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span id="fecha_vencimiento_display">No registrado</span>
                <?php endif; ?>
              </p>-->
				
				<p class="detail-item">
  <span class="detail-label">Fecha de Vencimiento:</span>
  <?php if (!empty($cliente['vencimiento_plan'])): ?>
    <?php
      $vencimientoDate = new DateTime($cliente['vencimiento_plan']);
      //$today = new DateTime(date('Y-m-d'));
    ?>
    <?php if ($cliente['vencimiento_plan'] < $hoy): ?>
      <span id="fecha_vencimiento_display" style="color:red;"><?php echo htmlspecialchars($cliente['vencimiento_plan']); ?></span>
    <?php else: ?>
      <span id="fecha_vencimiento_display"><?php echo htmlspecialchars($cliente['vencimiento_plan']); ?></span>
    <?php endif; ?>
  <?php else: ?>
    <span id="fecha_vencimiento_display">No registrado</span>
  <?php endif; ?>
</p>
				
				


				
				
				<?php if ($cliente['congelado'] == 1): ?>
        <!-- Mostrar información de congelado solo si el plan está congelado -->
        <p class="detail-item">
          <span class="detail-label">Fecha de Congelado:</span>
          <?php echo htmlspecialchars($cliente['fecha_congelado']); ?>
        </p>
        <?php
          if (!empty($cliente['fecha_congelado']) && !empty($cliente['vencimiento_plan'])) {
              $fechaCongelado = new DateTime($cliente['fecha_congelado']);
              $vencimientoOriginal = new DateTime($cliente['vencimiento_plan']);
              // Calculamos la diferencia en días y descontamos 1 día (para no contar el día en que se congeló)
              $diasRestantes = $fechaCongelado->diff($vencimientoOriginal)->days - 1;
              if ($diasRestantes < 0) { 
                  $diasRestantes = 0; 
              }
          } else {
              $diasRestantes = "N/D";
          }
        ?>
				
        <p class="detail-item">
          <span class="detail-label">Días Restantes:</span>
          <?php echo $diasRestantes; ?>
        </p>
      <?php endif; ?>
				
				<?php if ($cliente['congelado'] == 1): ?>				
				<button id="btnUnFreezePlan" class="btn btn-info"><i class="fa fa-thermometer-4"></i> Descongelar Plan</button>
				<?php else: ?>
				
				<?php if (isset($_SESSION["user_permissions"]) && in_array('Usar Cajas', $_SESSION["user_permissions"])): ?>
				
				<?php if(!$caja_id): ?>
				<div class="alert alert-danger">
      No tienes una caja abierta. Por favor abre la caja para continuar con las ventas.
    </div>
<?php else: ?>

	

<?php
//$botonHabilitado = false;
if (isset($_SESSION["user_permissions"]) && in_array('Pagar durante plan activo', $_SESSION["user_permissions"])){			
$botonHabilitado = true;
}else{
$botonHabilitado = false;	
}				
$today = new DateTime(date('Y-m-d'));
// Verificamos si hay fecha de pago válida
if (!empty($cliente['pago_plan'])) {
    $fechaPago = new DateTime($cliente['pago_plan']);
    // Si hay pago futuro o para hoy, se habilita el botón sin importar el vencimiento
    if ($fechaPago >= $today) {
        $botonHabilitado = true;
    }
}
// Si no hubo pago válido, miramos si el plan está vencido
if (!$botonHabilitado && !empty($cliente['vencimiento_plan'])) {
    $vencimientoDate = new DateTime($cliente['vencimiento_plan']);

    if ($vencimientoDate <= $today) {
        $botonHabilitado = true;
    }
}
?>
						
<!--div class="tooltip-wrapper" 
     title="<?php echo $botonHabilitado ? '' : 'El cliente tiene un plan activo. No se puede marcar un pago.'; ?>" 
     data-bs-toggle="tooltip" 
     data-bs-placement="top">
    <button 
        id="btnPago" 
        class="btn btn-success <?php echo $botonHabilitado ? '' : 'disabled'; ?>" 
        <?php echo $botonHabilitado ? '' : 'disabled'; ?>>
        <i class="fa fa-money"></i> Marcar Pago
    </button>
</div-->
				
				<button id="btnPago" class="btn btn-success"><i class="fa fa-money"></i> Marcar Pago</button>
				<?php endif; ?>				
				<?php endif; ?>
				
				
		
				
				<?php if ($cliente['estado'] == 'activo'): ?>
				<?php if (isset($_SESSION["user_permissions"]) && in_array('Congelar Planes', $_SESSION["user_permissions"])): ?>
				
				
				<button id="btnFreezePlan" class="btn btn-info" style="color: #fff;"><i class="fa fa-snowflake-o"></i> Congelar Plan</button>
				<?php endif; ?>
				
				<?php if (isset($_SESSION["user_permissions"]) && in_array('Anular Planes', $_SESSION["user_permissions"])): ?>
				<button id="btnNullPlan" class="btn btn-warning"><i class="fa fa-ban"></i> Anular Plan</button>				
				<?php endif; ?>
				
				<?php if (isset($_SESSION["user_permissions"]) && in_array('Transferir Planes', $_SESSION["user_permissions"])): ?>
				<button id="btnTransferPlan" class="btn btn-outline-secondary mx-1">
				  <i class="fas fa-random"></i> Transferir Plan
				</button>
				<?php endif; ?>
				
				<?php endif; ?>
				<?php endif; ?>
				
				
				
				<!--<button id="btnFreezePlan" class="btn btn-warning"><i class="fa fa-ban"></i> Conegelar Plan</button>-->

            </div>
          </div>
        <?php else: ?>
          <p class="detail-item"><span class="detail-label">Plan:</span> Sin Plan asignado</p>
        <?php endif; ?>
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

	