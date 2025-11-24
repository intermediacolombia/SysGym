<?php 
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel principal</title>
  <?php include('inc/header.php'); ?>

  <style>
    /* ===========================
       LAYOUT GENERAL DASHBOARD
    ============================*/
    .dashboard-container {
      max-width: 1400px;
      margin-top: 32px;
      margin-bottom: 40px;
    }

    /* ===========================
       HERO PRINCIPAL
    ============================*/
    .hero-card {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      padding: 22px 26px;
      border-radius: 22px;
      background: linear-gradient(135deg, var(--system-color-primary, #5FCA00) 0%, #8A0002 100%);
      color: #fff;
      box-shadow: 0 14px 30px rgba(0,0,0,0.30);
      overflow: hidden;
      margin-bottom: 24px;
    }

    .hero-main {
      z-index: 1;
      max-width: 60%;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .hero-title {
      font-size: 2.2rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      flex-wrap: wrap;
      align-items: baseline;
      gap: 6px;
    }

    .hero-title span.name {
      font-weight: 700;
    }

    .hero-sub {
      font-size: 0.95rem;
      opacity: 0.95;
    }

    .hero-tag {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 4px 12px;
      border-radius: 999px;
      background: rgba(0,0,0,0.20);
      font-size: 0.8rem;
      font-weight: 600;
      margin-top: 6px;
    }

    .hero-tag i {
      font-size: 0.9rem;
    }

    .hero-extra {
      z-index: 1;
      flex: 1;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      min-width: 220px;
    }

    .hero-avatar {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 8px;
    }

    .hero-avatar i {
      font-size: 24px;
    }

    .hero-card::before {
      content: "";
      position: absolute;
      right: -40px;
      bottom: -40px;
      width: 230px;
      height: 230px;
      background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.55), transparent 60%);
      opacity: 0.4;
      pointer-events: none;
    }

    /* ===========================
       KPI CARDS
    ============================*/
    .kpi-row {
      display: grid;
      grid-template-columns: repeat(4, minmax(0,1fr));
      gap: 18px;
      margin-bottom: 26px;
    }

    .kpi-card {
      border-radius: 18px;
      padding: 14px 16px;
      background: var(--system-bg-secondary, #ffffff);
      box-shadow: 0 8px 18px rgba(0,0,0,0.08);
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .kpi-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      opacity: 0.8;
      margin-bottom: 4px;
    }

    .kpi-header i {
      font-size: 1rem;
      opacity: 0.9;
    }

    .kpi-value {
      font-size: 1.65rem;
      font-weight: 700;
      line-height: 1.2;
    }

    .kpi-sub {
      font-size: 0.8rem;
      opacity: 0.8;
    }

    .kpi-trend {
      font-size: 0.78rem;
      font-weight: 600;
      margin-top: 2px;
    }

    .kpi-trend.positive {
      color: #16a34a;
    }

    .kpi-trend.negative {
      color: #dc2626;
    }

    .kpi-trend.neutral {
      color: #6b7280;
    }

    /* ===========================
       CHART CARDS
    ============================*/
    .chart-row {
      display: grid;
      grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr);
      gap: 18px;
      margin-bottom: 18px;
    }

    .chart-full-row {
      margin-bottom: 26px;
    }

    .chart-card {
      border-radius: 18px;
      padding: 16px 18px;
      background: var(--system-bg-secondary, #ffffff);
      box-shadow: 0 8px 18px rgba(0,0,0,0.08);
    }

    .chart-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .chart-card-title {
      font-size: 0.95rem;
      font-weight: 600;
    }

    .chart-card-sub {
      font-size: 0.78rem;
      opacity: 0.7;
    }

    /* ===========================
       GRID DE PANELES (LISTAS)
    ============================*/
    .panels-row {
      display: grid;
      grid-template-columns: repeat(2, minmax(0,1fr));
      gap: 22px;
    }

    .panel-card {
      border-radius: 18px;
      padding: 14px 16px 10px;
      background: var(--system-bg-secondary, #ffffff);
      box-shadow: 0 8px 18px rgba(0,0,0,0.08);
      max-height: 420px;
      display: flex;
      flex-direction: column;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }

    .panel-title {
      font-size: 0.95rem;
      font-weight: 600;
    }

    .panel-tag {
      font-size: 0.75rem;
      padding: 2px 8px;
      border-radius: 999px;
      background: rgba(0,0,0,0.05);
      opacity: 0.75;
    }

    .panel-body-scroll {
      margin-top: 4px;
      overflow-y: auto;
      padding-right: 2px;
    }

    .panel-body-scroll::-webkit-scrollbar {
      width: 4px;
    }
    .panel-body-scroll::-webkit-scrollbar-thumb {
      background: rgba(0,0,0,0.12);
      border-radius: 999px;
    }

    /* ===========================
       MODO OSCURO
    ============================*/
    body.dark-mode .hero-card {
      background: linear-gradient(135deg, #141b23 0%, #222f3b 100%);
      box-shadow: 0 12px 26px rgba(0,0,0,0.6);
    }

    body.dark-mode .hero-card::before {
      background: radial-gradient(circle at 30% 30%, rgba(82,165,224,0.35), transparent 60%);
      opacity: 0.5;
    }

    body.dark-mode .hero-tag {
      background: rgba(0,0,0,0.35);
    }

    body.dark-mode .kpi-card,
    body.dark-mode .chart-card,
    body.dark-mode .panel-card {
      background: #111827;
      box-shadow: 0 10px 22px rgba(0,0,0,0.75);
    }

    body.dark-mode .kpi-header,
    body.dark-mode .kpi-sub,
    body.dark-mode .chart-card-sub,
    body.dark-mode .panel-tag {
      color: #9ca3af;
      opacity: 0.85;
    }

    body.dark-mode .panel-body-scroll::-webkit-scrollbar-thumb {
      background: rgba(255,255,255,0.16);
    }

    /* RESPONSIVE */
    @media (max-width: 1199.98px) {
      .kpi-row {
        grid-template-columns: repeat(2, minmax(0,1fr));
      }
      .chart-row {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 767.98px) {
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
      .kpi-row {
        grid-template-columns: 1fr;
      }
      .panels-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<?php include('inc/menu.php'); ?>

<?php if(isset($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
    <?php 
      echo $_SESSION['error'];
      unset($_SESSION['error']);
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
<?php endif; ?>

<div class="container dashboard-container">

  <!-- ================= HERO PRINCIPAL ================= -->
  <div class="hero-card">
    <div class="hero-main">
      <div class="hero-avatar">
        <i class="fas fa-user-circle"></i>
      </div>
      <h1 class="hero-title">
        Hola,
        <span class="name"><?= htmlspecialchars($nombre . " " . $apellido); ?></span>
      </h1>
      <div class="hero-sub">
        Bienvenido al panel principal. Aquí tienes una vista rápida de la actividad del gimnasio:
        asistencias, vencimientos, ventas y créditos.
      </div>
      <div class="hero-tag">
        <i class="fas fa-bolt"></i> Panel de control en tiempo real
      </div>
    </div>

    <div class="hero-extra">
      <?php include('statistics_home.php'); ?>
    </div>
  </div>

  <!-- ================= KPIs SUPERIORES ================= -->
  <div class="kpi-row">

    <div class="kpi-card">
      <div class="kpi-header">
        <span>Asistencias hoy</span>
        <i class="fas fa-walking"></i>
      </div>
      <div class="kpi-value" id="kpi-asistencias-hoy">--</div>
      <div class="kpi-sub">Clientes que han registrado asistencia hoy.</div>
      <div class="kpi-trend neutral" id="kpi-asistencias-trend"></div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <span>Clientes activos</span>
        <i class="fas fa-users"></i>
      </div>
      <div class="kpi-value" id="kpi-clientes-activos">--</div>
      <div class="kpi-sub">Clientes con plan activo y no borrados.</div>
      <div class="kpi-trend neutral" id="kpi-clientes-trend"></div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <span>Ventas de hoy</span>
        <i class="fas fa-cash-register"></i>
      </div>
      <div class="kpi-value" id="kpi-ventas-hoy">$ --</div>
      <div class="kpi-sub">Total vendido hoy (todas las cajas, sin base).</div>
      <div class="kpi-trend neutral" id="kpi-ventas-trend"></div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <span>Créditos activos</span>
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
      <div class="kpi-value" id="kpi-creditos-activos">--</div>
      <div class="kpi-sub">Créditos abiertos de clientes activos.</div>
      <div class="kpi-trend neutral" id="kpi-creditos-trend"></div>
    </div>

  </div>

  <!-- ================= GRÁFICOS PRINCIPALES ================= -->
  <div class="chart-row">
    <div class="chart-card">
      <div class="chart-card-header">
        <div>
          <div class="chart-card-title">Asistencias por hora (hoy)</div>
          <div class="chart-card-sub">Distribución de asistencias durante la jornada.</div>
        </div>
      </div>
      <?php include('asis-chart-home.php'); ?>
    </div>

    <div class="chart-card">
      <div class="chart-card-header">
        <div>
          <div class="chart-card-title">Asistencias vs. día anterior</div>
          <div class="chart-card-sub">Comparativo rápido de actividad.</div>
        </div>
      </div>
      <?php include('asis-chart-home-compara.php'); ?>
    </div>
  </div>

  <div class="chart-full-row">
    <div class="chart-card">
      <div class="chart-card-header">
        <div>
          <div class="chart-card-title">Ventas comparadas con el día anterior</div>
          <div class="chart-card-sub">Total vendido por día, sumando todas las cajas.</div>
        </div>
      </div>
      <?php include('ventas-today.php'); ?>
    </div>
  </div>

  <!-- ================= GRID PRINCIPAL DE LISTAS ================= -->
  <div class="panels-row">

    <!-- COLUMNA IZQUIERDA -->
    <div>
      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes que cumplen años hoy', $_SESSION["user_permissions"])): ?>
        <div class="panel-card">
          <div class="panel-header">
            <div class="panel-title">Cumpleaños de hoy</div>
            <span class="panel-tag"><i class="far fa-smile"></i> Felicitaciones</span>
          </div>
          <div class="panel-body-scroll">
            <?php include('bd-today.php'); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
        <div class="panel-card">
          <div class="panel-header">
            <div class="panel-title">Asistencias únicas de hoy</div>
            <span class="panel-tag"><i class="fas fa-walking"></i> Actividad</span>
          </div>
          <div class="panel-body-scroll">
            <?php include('asistencias_hoy.php'); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes', $_SESSION["user_permissions"])): ?>
        <div class="panel-card">
          <div class="panel-header">
            <div class="panel-title">Últimos clientes inscritos</div>
            <span class="panel-tag"><i class="fas fa-user-plus"></i> Nuevos</span>
          </div>
          <div class="panel-body-scroll">
            <?php include('lastests-clients.php'); ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- COLUMNA DERECHA -->
    <div>
      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Vencimientos de Hoy', $_SESSION["user_permissions"])): ?>
        <div class="panel-card">
          <div class="panel-header">
            <div class="panel-title">Planes que vencen hoy</div>
            <span class="panel-tag"><i class="far fa-clock"></i> Urgente</span>
          </div>
          <div class="panel-body-scroll">
            <?php include('due-today.php'); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Próximos Pagos a Vencer', $_SESSION["user_permissions"])): ?>
        <div class="panel-card">
          <div class="panel-header">
            <div class="panel-title">Próximos pagos (7 días)</div>
            <span class="panel-tag"><i class="fas fa-calendar-alt"></i> Agenda</span>
          </div>
          <div class="panel-body-scroll">
            <?php include('upcoming-bills.php'); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
        <div class="panel-card">
          <div class="panel-header">
            <div class="panel-title">Créditos activos</div>
            <span class="panel-tag"><i class="fas fa-file-invoice-dollar"></i> Cartera</span>
          </div>
          <div class="panel-body-scroll">
            <?php include('credits.php'); ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /panels-row -->

</div><!-- /dashboard-container -->


<!-- BOTÓN SOPORTE -->
<div class="text-center mt-4 mb-4">
  <a href="<?php echo $url;?>/admin/support/" class="btn btn-outline-primary">
    <i class="fas fa-question-circle"></i> ¿Necesitas ayuda?
  </a>
</div>

<?php include('inc/menu-footer.php'); ?>

<!-- ========================== SCRIPTS ========================== -->

<script>
// Contador de asistencias (si ya usas asistencias_count.php)
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
// KPIs superiores: usa gets_home/get_kpis_home.php
document.addEventListener('DOMContentLoaded', () => {
  fetch('gets_home/get_kpis_home.php')
    .then(r => r.json())
    .then(d => {
      // Asistencias hoy
      if (typeof d.asistencias_hoy !== 'undefined') {
        document.getElementById('kpi-asistencias-hoy').textContent = d.asistencias_hoy;
      }
      // Clientes activos
      if (typeof d.clientes_activos !== 'undefined') {
        document.getElementById('kpi-clientes-activos').textContent = d.clientes_activos;
      }
      // Ventas hoy
      if (typeof d.ventas_hoy !== 'undefined') {
        document.getElementById('kpi-ventas-hoy').textContent = 
          "$" + Number(d.ventas_hoy).toLocaleString('es-CO', {maximumFractionDigits:0});
      }
      // Créditos activos
      if (typeof d.creditos_activos !== 'undefined') {
        document.getElementById('kpi-creditos-activos').textContent = d.creditos_activos;
      }

      // Si quieres, puedes usar d.ventas_vs_ayer para trend aquí
      if (d.ventas_vs_ayer && typeof d.ventas_vs_ayer.percent !== 'undefined') {
        const trend = document.getElementById('kpi-ventas-trend');
        const p = d.ventas_vs_ayer.percent;
        if (p > 0) {
          trend.textContent = `+${p}% vs ayer`;
          trend.classList.remove('negative','neutral');
          trend.classList.add('positive');
        } else if (p < 0) {
          trend.textContent = `${p}% vs ayer`;
          trend.classList.remove('positive','neutral');
          trend.classList.add('negative');
        } else {
          trend.textContent = `Igual que ayer`;
          trend.classList.remove('positive','negative');
          trend.classList.add('neutral');
        }
      }

    })
    .catch(err => console.error('Error KPIs home:', err));
});
</script>

</body>
</html>
