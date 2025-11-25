<?php 
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel Principal</title>
  <?php include('inc/header.php'); ?>

  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, <?= SYSTEM_COLOR_PRIMARY ?> 0%, <?= SYSTEM_COLOR_SECONDARY ?> 100%);
      --bg-light: #f8fafc;
      --card-bg: #ffffff;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.04);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.06);
      --shadow-lg: 0 20px 40px rgba(0,0,0,0.08);
      --accent-blue: #3b82f6;
      --accent-green: #10b981;
      --accent-purple: #8b5cf6;
      --accent-orange: #f59e0b;
    }

    body.dark-mode {
      --bg-light: #0f172a;
      --card-bg: #192229;
      --text-primary: #f1f5f9;
      --text-secondary: #94a3b8;
      --border-color: #334155;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
      --shadow-lg: 0 20px 40px rgba(0,0,0,0.5);
    }

    /* ===========================
       CONTAINER PRINCIPAL
    ============================*/
    .dashboard-wrapper {
      max-width: 1440px;
      margin: 0 auto;
      padding: 2rem 1.5rem;
    }

    /* ===========================
       HERO SECTION - MINIMALISTA
    ============================*/
    .hero-section {
      margin-bottom: 3rem;
    }

    .hero-greeting {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      margin-bottom: 0.75rem;
    }

    .hero-avatar {
      width: 72px;
      height: 72px;
      border-radius: 20px;
      background: var(--primary-gradient);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 32px;
      box-shadow: var(--shadow-md);
    }

    .hero-text h1 {
      font-size: 2rem;
      font-weight: 700;
      margin: 0 0 0.25rem 0;
      color: var(--text-primary);
    }

    .hero-text p {
      font-size: 1rem;
      color: var(--text-secondary);
      margin: 0;
    }

    .hero-stats {
      display: flex;
      gap: 0.5rem;
      margin-top: 1rem;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-secondary);
      box-shadow: var(--shadow-sm);
    }

    .hero-badge i {
      color: var(--accent-blue);
    }

    /* ===========================
       KPI CARDS - MODERNOS
    ============================*/
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.5rem;
      margin-bottom: 3rem;
    }

    .kpi-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 1.75rem;
      box-shadow: var(--shadow-md);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .kpi-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
    }

    .kpi-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--primary-gradient);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .kpi-card:hover::before {
      opacity: 1;
    }

    .kpi-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1.5rem;
    }

    .kpi-icon {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: white;
    }

    .kpi-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .kpi-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .kpi-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    .kpi-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

    .kpi-content {
      flex: 1;
    }

    .kpi-label {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.5rem;
    }

    .kpi-value {
      font-size: 2.25rem;
      font-weight: 700;
      color: var(--text-primary);
      line-height: 1;
      margin-bottom: 0.75rem;
    }

    .kpi-trend {
      display: inline-flex;
      align-items: center;
      gap: 0.375rem;
      font-size: 0.875rem;
      font-weight: 600;
      padding: 0.375rem 0.75rem;
      border-radius: 8px;
    }

    .kpi-trend.positive {
      color: #059669;
      background: rgba(16, 185, 129, 0.1);
    }

    .kpi-trend.negative {
      color: #dc2626;
      background: rgba(220, 38, 38, 0.1);
    }

    .kpi-trend.neutral {
      color: var(--text-secondary);
      background: rgba(100, 116, 139, 0.1);
    }

    /* ===========================
       CHARTS SECTION
    ============================*/
    .charts-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 1.5rem;
      margin-bottom: 3rem;
    }

    .chart-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 1.75rem;
      box-shadow: var(--shadow-md);
    }

    .chart-header {
      margin-bottom: 1.5rem;
    }

    .chart-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.25rem;
    }

    .chart-subtitle {
      font-size: 0.875rem;
      color: var(--text-secondary);
    }

    .chart-body {
      position: relative;
      height: 300px;
    }

    .chart-body canvas {
      max-height: 100% !important;
    }

    /* ===========================
       DATA PANELS
    ============================*/
    .panels-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
    }

    .panel-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 1.75rem;
      box-shadow: var(--shadow-md);
      display: flex;
      flex-direction: column;
      max-height: 500px;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border-color);
    }

    .panel-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-primary);
    }

    .panel-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.375rem 0.75rem;
      background: rgba(59, 130, 246, 0.1);
      color: var(--accent-blue);
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .panel-body {
      flex: 1;
      overflow-y: auto;
      padding-right: 0.5rem;
    }

    .panel-body::-webkit-scrollbar {
      width: 6px;
    }

    .panel-body::-webkit-scrollbar-track {
      background: transparent;
    }

    .panel-body::-webkit-scrollbar-thumb {
      background: var(--border-color);
      border-radius: 10px;
    }

    .panel-body::-webkit-scrollbar-thumb:hover {
      background: var(--text-secondary);
    }

    /* ===========================
       SECTION DIVIDER
    ============================*/
    .section-divider {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 3rem 0 2rem 0;
    }

    .section-divider h2 {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-primary);
      margin: 0;
    }

    .section-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border-color);
    }

    /* ===========================
       FLOATING ACTION BUTTON
    ============================*/
    .fab-support {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: var(--primary-gradient);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      text-decoration: none;
    }

    .fab-support:hover {
      transform: scale(1.1);
      box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5);
    }

    /* ===========================
       RESPONSIVE
    ============================*/
    @media (max-width: 1200px) {
      .charts-grid {
        grid-template-columns: 1fr;
      }

      .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .dashboard-wrapper {
        padding: 1.5rem 1rem;
      }

      .hero-greeting {
        flex-direction: column;
        align-items: flex-start;
      }

      .hero-avatar {
        width: 64px;
        height: 64px;
        font-size: 28px;
      }

      .hero-text h1 {
        font-size: 1.75rem;
      }

      .hero-stats {
        flex-direction: column;
        width: 100%;
      }

      .hero-badge {
        width: 100%;
      }

      .kpi-grid {
        grid-template-columns: 1fr;
      }

      .kpi-value {
        font-size: 2rem;
      }

      .panels-grid {
        grid-template-columns: 1fr;
      }

      .section-divider h2 {
        font-size: 1.25rem;
      }
    }

    /* ===========================
       ANIMATIONS
    ============================*/
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .kpi-card,
    .chart-card,
    .panel-card {
      animation: fadeInUp 0.5s ease-out backwards;
    }

    .kpi-card:nth-child(1) { animation-delay: 0.1s; }
    .kpi-card:nth-child(2) { animation-delay: 0.2s; }
    .kpi-card:nth-child(3) { animation-delay: 0.3s; }
    .kpi-card:nth-child(4) { animation-delay: 0.4s; }
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

<div class="dashboard-wrapper">

  <!-- ================= HERO SECTION ================= -->
  <section class="hero-section">
    <div class="hero-greeting">
      <div class="hero-avatar">
        <i class="fas fa-user-circle"></i>
      </div>
      <div class="hero-text">
        <h1>Hola, <?= htmlspecialchars($nombre . " " . $apellido); ?> 👋</h1>
        <p>Bienvenido a tu panel de gestión del gimnasio</p>
      </div>
    </div>
    <!--div class="hero-stats">
      <div class="hero-badge">
        <i class="fas fa-chart-line"></i>
        Panel en tiempo real
      </div>
      <div class="hero-badge">
        <i class="fas fa-dumbbell"></i>
        Gestión completa
      </div>
      <div style="flex: 1;"></div>
      <?php include('statistics_home.php'); ?>
    </div-->
  </section>

  <!-- ================= KPI CARDS - 2x4 GRID ================= -->
  <?php include('cards_home.php'); ?>
  <!-- ================= CHARTS SECTION ================= -->
  <div class="section-divider">
    <h2>Análisis y Métricas</h2>
  </div>

  <div class="charts-grid">
    
    <div class="chart-card unique">
      <div class="chart-header">
        <div class="chart-title">Asistencias por Hora</div>
        <div class="chart-subtitle">Distribución de visitas durante el día de hoy</div>
      </div>
      
        <?php include('asis-chart-home.php'); ?>
      
    </div>

    <div class="chart-card unique">
      <div class="chart-header">
        <div class="chart-title">Comparativa día semana</div>
        <div class="chart-subtitle">Asistencia hoy VS. <?= echo strftime("%A, %d de %B de %Y");?> de la semana pasada </div>
      </div>
      
        <?php include('asis-chart-home-compara.php'); ?>
		<hr>
		<div class="chart-header">
        <div class="chart-title">Ventas Comparadas</div>
      <div class="chart-subtitle">Ingresos de hoy vs. ayer (todas las cajas)</div>
      </div>
		
		<?php include('ventas-today.php'); ?>
      
    </div>

  </div>

  
  <!-- ================= DATA PANELS ================= -->
  <div class="section-divider">
    <h2>Información Detallada</h2>
  </div>

  <div class="panels-grid">

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes que cumplen años hoy', $_SESSION["user_permissions"])): ?>
    <div class="panel-card">
      <div class="panel-header">
        <div class="panel-title">🎂 Cumpleaños de Hoy</div>
        <span class="panel-badge">
          <i class="fas fa-cake-candles"></i>
          Felicitaciones
        </span>
      </div>
      <div class="panel-body">
        <?php include('bd-today.php'); ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Vencimientos de Hoy', $_SESSION["user_permissions"])): ?>
    <div class="panel-card">
      <div class="panel-header">
        <div class="panel-title">⏰ Planes que Vencen Hoy</div>
        <span class="panel-badge">
          <i class="fas fa-exclamation-triangle"></i>
          Urgente
        </span>
      </div>
      <div class="panel-body">
        <?php include('due-today.php'); ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
    <div class="panel-card">
      <div class="panel-header">
        <div class="panel-title">✅ Asistencias Registradas</div>
        <span class="panel-badge">
          <i class="fas fa-check-circle"></i>
          Hoy
        </span>
      </div>
      <div class="panel-body">
        <?php include('asistencias_hoy.php'); ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Próximos Pagos a Vencer', $_SESSION["user_permissions"])): ?>
    <div class="panel-card">
      <div class="panel-header">
        <div class="panel-title">📅 Próximos Pagos</div>
        <span class="panel-badge">
          <i class="fas fa-calendar-week"></i>
          7 días
        </span>
      </div>
      <div class="panel-body">
        <?php include('upcoming-bills.php'); ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes', $_SESSION["user_permissions"])): ?>
    <div class="panel-card">
      <div class="panel-header">
        <div class="panel-title">👥 Últimos Clientes</div>
        <span class="panel-badge">
          <i class="fas fa-user-plus"></i>
          Recientes
        </span>
      </div>
      <div class="panel-body">
        <?php include('lastests-clients.php'); ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Crear Creditos', $_SESSION["user_permissions"])): ?>
    <div class="panel-card">
      <div class="panel-header">
        <div class="panel-title">💳 Créditos Activos</div>
        <span class="panel-badge">
          <i class="fas fa-money-bill-wave"></i>
          Cartera
        </span>
      </div>
      <div class="panel-body">
        <?php include('credits.php'); ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>

<!-- ================= FLOATING ACTION BUTTON ================= -->
<a href="<?php echo $url;?>/admin/support/" class="fab-support" title="Soporte">
  <i class="fas fa-question-circle"></i>
</a>

<?php include('inc/menu-footer.php'); ?>

<!-- ========================== SCRIPTS ========================== -->

<script>
// Contador de asistencias
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

// KPIs superiores
function actualizarKPIs() {
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
          trend.innerHTML = `<i class="fas fa-arrow-up"></i> +${p}% vs ayer`;
          trend.classList.remove('negative','neutral');
          trend.classList.add('positive');
        } else if (p < 0) {
          trend.innerHTML = `<i class="fas fa-arrow-down"></i> ${p}% vs ayer`;
          trend.classList.remove('positive','neutral');
          trend.classList.add('negative');
        } else {
          trend.innerHTML = `<i class="fas fa-minus"></i> Sin cambios`;
          trend.classList.remove('positive','negative');
          trend.classList.add('neutral');
        }
      }
    })
    .catch(err => {
      console.error('Error KPIs home:', err);
    });
}

document.addEventListener('DOMContentLoaded', () => {
  actualizarContadorAsistencias();
  actualizarKPIs();
  
  // Actualizar cada 30 segundos
  setInterval(() => {
    actualizarContadorAsistencias();
    actualizarKPIs();
  }, 30000);
});
</script>

	

	
</body>
</html>

