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
    :root {
      --dash-bg-light: #f3f4f6;
      --dash-card-bg: rgba(255,255,255,0.92);
      --dash-border-subtle: rgba(148,163,184,0.25);
    }
    body.dark-mode {
      --dash-bg-light: #020617;
      --dash-card-bg: rgba(15,23,42,0.92);
      --dash-border-subtle: rgba(148,163,184,0.15);
    }

    body {
      background: radial-gradient(circle at top, rgba(95,202,0,0.08), transparent 55%),
                  radial-gradient(circle at bottom, rgba(139,0,0,0.12), transparent 65%),
                  var(--dash-bg-light) !important;
    }

    /* ===========================
       LAYOUT GENERAL DASHBOARD
    ============================*/
    .dashboard-container {
      max-width: 1400px;
      margin-top: 32px;
      margin-bottom: 40px;
    }

    /* ===========================
       HERO PRINCIPAL (ULTRA MODERNO)
    ============================*/
    .hero-card {
      position: relative;
      display: grid;
      grid-template-columns: minmax(0, 1.7fr) minmax(260px, 1.2fr);
      gap: 24px;
      padding: 22px 26px;
      border-radius: 26px;
      background: radial-gradient(circle at top left, rgba(255,255,255,0.28), transparent 45%),
                  linear-gradient(120deg, var(--system-color-primary, #5FCA00) 0%, #8A0002 45%, #111827 100%);
      color: #fff;
      box-shadow: 0 22px 40px rgba(15,23,42,0.65);
      overflow: hidden;
      margin-bottom: 24px;
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.15);
    }

    .hero-main {
      z-index: 1;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .hero-header-row {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .hero-avatar {
      width: 56px;
      height: 56px;
      border-radius: 999px;
      background: rgba(15,23,42,0.18);
      border: 1px solid rgba(255,255,255,0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 24px rgba(15,23,42,0.6);
    }

    .hero-avatar i {
      font-size: 26px;
    }

    .hero-title {
      font-size: 1.95rem;
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
      opacity: 0.97;
      max-width: 620px;
    }

    .hero-tags-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 4px;
    }

    .hero-tag {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 4px 11px;
      border-radius: 999px;
      background: rgba(15,23,42,0.45);
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.03em;
      text-transform: uppercase;
    }

    .hero-tag i {
      font-size: 0.9rem;
    }

    .hero-extra {
      z-index: 1;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      min-width: 260px;
    }

    .hero-card::before,
    .hero-card::after {
      content: "";
      position: absolute;
      border-radius: 999px;
      filter: blur(0);
      opacity: 0.55;
      pointer-events: none;
      mix-blend-mode: screen;
    }

    .hero-card::before {
      right: -60px;
      bottom: -80px;
      width: 260px;
      height: 260px;
      background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.85), transparent 60%);
    }

    .hero-card::after {
      left: -90px;
      top: -120px;
      width: 260px;
      height: 260px;
      background: radial-gradient(circle at 40% 40%, rgba(255,255,255,0.35), transparent 60%);
    }

    body.dark-mode .hero-card {
      background: radial-gradient(circle at top left, rgba(37,99,235,0.22), transparent 45%),
                  linear-gradient(120deg, #0f172a 0%, #111827 55%, #020617 100%);
      border-color: rgba(148,163,184,0.38);
    }

    /* ===========================
       KPI CARDS
    ============================*/
    .kpi-row {
      display: grid;
      grid-template-columns: repeat(4, minmax(0,1fr));
      gap: 22px;
      margin: 30px 0;
    }

    .kpi-card {
      position: relative;
      border-radius: 20px;
      padding: 18px 18px 16px;
      background: var(--dash-card-bg);
      box-shadow: 0 18px 40px rgba(15,23,42,0.16);
      border: 1px solid var(--dash-border-subtle);
      backdrop-filter: blur(12px);
      display: flex;
      flex-direction: column;
      gap: 4px;
      overflow: hidden;
    }

    .kpi-card::before {
      content:"";
      position:absolute;
      inset:auto -40px -60px auto;
      height:120px;
      width:120px;
      border-radius: 999px;
      background: radial-gradient(circle at 30% 30%, rgba(95,202,0,0.22), transparent 60%);
      opacity: 0.8;
      pointer-events:none;
    }

    .kpi-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      opacity: 0.8;
      margin-bottom: 3px;
    }

    .kpi-header i {
      font-size: 1.05rem;
      opacity: 0.9;
    }

    .kpi-value {
      font-size: 1.9rem;
      font-weight: 700;
      line-height: 1.2;
    }

    .kpi-sub {
      font-size: 0.8rem;
      opacity: 0.75;
    }

    .kpi-trend {
      font-size: 0.78rem;
      font-weight: 600;
      margin-top: 4px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 8px;
      border-radius: 999px;
      background: rgba(148,163,184,0.1);
    }

    .kpi-trend.positive {
      color: #16a34a;
      background: rgba(22,163,74,0.08);
    }

    .kpi-trend.negative {
      color: #dc2626;
      background: rgba(220,38,38,0.08);
    }

    .kpi-trend.neutral {
      color: #6b7280;
    }

    /* ===========================
       CHART CARDS
    ============================*/
    .chart-row {
      display: grid;
      grid-template-columns: minmax(0, 2fr) minmax(0, 1.1fr);
      gap: 20px;
      margin-bottom: 26px;
    }

    .chart-card {
      border-radius: 20px;
      padding: 14px 16px 10px;
      background: var(--dash-card-bg);
      box-shadow: 0 18px 40px rgba(15,23,42,0.16);
      border: 1px solid var(--dash-border-subtle);
      backdrop-filter: blur(12px);
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .chart-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
      gap: 10px;
    }

    .chart-card-title {
      font-size: 0.95rem;
      font-weight: 600;
    }

    .chart-card-sub {
      font-size: 0.78rem;
      opacity: 0.72;
    }

    .chart-card canvas {
      max-height: 260px !important;
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
      border-radius: 20px;
      padding: 14px 16px 10px;
      background: var(--dash-card-bg);
      box-shadow: 0 18px 40px rgba(15,23,42,0.16);
      border: 1px solid var(--dash-border-subtle);
      backdrop-filter: blur(10px);
      max-height: 420px;
      display: flex;
      flex-direction: column;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
      gap: 10px;
    }

    .panel-title {
      font-size: 0.95rem;
      font-weight: 600;
    }

    .panel-tag {
      font-size: 0.75rem;
      padding: 3px 9px;
      border-radius: 999px;
      background: rgba(148,163,184,0.16);
      opacity: 0.9;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .panel-tag i {
      font-size: 0.85rem;
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
      background: rgba(148,163,184,0.4);
      border-radius: 999px;
    }

    body.dark-mode .panel-body-scroll::-webkit-scrollbar-thumb {
      background: rgba(148,163,184,0.65);
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
        grid-template-columns: 1fr;
      }
      .hero-extra {
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
      <div class="hero-header-row">
        <div class="hero-avatar">
          <i class="fas fa-user-circle"></i>
        </div>
        <div>
          <h1 class="hero-title">
            Hola,
            <span class="name"><?= htmlspecialchars($nombre . " " . $apellido); ?></span>
          </h1>
          <div class="hero-sub">
            Este es tu panel principal. Visualiza asistencias, vencimientos, ventas,
            créditos y la actividad diaria del gimnasio en un solo lugar.
          </div>
        </div>
      </div>

      <div class="hero-tags-row">
        <div class="hero-tag">
          <i class="fas fa-bolt"></i> Panel en tiempo real
        </div>
        <div class="hero-tag">
          <i class="fas fa-dumbbell"></i> Gestión de clientes y planes
        </div>
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
      <div class="kpi-trend neutral" id="kpi-asistencias-trend">
        Esperando datos…
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <span>Clientes activos</span>
        <i class="fas fa-users"></i>
      </div>
      <div class="kpi-value" id="kpi-clientes-activos">--</div>
      <div class="kpi-sub">Clientes con plan activo y no borrados.</div>
      <div class="kpi-trend neutral" id="kpi-clientes-trend">
        Actualizado diariamente
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <span>Ventas de hoy</span>
        <i class="fas fa-cash-register"></i>
      </div>
      <div class="kpi-value" id="kpi-ventas-hoy">$ --</div>
      <div class="kpi-sub">Total vendido hoy (todas las cajas, sin base).</div>
      <div class="kpi-trend neutral" id="kpi-ventas-trend">
        Comparando con el día anterior…
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <span>Créditos activos</span>
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
      <div class="kpi-value" id="kpi-creditos-activos">--</div>
      <div class="kpi-sub">Créditos abiertos de clientes activos.</div>
      <div class="kpi-trend neutral" id="kpi-creditos-trend">
        Cartera en seguimiento
      </div>
    </div>

  </div>

  <!-- ================= GRÁFICOS PRINCIPALES ================= -->
  <div class="chart-row">

    <!-- Columna principal: asistencias + ventas -->
    <div style="display:flex; flex-direction:column; gap:20px;">

      <!-- Asistencias por hora -->
      <div class="chart-card">
        <div class="chart-card-header">
          <div>
            <div class="chart-card-title">Asistencias por hora (hoy)</div>
            <div class="chart-card-sub">Distribución de asistencias durante la jornada.</div>
          </div>
        </div>
        <?php include('asis-chart-home.php'); ?>
      </div>

      <!-- Ventas hoy vs ayer -->
      <div class="chart-card" style="height: 320px;">
        <div class="chart-card-header">
          <div>
            <div class="chart-card-title">Ventas comparadas (hoy vs ayer)</div>
            <div class="chart-card-sub">Total vendido por día, sumando todas las cajas.</div>
          </div>
        </div>
        <?php include('ventas-today.php'); ?>
      </div>

    </div>

    <!-- Columna lateral: asistencias vs día anterior -->
    <div class="chart-card">
      <div class="chart-card-header">
        <div>
          <div class="chart-card-title">Asistencias vs. día anterior</div>
          <div class="chart-card-sub">Comparativo rápido de actividad diaria.</div>
        </div>
      </div>
      <?php include('asis-chart-home-compara.php'); ?>
    </div>

  </div>

  <!-- ================= GRID PRINCIPAL DE LISTAS ================= -->
  <div class="panels-row">

    <!-- COLUMNA IZQUIERDA -->
    <div>
      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes que cumplen años hoy', $_SESSION["user_permissions"])): ?>
        <div class="panel-card mb-3">
          <div class="panel-header">
            <div class="panel-title">Cumpleaños de hoy</div>
            <span class="panel-tag">
              <i class="far fa-smile"></i> Felicitaciones
            </span>
          </div>
          <div class="panel-body-scroll">
            <?php include('bd-today.php'); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
        <div class="panel-card mb-3">
          <div class="panel-header">
            <div class="panel-title">Asistencias únicas registradas hoy</div>
            <span class="panel-tag">
              <i class="fas fa-walking"></i> Actividad
            </span>
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
            <span class="panel-tag">
              <i class="fas fa-user-plus"></i> Nuevos
            </span>
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
        <div class="panel-card mb-3">
          <div class="panel-header">
            <div class="panel-title">Planes que vencen hoy</div>
            <span class="panel-tag">
              <i class="far fa-clock"></i> Urgente
            </span>
          </div>
          <div class="panel-body-scroll">
            <?php include('due-today.php'); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Próximos Pagos a Vencer', $_SESSION["user_permissions"])): ?>
        <div class="panel-card mb-3">
          <div class="panel-header">
            <div class="panel-title">Próximos pagos (7 días)</div>
            <span class="panel-tag">
              <i class="fas fa-calendar-alt"></i> Agenda
            </span>
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
            <span class="panel-tag">
              <i class="fas fa-file-invoice-dollar"></i> Cartera
            </span>
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

      // Trend ventas vs ayer
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
    .catch(err => {
      console.error('Error KPIs home:', err);
    });
});
</script>

</body>
</html>

