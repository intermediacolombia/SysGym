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
      
<div style="margin-left: auto;">
  <button onclick="abrirModalAsistencia()" class="btn-registrar-asistencia">
    <i class="fas fa-fingerprint"></i>
    <span>Registrar Asistencia</span>
  </button>
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
    <div class="chart-subtitle">
      Asistencia hoy VS. 
      <?php 
      setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish'); 
      echo strftime("%A"); 
      ?> de la semana pasada a las 
      <span id="hora-asis"><?= $hora; ?></span>
    </div>
  </div>

  <?php include('asis-chart-home-compara.php'); ?>

  <hr>

  <div class="chart-header">
    <div class="chart-title">Ventas Comparadas</div>
    <div class="chart-subtitle">
      Ingresos de hoy vs. ayer (todas las cajas) a las 
      <span id="hora-ventas"><?= $hora; ?></span>
    </div>
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

	
<script>
async function actualizarHora() {
    try {
        const res = await fetch("gets_home/get_hora.php");
        const data = await res.json();

        if (!data.hora) return;

        // Actualizar hora en asistencia
        const hAsis = document.getElementById("hora-asis");
        if (hAsis) hAsis.textContent = data.hora;

        // Actualizar hora en ventas
        const hVentas = document.getElementById("hora-ventas");
        if (hVentas) hVentas.textContent = data.hora;

    } catch (e) {
        console.error("Error actualizando hora", e);
    }
}

// Primera carga
actualizarHora();

// Cada 60 segundos
setInterval(actualizarHora, 60000);

// También actualizar si cambia tema
document.addEventListener("theme-changed", actualizarHora);
</script>

<!-- ===================== MODAL REGISTRAR ASISTENCIA ===================== -->
<style>
  .btn-registrar-asistencia {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1.4rem;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 14px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(102,126,234,0.35);
    transition: all 0.25s ease;
    letter-spacing: 0.01em;
    white-space: nowrap;
  }
  .btn-registrar-asistencia:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102,126,234,0.45);
  }
  .btn-registrar-asistencia i {
    font-size: 1.1rem;
  }

  /* Overlay */
  #modal-asistencia-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(6px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
  }
  #modal-asistencia-overlay.activo {
    display: flex;
  }

  /* Modal Box */
  .modal-asis-box {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 24px;
    padding: 2rem;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 30px 80px rgba(0,0,0,0.2);
    animation: modalEntrada 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
    position: relative;
  }
  @keyframes modalEntrada {
    from { opacity: 0; transform: scale(0.88) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
  }

  .modal-asis-close {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    width: 34px; height: 34px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    background: transparent;
    color: var(--text-secondary);
    font-size: 1rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
  }
  .modal-asis-close:hover {
    background: var(--border-color);
    color: var(--text-primary);
  }

  .modal-asis-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
  }
  .modal-asis-sub {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
  }

  .modal-asis-search-wrap {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
  }
  .modal-asis-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    background: var(--bg-light);
    color: var(--text-primary);
    font-size: 1rem;
    outline: none;
    transition: border-color 0.2s;
  }
  .modal-asis-input:focus {
    border-color: #3b82f6;
  }
  .modal-asis-search-btn {
    padding: 0.75rem 1.1rem;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    cursor: pointer;
    transition: opacity 0.2s;
  }
  .modal-asis-search-btn:hover { opacity: 0.85; }

  /* Result card */
  .asis-result-card {
    border: 1.5px solid var(--border-color);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    background: var(--bg-light);
    display: none;
  }
  .asis-result-card.visible { display: block; }

  .asis-client-header {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    margin-bottom: 1rem;
  }
  .asis-client-avatar {
    width: 48px; height: 48px;
    border-radius: 14px;
    background: var(--primary-gradient);
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
  }
  .asis-client-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--text-primary);
  }
  .asis-client-doc {
    font-size: 0.8rem;
    color: var(--text-secondary);
  }

  .asis-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.75rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
  }
  .asis-status-badge.activo    { background: rgba(16,185,129,0.12); color: #059669; }
  .asis-status-badge.vencido   { background: rgba(220,38,38,0.1);   color: #dc2626; }
  .asis-status-badge.inactivo  { background: rgba(100,116,139,0.12);color: #64748b; }
  .asis-status-badge.congelado { background: rgba(59,130,246,0.12); color: #2563eb; }

  /* Alertas dentro del modal */
  .asis-alert {
    padding: 0.85rem 1rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    margin-bottom: 1rem;
  }
  .asis-alert.warn  { background: rgba(245,158,11,0.12); color: #b45309; border: 1px solid rgba(245,158,11,0.25); }
  .asis-alert.error { background: rgba(220,38,38,0.08);  color: #dc2626; border: 1px solid rgba(220,38,38,0.2); }
  .asis-alert.ok    { background: rgba(16,185,129,0.1);  color: #059669; border: 1px solid rgba(16,185,129,0.2); }
  .asis-alert i { margin-top: 0.1rem; flex-shrink: 0; }

  .asis-action-row {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.5rem;
  }
  .asis-btn-confirm {
    flex: 1;
    padding: 0.75rem;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: opacity 0.2s;
  }
  .asis-btn-confirm:hover { opacity: 0.85; }
  .asis-btn-confirm:disabled { opacity: 0.4; cursor: not-allowed; }
  .asis-btn-cancel {
    padding: 0.75rem 1rem;
    background: transparent;
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    color: var(--text-secondary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }
  .asis-btn-cancel:hover { background: var(--border-color); }

  .asis-spinner {
    width: 20px; height: 20px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: inline-block;
    vertical-align: middle;
    margin-right: 6px;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .asis-not-found {
    text-align: center;
    padding: 1.5rem 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
    display: none;
  }
  .asis-not-found i { font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.4; }
</style>

<!-- MODAL HTML -->
<div id="modal-asistencia-overlay">
  <div class="modal-asis-box">
    <button class="modal-asis-close" onclick="cerrarModalAsistencia()"><i class="fas fa-times"></i></button>
    
    <div class="modal-asis-title"><i class="fas fa-fingerprint" style="margin-right:0.5rem;"></i>Registrar Asistencia</div>
    <div class="modal-asis-sub">Busca al cliente por su número de documento</div>

    <div class="modal-asis-search-wrap">
      <input 
        type="text" 
        id="asis-doc-input" 
        class="modal-asis-input" 
        placeholder="Número de documento..." 
        inputmode="numeric"
        onkeydown="if(event.key==='Enter') buscarClienteAsistencia()"
      >
      <button class="modal-asis-search-btn" onclick="buscarClienteAsistencia()">
        <i class="fas fa-search"></i>
      </button>
    </div>

    <!-- Not found -->
    <div class="asis-not-found" id="asis-not-found">
      <i class="fas fa-user-slash"></i>
      No se encontró ningún cliente con ese documento.
    </div>

    <!-- Result card -->
    <div class="asis-result-card" id="asis-result-card">
      <div class="asis-client-header">
        <div class="asis-client-avatar"><i class="fas fa-user"></i></div>
        <div>
          <div class="asis-client-name" id="asis-nombre"></div>
          <div class="asis-client-doc" id="asis-doc-display"></div>
        </div>
      </div>

      <div id="asis-status-badge-wrap"></div>
      <div id="asis-alert-wrap"></div>
      <div id="asis-action-wrap"></div>
    </div>
  </div>
</div>

<script>
// ─── Estado ───────────────────────────────────────────────
let _asisClienteId = null;
let _asisYaRegistro = false;
let _asisPermiteRegistro = true;

// ─── Abrir / Cerrar ───────────────────────────────────────
function abrirModalAsistencia() {
  document.getElementById('modal-asistencia-overlay').classList.add('activo');
  document.getElementById('asis-doc-input').value = '';
  resetResultadoAsis();
  setTimeout(() => document.getElementById('asis-doc-input').focus(), 100);
}

function cerrarModalAsistencia() {
  document.getElementById('modal-asistencia-overlay').classList.remove('activo');
}

// Cerrar al clic fuera
document.getElementById('modal-asistencia-overlay').addEventListener('click', function(e){
  if (e.target === this) cerrarModalAsistencia();
});

// ─── Reset ─────────────────────────────────────────────────
function resetResultadoAsis() {
  document.getElementById('asis-result-card').classList.remove('visible');
  document.getElementById('asis-not-found').style.display = 'none';
  _asisClienteId = null;
  _asisYaRegistro = false;
  _asisPermiteRegistro = true;
}

// ─── Buscar cliente ────────────────────────────────────────
function buscarClienteAsistencia() {
  const doc = document.getElementById('asis-doc-input').value.trim();
  if (!doc) return;

  resetResultadoAsis();

  const btn = document.querySelector('.modal-asis-search-btn');
  btn.innerHTML = '<i class="asis-spinner"></i>';
  btn.disabled = true;

  fetch(`gets_home/get_cliente_asis.php?documento=${encodeURIComponent(doc)}`)
    .then(r => r.json())
    .then(data => {
      btn.innerHTML = '<i class="fas fa-search"></i>';
      btn.disabled = false;

      if (!data || !data.id) {
        document.getElementById('asis-not-found').style.display = 'block';
        return;
      }

      _asisClienteId = data.id;
      renderResultadoAsis(data);
    })
    .catch(() => {
      btn.innerHTML = '<i class="fas fa-search"></i>';
      btn.disabled = false;
      document.getElementById('asis-not-found').style.display = 'block';
    });
}

// ─── Render resultado ──────────────────────────────────────
function renderResultadoAsis(data) {
  document.getElementById('asis-nombre').textContent = data.nombre_completo;
  document.getElementById('asis-doc-display').textContent = '📄 ' + data.documento;

  // Usar estado_real que ya combina congelado + vencimiento + estado base
  const estado = (data.estado_real || data.estado || '').toLowerCase();

  const estadoLabels = {
    activo:    'Activo',
    inactivo:  'Inactivo',
    vencido:   'Plan Vencido',
    congelado: 'Plan Congelado',
  };
  const estadoLabel = estadoLabels[estado] || data.estado_real || 'Desconocido';

  let estadoClass = 'activo';
  if (estado === 'vencido')   estadoClass = 'vencido';
  if (estado === 'inactivo')  estadoClass = 'inactivo';
  if (estado === 'congelado') estadoClass = 'congelado';

  document.getElementById('asis-status-badge-wrap').innerHTML = `
    <span class="asis-status-badge ${estadoClass}">
      <i class="fas fa-circle" style="font-size:0.5rem;"></i>
      ${estadoLabel}
    </span>
  `;

  const bloqueado = ['vencido', 'inactivo', 'congelado'].includes(estado);
  _asisPermiteRegistro = !bloqueado;
  _asisYaRegistro = data.ya_asistio > 0;

  let alertHTML = '';
  let accionHTML = '';

  if (bloqueado) {
    const razones = {
      vencido:   'su plan está <strong>vencido</strong>',
      inactivo:  'el cliente se encuentra <strong>inactivo</strong>',
      congelado: 'el plan está <strong>congelado</strong>',
    };
    alertHTML = `
      <div class="asis-alert error">
        <i class="fas fa-ban"></i>
        <span>No se puede registrar asistencia porque ${razones[estado] || 'el cliente tiene un estado bloqueante'}.</span>
      </div>`;
    accionHTML = `<div class="asis-action-row">
      <button class="asis-btn-cancel" onclick="cerrarModalAsistencia()">Cerrar</button>
    </div>`;

  } else if (_asisYaRegistro) {
    alertHTML = `
      <div class="asis-alert warn">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Este cliente <strong>ya registró asistencia hoy</strong>. ¿Deseas registrar una entrada adicional?</span>
      </div>`;
    accionHTML = `<div class="asis-action-row">
      <button class="asis-btn-cancel" onclick="cerrarModalAsistencia()">Cancelar</button>
      <button class="asis-btn-confirm" onclick="confirmarAsistencia()">
        <i class="fas fa-check"></i> Registrar de todas formas
      </button>
    </div>`;

  } else {
    alertHTML = `
      <div class="asis-alert ok">
        <i class="fas fa-check-circle"></i>
        <span>Cliente activo y listo para registrar asistencia.</span>
      </div>`;
    accionHTML = `<div class="asis-action-row">
      <button class="asis-btn-cancel" onclick="cerrarModalAsistencia()">Cancelar</button>
      <button class="asis-btn-confirm" onclick="confirmarAsistencia()">
        <i class="fas fa-fingerprint"></i> Confirmar Asistencia
      </button>
    </div>`;
  }

  document.getElementById('asis-alert-wrap').innerHTML = alertHTML;
  document.getElementById('asis-action-wrap').innerHTML = accionHTML;
  document.getElementById('asis-result-card').classList.add('visible');
}

// ─── Confirmar registro ────────────────────────────────────
function confirmarAsistencia() {
  if (!_asisClienteId) return;

  const btn = document.querySelector('.asis-btn-confirm');
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="asis-spinner"></span> Registrando...'; }

  fetch('gets_home/post_registrar_asistencia.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ cliente_id: _asisClienteId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('asis-alert-wrap').innerHTML = `
        <div class="asis-alert ok">
          <i class="fas fa-check-circle"></i>
          <span><strong>¡Asistencia registrada!</strong> ${data.hora || ''}</span>
        </div>`;
      document.getElementById('asis-action-wrap').innerHTML = `
        <div class="asis-action-row">
          <button class="asis-btn-confirm" onclick="cerrarModalAsistencia()" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="fas fa-check"></i> Listo
          </button>
        </div>`;

      // Refrescar contador
      if (typeof actualizarContadorAsistencias === 'function') actualizarContadorAsistencias();
      if (typeof actualizarKPIs === 'function') actualizarKPIs();

    } else {
      document.getElementById('asis-alert-wrap').innerHTML = `
        <div class="asis-alert error">
          <i class="fas fa-times-circle"></i>
          <span>${data.message || 'Error al registrar la asistencia.'}</span>
        </div>`;
      if (btn) { btn.disabled = false; btn.innerHTML = 'Reintentar'; }
    }
  })
  .catch(() => {
    document.getElementById('asis-alert-wrap').innerHTML = `
      <div class="asis-alert error">
        <i class="fas fa-wifi"></i>
        <span>Error de conexión. Intenta nuevamente.</span>
      </div>`;
    if (btn) { btn.disabled = false; btn.innerHTML = 'Reintentar'; }
  });
}
</script>
	
</body>
</html>

