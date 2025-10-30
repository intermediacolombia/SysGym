<?php 
require_once __DIR__ . '/../login/session.php';

require_once __DIR__ . '/../../inc/config.php';
//session_start();
 
$permisopage = 'Ver Clientes';
include('../login/restriction.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
      <meta charset='UTF-8'>
      <title>Error</title>
      <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
      <link href='https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css' rel='stylesheet'>
    </head>
    <body>
      <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
      <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js'></script>
      <script>
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se proporcionó el id del cliente.'
        }).then(function(){
          window.location.href = 'index.php';
        });
      </script>
    </body>
    </html>";
    exit;
}

$id = trim($_GET['id']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener datos del cliente (solo si no está borrado)
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND borrado = 0");
    $stmt->execute([':id' => $id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
          <meta charset='UTF-8'>
          <title>Error</title>
          <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
          <link href='https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css' rel='stylesheet'>
        </head>
        <body>
          <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
          <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js'></script>
          <script>
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Cliente no encontrado.'
            }).then(function(){
              window.location.href = 'index.php';
            });
          </script>
        </body>
        </html>";
        exit;
    }
    
    // Calcular edad si la fecha de nacimiento es válida
    $edad = "";
    if (!empty($cliente['fecha_nacimiento'])) {
        $birthDate = new DateTime($cliente['fecha_nacimiento']);
        $today = new DateTime();
        $edad = $today->diff($birthDate)->y;
    }
    
    // Obtener datos del plan asignado (si existe)
    $planInfo = null;
    if (!empty($cliente['plan'])) {
        $stmtPlan = $pdo->prepare("SELECT * FROM planes WHERE id = :plan AND borrado = 0");
        $stmtPlan->execute([':plan' => $cliente['plan']]);
        $planInfo = $stmtPlan->fetch(PDO::FETCH_ASSOC);
    }
    
    // Definir el color del encabezado según el estado del cliente
    $headerColor = ($cliente['estado'] === 'activo') ? '#28a745' : '#FD2D23';
    
    // Validar si la fecha de vencimiento está vencida
   /*$vencido = false;
    if (!empty($cliente['vencimiento_plan'])) {
        $vencimientoDate = new DateTime($cliente['vencimiento_plan']);
        $today = new DateTime();
        if ($today > $vencimientoDate) {
            $vencido = true;
        }
    }*/
	
	// Validar si la fecha de vencimiento está vencida (solo si venció antes de hoy)
$vencido = false;
if (!empty($cliente['vencimiento_plan'])) {
    $vencimientoDate = new DateTime($cliente['vencimiento_plan']);
    // Obtener la fecha actual sin hora
    $today = new DateTime(date('Y-m-d'));
    if ($vencimientoDate < $today) {
        $vencido = true;
    }
}

	// … tras cargar $planInfo …
$stmtCr = $pdo->prepare("
  SELECT COALESCE(SUM(valor),0)
  FROM creditos
  WHERE idCliente = :id AND estado = 0
");
$stmtCr->execute([':id' => $id]);
$pendingCredit = (float)$stmtCr->fetchColumn();

    
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle del Cliente</title>
  <?php include('../inc/header.php'); ?>
  <style>
	 <?php if ($cliente['congelado'] == 1): ?>
	  
	  .card-header {
      background-color: #888888;
      color: #fff;
		  -webkit-user-select: none; /* Chrome, Safari */
  -moz-user-select: none;    /* Firefox */
  -ms-user-select: none;     /* Internet Explorer/Edge */
  user-select: none;         /* Estándar */
    }
	  
	  <?php else: ?>
    .card-header {
      background-color: <?php echo $headerColor; ?>;
      color: #fff;
    }
	<?php endif; ?>
    .detail-label {
      font-weight: bold;
    }
    .detail-item {
      margin-bottom: 0.5rem;
    }
    .section-title {
      margin-top: 20px;
      margin-bottom: 10px;
      font-size: 1.5rem;
      font-weight: bold;
      border-bottom: 2px solid #ddd;
      padding-bottom: 5px;
    }
    .badge-vencido {
      background-color: red;
      color: white;
      border-radius: 50px;
      padding: 5px 10px;
      font-size: 0.9rem;
      margin-left: 10px;
    }
	  <?php if ($cliente['congelado'] == 1): ?>
	  .card-body {		  
		  color: #2424;
		  -webkit-user-select: none; /* Chrome, Safari */
  -moz-user-select: none;    /* Firefox */
  -ms-user-select: none;     /* Internet Explorer/Edge */
  user-select: none;         /* Estándar */
	  }
	  <?php endif; ?>

	  
	 


  </style>
	
	<style>
    /* Opcional: para ajustar el tamaño del modal o el iframe */
    .modal-lg {
      max-width: 90%;
    }
    #invoiceModalFrame {
      width: 100%;
      height: 80vh;
      border: none;
    }
 
.tooltip-wrapper {
    display: inline-block; /* Asegura que el contenedor tenga el mismo tamaño que el botón */
    position: relative; /* Necesario para que el tooltip funcione correctamente */
}

.tooltip-wrapper .btn.disabled {
    pointer-events: none; /* Desactiva interacciones con el botón deshabilitado */
    opacity: 0.65; /* Reduce la opacidad para indicar que está deshabilitado */
}
		
table{
	width: 100%!important;
}
		
.nav-link {
    color: #E21F0C!important;
}
		
.nav-link:focus, .nav-link:hover {
    color: #3A0102!important;;
}
		
.nav-link.active {
    color: #000!important;
}
		
		.foto-perfil {
  width: 180px;
  height: 180px;
  object-fit: cover;
  border-radius: 50%;
  border: 6px solid #00b16a;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.foto-perfil:hover {
  transform: scale(1.05);
  box-shadow: 0 0 20px rgba(0,0,0,0.25);
}
.card-header {
  border-top-left-radius: .5rem !important;
  border-top-right-radius: .5rem !important;
}
.card-body p {
  margin-bottom: .4rem;
}


#fotoModal img {
  max-height: 90vh;
  object-fit: contain;
}

</style>
	
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Perfil del Cliente <?php echo htmlspecialchars($cliente['nombres'] . " " . $cliente['apellidos']); ?></h1>
	 

  </div>
</div>
  <?php include('../inc/menu.php'); ?>
  <div class="container my-5">
	  <?php
      // Consulta el formulario asociado al cliente
      $stmtForm = $pdo->prepare("SELECT id FROM formularios WHERE cliente_id = :cliente_id ORDER BY id DESC LIMIT 1");
      $stmtForm->execute([':cliente_id' => $id]);
      $formulario = $stmtForm->fetch(PDO::FETCH_ASSOC);
      $formId = $formulario ? $formulario['id'] : null;
    ?>    
    <!-- Botones adicionales -->
<div class="mt-4 d-flex justify-content-between align-items-center">
  <!-- Botón a la izquierda -->
  
    <a href="index.php" class="btn btn-secondary">
      <i class="far fa-address-book"></i> Volver al listado
    </a>
   
  <!-- Botones a la derecha -->
  <div>
<?php if (isset($_SESSION["user_permissions"]) && in_array('Editar Clientes', $_SESSION["user_permissions"])): ?>
	<?php if ($cliente['congelado'] == 0): ?>
    <a href="edit.php?id=<?php echo urlencode($id); ?>" class="btn btn-primary mx-1">
      <i class="fa fa-edit"></i> Editar
    </a>
    <?php endif; ?>
<?php endif; ?>
	  
<?php if (isset($_SESSION["user_permissions"]) && in_array('Borrar Clientes', $_SESSION["user_permissions"])): ?>
	  <?php if ($cliente['congelado'] == 0): ?>
      <button id="btnDelete" class="btn btn-danger mx-1">
        <i class="fa fa-trash-o"></i> Borrar Cliente
      </button>
	  <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<br>
<br>

	  
<ul class="nav nav-tabs" id="clientTabs" role="tablist">

  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="perfil-tab" data-bs-toggle="tab" data-bs-target="#perfil" type="button" role="tab">
      <i class="fa fa-user me-1"></i> Perfil
    </button>
  </li>

<?php if (isset($_SESSION["user_permissions"])
          && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="asistencias-tab" data-bs-toggle="tab" data-bs-target="#asistencias" type="button" role="tab">
      <i class="fa fa-calendar-check me-1"></i> Asistencias
    </button>
  </li>
<?php endif; ?>

<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Historial  de Facturas del Cliente', $_SESSION["user_permissions"])): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="facturas-tab" data-bs-toggle="tab" data-bs-target="#facturas" type="button" role="tab">
      <i class="fas fa-file-invoice-dollar me-1"></i> Facturas
    </button>
  </li>
<?php endif; ?>
	
<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="creditos-tab" data-bs-toggle="tab" data-bs-target="#creditos" type="button" role="tab">
      <i class="fa fa-credit-card me-1"></i> Créditos
    </button>
  </li>
<?php endif; ?>
	

	<?php if (isset($_SESSION["user_permissions"]) && in_array('Manejar Valoraciones', $_SESSION["user_permissions"])): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="valoraciones-tab" data-bs-toggle="tab" data-bs-target="#valoraciones" type="button" role="tab">
      <i class="fas fa-heartbeat me-1"></i> Valoraciones
    </button>
  </li>
<?php endif; ?>
	

  <li class="nav-item" role="presentation">
    <button class="nav-link" id="rutinas-tab" data-bs-toggle="tab" data-bs-target="#rutinas" type="button" role="tab" disabled>
      <i class="material-icons" style="font-size:16px">fitness_center</i> Rutinas
    </button>
  </li>


</ul>
	  

<div class="tab-content mt-3" id="clientTabsContent">
	
	<div class="tab-pane fade show active" id="perfil" role="tabpanel" aria-labelledby="perfil-tab">
		<?php include('tabs/perfil_tab.php');?>	
    </div>

	<div class="tab-pane fade" id="asistencias" role="tabpanel" aria-labelledby="asistencias-tab">	
	<?php include('tabs/asistencias_tab.php');?>
	</div>

	<div class="tab-pane fade" id="facturas" role="tabpanel" aria-labelledby="facturas-tab">
	<?php include('tabs/facturas_tab.php');?>
	</div>



	 <div class="tab-pane fade" id="creditos" role="tabpanel" aria-labelledby="creditos-tab">
	<?php include('tabs/creditos_tab.php');?>	 
	</div>

	<div class="tab-pane fade" id="valoraciones" role="tabpanel" aria-labelledby="valoraciones-tab">
		<?php include('tabs/valoraciones_tab.php');?>
	</div>
	
</div>

<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
<!-- Modal Crédito Pendiente -->
<div class="modal fade" id="pendingCreditModal" tabindex="-1" aria-labelledby="pendingCreditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header justify-content-center">
        <h5 class="modal-title" id="pendingCreditModalLabel">Créditos Pendientes</h5>
      </div>
      <div class="modal-body text-center">
        <!-- Imagen de alerta -->
        <img
          src="https://cdn-icons-gif.flaticon.com/6844/6844353.gif"
          alt="Alerta Crédito"
          class="mb-3"
          style="max-width:100px; height:auto;"
        >
        <p>El cliente tiene uno o más créditos pendientes. ¿Qué quieres hacer?</p>
      </div>
      <div class="modal-footer">
        <button type="button" id="payCreditBtn" class="btn btn-primary">Pagar Crédito</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Omitir</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
	  
<?php if (isset($_SESSION["user_permissions"]) && in_array('Transferir Planes', $_SESSION["user_permissions"])): ?>	  
<!-- Modal Transferir Plan -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-narrow"> <!-- Clase personalizada -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Transferir Plan a Otro Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Mensaje informativo -->
        <div class="alert alert-info">
          Solo se puede transferir el plan a clientes <strong>sin plan asignado</strong> o con el <strong>plan vencido</strong>.
        </div>

        <input type="text" class="form-control mb-3" id="searchClientInput" placeholder="Buscar por cédula, nombre o apellido">
		  
		<!--span id="selectedClientInfo" class="me-auto fw-bold"></span-->
		 
        <div id="searchResults" class="list-group" style="max-height: 300px; overflow-y: auto;"></div>
		  
      </div>
      <div class="modal-footer d-none" id="confirmTransferFooter">
        
        <button type="button" class="btn btn-success" id="confirmTransferBtn">Confirmar Transferencia</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
	  
	 








	



	
	
  <?php include('../inc/menu-footer.php'); ?>  
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap 5 JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>	
	<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script><br>

	<!-- Pasar la variable PHP a JS -->
 <script>
  var pendingCredit = <?php echo json_encode((int)$pendingCredit); ?>;

  document.addEventListener('DOMContentLoaded', function () {
    if (pendingCredit > 0) {
      var pendingModal = new bootstrap.Modal(document.getElementById('pendingCreditModal'), {
        backdrop: 'static',
        keyboard: false
      });

      // Espera a que todo el DOM esté cargado, incluyendo las pestañas
      setTimeout(() => {
        pendingModal.show();
      }, 100);

      document.getElementById('payCreditBtn').addEventListener('click', function () {
        pendingModal.hide();

        // Activar la pestaña "Créditos"
        const creditTabBtn = document.querySelector('#clientTabs button[data-bs-target="#creditos"]');
        if (creditTabBtn) {
          const tabInstance = new bootstrap.Tab(creditTabBtn);
          tabInstance.show();

          // Hacer scroll después de que se muestre la pestaña
          setTimeout(() => {
            const tbl = document.getElementById('creditos');
            if (tbl) {
              tbl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          }, 300);
        }
      });
    }
  });
</script>


	
<script>
$(document).ready(function(){
	
	
	
  /*-----------------------------
    Botón: Borrar Cliente
  -----------------------------*/
  $("#btnDelete").on("click", function(){
    Swal.fire({
      title: "¿Está seguro?",
      text: "Esta acción borrará el cliente.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, borrar",
      cancelButtonText: "Cancelar"
    }).then((result) => {
      if(result.isConfirmed){
        window.location.href = "delete.php?id=<?php echo urlencode($id); ?>";
      }
    });
  });

  /*-----------------------------
    Botón: Anular Plan
  -----------------------------*/
  // Usamos delegación de eventos para asegurarnos de que el botón exista en el DOM
  $(document).on("click", "#btnNullPlan", function(){
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Se anulará el plan asignado a este cliente y quedará sin plan.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, anular",
      cancelButtonText: "Cancelar"
    }).then((result) => {
      if(result.isConfirmed){
        $.ajax({
          url: "null-plan.php",
          method: "POST",
          data: { id: "<?php echo $id; ?>" },
          dataType: "json",
          success: function(response){
            if(response.status === 'success'){
              Swal.fire("Éxito", response.message, "success").then(function(){
                window.location.reload();
              });
            } else {
              Swal.fire("Error", response.message, "error");
            }
          },
          error: function(){
            Swal.fire("Error", "No se pudo anular el plan.", "error");
          }
        });
      }
    });
  });

  /*-----------------------------
    (Opcional) Botón: Congelar Plan
    (Código comentado; descomentar para activarlo)
  -----------------------------*/
  
  $("#btnFreezePlan").on("click", function(){
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Se congelará el plan del cliente.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, congelar",
      cancelButtonText: "Cancelar"
    }).then((result) => {
      if(result.isConfirmed){
        $.ajax({
          url: "freeze_plan.php",
          method: "POST",
          data: { id: "<?php echo $id; ?>" },
          dataType: "json",
          success: function(response){
            if(response.status === 'success'){
              Swal.fire("Éxito", response.message, "success").then(function(){
                window.location.reload();
              });
            } else {
              Swal.fire("Error", response.message, "error");
            }
          },
          error: function(){
            Swal.fire("Error", "No se pudo congelar el plan.", "error");
          }
        });
      }
    });
  });
  
	
/*-----------------------------
    (Opcional) Botón: desCongelar Plan
    (Código comentado; descomentar para activarlo)
  -----------------------------*/
  
  $("#btnUnFreezePlan").on("click", function(){
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Se descongelará el plan del cliente.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, descongelar",
      cancelButtonText: "Cancelar"
    }).then((result) => {
      if(result.isConfirmed){
        $.ajax({
          url: "unfreeze_plan.php",
          method: "POST",
          data: { id: "<?php echo $id; ?>" },
          dataType: "json",
          success: function(response){
            if(response.status === 'success'){
              Swal.fire("Éxito", response.message, "success").then(function(){
                window.location.reload();
              });
            } else {
              Swal.fire("Error", response.message, "error");
            }
          },
          error: function(){
            Swal.fire("Error", "No se pudo descongelar el plancito.", "error");
          }
        });
      }
    });
  });
	
	
	// Abrir modal al hacer clic en Transferir Plan
$("#btnTransferPlan").on("click", function () {
  $("#searchClientInput").val("");
  $("#searchResults").empty();
  $("#confirmTransferFooter").addClass('d-none');
  $("#transferModal").modal('show');
});

// Buscar cliente por nombre o cédula
$("#searchClientInput").on("input", function () {
  const query = $(this).val().trim();
  if (query.length < 3) return;

  $.getJSON("buscar_clientes.php", { q: query, exclude_id: <?= $id ?> }, function (data) {
    const $results = $("#searchResults").empty();
    if (data.length === 0) {
      $results.append(`<div class="list-group-item">Sin resultados</div>`);
    } else {
      data.forEach(c => {
        const item = $(`
          <button type="button" class="list-group-item list-group-item-action">
            ${c.nombres} ${c.apellidos} (${c.identificacion})
          </button>
        `);
        item.data("cliente", c);
        item.on("click", function () {
          const cliente = $(this).data("cliente");
          $("#searchResults").html(`
  <div class="text-center mb-3">
    <div class="alert alert-warning mb-2">
      Origen: <strong><?php echo htmlspecialchars($cliente['nombres'] . " " . $cliente['apellidos']); ?> (<?php echo htmlspecialchars($cliente['identificacion']); ?>)</strong>
    </div>

    <div style="font-size: 24px; color: #6c757d; margin: 10px 0;">
      <i class="fas fa-arrow-down"></i><br>
      <!--i class="fas fa-exchange-alt fa-2x"></i><br>
      <i class="fas fa-arrow-up"></i-->
    </div>

    <div class="alert alert-success mt-2">
      Destino: <strong>${cliente.nombres} ${cliente.apellidos} (${cliente.identificacion})</strong>
    </div>
  </div>
`);

          $("#selectedClientInfo").text(`Seleccionado: ${cliente.nombres} ${cliente.apellidos} (${cliente.identificacion})`);
          $("#confirmTransferFooter").removeClass('d-none').data("clienteId", cliente.id);
        });
        $results.append(item);
      });
    }
  });
});

// Confirmar transferencia
$("#confirmTransferBtn").on("click", function () {
  const destinoId = $("#confirmTransferFooter").data("clienteId");
  const origenId = <?= $id ?>;

  Swal.fire({
    title: '¿Transferir plan?',
    text: 'Se moverá el plan del cliente actual al seleccionado',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, transferir',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post("transferir_plan.php", {
        origen_id: origenId,
        destino_id: destinoId
      }, function (response) {
        if (response.status === 'success') {
          Swal.fire("Transferido", response.message, "success").then(() => location.reload());
        } else {
          Swal.fire("Error", response.message, "error");
        }
      }, "json");
    }
  });
});

  	

  /*-----------------------------
    Mostrar mensajes de sesión (si existen)
  -----------------------------*/
  <?php if(isset($_SESSION['success'])): ?>
    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: '<?php echo $_SESSION['success']; ?>'
    });
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
  <?php if(isset($_SESSION['error'])): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '<?php echo $_SESSION['error']; ?>'
    });
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  /*-----------------------------
    DataTable: Facturas
  -----------------------------*/
  var facturasTable = $('#facturas-table').DataTable({
    "ajax": {
      "url": "get_facturas.php",
      "type": "GET",
      "data": { client_id: "<?php echo $id; ?>" },
      "dataSrc": "data"
    },
    "columns": [
      { "data": "id" },
      { "data": "fecha" },
      { "data": "detalle" },
      { 
        "data": "valor",
        "render": function(data, type, row) {
          var num = parseFloat(data);
          if (isNaN(num)) return data;
          return "$" + Math.round(num).toLocaleString('es-CO');
        }
      }
    ],
    "order": [[0, "desc"]],
    "language": {
      "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
    },
    "footerCallback": function ( row, data, start, end, display ) {
      var api = this.api();
      var intVal = function(i) {
        if (typeof i === 'string') {
          return Math.round(parseFloat(i));
        } else if (typeof i === 'number') {
          return Math.round(i);
        }
        return 0;
      };
      var total = api.column(3, { search: 'applied' }).data().reduce(function(a, b) {
        return intVal(a) + intVal(b);
      }, 0);
      $("#totalGlobal").html("Total Global: $" + total.toLocaleString('es-CO'));
    }
  });
  
  // Al hacer clic en una fila de la tabla de facturas, redirige al PDF correspondiente
  $('#facturas-table tbody').on('click', 'tr', function() {
    var data = facturasTable.row(this).data();
    if(data && data.id) {
      // Construir la URL a cargar (asegúrate de que la ruta sea correcta)
          var invoiceUrl = "/pdf/invoice.php?id=" + encodeURIComponent(data.id);
			var invoiceUrlDw = "/pdf/?type=invoice&id=" + encodeURIComponent(data.id);
          $("#invoiceModalFrame").attr("src", invoiceUrl);
          $("#downloadInvoice").attr("href", invoiceUrlDw);
          // Abrir el modal
          var myModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
          myModal.show();
    }
  });
	
$('#sendInvoice').on('click', function() {
    var invoiceId = $('#invoiceModalFrame').attr('src').split('id=')[1];
    
    Swal.fire({
        title: 'Confirmar Envío',
        text: '¿Deseas enviar esta factura al cliente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'send_invoice.php',
                method: 'POST',
                data: { invoice_id: invoiceId },
                dataType: 'json',
                success: function(response){
                    if(response.status === 'success'){
                        Swal.fire('Éxito', response.message, 'success');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(){
                    Swal.fire('Error', 'No se pudo enviar la factura.', 'error');
                }
            });
        }
    });
});
	
	
$('#resendConsent').on('click', function() {
    var clientId = "<?php echo $id; ?>"; // ID del cliente actual
    
    Swal.fire({
        title: 'Confirmar Reenvío',
        text: '¿Deseas reenviar el consentimiento informado al cliente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'resend_consent.php',
                method: 'POST',
                data: { client_id: clientId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Éxito', response.message, 'success');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo reenviar el consentimiento.', 'error');
                }
            });
        }
    });
});
	
	/*$('#facturas-table tbody').on('click', 'tr', function() {
    var data = facturasTable.row(this).data();
    if(data && data.id) {
      window.location.href = "https://sysgym.intermediacolombia.com/pdf/?id=" + encodeURIComponent(data.id) + "&type=invoice";
    } else {
      console.error("No se encontró el id en la fila");
    }
  });*/
	
	// Al hacer clic en una fila, cargar la factura en el modal
      /*$('#tabla-facturas tbody').on('click', 'tr', function() {
        var data = table.row(this).data();
        if(data && data.id) {
          // Construir la URL a cargar (asegúrate de que la ruta sea correcta)
          var invoiceUrl = "/pdf/invoice.php?id=" + encodeURIComponent(data.id);
          $("#invoiceModalFrame").attr("src", invoiceUrl);
          // Abrir el modal
          var myModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
          myModal.show();
        }
      });*/

  /*-----------------------------
    DataTable: Créditos
  -----------------------------*/
 /*-----------------------------
    DataTable: Créditos
  -----------------------------*/
if ( ! $.fn.DataTable.isDataTable('#creditos-table') ) {
  var creditosTable = $('#creditos-table').DataTable({
    "ajax": {
      "url": "get_creditos.php",
      "type": "GET",
      "data": { client_id: "<?php echo $id; ?>" },
      "dataSrc": "data"
    },
    "columns": [
      { // ✅ Columna de selección múltiple
        "data": "id",
        "orderable": false,
        "searchable": false,
        "render": function(data, type, row) {
          return `<input type="checkbox" class="credit-check" data-id="${data}">`;
        },
        "className": "text-center"
      },
      { "data": "fecha" },
      { 
        "data": "valor",
        "render": function(data, type, row) {
          var num = parseFloat(data);
          return isNaN(num) ? data : "$" + Math.round(num).toLocaleString('es-CO');
        }
      },
      { "data": "fecha_limite" },
      { "data": "descripcion" }
    ],
    "order": [[1, "desc"]], // ahora la columna 1 (fecha)
    "language": {
      "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
    }
  });

  /*───────────────────────────────────────────────
   * SELECCIÓN INDIVIDUAL Y GLOBAL DE CRÉDITOS
   *───────────────────────────────────────────────*/
  // Marcar/deseleccionar todos
  $('#selectAllCredits').on('change', function() {
    const checked = this.checked;
    $('#creditos-table tbody input[type="checkbox"]').prop('checked', checked);
    togglePagoMasivoButton();
  });

  // Detectar cambio individual
  $(document).on('change', '#creditos-table tbody input[type="checkbox"]', togglePagoMasivoButton);

  function togglePagoMasivoButton() {
    const anySelected = $('#creditos-table tbody input[type="checkbox"]:checked').length > 0;
    $('#btnPagarCreditosWrapper').toggle(anySelected);
  }

  /*───────────────────────────────────────────────
   * CLICK EN FILA: Editar crédito (mantiene flujo actual)
   *───────────────────────────────────────────────*/
  $('#creditos-table tbody').on('click', 'tr', function(e) {
    // Evitar abrir modal si se hizo clic en checkbox
    if ($(e.target).is('input[type="checkbox"]')) return;

    var data = creditosTable.row(this).data();
    if(data) {
      $('#editCreditId').val(data.id);
      $('#editCreditFecha').val(data.fecha);
      $('#editCreditFechaLimite').val(data.fecha_limite);
      $('#editCreditDescripcion').val(data.descripcion);
      $('#creditDetail').text(data.descripcion);

      var originalValue = parseFloat(data.valor);
      $('#originalCreditValue').text("$" + originalValue.toLocaleString('es-CO'));
      $('#editCreditForm').data('originalValue', originalValue);
      $('#remainingValueDisplay').text("Valor Restante: $" + originalValue.toLocaleString('es-CO'));
      $('#editPago').val("");

      var editModal = new bootstrap.Modal(document.getElementById('editCreditModal'));
      editModal.show();
    }
  });

  /*───────────────────────────────────────────────
   * Actualizar valor restante en pago individual
   *───────────────────────────────────────────────*/
  $('#editCreditForm #editPago').on('input', function() {
    var pago = parseFloat($(this).val());
    if (isNaN(pago)) { pago = 0; }
    var originalValue = $('#editCreditForm').data('originalValue') || 0;
    if (pago > originalValue) {
      pago = originalValue;
      $(this).val(pago);
    }
    var restante = originalValue - pago;
    $('#remainingValueDisplay').text("Valor Restante: $" + restante.toLocaleString('es-CO'));
  });

  /*───────────────────────────────────────────────
   * Pago individual vía AJAX
   *───────────────────────────────────────────────*/
  $('#editCreditForm').on('submit', function(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Confirmar Pago',
      text: '¿Desea aplicar el pago al crédito?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, aplicar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        var formData = $(this).serialize();
        $.ajax({
          url: 'update_credito.php',
          method: 'POST',
          data: formData,
          dataType: 'json',
          success: function(response) {
            if(response.status === 'success'){
              Swal.fire('Éxito', response.message, 'success');
              creditosTable.ajax.reload(null, false);
              $('#editCreditModal').modal('hide');
            } else {
              Swal.fire('Error', response.message, 'error');
            }
          },
          error: function() {
            Swal.fire('Error', 'No se pudo actualizar el crédito.', 'error');
          }
        });
      }
    });
  });
}

	
  /*-----------------------------
    DataTable: Valoraciones
  -----------------------------*/
// --------------------------------------------------
// 1) Inicializa tu DataTable SIN la columna de editar
// --------------------------------------------------
var valTable = $('#valoraciones-table').DataTable({
  ajax: {
    url: 'get_valoraciones.php',
    type: 'GET',
    data: { cliente_id: '<?= $id ?>' },
    dataSrc: 'data'
  },
  columns: [
    { data: 'fecha' },
    { data: 'peso', render: d => parseFloat(d).toFixed(2) },
    { data: 'estatura', render: d => parseFloat(d).toFixed(2) },
    { data: 'imc', render: d => d ? parseFloat(d).toFixed(2) : '' },
    { data: 'porcentaje_grasa_corporal', render: d => d ? parseFloat(d).toFixed(2) : '' },
    { data: 'porcentaje_musculo_esqueletico', render: d => d ? parseFloat(d).toFixed(2) : '' },
    { data: 'metabolismo_basal' },
    { data: 'edad_corporal' },
    { data: 'nivel_grasa_visceral' }
  ],
  order: [[0,'desc']],
  language: {
    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
  }
});

// --------------------------------------------------
// 2) Al hacer click en una fila, ve a edit.php?id=…
// --------------------------------------------------
$('#valoraciones-table tbody').on('click', 'tr', function(){
  var data = valTable.row(this).data();
  if (data && data.id) {
    window.location.href = '/admin/valoraciones/edit.php?id=' + data.id;
  }
});

  /*-----------------------------
    DataTable: Mensajes (Modal)
  -----------------------------*/
  $('#mensajesTable').DataTable({
    destroy: true,
    ajax: {
      url: 'get_all_send.php',
      type: 'GET',
      data: { id: "<?php echo $id; ?>" },
      dataSrc: 'data.data'
    },
    columns: [
      { data: 'phonenumber' },
      { 
        data: 'text',
        render: function(data) { return data.replace(/\n/g, "<br>"); }
      },
      { data: 'status' },
      { data: 'statusInfo' },
      { data: 'delivery' },
      { data: 'createdAt' },
      { data: 'executedAt' },
      { 
        data: 'url',
        render: function(data) { return data ? '<a href="'+data+'" target="_blank">Ver PDF</a>' : 'Sin URL'; }
      }
    ],
    order: [[5, 'desc']],
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
    }
  });

  /*-----------------------------
    Modal de Edición de Crédito: Selección de Medio de Pago
  -----------------------------*/
  $("#editCreditPaymentMethod").on("change", function(){
    if ($(this).val() === 'Transferencia') {
      $("#editCreditBankDiv").show();
    } else {
      $("#editCreditBankDiv").hide();
      $("#editCreditBankSelection").val("");
    }
  });

});
</script>
	
	
	
<script>
$(document).ready(function(){
  var originalTotal = <?php echo isset($planInfo['precio']) ? floatval($planInfo['precio']) : 0; ?>;
  var paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'), { keyboard: false });
  var processingPayment = false;

  function limitarPagosDivididos() {
    let first = parseFloat($("#first_payment_value").val()) || 0;
    let second = parseFloat($("#second_payment_value").val()) || 0;
    const total = first + second;

    if (total > originalTotal) {
      const exceso = total - originalTotal;
      if (document.activeElement.id === "first_payment_value" && first > 0) {
        first -= exceso;
        $("#first_payment_value").val(first);
      } else if (document.activeElement.id === "second_payment_value" && second > 0) {
        second -= exceso;
        $("#second_payment_value").val(second);
      }
    }
  }

  $("#first_payment_value, #second_payment_value").on("input", limitarPagosDivididos);

  $("#btnPago").click(function(){
    $("#paymentForm")[0].reset();
    $("#bankDiv, #creditFields, #secondPaymentWrapper, #secondBankDiv, #firstPaymentValueWrapper").hide();
    $("#valorRestanteDisplay").text("Valor Restante: $" + originalTotal.toLocaleString('es-CO'));
    $("#originalTotalDisplay").text("$" + originalTotal.toLocaleString('es-CO'));
	  
	  // Mostrar las fechas actuales del cliente
  const fechaPago = $("#fecha_pago_display").text() || "--";
  const fechaVenc = $("#fecha_vencimiento_display").text() || "--";
  $("#fechaPagoActual").text(fechaPago);
  $("#fechaVencimientoActual").text(fechaVenc);
  $("#fechasActualesInfo").show();
	  
    paymentModal.show();
  });

  $("#payment_method").change(function(){
    $("#bankDiv").toggle($(this).val() === 'Transferencia');
  });

  $("#second_payment_method").change(function(){
    $("#secondBankDiv").toggle($(this).val() === 'Transferencia');
  });

  $("#credit_check").change(function(){
    $("#creditFields").toggle($(this).is(":checked"));
  });

  $("#split_payment_check").change(function(){
    const isChecked = $(this).is(":checked");
    $("#secondPaymentWrapper, #firstPaymentValueWrapper").toggle(isChecked);
    if (!isChecked) {
      $("#first_payment_value, #second_payment_value, #second_bank, #second_payment_method").val('');
      $("#secondBankDiv").hide();
    }
  });

  $("#valor_pagado").on("input", function(){
    let paid = parseFloat($(this).val()) || 0;
    let remaining = Math.max(originalTotal - paid, 0);
    $("#valorRestanteDisplay").text("Valor Restante: $" + remaining.toLocaleString('es-CO'));
  });

  $("#confirmPaymentBtn").off('click').on('click', function(){
    if (processingPayment) return;
    processingPayment = true;

    let paymentMethod = $("#payment_method").val();
    let bank = $("#bank").val();
    let creditSelected = $("#credit_check").is(":checked");
    let valorPagado = creditSelected ? $("#valor_pagado").val() : null;
    let fechaPlazo = creditSelected ? $("#fecha_plazo").val() : null;
    let splitPayment = $("#split_payment_check").is(":checked");
    let secondMethod = $("#second_payment_method").val();
    let secondBank = $("#second_bank").val();
    let firstValue = parseFloat($("#first_payment_value").val()) || 0;
    let secondValue = parseFloat($("#second_payment_value").val()) || 0;

    if (!paymentMethod) { Swal.fire("Atención","Seleccione un medio de pago","warning"); processingPayment=false; return; }
    if (paymentMethod === 'Transferencia' && !bank) { Swal.fire("Atención","Seleccione un banco","warning"); processingPayment=false; return; }
    if (splitPayment && (!secondMethod || secondValue <= 0)) { Swal.fire("Atención","Complete los datos del segundo pago","warning"); processingPayment=false; return; }

    paymentModal.hide();

    Swal.fire({
      title: "Confirmar Pago",
      text: "¿Deseas marcar este pago y actualizar las fechas?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, confirmar",
      cancelButtonText: "Cancelar"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: 'update_pago.php',
          method: 'POST',
          data: {
            id: '<?php echo $id; ?>',
            paymentMethod: paymentMethod,
            bank: bank,
            credit: creditSelected,
            valorPagado: valorPagado,
            fechaPlazo: fechaPlazo,
            splitPayment: splitPayment,
            secondPaymentMethod: secondMethod,
            secondBank: secondBank,
            first_payment_value: firstValue,
            second_payment_value: secondValue,
			respetarFechas: $('#respetarFechas').is(':checked') ? 1 : 0
          },
          dataType: 'json',
          success: function(response){
            if(response.status === 'success'){
              Swal.fire('Éxito', response.message, 'success');
              $("#fecha_pago_display").text(response.pago_plan);
              $("#fecha_vencimiento_display").text(response.vencimiento_plan).css("color", "red");
            } else {
              Swal.fire('Error', response.message, 'error');
            }
            processingPayment = false;
          },
          error: function(){
            Swal.fire('Error', 'No se pudo actualizar la fecha de pago.', 'error');
            processingPayment = false;
          }
        });
      } else {
        processingPayment = false;
      }
    });
  });
});
</script>

	
	
<script>

$(document).ready(function(){
  // Cuando cambie el medio de pago en el modal de editar crédito
  $("#editCreditPaymentMethod").change(function(){
    if ($(this).val() === 'Transferencia') {
      $("#editCreditBankDiv").show();
    } else {
      $("#editCreditBankDiv").hide();
      // Opcional: limpiar la selección del banco si cambia a efectivo
      $("#editCreditBankSelection").val("");
    }
  });
});

</script>
<script>
$(document).ready(function(){
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>


<script>
$(function(){

  const clienteId = <?= $id ?>;      // mismo id del detalle
  let dataCache   = [];              // guardaremos todas las valoraciones

  /* ---------- 1. Cargar fechas al abrir el modal ---------- */
  $('#comparisonModal').on('show.bs.modal', function(){

    // Obtener listado de valoraciones solo si aún no se cargó
    if (dataCache.length === 0) {
      $.getJSON('get_valoraciones.php',
        { cliente_id: clienteId, full: 0 },   // full=0 sólo devuelve id y fecha
        function(resp){
          dataCache = resp.data;
          fillSelects();
        });
    } else { fillSelects(); }

    function fillSelects(){
      const $a = $('#valA').empty();
      const $b = $('#valB').empty();
      dataCache.forEach(v => {
        const txt = `${v.fecha}`;
        $('<option>').val(v.id).text(txt).appendTo($a);
        $('<option>').val(v.id).text(txt).appendTo($b);
      });
    }
  });

  /* ---------- 2. Comparar al hacer clic ---------- */
  $('#compareBtn').on('click', function(){

    const idA = $('#valA').val();
    const idB = $('#valB').val();
    if (!idA || !idB || idA === idB) {
      Swal.fire('Atención','Seleccione dos valoraciones diferentes','warning');
      return;
    }

    // Cargar ambas valoraciones en paralelo
    $.when(
      $.getJSON('valoracion_full.php', { id: idA }),
      $.getJSON('valoracion_full.php', { id: idB })
    ).done(function(resA, resB){

      const a = resA[0], b = resB[0];
      if (a.error || b.error) {
        Swal.fire('Error','No se pudieron cargar las valoraciones','error');
        return;
      }

      renderComparison(a, b);
    });
  });

  /* ---------- 3. Pintar tabla de diferencias ---------- */
  function renderComparison(a, b){

    // Campos a comparar (label → columna en BD)
    const campos = {
  // Datos generales
  "Peso (Kg)"                : "peso",
  "Estatura (m)"             : "estatura",
  "IMC"                      : "imc",
  "% Grasa Corporal"         : "porcentaje_grasa_corporal",
  "% Músculo Esquelético"    : "porcentaje_musculo_esqueletico",
  "Metabolismo Basal (kcal)" : "metabolismo_basal",
  "Edad Corporal (años)"     : "edad_corporal",
  "Grasa Visceral (nivel)"   : "nivel_grasa_visceral",

  // Perímetros / pliegues corporales
  "Hombros"                  : "hombros",
  "Pecho"                    : "pecho",
  "Abdomen"                  : "abdomen",
  "Cintura"                  : "cintura",
  "Cadera"                   : "cadera",

  // Extremidad IZQUIERDA
  "Brazo Izq."               : "izq_brazo",
  "Antebrazo Izq."           : "izq_antebrazo",
  "Muñeca Izq."              : "izq_muneca",
  "Muslo Medio Izq."         : "izq_muslo_medio",
  "Pantorrilla Izq."         : "izq_pantorrilla",

  // Extremidad DERECHA
  "Brazo Der."               : "der_brazo",
  "Antebrazo Der."           : "der_antebrazo",
  "Muñeca Der."              : "der_muneca",
  "Muslo Medio Der."         : "der_muslo_medio",
  "Pantorrilla Der."         : "der_pantorrilla"
};


    let html = `
      <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>Medida</th>
            <th>${a.fecha}</th>
            <th>${b.fecha}</th>
            <th>∆</th>
          </tr>
        </thead><tbody>
    `;

    $.each(campos, function(label, col){

      const valA = parseFloat(a[col]) || 0;
      const valB = parseFloat(b[col]) || 0;
      const diff = (valB - valA).toFixed(2);

      let icon  = '<i class="fa fa-minus text-muted"></i>';
      let clazz = '';
      if (diff > 0) { icon = '<i class="fa fa-arrow-up text-success"></i>'; clazz='table-success'; }
      if (diff < 0) { icon = '<i class="fa fa-arrow-down text-danger"></i>'; clazz='table-danger'; }

      html += `
        <tr class="${clazz}">
          <td class="text-start">${label}</td>
          <td>${valA}</td>
          <td>${valB}</td>
          <td>${icon} ${Math.abs(diff)}</td>
        </tr>`;
    });

    html += '</tbody></table>';
    $('#comparisonResult').html(html);
  }

});
</script>

<script>
if ( ! $.fn.DataTable.isDataTable('#asistencias-table') ) {
  $('#asistencias-table').DataTable({
    ajax: {
      url : 'get_asistencias.php',
      type: 'GET',
      data: { cliente_id: '<?= $id ?>' },
      dataSrc: 'data'
    },
    columns: [
		{                               // 1  ← día de la semana
        data: 'dia_semana',
        render: d => d.charAt(0).toUpperCase()+d.slice(1)   // “lunes” ➜ “Lunes”
      },
      { data: 'fecha' },              // 0
      
      {                               // 2 hora
        data: 'hora',
        render: function(data, type){
          if (type === 'display'){
            const [h,m] = data.split(':');
            let hh = parseInt(h,10), suf = ' am';
            if (hh === 0) hh = 12;
            else if (hh >= 12){ suf=' pm'; if (hh>12) hh-=12; }
            return hh+':'+m+suf;
          }
          return data;                // ordenar con 24 h
        }
      }
    ],
    order: [[1,'desc'],[2,'desc']],   // fecha ↓, luego hora ↓
    language: { url:
      '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
  });
}

</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
  const clienteFoto = document.getElementById('clienteFoto');
  const fotoAmpliada = document.getElementById('fotoAmpliada');
  const fotoModal = document.getElementById('fotoModal');

  clienteFoto.addEventListener('click', function() {
    const src = this.getAttribute('src');
    fotoAmpliada.src = src;
  });

  fotoModal.addEventListener('hidden.bs.modal', function() {
    fotoAmpliada.src = '';
  });
});
</script>
	

</body>
</html>