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
    /* ===================================================================
   DASHBOARD – TARJETAS ESTILO "SMART HOME" (tipo mockup)
   Totalmente unificado – Claroscuro compatible
=================================================================== */

/* ---- Contenedor general dentro de dashboard-card ---- */
.card-list-wrapper {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
}

/* ======================================================
   TARJETAS PRINCIPALES (tipo My Devices)
====================================================== */
.card-item {
    position: relative;
    padding: 18px 20px;
    background: #ffffff;
    border-radius: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 18px;
    min-height: 85px;

    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    transition: transform .12s ease, box-shadow .15s ease, background .20s ease;
}

.card-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
}

/* ======================================================
   AVATAR – estilo moderno
====================================================== */
.card-avatar {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
}

.card-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ======================================================
   TEXTO
====================================================== */
.card-title {
    font-size: 17px;
    font-weight: 700;
    color: #1a1a1a;
}

.card-sub {
    font-size: 13px;
    color: #555;
    margin-top: 3px;
}

.card-info {
    flex: 1;
}

/* ======================================================
   BADGES (edades, días, planes...)
====================================================== */
.badge-pill {
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
}

/* Colores estilo mockup */
.badge-orange { background: #ff9d43; }
.badge-purple { background: #7a66ff; }
.badge-blue   { background: #2196f3; }
.badge-green  { background: #2ecc71; }
.badge-red    { background: #ff5252; }
.badge-yellow { background: #fbc02d; }

/* ======================================================
   NO HAY DATOS
====================================================== */
.card-list-empty {
    padding: 35px 20px;
    text-align: center;
    border-radius: 16px;
    background: #f0f0f0;
    color: #666;
    font-size: 15px;
}

/* ======================================================
   TITULOS DENTRO DEL DASHBOARD
====================================================== */
.section-title {
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 14px;
    padding-left: 4px;
}

/* Título fijo arriba de cada tarjeta */
.dashboard-card .section-title {
    position: sticky;
    top: 0;
    z-index: 20;
    background: inherit;
    padding: 12px 4px;
}

/* ======================================================
   TARJETA CONTENEDORA (dashboard-card)
====================================================== */
.dashboard-card {
    background: #ffffff;
    border-radius: 22px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.10);
    border: none;
    height: 420px;
    display: flex;
    flex-direction: column;
    margin-bottom: 30px;
}

/* SCROLL INTERNO */
.dashboard-scroll {
    overflow-y: auto;
    margin-top: 10px;
    padding-right: 6px;
}

.dashboard-scroll::-webkit-scrollbar {
    width: 6px;
}
.dashboard-scroll::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,.2);
    border-radius: 4px;
}

/* ======================================================
   MODO OSCURO
====================================================== */
body.dark-mode .dashboard-card {
    background: #1b2330;
    box-shadow: 0 10px 25px rgba(0,0,0,0.35);
}

body.dark-mode .section-title {
    color: #fff;
}

body.dark-mode .card-item {
    background: #253140;
    box-shadow: 0 4px 12px rgba(0,0,0,0.30);
}

body.dark-mode .card-item:hover {
    background: #2e3d51;
    box-shadow: 0 6px 20px rgba(0,0,0,0.45);
}

body.dark-mode .card-title {
    color: #fff;
}

body.dark-mode .card-sub {
    color: #c7cdd3;
}

body.dark-mode .card-list-empty {
    background: #2a3747;
    color: #c7cdd3;
}

body.dark-mode .dashboard-scroll::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,.2);
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
        <div class="hero-tag">
          <i class="fas fa-bolt"></i> Panel de control en tiempo real
        </div>
      </div>

      <!-- Aquí sigue usándose tu archivo de estadísticas -->
      <div class="hero-extra">
        <?php include('statistics_home.php'); ?>
      </div>
  </div>

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



