<?php 
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Sistema</title>
  <?php include('inc/header.php'); ?>
 
<style>

/* -------------------------------------------
   DASHBOARD CARD – DISEÑO MODERNO UNIFICADO
--------------------------------------------*/
.dashboard-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: 1px solid #e9e9e9;
    height: 380px;                 /* MISMO TAMAÑO PARA TODAS */
    display: flex;
    flex-direction: column;
}

.dashboard-card .section-title {
    font-size: 1.05rem;
    font-weight: bold;
    margin-bottom: 12px;
    border-bottom: 2px solid #eee;
    padding-bottom: 6px;
}

/* Contenido interno deslizable */
.dashboard-scroll {
    overflow-y: auto;
    flex: 1;
    padding-right: 5px;
}

/* Scroll estético */
.dashboard-scroll::-webkit-scrollbar {
    width: 6px;
}
.dashboard-scroll::-webkit-scrollbar-thumb {
    background: #bbb;
    border-radius: 3px;
}
.dashboard-scroll::-webkit-scrollbar-thumb:hover {
    background: #999;
}

/* Tablas internas estilos */
.custom-table {
  width: 100%;
  font-size: 13px!important;
  border-collapse: collapse;
}
.custom-table thead {
  background-color: #343a40;
  color: #fff;
}
.custom-table th,
.custom-table td {
  padding: 10px;
  border: 1px solid #ddd;
}
.custom-table tbody tr:hover {
  background-color: #f8f9fa;
}

/* Bienvenida */
.welcome-wrapper{
  position: relative;
}
.text-primary { color:#000!important; }

/* contador asistencias */
.asistencias-counter{
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 250px;
  background: linear-gradient(135deg,var(--system-color-primary) 0%,#8A0002 100%);
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

<?php if(isset($_SESSION['error'])): ?>
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

  <!-- ——— TITULO + CONTADOR ——— -->
  <div class="welcome-wrapper position-relative mb-4">
      <h1 class="welcome-title text-primary text-center m-0">
        Bienvenido, <br><span style="color:var(--system-color-primary);">
        <?php echo htmlspecialchars($nombre . " " . $apellido); ?>
        </span>
      </h1>

      <?php include('statistics_home.php'); ?>
  </div>


  <div class="card card-custom p-4">
    <div class="row">

      <!-- ========================================================= -->
      <!-- ===================== COLUMNA IZQUIERDA ================= -->
      <!-- ========================================================= -->
      <div class="col-md-6">

        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes que cumplen años hoy', $_SESSION["user_permissions"])): ?>
        <div class="dashboard-card">
          <div class="dashboard-scroll">
            <?php include('bd-today.php'); ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
        <div class="dashboard-card">
          <div class="dashboard-scroll">
            <?php include('asistencias_hoy.php'); ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes', $_SESSION["user_permissions"])): ?>
        <div class="dashboard-card">
          <div class="dashboard-scroll">
            <?php include('lastests-clients.php'); ?>
          </div>
        </div>
        <?php endif; ?>

        
      </div>


      <!-- ========================================================= -->
      <!-- ===================== COLUMNA DERECHA =================== -->
      <!-- ========================================================= -->
      <div class="col-md-6">

        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Vencimientos de Hoy', $_SESSION["user_permissions"])): ?>
        <div class="dashboard-card">
          <div class="dashboard-scroll">
            <?php include('due-today.php'); ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Próximos Pagos a Vencer', $_SESSION["user_permissions"])): ?>
        <div class="dashboard-card">
          <div class="dashboard-scroll">
            <?php include('upcoming-bills.php'); ?>
          </div>
        </div>
        <?php endif; ?>
		  
		  
		  <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
        <div class="dashboard-card">
          <div class="dashboard-scroll">
            <?php include('credits.php'); ?>
          </div>
        </div>
        <?php endif; ?>


      </div>

    </div>
  </div>

</div>


<!-- BOTÓN SOPORTE -->
<div class="text-center mt-4">
  <a href="<?php echo $url;?>/admin/support/" class="btn btn-outline-primary">
    <i class="fas fa-question-circle"></i> ¿Necesitas ayuda?
  </a>
</div>

<?php include('inc/menu-footer.php'); ?>

<!-- ========================== SCRIPTS ========================== -->

<script>
/* Contador asistencias */
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
  actualizarContadorAsistencias();
  setInterval(actualizarContadorAsistencias, 10000);
});
</script>


<script>
$(function () {

  /* DataTables solo si existe la tabla */
  function iniDT(selector, orderCol, orderDir='asc') {
    if ($(selector).length && !$.fn.DataTable.isDataTable(selector)) {
      $(selector).DataTable({
        pageLength: 10,
        order: [[orderCol, orderDir]],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
      });
    }
  }

  iniDT('#asistencias-hoy', 1, 'desc');
  iniDT('#cumpleanos-hoy', 0, 'asc');
  iniDT('#latest-clients', 0, 'desc');
  iniDT('#creditos-activos', 4, 'asc');
  iniDT('#planes-hoy', 0, 'asc');
  iniDT('#upcoming-payments', 4, 'asc');

  /* Click en filas → perfil cliente */
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
