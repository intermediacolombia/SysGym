<?php 
require_once __DIR__ . '/../login/session.php'; 
require_once __DIR__ . '/../../inc/config.php';
$permisopage = 'Usar Cajas';
include('../login/restriction.php');



// 1. Consultar productos disponibles
$stmt = db()->prepare("SELECT id, nombre, precio, coste, stock FROM productos WHERE borrado = 0 AND stock > 0");
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Consultar caja abierta (estado=1)
$stmtCaja = db()->prepare("SELECT * FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
$stmtCaja->execute([':usuario_id' => $id_user]);
$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
$caja_id = $caja ? $caja['id'] : null;

// Se asume que la tabla 'cajas' tiene 'monto_inicial' (la base)
$base = $caja ? (float)$caja['monto_inicial'] : 0.0;  

// 3. Consultar el total de ventas de esa caja (para la vista inicial, aunque se actualizará vía AJAX)
$totalVentas = 0;
if ($caja_id) {
  $stmtTotal = db()->prepare("SELECT IFNULL(SUM(valor), 0) as total FROM ventas WHERE caja_id = :caja_id");
  $stmtTotal->execute([':caja_id' => $caja_id]);
  $rowTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);
  $totalVentas = $rowTotal ? $rowTotal['total'] : 0;
}

// 4. Total en caja = base + totalVentas (vista inicial)
$totalCaja = $base + $totalVentas;




?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Página de Venta</title>
  <?php include('../inc/header.php'); ?>
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
	
  <style>
    /*$base-margin: 20px;
$blue: #345F90;
$tab-height: 35px;
$tab-border-radius: 35px;


.container{
	width: 75%;
	margin: 3rem auto;
}*/



.tab-slider--nav{
	width: 100%;
	float: left;
	margin-bottom: 20px;
}
.tab-slider--tabs{
	display: block;
	float: left;
	margin: 0;
	padding: 0;
	list-style: none;
	position: relative;
	border-radius: 35px;
	overflow: hidden;
	background: #C9D8E6;
	height: 35px;
	user-select: none; 
	&:after{
		content: "";
		width: 50%;
		background: var(--system-color-primary);
		height: 100%;
		position: absolute;
		top: 0;
		left: 0;
		transition: all 250ms ease-in-out;
		border-radius: 35px;
	}
	&.slide:after{
		left: 50%;
	}
}

.tab-slider--trigger {
	font-size: 12px;
	line-height: 1;
	font-weight: bold;
	color: #000;
	text-transform: uppercase;
	text-align: center;
	padding: 11px 20px;
	position: relative;
	z-index: 2;
	cursor: pointer;
	display: inline-block;
	transition: color 250ms ease-in-out;
	user-select: none; 
	&.active {
		color: #fff;
	}
}
.tab-slider--body{
	margin-bottom: 20px;
}
	  
	  #mensaje {
    position: fixed;
	  width: 500px;
    bottom: 20px;            /* Ajusta la distancia desde abajo */
    left: 50%;
    transform: translateX(-50%);
    font-size: 16px;         /* Tamaño de fuente */
    color: #000;             /* Color del texto */
    /* No se define background para que no tenga fondo */
    z-index: 9999;           /* Para que esté siempre al frente */
  }
	  
	
	  
  </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
	<h1>Venta de Productos</h1>
	</div>
	</div>
<?php include('../inc/menu.php'); ?>
<div class="container mt-4">
  
  
  <?php if(!$caja_id): ?>
    <button class="btn btn-primary mb-3" id="btnAbrirCaja">Abrir Caja</button>
    <div class="alert alert-danger">No tienes una caja abierta. Por favor abre la caja para continuar con las ventas.</div>
  <?php else: ?>
    <!-- Bloque de totales detallados -->
   <div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-danger text-white">
    <h5 class="card-title mb-0">Resumen de Caja</h5>
  </div>
  <div class="card-body">
    <div class="row">
      <!-- Columna de Efectivo -->
      <div class="col-md-6 border-end">
        <h6 class="text-uppercase text-muted mb-3"><i class='fas fa-coins'></i> Efectivo</h6>
        <p class="mb-2">
          <strong>Base:</strong> $<span id="baseCaja2"><?php echo number_format($base, 0, '', '.'); ?></span>
        </p>
        <p class="mb-2">
          <strong>Total en Efectivo:</strong> $<span id="efectivoVentas">0</span>
        </p>
		  
		 <!-- Nueva línea para Total Egresos -->
        <p class="mb-2">
          <strong>Total Egresos:</strong> $-<span id="egresosTotal">0</span>
        </p>
		  
        <p class="mb-2">
          <strong>Total Base + Ventas Efectivo<span id="egresosLabel"></span>:</strong> $<span id="efectivoTotal">0</span>

        </p>
        
		  
		  
		  
		  	
		  <hr>
		  <h6 class="text-uppercase text-muted mb-3" title=""><i class='fab fa-get-pocket'></i> Bolsillos</h6>
		  <div class="alert alert-danger" role="alert" style="font-size: 12px;"><strong style="color: red;">Nota:</strong> Esta información no se suma, solo se identifica cuanto debe ir en cada bolsillo y solo contempla el dinero en efectivo en la caja.</div>
		  <div id="bolsillosContainer"></div>
		  
		  
		  
      </div>
		
		
      <!-- Columna de Transferencias -->
<div class="col-md-6">
  <h6 class="text-uppercase text-muted mb-3"><i class="fa fa-bank"></i> Transferencias</h6>
  <p class="mb-2">
    <strong>Total Transferencias:</strong> $<span id="totalTransferencias">0</span>
  </p>
  <ul class="list-group list-group-flush" id="listaBancosTransferencias">
    <?php foreach(getBancosDisponibles() as $banco): 
      $bancoId = preg_replace('/[^a-zA-Z0-9]/', '', $banco);
    ?>
    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
      <?= htmlspecialchars($banco) ?>
      <span>$<span id="transfer<?= $bancoId ?>" class="transfer-banco" data-banco="<?= htmlspecialchars($banco) ?>">0</span></span>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
		
		
    </div>
    <hr class="my-4">
    <div class="text-center">
      <h4 class="mb-3">Total Turno: $<span id="totalCaja2">0</span></h4>
		

      <button class="btn btn-danger" id="btnCerrarCaja">Cerrar Caja</button>
    </div>
  </div>
</div>

	


    <div class="row">
      <div class="container">
        <div class="tab-slider--nav">
          <ul class="tab-slider--tabs">
            <li class="tab-slider--trigger active" rel="tab1"><i class='fa fa-shopping-basket'></i> Productos</li>
            <li class="tab-slider--trigger" rel="tab2"><i class='far fa-money-bill-alt'></i> Ventas</li>
          </ul>
        </div>
		  <button class="btn btn-success" id="btnIngreso"><i class='fas fa-hand-holding-usd'></i> Registrar Ingreso</button>
			<button class="btn btn-warning" id="btnEgreso"><i class='fas fa-comment-dollar'></i> Registrar Egreso</button>
        <div class="tab-slider--container">
          <div id="tab1" class="tab-slider--body">
            <h5>Productos Disponibles</h5>
            <table id="productos-table" class="table table-striped w-100">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Precio</th>
                  <th>Stock</th>
                  <th>Cantidad</th>
                  <th>Método de Pago</th>
					<?php if (isset($_SESSION["user_permissions"]) && in_array('Crear creditos productos', $_SESSION["user_permissions"])): ?>
					<th>Crédito</th>
					<?php endif;?>
					<th>Dividir</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($productos as $producto): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                    <td>$<?php echo number_format($producto['precio'], 0, '', '.'); ?></td>
                    <td><?php echo $producto['stock']; ?></td>
                    <td>
                      <input type="number" min="1" max="<?php echo $producto['stock']; ?>" value="1" class="form-control cantidadVenta" data-producto-id="<?php echo $producto['id']; ?>">
                    </td>
                    <td>
                      <select class="form-select paymentMethod" data-producto-id="<?php echo $producto['id']; ?>">
                        <option value="Efectivo" selected>Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                      </select>
						
					<select class="form-select bankSelect d-none" data-producto-id="<?php echo $producto['id']; ?>">
  					<?= getBancosOptions() ?>
					</select>
					
                    </td>
					  <?php if (isset($_SESSION["user_permissions"]) && in_array('Crear creditos productos', $_SESSION["user_permissions"])): ?>
					<td>
  <input type="checkbox" class="creditoCheckbox" data-producto-id="<?php echo $producto['id']; ?>"> Crédito
</td>
					  <?php endif;?>
					  
					  <td>
  <input type="checkbox"
         class="splitCheckbox"
         data-producto-id="<?php echo $producto['id']; ?>">
  Dividir pago
</td>

					  
                    <td>
                      <button class="btn btn-success btn-vender" 
        data-producto-id="<?php echo $producto['id']; ?>" 
        data-precio="<?php echo $producto['precio']; ?>"
        data-coste="<?php echo $producto['coste']; ?>">+</button>

                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div id="tab2" class="tab-slider--body">
            <h5>Ventas de la Caja</h5>
            <table id="ventas-table" class="table table-striped w-100">
              <thead>
                <tr>
                  <th>Detalle</th>
                  <th>Cantidad</th>
                  <th>Valor</th>
                  <th>Medio</th>
                  <th>Banco</th>
                  <th>Hora</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody><!-- Se llenará vía AJAX --></tbody>
            </table>
          </div>
        </div>
      </div>
		<div id="mensaje"></div>
    </div>
  <?php endif; ?>
  
</div>

<!-- Modal para Apertura de Caja: Base Inicial -->
<div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAbrirCaja">
        <div class="modal-header">
          <h5 class="modal-title">Abrir Caja</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <label for="base" class="form-label">Base Inicial</label>
		 <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">$</span>
                </div>
          <input type="number" min="0" class="form-control" id="base" name="base" required>
			</div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Abrir</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
	

<!-- Modal para registrar ingreso -->
<div class="modal fade" id="modalIngreso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formIngreso">
        <div class="modal-header">
          <h5 class="modal-title">Registrar Ingreso</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="ingresoDetalle" class="form-label">Detalle</label>
            <input type="text" class="form-control" id="ingresoDetalle" name="detalle" placeholder="Descripción del ingreso" required>
          </div>
          <div class="mb-3">
            <label for="ingresoValor" class="form-label">Valor</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" class="form-control" id="ingresoValor" name="valor" min="1" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="ingresoMetodo" class="form-label">Método de Pago</label>
            <select class="form-select" id="ingresoMetodo" name="metodo" required>
              <option value="Efectivo" selected>Efectivo</option>
              <option value="Transferencia">Transferencia</option>
            </select>
          </div>

          <div class="mb-3 d-none" id="ingresoBancoWrap">
  <label for="ingresoBanco" class="form-label">Banco</label>
  <select class="form-select" id="ingresoBanco" name="banco">
    <?= getBancosOptions() ?>
  </select>
</div>

          <p class="text-muted">El valor ingresado se sumará al total de la caja.</p>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Registrar Ingreso</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

	
	
<!-- Modal para registrar egreso -->
<div class="modal fade" id="modalEgreso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEgreso">
        <div class="modal-header">
          <h5 class="modal-title">Registrar Egreso</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="egresoDetalle" class="form-label">Detalle</label>
            <input type="text" class="form-control" id="egresoDetalle" name="detalle" placeholder="Descripción del egreso" required>
          </div>
          <div class="mb-3">
            <!--<label for="egresoCantidad" class="form-label">Cantidad</label>-->
            <input type="number" class="form-control" id="egresoCantidad" name="cantidad" min="1" value="1" required hidden="">
          </div>
          <div class="mb-3">
            <label for="egresoValor" class="form-label">Valor</label>
			 <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">$</span>
                </div>
            <input type="number" class="form-control" id="egresoValor" name="valor" min="1" required>
			  </div>
          </div>
          <p class="text-muted">El valor ingresado se restará al total de la caja.</p>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Registrar Egreso</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
	
<?php if (isset($_SESSION["user_permissions"]) && in_array('Crear creditos productos', $_SESSION["user_permissions"])): ?>	
<!-- Modal para Ventas a Crédito -->
<div class="modal fade" id="modalCredito" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formCredito">
        <div class="modal-header">
          <h5 class="modal-title">Venta a Crédito</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="clienteSearch" class="form-label">Buscar Cliente (Identificación, Nombre o Apellido)</label>
            <input type="text" class="form-control" id="clienteSearch" placeholder="Escriba para buscar">
            <div id="clienteResults" class="mt-2"></div>
          </div>
          <div class="mb-3 d-none" id="valorPagadoContainer">
  <label for="valorPagado" class="form-label">Valor Pagado</label>
  <div class="input-group">
    <span class="input-group-text">$</span>
    <input type="number" class="form-control" id="valorPagado" name="valor_pagado" min="0" required>
  </div>
</div>
          <div class="mb-3 d-none" id="fechaLimiteContainer">
  <label for="fechaLimite" class="form-label">Fecha Límite del Crédito</label>
  <input type="date" class="form-control" id="fechaLimite" name="fecha_limite" required>
</div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Registrar Venta a Crédito</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif;?>
	
	<!--Modal dividir pago -->
	<div class="modal fade" id="modalSplit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formSplit">
        <div class="modal-header">
          <h5 class="modal-title">Dividir Pago</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="alert alert-info">
            Total del producto: $<span id="splitTotal">0</span>
          </div>

          <!-- Pago 1 -->
          <h6 class="mb-2">Pago 1</h6>
          <div class="mb-2">
            <label class="form-label">Método</label>
            <select class="form-select" id="splitMethod1" required>
              <option value="Efectivo" selected>Efectivo</option>
              <option value="Transferencia">Transferencia</option>
            </select>
          </div>
			
			
          <div class="mb-3 d-none" id="splitBankWrap1">
  <label class="form-label">Banco</label>
  <select class="form-select" id="splitBank1">
    <?= getBancosOptions() ?>
  </select>
</div>
          <div class="mb-4">
            <label class="form-label">Valor Pago 1</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" min="0" class="form-control" id="splitValue1" value="0" required>
            </div>
          </div>

          <!-- Pago 2 -->
          <h6 class="mb-2">Pago 2</h6>
          <div class="mb-2">
            <label class="form-label">Método</label>
            <select class="form-select" id="splitMethod2" required>
              <option value="Efectivo">Efectivo</option>
              <option value="Transferencia">Transferencia</option>
            </select>
          </div>
          <div class="mb-3 d-none" id="splitBankWrap2">
            <label class="form-label">Banco</label>
            <select class="form-select" id="splitBank2">
              <option value="">Seleccione banco</option>
              <option value="Bancolombia">Bancolombia</option>
              <option value="Daviplata">Daviplata</option>
              <option value="Nequi">Nequi</option>
              <option value="Davivienda">Davivienda</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Valor Pago 2</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" min="0" class="form-control" id="splitValue2" value="0" required>
            </div>
            <div class="form-text">La suma de los dos pagos debe ser igual al total.</div>
          </div>

          <hr>
          <div class="small text-muted">
            El segundo registro se guardará como movimiento independiente sin afectar el stock.
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Registrar pagos</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>

        <!-- Datos de contexto -->
        <input type="hidden" id="splitPid">
        <input type="hidden" id="splitCant">
        <input type="hidden" id="splitPrecio">
        <input type="hidden" id="splitCoste">
        <input type="hidden" id="splitDetalle">
      </form>
    </div>
  </div>
</div>
<!--FIN Modal dividir pago -->

	

<?php include('../inc/menu-footer.php'); ?>

<!-- Scripts: jQuery, Bootstrap, SweetAlert, DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
  // Mostrar modal para abrir caja
  $('#btnAbrirCaja').click(function(){
    $('#modalAbrirCaja').modal('show');
  });

  // Enviar formulario para abrir caja
  $('#formAbrirCaja').submit(function(e){
    e.preventDefault();
    var base = $('#base').val();
    $.post('open.php', { monto_inicial: base }, function(res){
      try {
        var data = (typeof res === 'string') ? JSON.parse(res) : res;
        if(data.status === 'success'){
          Swal.fire('Caja Abierta', data.message, 'success').then(() => location.reload());
        } else {
          Swal.fire('Error', data.message, 'error');
        }
      } catch(e){
        Swal.fire('Resultado', res, 'info').then(() => location.reload());
      }
    }).fail(function(){
      Swal.fire('Error', 'No se pudo abrir la caja (error de red).', 'error');
    });
  });

  // Inicializar DataTable para la tabla de productos
  var productsTable = $('#productos-table').DataTable({
	  "pageLength": 50,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
  });

  // Inicializar DataTable para la tabla de ventas (se inicializa una sola vez)
  /*var ventasTable = $('#ventas-table').DataTable({
    ajax: {
      url: 'fetch_sales.php',
      type: 'GET',
      data: function(d){
        d.caja_id = "<?php echo $caja_id; ?>";
        d._ = new Date().getTime();
      },
      cache: false
    },
    columns: [
      { data: 'detalle' },
      { data: 'cantidad' },
      { 
        data: 'valor',
        render: function(d){ 
          return '$' + parseFloat(d).toLocaleString('es-CO', { maximumFractionDigits: 0 });
        }
      },
      { data: 'payment_method' },
      { data: 'bank' },
      { data: 'hora_formateada' },
      {
        data: null,
        render: function(row){
          return `<button class='btn btn-danger btn-delete-sale' data-sale-id='${row.id}' data-producto-id='${row.producto_id}' data-cantidad='${row.cantidad}'>-</button>`;
        }
      }
    ],
    order: [[5, 'desc']],
	"pageLength": 50,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
  });*/
	
	//var ventasTable = $('#ventas-table').DataTable({
	window.ventasTable = $('#ventas-table').DataTable({
    ajax: {
        url: 'fetch_sales.php',
        type: 'GET',
        data: function(d){
            d.caja_id = "<?php echo $caja_id; ?>";
            d._ = new Date().getTime();
        },
        cache: false
    },
    columns: [
        { data: 'detalle' },
        { data: 'cantidad' },
        { 
            data: 'valor',
            render: function(d){ 
                return '$' + parseFloat(d).toLocaleString('es-CO', { maximumFractionDigits: 0 });
            }
        },
        { data: 'payment_method' },
        { data: 'bank' },
        { 
            data: 'hora_formateada', // Columna que contiene la hora en formato AM/PM
            render: function(data, type, row) {
                if (type === 'sort') {
                    // Convertir la hora AM/PM a un formato 24 horas para ordenar
                    const time = new Date("1970/01/01 " + data);
                    return time.getTime(); // Devuelve el timestamp para ordenar
                }
                return data; // Mostrar la hora formateada como está
            }
        },
        {
            data: null,
            render: function(row){
                return `<button class='btn btn-danger btn-delete-sale' data-sale-id='${row.id}' data-producto-id='${row.producto_id}' data-cantidad='${row.cantidad}'>-</button>`;
            }
        }
    ],
    order: [[5, 'desc']], // Ordenar por la columna "hora" en orden descendente
    "pageLength": 50,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
});

  	
	//function refreshVentas(){
	window.refreshVentas = function(){
    console.log("Ejecutando refreshVentas...");
    $.ajax({
      url: 'get_total_caja.php',
      type: 'GET',
      data: { caja_id: "<?php echo $caja_id; ?>", _: new Date().getTime() },
      dataType: 'json',
      cache: false,
      success: function(res){
        console.log("Respuesta de get_total_caja:", res);
        if(res.status === 'success'){

          var baseCaja = parseFloat($('#baseCaja2').text().replace(/\./g, ''));
          var efectivoVentas = parseFloat(res.efectivo);
          var egresos = parseFloat(res.egresos); // Aquí obtienes los egresos
			
			
			
		// Si egresos es mayor que 0, se muestra " - Egresos" en el span "egresosLabel"
      	  if (egresos > 0) {
        	$('#egresosLabel').text(" - Egresos");
      		} else {
        		$('#egresosLabel').text("");
      		}			
			//end texto
			
			
			

          var totalEfectivo = baseCaja + efectivoVentas - egresos;

          $('#efectivoVentas').text(efectivoVentas.toLocaleString('es-CO'));
          $('#efectivoTotal').text(totalEfectivo.toLocaleString('es-CO'));
          $('#egresosTotal').text(egresos.toLocaleString('es-CO')); // Mostrar egresos aquí

          var totalBancolombia = parseFloat(res.Transferencias.Bancolombia);
          var totalDaviplata   = parseFloat(res.Transferencias.Daviplata);
          var totalNequi       = parseFloat(res.Transferencias.Nequi);
          var totalDavivienda  = parseFloat(res.Transferencias.Davivienda);

          $('#transferBancolombia2').text(totalBancolombia.toLocaleString('es-CO'));
          $('#transferDaviplata2').text(totalDaviplata.toLocaleString('es-CO'));
          $('#transferNequi2').text(totalNequi.toLocaleString('es-CO'));
          $('#transferDavivienda2').text(totalDavivienda.toLocaleString('es-CO'));

          var totalTransferencias = totalBancolombia + totalDaviplata + totalNequi + totalDavivienda;
          
          // Calcula el total final ya considerando egresos restados
          var totalCaja = totalEfectivo + totalTransferencias;
          $('#totalCaja2').text(totalCaja.toLocaleString('es-CO'));
          $('#totalTransferencias').text(totalTransferencias.toLocaleString('es-CO'));

          // Procesar dinámicamente los totales por bolsillos
          if(res.bolsillos){
            var bolsillosContainer = $('#bolsillosContainer'); // Asegúrate de tener este contenedor en el HTML
            bolsillosContainer.empty(); // Limpia el contenedor antes de agregar
            $.each(res.bolsillos, function(bolsillo, total){
              bolsillosContainer.append('<strong>' + bolsillo + ':</strong> $' + parseFloat(total).toLocaleString('es-CO') + '<br>');
            });
          }

        }
      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("Error en get_total_caja:", textStatus, errorThrown);
      }
    });
}


  
  // Llamada inicial para refrescar totales
  refreshVentas();
  
  // Mostrar/ocultar select de banco según método de pago
  $(document).on('change', '.paymentMethod', function(){
    var productoId = $(this).data('producto-id');
    if ($(this).val() === 'Transferencia') {
      $(`.bankSelect[data-producto-id='${productoId}']`).removeClass('d-none');
    } else {
      $(`.bankSelect[data-producto-id='${productoId}']`).addClass('d-none').val('');
    }
  });
  
  // Registrar venta
  $('#productos-table').on('click', '.btn-vender', function () {
  var btn = $(this);
  var pid = btn.data('producto-id');
  var precio = parseFloat(btn.data('precio'));
  var coste = parseFloat(btn.data('coste'));
  var cant = parseInt(btn.closest('tr').find('.cantidadVenta').val());
  var isCredito = btn.closest('tr').find('.creditoCheckbox').is(':checked');

  if (cant <= 0) {
    alert('Cantidad inválida');
    return;
  }

  if (isCredito) {
    // Abrir el modal para ventas a crédito
    $('#modalCredito').data('producto-id', pid).data('cantidad', cant).data('precio', precio).modal('show');
  } else {
    // Procesar venta normal
    procesarVentaNormal(btn, pid, cant, precio, coste);
  }
});

// Función para procesar venta normal (ya existente)
function procesarVentaNormal(btn, pid, cant, precio, coste) {
  var detalle = btn.closest('tr').find('td:first').text();
  var paymentMethod = btn.closest('tr').find('.paymentMethod').val();
  var bank = '';
  if (paymentMethod === 'Transferencia') {
    bank = btn.closest('tr').find('.bankSelect').val();
    if (bank === '') {
      alert('Por favor, seleccione un banco para la Transferencia.');
      return;
    }
  }
  $.ajax({
    url: 'register_sale.php',
    type: 'POST',
    data: { 
      producto_id: pid, 
      cantidad: cant, 
      precio: precio,
      coste: coste,
      detalle: detalle,
      payment_method: paymentMethod,
      bank: bank
    },
    dataType: 'json',
    success: function(res){
      if(res.status === 'success'){
        $('#mensaje').html(`<div class='alert alert-success'>${res.message}</div>`);
        $('#mensaje').stop(true, true).css('opacity', 1).show(); // Reinicia y muestra el mensaje
        setTimeout(function(){
          $('#mensaje').fadeOut(1000); // Desvanece en 1 segundo
        }, 5000);
		  
        btn.closest('tr').find('td:nth-child(3)').text(res.nuevo_stock);
        btn.closest('tr').find('.cantidadVenta').val(1);
        // Usar delay para refrescar totales y tabla
		  
		  // Reiniciar el método de pago a "Efectivo" y ocultar el select de banco
    var $tr = btn.closest('tr');
    $tr.find('.paymentMethod').val('Efectivo');
    $tr.find('.bankSelect').val('').addClass('d-none');
		// Fin Reiniciar el método de pago a "Efectivo" y ocultar el select de banco
		  
        setTimeout(function(){ 
          ventasTable.ajax.reload(null, false);
          refreshVentas();
        }, 500);
      } else {
        $('#mensaje').html(`<div class='alert alert-danger'>${res.message}</div>`);
      }
    }
  });
}
  
  // Borrar venta
  $('#ventas-table').on('click', '.btn-delete-sale', function(){
    var saleId = $(this).data('sale-id');
    var productoId = $(this).data('producto-id');
    var cantidad = $(this).data('cantidad');
    
    Swal.fire({
      title: '¿Borrar esta venta?',
      text: 'Se eliminará esta venta y se restaurará el stock.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, borrar',
      cancelButtonText: 'Cancelar'
    }).then((r)=>{
      if(r.isConfirmed){
        $.ajax({
          url: 'delete_sale.php',
          type: 'POST',
          data: { sale_id: saleId, producto_id: productoId, cantidad: cantidad },
          dataType: 'json',
          success: function(res){
            if(res.status==='success'){
              $('#mensaje').html(`<div class='alert alert-success'>${res.message}</div>`);
				$('#mensaje').stop(true, true).css('opacity', 1).show(); // Reinicia y muestra el mensaje
setTimeout(function(){
  $('#mensaje').fadeOut(1000); // Desvanece en 1 segundo
}, 5000);
              var input = $(`#productos-table .cantidadVenta[data-producto-id='${productoId}']`);
              if(input.length){
                input.closest('tr').find('td:nth-child(3)').text(res.nuevo_stock);
              }
              setTimeout(function(){ 
                ventasTable.ajax.reload(null, false);
                refreshVentas();
              }, 500);
            } else {
              $('#mensaje').html(`<div class='alert alert-danger'>${res.message}</div>`);
            }
          }
        });
      }
    });
  });
  
  // Cerrar Caja
 $('#btnCerrarCaja').click(function(){
    Swal.fire({
      title: 'Cerrar Caja',
      text: '¿Deseas cerrar la caja actual?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, cerrar',
      cancelButtonText: 'Cancelar'
    }).then((r)=>{
      if(r.isConfirmed){
        $.get('close.php', function(res){
          if(res.status==='success'){
            Swal.fire('Caja Cerrada', res.message, 'success')
              .then(()=> location.reload());
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json');
      }
    });
  });
	
	
	$('#clienteSearch').on('input', function () {
  var query = $(this).val();
  if (query.length >= 3) {
    $.ajax({
      url: 'search_clientes.php',
      type: 'GET',
      data: { query: query },
      success: function (res) {
        var results = JSON.parse(res);
        var html = '';
        if (results.length > 0) {
          results.forEach(function (cliente) {
            html += `<div class="cliente-item" data-cliente-id="${cliente.id}">${cliente.nombres} ${cliente.apellidos} (${cliente.identificacion})</div>`;
          });
        } else {
          html = '<div>No se encontraron clientes.</div>';
        }
        $('#clienteResults').html(html);
      }
    });
  } else {
    $('#clienteResults').empty();
  }
});

// Seleccionar un cliente
$(document).on('click', '.cliente-item', function () {
  var clienteId = $(this).data('cliente-id');
  var clienteNombre = $(this).text();
  $('#clienteSearch').val(clienteNombre).data('cliente-id', clienteId);
  $('#clienteResults').empty();
   $('#valorPagadoContainer').removeClass('d-none'); // Mostrar campo de Valor Pagado
  $('#fechaLimiteContainer').removeClass('d-none'); // Mostrar campo de Fecha Límite
	
});
	
	
	
	$('#formCredito').submit(function (e) {
  e.preventDefault();
  var productoId = $('#modalCredito').data('producto-id');
  var cantidad = $('#modalCredito').data('cantidad');
  var precio = $('#modalCredito').data('precio');
  var clienteId = $('#clienteSearch').data('cliente-id');
  var valorPagado = parseFloat($('#valorPagado').val());
  var fechaLimite = $('#fechaLimite').val();

  // Obtener el método de pago y el banco de la fila correspondiente
  var $tr = $(`#productos-table tr td input[data-producto-id='${productoId}']`).closest('tr');
  var paymentMethod = $tr.find('.paymentMethod').val();
  var bank = paymentMethod === 'Transferencia' ? $tr.find('.bankSelect').val() : '';

  if (!clienteId) {
    alert('Debe seleccionar un cliente.');
    return;
  }

  if (isNaN(valorPagado) || valorPagado < 0) {
    alert('El valor pagado es inválido.');
    return;
  }

  if (!fechaLimite) {
    alert('Debe seleccionar una fecha límite para el crédito.');
    return;
  }

  $.ajax({
    url: 'register_credito.php',
    type: 'POST',
    data: {
      producto_id: productoId,
      cantidad: cantidad,
      precio: precio,
      cliente_id: clienteId,
      valor_pagado: valorPagado,
      fecha_limite: fechaLimite,
      payment_method: paymentMethod, // Método de pago
      bank: bank // Banco (si aplica)
    },
    dataType: 'json',
    success: function (res) {
      if (res.status === 'success') {
        Swal.fire('Venta Registrada', res.message, 'success').then(function () {
          location.reload();
        });
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    },
    error: function () {
      Swal.fire('Error', 'No se pudo registrar la venta a crédito.', 'error');
    }
  });
});
	
	
	
	
	// Calcular el total del producto cuando se selecciona un cliente
$(document).on('click', '.cliente-item', function () {
  var clienteId = $(this).data('cliente-id');
  var clienteNombre = $(this).text();
  var productoId = $('#modalCredito').data('producto-id');
  var cantidad = $('#modalCredito').data('cantidad');
  var precio = $('#modalCredito').data('precio');

  // Calcular el total del producto
  var totalProducto = cantidad * precio;

  // Mostrar los campos de Valor Pagado y Fecha Límite
  $('#clienteSearch').val(clienteNombre).data('cliente-id', clienteId);
  $('#clienteResults').empty();
  $('#valorPagadoContainer').removeClass('d-none');
  $('#fechaLimiteContainer').removeClass('d-none');

  // Establecer el atributo max en el campo Valor Pagado (máximo: totalProducto - 1)
  var maxValorPagado = totalProducto - 1;
  $('#valorPagado').attr('max', maxValorPagado);

  // Limpiar el campo Valor Pagado
  $('#valorPagado').val('');
});

// Validar el campo Valor Pagado en tiempo real
$('#valorPagado').on('input', function () {
  var valorPagado = parseFloat($(this).val());
  var maxValor = parseFloat($(this).attr('max'));

  if (isNaN(valorPagado) || valorPagado < 0) {
    $(this).val(0); // Forzar un valor válido si es inválido
  } else if (valorPagado > maxValor) {
    $(this).val(maxValor); // Limitar al valor máximo permitido
    alert(`El valor pagado no puede ser mayor a $${maxValor.toLocaleString('es-CO')}.`);
  }
});
	
	
	
});
</script>

<script>
$(document).ready(function(){
  $(".tab-slider--body").hide();
  $(".tab-slider--body:first").show();
});
$(".tab-slider--nav li").click(function() {
  $(".tab-slider--body").hide();
  var activeTab = $(this).attr("rel");
  $("#"+activeTab).fadeIn();
  if($(this).attr("rel") == "tab2"){
    $('.tab-slider--tabs').addClass('slide');
  } else {
    $('.tab-slider--tabs').removeClass('slide');
  }
  $(".tab-slider--nav li").removeClass("active");
  $(this).addClass("active");
});
</script>
	
	
<script>
$(document).ready(function(){
  // Mostrar modal al hacer clic en el botón Egreso
  $('#btnEgreso').click(function(){
    $('#modalEgreso').modal('show');
  });
  
  // Enviar formulario de egreso
  $('#formEgreso').submit(function(e){
    e.preventDefault();
    var formData = $(this).serialize();
    $.ajax({
      url: 'register_egreso.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(res){
        if(res.status === 'success'){
          Swal.fire('Egreso Registrado', res.message, 'success').then(function(){
            location.reload();
          });
        } else {
          Swal.fire('Error', res.message, 'error');
        }
      },
      error: function(){
        Swal.fire('Error', 'No se pudo registrar el egreso (error de red).', 'error');
      }
    });
  });
});

	
	

	</script>
	
	<script>
$(function () {
  // Mostrar/ocultar banco según método seleccionado en el modal
  function toggleBankWrap(idx) {
    const method = $('#splitMethod' + idx).val();
    $('#splitBankWrap' + idx).toggleClass('d-none', method !== 'Transferencia');
    if (method !== 'Transferencia') {
      $('#splitBank' + idx).val('');
    }
  }
  $('#splitMethod1').on('change', () => toggleBankWrap(1));
  $('#splitMethod2').on('change', () => toggleBankWrap(2));

  // Auto-calcular Pago 2 = total - Pago 1
  function recalcSplit() {
    const total = parseInt($('#splitTotal').data('raw') || 0, 10);
    let v1 = parseInt($('#splitValue1').val() || 0, 10);
    if (v1 < 0) v1 = 0;
    if (v1 > total) v1 = total;
    $('#splitValue1').val(v1);
    $('#splitValue2').val(total - v1);
  }
  $('#splitValue1').on('input', recalcSplit);

  // Al marcar "Dividir pago" abrimos el modal con datos de la fila
  $('#productos-table').on('change', '.splitCheckbox', function () {
    const $cb = $(this);
    if (!$cb.is(':checked')) return;

    const pid = $cb.data('producto-id');
    const $tr = $cb.closest('tr');

    // No permitir dividir junto con "Crédito"
    const isCredito = $tr.find('.creditoCheckbox').is(':checked');
    if (isCredito) {
      Swal.fire('No permitido', 'No puedes dividir un pago cuando la venta es a crédito.', 'info');
      $cb.prop('checked', false);
      return;
    }

    const detalle = $tr.find('td:first').text().trim();
    const precio  = parseFloat($tr.find('.btn-vender').data('precio'));
    const coste   = parseFloat($tr.find('.btn-vender').data('coste'));
    const cant    = parseInt($tr.find('.cantidadVenta').val() || 1, 10);

    if (cant <= 0) {
      Swal.fire('Cantidad inválida', 'La cantidad debe ser mayor a 0.', 'warning');
      $cb.prop('checked', false);
      return;
    }

    const total = Math.round(precio * cant);

    // Cargar datos en el modal
    $('#splitPid').val(pid);
    $('#splitCant').val(cant);
    $('#splitPrecio').val(precio);
    $('#splitCoste').val(coste);
    $('#splitDetalle').val(detalle);

    $('#splitTotal')
      .text(total.toLocaleString('es-CO'))
      .data('raw', total);

    // Reset modal
    $('#splitMethod1').val('Efectivo');  toggleBankWrap(1);
    $('#splitMethod2').val('Efectivo');  toggleBankWrap(2);
    $('#splitBank1').val('');
    $('#splitBank2').val('');
    $('#splitValue1').val(0);
    $('#splitValue2').val(total);

    // Mostrar modal
    $('#modalSplit').modal('show');
  });

  // Enviar dos registros (1 descuenta stock, 2 no descuenta)
  $('#formSplit').on('submit', function (e) {
    e.preventDefault();

    const pid     = $('#splitPid').val();
    const cant    = parseInt($('#splitCant').val(), 10);
    const precio  = parseFloat($('#splitPrecio').val());
    const coste   = parseFloat($('#splitCoste').val());
    const detalle = $('#splitDetalle').val();

    const total   = parseInt($('#splitTotal').data('raw') || 0, 10);
    let v1        = parseInt($('#splitValue1').val() || 0, 10);
    let v2        = parseInt($('#splitValue2').val() || 0, 10);

    // Métodos/bancos
    const m1 = $('#splitMethod1').val();
    const b1 = (m1 === 'Transferencia') ? ($('#splitBank1').val() || '') : '';
    const m2 = $('#splitMethod2').val();
    const b2 = (m2 === 'Transferencia') ? ($('#splitBank2').val() || '') : '';

    // Validaciones
    if (v1 < 0 || v2 < 0) {
      Swal.fire('Valores inválidos', 'Los valores no pueden ser negativos.', 'error'); return;
    }
    if (v1 + v2 !== total) {
      Swal.fire('Suma incorrecta', 'La suma de los pagos debe ser igual al total.', 'error'); return;
    }
    if (m1 === 'Transferencia' && !b1) {
      Swal.fire('Falta banco', 'Selecciona el banco para el Pago 1.', 'warning'); return;
    }
    if (m2 === 'Transferencia' && !b2) {
      Swal.fire('Falta banco', 'Selecciona el banco para el Pago 2.', 'warning'); return;
    }

    // Primera venta (descuenta stock)
    $.ajax({
      url: 'register_sale.php',
      type: 'POST',
      dataType: 'json',
      data: {
        producto_id: pid,
        cantidad: cant,
        precio: precio,
        coste: coste,
        detalle: detalle,
        payment_method: m1,
        bank: b1,
        valor_override: v1 // <= usamos este valor exacto
      },
      success: function (res1) {
        if (res1.status !== 'success') {
          Swal.fire('Error', res1.message || 'No se pudo registrar el primer pago.', 'error');
          return;
        }

        // Actualizamos stock en la fila
        const $input = $(`#productos-table .cantidadVenta[data-producto-id='${pid}']`);
        if ($input.length) {
          $input.closest('tr').find('td:nth-child(3)').text(res1.nuevo_stock);
          $input.val(1);
          // resetear selects de esa fila
          const $tr = $input.closest('tr');
          $tr.find('.paymentMethod').val('Efectivo');
          $tr.find('.bankSelect').val('').addClass('d-none');
          $tr.find('.splitCheckbox').prop('checked', false);
        }

        // Segunda venta (NO descuenta stock)
        $.ajax({
          url: 'register_sale.php',
          type: 'POST',
          dataType: 'json',
          data: {
            producto_id: pid,
            cantidad: 0,        // <- no afecta stock
            precio: precio,
            coste: coste,
            detalle: detalle,
            payment_method: m2,
            bank: b2,
            valor_override: v2,
            skip_stock: 1       // <- bandera para no descontar stock
          },
          success: function (res2) {
            if (res2.status !== 'success') {
              Swal.fire('Atención', 'El segundo pago no se registró, revísalo manualmente.', 'warning');
            } else {
              $('#mensaje').html(`<div class='alert alert-success'>Pagos registrados correctamente.</div>`)
                           .stop(true, true).css('opacity', 1).show();
              setTimeout(()=> $('#mensaje').fadeOut(1000), 5000);
            }

            $('#modalSplit').modal('hide');
            setTimeout(function(){
              $('#ventas-table').DataTable().ajax.reload(null, false);
              if (typeof refreshVentas === 'function') refreshVentas();
            }, 400);
          },
          error: function () {
            Swal.fire('Error', 'No se pudo registrar el segundo pago.', 'error');
          }
        });
      },
      error: function () {
        Swal.fire('Error', 'No se pudo registrar el primer pago.', 'error');
      }
    });
  });
});
</script>

<script>
$(document).ready(function(){
  // Mostrar modal al hacer clic en el botón Ingreso
  $('#btnIngreso').click(function(){
    $('#modalIngreso').modal('show');
  });

  // Mostrar/ocultar selector de banco según método
  $('#ingresoMetodo').on('change', function(){
    if ($(this).val() === 'Transferencia') {
      $('#ingresoBancoWrap').removeClass('d-none');
    } else {
      $('#ingresoBancoWrap').addClass('d-none');
      $('#ingresoBanco').val('');
    }
  });

  // Enviar formulario de ingreso
  $('#formIngreso').submit(function(e){
    e.preventDefault();
    var formData = $(this).serialize();

    $.ajax({
      url: 'register_ingreso.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(res){
        if(res.status === 'success'){
          Swal.fire('Ingreso Registrado', res.message, 'success').then(function(){
            location.reload();
          });
        } else {
          Swal.fire('Error', res.message, 'error');
        }
      },
      error: function(){
        Swal.fire('Error', 'No se pudo registrar el ingreso (error de red).', 'error');
      }
    });
  });
});
</script>
	

	
</body>
</html>



















