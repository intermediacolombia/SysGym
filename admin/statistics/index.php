<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Estadisticas';
include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';

$primerDiaMes = date('Y-m-01');
$ultimoDiaMes = date('Y-m-t');

try {
    $kpiActivos = (int)db()->query("
        SELECT COUNT(*) FROM clientes WHERE estado='activo' AND borrado=0
    ")->fetchColumn();

    $s = db()->prepare("SELECT COUNT(DISTINCT idCliente) FROM asistencias WHERE fecha BETWEEN :i AND :f");
    $s->execute([':i' => $primerDiaMes, ':f' => $ultimoDiaMes]);
    $kpiAsisMes = (int)$s->fetchColumn();

    $s = db()->prepare("SELECT COALESCE(SUM(valor),0) FROM ventas WHERE payment_method != 'Egreso' AND fecha BETWEEN :i AND :f");
    $s->execute([':i' => $primerDiaMes, ':f' => $ultimoDiaMes]);
    $kpiIngresos = (float)$s->fetchColumn();

    $s = db()->prepare("SELECT COALESCE(SUM(ABS(valor)),0) FROM ventas WHERE payment_method = 'Egreso' AND fecha BETWEEN :i AND :f");
    $s->execute([':i' => $primerDiaMes, ':f' => $ultimoDiaMes]);
    $kpiEgresos = (float)$s->fetchColumn();

    $s = db()->prepare("SELECT COUNT(*) FROM clientes WHERE borrado=0 AND DATE(created_at) BETWEEN :i AND :f");
    $s->execute([':i' => $primerDiaMes, ':f' => $ultimoDiaMes]);
    $kpiNuevos = (int)$s->fetchColumn();
} catch (PDOException $e) {
    $kpiActivos = $kpiAsisMes = $kpiNuevos = 0;
    $kpiIngresos = $kpiEgresos = 0.0;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Estadísticas — SysGYM</title>
  <?php include('../inc/header.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
  Chart.defaults.maintainAspectRatio = false;
  Chart.defaults.font.family = "'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif";
  Chart.defaults.font.size   = 11;
  </script>
  <style>
    .stats-page { padding: 24px; }

    /* Header */
    .stats-header {
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 14px; margin-bottom: 24px;
    }
    .stats-title { font-size: 1.55rem; font-weight: 800; margin: 0; }

    /* Filter pills */
    .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
    .pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 18px; border-radius: 30px; font-size: .8rem; font-weight: 600;
      cursor: pointer; border: 2px solid #e2e8f0; background: #fff;
      color: #64748b; transition: all .18s; line-height: 1;
    }
    .pill:hover { border-color: var(--pill-color, #6366f1); color: var(--pill-color, #6366f1); }
    .pill.active { background: var(--pill-color, #6366f1); border-color: var(--pill-color, #6366f1); color: #fff; }
    .pill[data-cat="all"]         { --pill-color: #6366f1; }
    .pill[data-cat="clientes"]    { --pill-color: #3b82f6; }
    .pill[data-cat="asistencias"] { --pill-color: #8b5cf6; }
    .pill[data-cat="finanzas"]    { --pill-color: #10b981; }

    /* KPI row */
    .kpi-row { display: grid; grid-template-columns: repeat(5,1fr); gap: 14px; margin-bottom: 24px; }
    .kpi-card {
      border-radius: 14px; padding: 16px 18px; color: #fff;
      position: relative; overflow: hidden;
    }
    .kpi-card::after {
      content: ''; position: absolute; right: -14px; top: -14px;
      width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,.13);
    }
    .kpi-val { font-size: 1.65rem; font-weight: 900; line-height: 1; }
    .kpi-lbl { font-size: .6rem; opacity: .82; text-transform: uppercase; letter-spacing: .5px; margin-top: 5px; }
    .kpi-sub { font-size: .68rem; opacity: .72; margin-top: 5px; }
    .kpi-blue   { background: linear-gradient(135deg,#3b82f6,#6366f1); }
    .kpi-purple { background: linear-gradient(135deg,#8b5cf6,#a855f7); }
    .kpi-green  { background: linear-gradient(135deg,#10b981,#059669); }
    .kpi-red    { background: linear-gradient(135deg,#ef4444,#dc2626); }
    .kpi-teal   { background: linear-gradient(135deg,#14b8a6,#0d9488); }

    /* Charts layout */
    .charts-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px; }
    .charts-main, .charts-side { display: flex; flex-direction: column; gap: 16px; }

    /* Chart card */
    .chart-card {
      background: var(--card-bg, #fff);
      border: 1px solid var(--border-color, #e2e8f0);
      border-radius: 14px; padding: 18px;
      box-shadow: 0 2px 8px rgba(0,0,0,.04);
      transition: box-shadow .2s;
    }
    .chart-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.09); }
    .chart-card-header {
      display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;
    }
    .chart-card-title { font-weight: 700; font-size: .88rem; display: flex; align-items: center; gap: 8px; }
    .chart-card-icon {
      width: 28px; height: 28px; border-radius: 7px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: .72rem; color: #fff;
    }
    .chart-badge { font-size: .6rem; padding: 3px 10px; border-radius: 12px; font-weight: 600; white-space: nowrap; }
    .b-blue   { background:#dbeafe; color:#1d4ed8; }
    .b-purple { background:#ede9fe; color:#5b21b6; }
    .b-green  { background:#d1fae5; color:#065f46; }
    .b-orange { background:#ffedd5; color:#9a3412; }
    .b-teal   { background:#ccfbf1; color:#0f766e; }

    /* Chart container heights */
    .chart-card     .chart-container { position:relative; height:230px; }
    .chart-card-sm  .chart-container { position:relative; height:160px; }
    .chart-card     .chart-container canvas,
    .chart-card-sm  .chart-container canvas { position:absolute; top:0; left:0; width:100%!important; height:100%!important; }
    .chart-card-full .chart-container { height:200px; }

    /* Full-width bottom */
    .chart-card-full { margin-bottom: 16px; }

    /* Responsive */
    @media (max-width: 960px) {
      .charts-layout { grid-template-columns: 1fr; }
      .kpi-row { grid-template-columns: repeat(3,1fr); }
    }
    @media (max-width: 560px) {
      .kpi-row { grid-template-columns: repeat(2,1fr); }
    }

    /* Dark mode */
    body.dark-mode .pill { background: var(--card-bg); border-color: var(--border-color); color: var(--text-secondary); }
    body.dark-mode .chart-card { background: var(--card-bg); border-color: var(--border-color); }
  </style>
</head>
<body>
  <?php include('../inc/menu.php'); ?>

  <div class="portada stats-page">

    <!-- Header + filtros -->
    <div class="stats-header">
      <h1 class="stats-title"><i class="fas fa-chart-bar"></i> Estadísticas</h1>
      <div class="filter-pills" id="filterPills">
        <button class="pill active" data-cat="all"><i class="fas fa-th-large"></i> Todos</button>
        <button class="pill" data-cat="clientes"><i class="fas fa-users"></i> Clientes</button>
        <button class="pill" data-cat="asistencias"><i class="fas fa-walking"></i> Asistencias</button>
        <button class="pill" data-cat="finanzas"><i class="fas fa-dollar-sign"></i> Finanzas</button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-row">
      <div class="kpi-card kpi-blue">
        <div class="kpi-val"><?= $kpiActivos ?></div>
        <div class="kpi-lbl"><i class="fas fa-users"></i> Clientes Activos</div>
        <div class="kpi-sub">Con plan vigente</div>
      </div>
      <div class="kpi-card kpi-purple">
        <div class="kpi-val"><?= $kpiAsisMes ?></div>
        <div class="kpi-lbl"><i class="fas fa-walking"></i> Asistencias Este Mes</div>
        <div class="kpi-sub">Clientes únicos · <?= date('F') ?></div>
      </div>
      <div class="kpi-card kpi-green">
        <div class="kpi-val">$<?= number_format($kpiIngresos, 0, ',', '.') ?></div>
        <div class="kpi-lbl"><i class="fas fa-arrow-up"></i> Ingresos del Mes</div>
        <div class="kpi-sub">Acumulado <?= date('F') ?></div>
      </div>
      <div class="kpi-card kpi-red">
        <div class="kpi-val">$<?= number_format($kpiEgresos, 0, ',', '.') ?></div>
        <div class="kpi-lbl"><i class="fas fa-arrow-down"></i> Egresos del Mes</div>
        <div class="kpi-sub">Salidas de caja</div>
      </div>
      <div class="kpi-card kpi-teal">
        <div class="kpi-val"><?= $kpiNuevos ?></div>
        <div class="kpi-lbl"><i class="fas fa-user-plus"></i> Nuevos Este Mes</div>
        <div class="kpi-sub"><?= date('F Y') ?></div>
      </div>
    </div>

    <!-- Grid principal -->
    <div class="charts-layout">

      <!-- Columna izquierda (2fr) -->
      <div class="charts-main">

        <div class="chart-card" data-cat="asistencias">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#8b5cf6"><i class="fas fa-walking"></i></div>
              Asistencias por Mes
            </div>
            <span class="chart-badge b-purple">Últimos 12 meses</span>
          </div>
          <?php include('asistencias_mes.php'); ?>
        </div>

        <div class="chart-card" data-cat="finanzas">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#10b981"><i class="fas fa-balance-scale"></i></div>
              Ingresos vs Egresos
            </div>
            <span class="chart-badge b-green">Últimos 6 meses</span>
          </div>
          <?php include('ingresos_egresos.php'); ?>
        </div>

        <div class="chart-card" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#f59e0b"><i class="fas fa-user-plus"></i></div>
              Nuevos Clientes Mensuales
            </div>
            <span class="chart-badge b-orange">Histórico</span>
          </div>
          <?php include('nuevos.php'); ?>
        </div>

        <div class="chart-card" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#6366f1"><i class="fas fa-birthday-cake"></i></div>
              Cumpleaños por Mes
            </div>
            <span class="chart-badge b-blue">Todo el año</span>
          </div>
          <?php include('cumpleanos.php'); ?>
        </div>

      </div>

      <!-- Columna derecha (1fr) -->
      <div class="charts-side">

        <div class="chart-card chart-card-sm" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#10b981"><i class="fas fa-user-check"></i></div>
              Activos vs Inactivos
            </div>
          </div>
          <?php include('activo.php'); ?>
        </div>

        <div class="chart-card chart-card-sm" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#ec4899"><i class="fas fa-restroom"></i></div>
              Género
            </div>
          </div>
          <?php include('genero.php'); ?>
        </div>

        <div class="chart-card chart-card-sm" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#ef4444"><i class="fas fa-child"></i></div>
              Distribución por Edad
            </div>
          </div>
          <?php include('edad.php'); ?>
        </div>

      </div>

    </div>

    <!-- Fila inferior ancho completo -->
    <div class="chart-card chart-card-full" data-cat="clientes">
      <div class="chart-card-header">
        <div class="chart-card-title">
          <div class="chart-card-icon" style="background:#14b8a6"><i class="fas fa-running"></i></div>
          Usuarios por Plan
        </div>
        <span class="chart-badge b-teal">Planes activos</span>
      </div>
      <?php include('planes.php'); ?>
    </div>

  </div>

  <?php include('../inc/menu-footer.php'); ?>

  <script>
  const _dark = document.body.classList.contains('dark-mode');
  Chart.defaults.color       = _dark ? '#94a3b8' : '#64748b';
  Chart.defaults.borderColor = _dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

  // Filtros por categoría
  const pills = document.querySelectorAll('#filterPills .pill');

  function applyFilters() {
    const active = [...pills]
      .filter(p => p.classList.contains('active'))
      .map(p => p.dataset.cat);
    const showAll = active.includes('all') || active.length === 0;

    document.querySelectorAll('[data-cat]').forEach(card => {
      if (card.closest('#filterPills')) return;
      card.style.display = (showAll || active.includes(card.dataset.cat)) ? '' : 'none';
    });
  }

  pills.forEach(pill => {
    pill.addEventListener('click', () => {
      const cat = pill.dataset.cat;
      if (cat === 'all') {
        pills.forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
      } else {
        document.querySelector('.pill[data-cat="all"]').classList.remove('active');
        pill.classList.toggle('active');
        const anyActive = [...pills].some(p => p.classList.contains('active'));
        if (!anyActive) document.querySelector('.pill[data-cat="all"]').classList.add('active');
      }
      applyFilters();
    });
  });
  </script>

</body>
</html>
