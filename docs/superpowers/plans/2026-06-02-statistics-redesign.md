# Statistics Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rediseñar `/admin/statistics/index.php` con KPIs de colores, filtros por categoría tipo pill, layout 2 columnas, y 2 gráficos nuevos (asistencias por mes, ingresos vs egresos).

**Architecture:** Se crean 2 nuevos archivos PHP para los gráficos nuevos siguiendo el patrón existente (query + canvas + Chart.js script). Se reescribe `index.php` completamente con CSS propio, conservando todos los `include()` originales intactos.

**Tech Stack:** PHP 8+, PDO, Chart.js 4.4, CSS Grid/Flexbox, jQuery (ya cargado via header.php)

---

### Task 1: Crear `admin/statistics/asistencias_mes.php`

**Files:**
- Create: `admin/statistics/asistencias_mes.php`

- [ ] **Step 1: Crear el archivo**

```php
<?php
require_once __DIR__ . '/../../inc/config.php';

try {
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(fecha, '%b %Y') AS label,
               COUNT(DISTINCT idCliente) AS total
        FROM asistencias
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
        GROUP BY DATE_FORMAT(fecha, '%Y-%m')
        ORDER BY DATE_FORMAT(fecha, '%Y-%m') ASC
    ");
    $stmt->execute();
    $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $labels = array_column($rows, 'label');
    $data   = array_map('intval', array_column($rows, 'total'));
} catch (PDOException $e) {
    $labels = [];
    $data   = [];
}
?>
<div class="chart-container">
  <canvas id="asisChart"></canvas>
</div>
<script>
new Chart(document.getElementById('asisChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Asistencias únicas',
      data: <?= json_encode($data) ?>,
      borderColor: '#8b5cf6',
      backgroundColor: 'rgba(139,92,246,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 4,
      pointBackgroundColor: '#8b5cf6',
      pointBorderWidth: 0
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, ticks: { precision: 0 } }
    }
  }
});
</script>
```

- [ ] **Step 2: Verificar manualmente**

Abrir en browser: `http://localhost/admin/statistics/` — no debe mostrar errores PHP. El gráfico de asistencias aparece al incluirlo (verificar en Task 3).

---

### Task 2: Crear `admin/statistics/ingresos_egresos.php`

**Files:**
- Create: `admin/statistics/ingresos_egresos.php`

- [ ] **Step 1: Crear el archivo**

```php
<?php
require_once __DIR__ . '/../../inc/config.php';

try {
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(fecha, '%b %Y') AS label,
               SUM(CASE WHEN payment_method != 'Egreso' THEN valor ELSE 0 END)       AS ingresos,
               SUM(CASE WHEN payment_method  = 'Egreso' THEN ABS(valor) ELSE 0 END)  AS egresos
        FROM ventas
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(fecha, '%Y-%m')
        ORDER BY DATE_FORMAT(fecha, '%Y-%m') ASC
    ");
    $stmt->execute();
    $rows     = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $labels   = array_column($rows, 'label');
    $ingresos = array_map('floatval', array_column($rows, 'ingresos'));
    $egresos  = array_map('floatval', array_column($rows, 'egresos'));
} catch (PDOException $e) {
    $labels = [];
    $ingresos = [];
    $egresos  = [];
}
?>
<div class="chart-container">
  <canvas id="ingEgrChart"></canvas>
</div>
<script>
new Chart(document.getElementById('ingEgrChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [
      {
        label: 'Ingresos',
        data: <?= json_encode($ingresos) ?>,
        backgroundColor: 'rgba(16,185,129,0.78)',
        borderRadius: 5,
        borderSkipped: false
      },
      {
        label: 'Egresos',
        data: <?= json_encode($egresos) ?>,
        backgroundColor: 'rgba(239,68,68,0.72)',
        borderRadius: 5,
        borderSkipped: false
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        display: true,
        position: 'top',
        labels: { boxWidth: 12, padding: 14 }
      }
    },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true }
    }
  }
});
</script>
```

- [ ] **Step 2: Commit los 2 archivos nuevos**

```bash
git add admin/statistics/asistencias_mes.php admin/statistics/ingresos_egresos.php
git commit -m "Feat: gráficos asistencias por mes e ingresos vs egresos en /statistics"
```

---

### Task 3: Reescribir `admin/statistics/index.php`

**Files:**
- Modify: `admin/statistics/index.php` (reescritura completa)

- [ ] **Step 1: Reemplazar el contenido completo del archivo**

```php
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
  <style>
    .stats-page { padding: 24px; }

    /* ── Header ── */
    .stats-header {
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 14px; margin-bottom: 24px;
    }
    .stats-title { font-size: 1.55rem; font-weight: 800; margin: 0; }

    /* ── Filter pills ── */
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

    /* ── KPI row ── */
    .kpi-row { display: grid; grid-template-columns: repeat(5,1fr); gap: 14px; margin-bottom: 24px; }
    .kpi-card {
      border-radius: 14px; padding: 16px 18px; color: #fff;
      position: relative; overflow: hidden;
    }
    .kpi-card::after {
      content: ''; position: absolute; right: -14px; top: -14px;
      width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,.13);
    }
    .kpi-val  { font-size: 1.65rem; font-weight: 900; line-height: 1; }
    .kpi-lbl  { font-size: .6rem; opacity: .82; text-transform: uppercase; letter-spacing: .5px; margin-top: 5px; }
    .kpi-sub  { font-size: .68rem; opacity: .72; margin-top: 5px; }
    .kpi-blue   { background: linear-gradient(135deg,#3b82f6,#6366f1); }
    .kpi-purple { background: linear-gradient(135deg,#8b5cf6,#a855f7); }
    .kpi-green  { background: linear-gradient(135deg,#10b981,#059669); }
    .kpi-red    { background: linear-gradient(135deg,#ef4444,#dc2626); }
    .kpi-teal   { background: linear-gradient(135deg,#14b8a6,#0d9488); }

    /* ── Charts layout ── */
    .charts-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px; }
    .charts-main, .charts-side { display: flex; flex-direction: column; gap: 16px; }

    /* ── Chart card ── */
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
      width: 26px; height: 26px; border-radius: 7px; flex-shrink: 0;
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

    /* Full-width bottom card */
    .chart-card-full { margin-bottom: 16px; }
    .chart-card-full .chart-container { height: 200px; }

    /* Filter hide */
    .chart-card[data-cat].hidden { display: none; }

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
      <h1 class="stats-title">📊 Estadísticas</h1>
      <div class="filter-pills" id="filterPills">
        <button class="pill active" data-cat="all">✅ Todos</button>
        <button class="pill" data-cat="clientes">👥 Clientes</button>
        <button class="pill" data-cat="asistencias">🚶 Asistencias</button>
        <button class="pill" data-cat="finanzas">💰 Finanzas</button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-row">
      <div class="kpi-card kpi-blue">
        <div class="kpi-val"><?= $kpiActivos ?></div>
        <div class="kpi-lbl">Clientes Activos</div>
        <div class="kpi-sub">Con plan vigente</div>
      </div>
      <div class="kpi-card kpi-purple">
        <div class="kpi-val"><?= $kpiAsisMes ?></div>
        <div class="kpi-lbl">Asistencias Este Mes</div>
        <div class="kpi-sub">Clientes únicos · <?= date('F') ?></div>
      </div>
      <div class="kpi-card kpi-green">
        <div class="kpi-val">$<?= number_format($kpiIngresos, 0, ',', '.') ?></div>
        <div class="kpi-lbl">Ingresos del Mes</div>
        <div class="kpi-sub">Acumulado <?= date('F') ?></div>
      </div>
      <div class="kpi-card kpi-red">
        <div class="kpi-val">$<?= number_format($kpiEgresos, 0, ',', '.') ?></div>
        <div class="kpi-lbl">Egresos del Mes</div>
        <div class="kpi-sub">Salidas de caja</div>
      </div>
      <div class="kpi-card kpi-teal">
        <div class="kpi-val"><?= $kpiNuevos ?></div>
        <div class="kpi-lbl">Nuevos Este Mes</div>
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
              <div class="chart-card-icon" style="background:#8b5cf6">🚶</div>
              Asistencias por Mes
            </div>
            <span class="chart-badge b-purple">Últimos 12 meses</span>
          </div>
          <?php include('asistencias_mes.php'); ?>
        </div>

        <div class="chart-card" data-cat="finanzas">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#10b981">💰</div>
              Ingresos vs Egresos
            </div>
            <span class="chart-badge b-green">Últimos 6 meses</span>
          </div>
          <?php include('ingresos_egresos.php'); ?>
        </div>

        <div class="chart-card" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#f59e0b">➕</div>
              Nuevos Clientes Mensuales
            </div>
            <span class="chart-badge b-orange">Histórico</span>
          </div>
          <?php include('nuevos.php'); ?>
        </div>

        <div class="chart-card" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#6366f1">🎂</div>
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
              <div class="chart-card-icon" style="background:#10b981">⚖️</div>
              Activos vs Inactivos
            </div>
          </div>
          <?php include('activo.php'); ?>
        </div>

        <div class="chart-card chart-card-sm" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#ec4899">⚥</div>
              Género
            </div>
          </div>
          <?php include('genero.php'); ?>
        </div>

        <div class="chart-card chart-card-sm" data-cat="clientes">
          <div class="chart-card-header">
            <div class="chart-card-title">
              <div class="chart-card-icon" style="background:#ef4444">📅</div>
              Distribución por Edad
            </div>
          </div>
          <?php include('edad.php'); ?>
        </div>

      </div>

    </div>

    <!-- Fila inferior: ancho completo -->
    <div class="chart-card chart-card-full" data-cat="clientes">
      <div class="chart-card-header">
        <div class="chart-card-title">
          <div class="chart-card-icon" style="background:#14b8a6">🏷️</div>
          Usuarios por Plan
        </div>
        <span class="chart-badge b-teal">Planes activos</span>
      </div>
      <?php include('planes.php'); ?>
    </div>

  </div>

  <?php include('../inc/menu-footer.php'); ?>

  <script>
  // Defaults globales de Chart.js — aplican a todos los gráficos incluyendo los existentes
  Chart.defaults.maintainAspectRatio = false;
  Chart.defaults.font.family = "'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif";
  Chart.defaults.font.size   = 11;
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
      if (card.closest('#filterPills')) return; // no ocultar los propios pills
      const show = showAll || active.includes(card.dataset.cat);
      card.style.display = show ? '' : 'none';
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
```

- [ ] **Step 2: Verificar en browser**

Abrir `http://localhost/admin/statistics/`:
- Deben aparecer las 5 tarjetas KPI con colores degradados
- Los 8 gráficos visibles por defecto (Todos activo)
- Clic en 👥 Clientes → solo gráficos de clientes visibles
- Clic en 🚶 Asistencias → solo asistencias por mes
- Clic en 💰 Finanzas → solo ingresos vs egresos
- Multi-selección: clic en Clientes + Asistencias → ambas categorías visibles
- Clic en Todos → todo vuelve a mostrarse
- Verificar dark mode si aplica

- [ ] **Step 3: Commit final**

```bash
git add admin/statistics/index.php
git commit -m "Feat: rediseño completo /statistics — KPIs, pills por categoría, layout 2 columnas"
git push origin main
```

---

## Notas

- `Chart.defaults.maintainAspectRatio = false` se aplica globalmente **antes** de que los `include()` de los gráficos existentes creen sus instancias, por lo que todos respetan la altura CSS del contenedor.
- Los archivos `nuevos.php`, `planes.php`, `activo.php`, `genero.php`, `edad.php`, `cumpleanos.php` **no se modifican**.
- El `include('../inc/header.php')` ya carga jQuery y las variables CSS del sistema, por lo que el dark mode funciona sin cambios adicionales.
