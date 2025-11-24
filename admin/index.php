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
	  
	  
	  
    /* ===========================
       LAYOUT GENERAL DASHBOARD
    ============================*/

    .dashboard-container {
        max-width: 1400px;
        margin-top: 40px;
    }

    /* Hero tipo “Hi Jasica!” */
    .hero-card {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 26px 30px;
        border-radius: 22px;
        background: linear-gradient(135deg, var(--system-color-primary) 0%, #8A0002 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(0,0,0,0.30);
        overflow: hidden;
        margin-bottom: 32px;
    }

    .hero-main {
        z-index: 1;
        max-width: 60%;
    }

    .hero-title {
        font-size: 2.3rem;
        font-weight: 700;
        margin: 0 0 8px;
    }

    .hero-title span {
        display: inline-block;
    }

    .hero-sub {
        font-size: 0.95rem;
        opacity: 0.92;
        margin-bottom: 10px;
    }

    .hero-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        background: rgba(0,0,0,0.20);
        font-size: 0.8rem;
        font-weight: 600;
    }

    .hero-extra {
        z-index: 1;
        flex: 1;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    /* “Ilustración” de fondo suave */
    .hero-card::before {
        content: "";
        position: absolute;
        right: -40px;
        bottom: -40px;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.55), transparent 60%);
        opacity: 0.4;
        pointer-events: none;
    }

    /* Asistencias counter que ya tenías (se posiciona dentro del hero) */
    .asistencias-counter{
      position:absolute;
      right: 24px;
      bottom: 24px;
      width: 230px;
      background: rgba(0,0,0,0.35);
      border-radius: 16px;
      color:#fff;
      box-shadow:0 4px 10px rgba(0,0,0,.25);
    }
    .asistencias-counter .display-4{
      font-size:32px;
      font-weight:700;
      line-height:1;
    }

    /* GRID PRINCIPAL DEL DASHBOARD (dos columnas como el mockup) */
    .dashboard-layout {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 28px;
}

   

    /* Responsive */
    @media (max-width: 991.98px) {
        .hero-card {
            flex-direction: column;
            align-items: flex-start;
        }
        .hero-main {
            max-width: 100%;
        }
        .hero-extra {
            width: 100%;
            justify-content: flex-start;
        }
        .dashboard-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px){
        .hero-title {
            font-size: 1.9rem;
        }
    }

    /* ===========================
       MODO OSCURO – HERO / LAYOUT
    ============================*/
    body.dark-mode .hero-card {
        background: linear-gradient(135deg, #141b23 0%, #222f3b 100%);
        box-shadow: 0 12px 26px rgba(0,0,0,0.45);
    }

    body.dark-mode .hero-card::before {
        background: radial-gradient(circle at 30% 30%, rgba(82,165,224,0.35), transparent 60%);
        opacity: 0.5;
    }

    body.dark-mode .hero-tag {
        background: rgba(0,0,0,0.35);
    }

    body.dark-mode .asistencias-counter {
        background: rgba(0,0,0,0.55);
    }

    .welcome-title { /* ya no centrada en body, se integra al hero */
        color: #fff;
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


<div class="container dashboard-container">

  <!-- ================= HERO PRINCIPAL (tipo “Hi Jasica!”) ================= -->
  <div class="welcome-wrapper hero-card">

      <div class="hero-main">
        <h1 class="hero-title">
          Hola,
          <span><?= htmlspecialchars($nombre . " " . $apellido); ?></span>
        </h1>
        <div class="hero-sub">
          Bienvenido al panel principal. Aquí puedes ver rápidamente
          los clientes que asisten, cumplen años, vencen hoy y más.
        </div>
        <!--div class="hero-tag">
          <i class="fas fa-bolt"></i> Panel de control en tiempo real
        </div-->
      </div>

      <!-- Aquí sigue usándose tu archivo de estadísticas -->
      <div class="hero-extra" style="border-radius:25px;">
        <?php include('statistics_home.php'); ?>
      </div>
  </div>

	
	<?php include('asis-chart-home.php'); ?>
	<?php include('asis-chart-home-compara.php'); ?>
	<?php include('ventas-today.php'); ?>
  <!-- ================= GRID PRINCIPAL DE TARJETAS ================= -->
  <div class="dashboard-layout">

    <!-- ===================== COLUMNA IZQUIERDA ===================== -->
    <div>

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

    <!-- ===================== COLUMNA DERECHA ======================= -->
    <div>

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

  </div><!-- /dashboard-layout -->

</div><!-- /container -->


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
        const el = document.getElementById('asistencias-count');
        if (el) el.textContent = d.count;
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

  /* Click en filas → perfil cliente (por si aún usas tablas en otro lado) */
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



