<?php require_once __DIR__ . '/login/session.php';
?>
<?php
session_start();
require_once __DIR__ . '/../inc/config.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Sistema</title>
  <?php include('inc/header.php'); ?>
 
  
  <style>
    
	  
	  .text-primary {
    color: #000!important;
}
    .card-custom {
      background: #fff;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .section-title {
      font-size: 1.25rem;
      margin-bottom: 15px;
      border-bottom: 2px solid #eee;
      padding-bottom: 5px;
		font-weight: bold;
    }
	  
	  
	  /* Estilos para la tabla */
.custom-table {
  width: 100%;              /* Ajusta el ancho de la tabla */
  font-size: 12px!important;          /* Tamaño de fuente; cámbialo según necesites */
  border-collapse: collapse; /* Elimina espacios entre celdas */
}

/* Estilos para encabezados y celdas */
.custom-table thead {
  background-color: #343a40; /* Color de fondo del encabezado */
  color: #fff;               /* Color de texto del encabezado */
}

.custom-table th,
.custom-table td {
  padding: 12px;             /* Espaciado interno */
  border: 1px solid #ddd;    /* Borde de celdas */
  text-align: left;          /* Alineación del texto */
}

/* Efecto hover en filas */
.custom-table tbody tr:hover {
  background-color: #f8f9fa; /* Color de fondo al pasar el ratón */
}
	  

	  /* contenedor de bienvenida */
.welcome-wrapper{
  position: relative;                 /*  ⤵  el contador se posiciona sobre él   */
}

/* tarjeta–contador: misma línea, pegada al borde derecho */
.asistencias-counter{
  position: absolute;                 /* sale del flujo normal */
  right: 0;                           /* borde derecho */
  top: 50%;                           /* centrado vertical respecto al h1 */
  transform: translateY(-50%);        /* ajusta al centro exacto */
  width: 250px;
  background: linear-gradient(135deg,#E21F0C 0%,#8A0002 100%);
  border-radius: 12px;
  color: #fff;
  box-shadow: 0 4px 10px rgba(0,0,0,.15);
}
.asistencias-counter .display-4{
  font-size: 40px;
  font-weight: 700;
  line-height: 1;
}

  </style>
	
	

</head>
<body>
	
	
	
	
  <?php include('inc/menu.php'); ?>
	
	<?php
session_start();
if(isset($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php 
      echo $_SESSION['error'];
      unset($_SESSION['error']);
    ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php endif; ?>

  <div class="container my-5">
    <!-- ——— Bienvenida + contador en una sola línea ——— -->
<div class="welcome-wrapper position-relative mb-4">
  <h1 class="welcome-title text-primary text-center m-0">
    Bienvenido, <br><span style="color:#E21F0C;"><?php echo htmlspecialchars($nombre . " " . $apellido); ?></span>
  </h1>
<?php include('statistics_home.php'); ?>
	
</div>


	  
    <div class="card card-custom">
      <div class="row">
        <!-- Primera Columna -->
        <div class="col-md-6">
			
          <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes que cumplen años hoy', $_SESSION["user_permissions"])): ?>
          <?php include('bd-today.php'); ?>
			<?php endif; ?>
		<hr>
		
			
			
			<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>			
			<?php include('asistencias_hoy.php'); ?>
			<?php endif; ?>
			
			<hr>
			
			<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes', $_SESSION["user_permissions"])): ?>
          		
			<?php include('lastests-clients.php'); ?>
			<hr>
			<?php endif; ?>
			
		
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
			<?php include('credits.php'); ?>
          <?php endif; ?>
        </div>
        <!-- Segunda Columna -->
        <div class="col-md-6">
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Vencimientos de Hoy', $_SESSION["user_permissions"])): ?>			
          <?php include('due-today.php'); ?>          
          <hr>
		<?php endif;?>
			
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Próximos Pagos a Vencer', $_SESSION["user_permissions"])): ?>
          <?php include('upcoming-bills.php'); ?>
		<?php endif;?>
        </div>
      </div>
      
    </div>
  </div>
<div class="text-center mt-4">
        <a href="<?php echo $url;?>/admin/support/" class="btn btn-outline-primary">
          <i class="fas fa-question-circle"></i> ¿Necesitas ayuda?
        </a>
      </div>
  <?php include('inc/menu-footer.php'); ?>

  <!-- Scripts -->
	
	<script>
/* ----- contador en vivo de asistencias ----- */
function actualizarContadorAsistencias(){
  fetch('<?php echo $url; ?>/admin/asistencias_count.php')
    .then(r => r.json())
    .then(d => {
      if (typeof d.count !== 'undefined'){
        document.getElementById('asistencias-count').textContent = d.count;
      }
    })
    .catch(err => console.error('Error contador asistencias:', err));
}

document.addEventListener('DOMContentLoaded', () => {
  actualizarContadorAsistencias();          // inicial
  setInterval(actualizarContadorAsistencias, 10000);  // cada 10 s
});
</script>

 
	
<script>
$(function () {

  /* —— inicializar DataTables solo si la tabla existe y no está montada —— */
  function iniDT(selector, orderCol, orderDir='asc') {
    if ($(selector).length && !$.fn.DataTable.isDataTable(selector)) {
      $(selector).DataTable({
        pageLength: 10,
        order: [[orderCol, orderDir]],
        language: {
          url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
      });
    }
  }

	iniDT('#asistencias-hoy', 1, 'desc');  // ya lo tenías
	iniDT('#cumpleanos-hoy', 0, 'asc');    // ← cumpleaños de hoy
	//iniDT('#latest-clients', 0, 'desc');
	iniDT('#creditos-activos', 4, 'asc');   // ordena por fecha límite ascendente
	iniDT('#planes-hoy', 0, 'asc');      // ordena por fecha (o ajusta como prefieras)
	iniDT('#upcoming-payments', 4, 'asc');   // orden por fecha de vencimiento



  /* —— clic general de filas con data-id → perfil cliente —— */
  $(document).on('click', 'table tbody tr[data-id]', function () {
    const id = $(this).data('id');
    if (id) {
      window.location.href =
        "<?php echo $url; ?>/admin/clients/detail.php?id=" + encodeURIComponent(id);
    }
  });

});
</script>


	
</body>
</html>
