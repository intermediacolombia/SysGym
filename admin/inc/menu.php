<!-- ============================================================
     MENÚ LATERAL MODERNO — reemplaza inc/menu.php
     Mantiene toda la lógica PHP original: permisos, submenús,
     dark mode, caja, foto de perfil, logout modal.
     ============================================================ -->

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Variables dinámicas PHP que no pueden ir en el .css estático -->
<style>
:root {
  --nav-active-bg: linear-gradient(135deg, <?= SYSTEM_COLOR_PRIMARY ?> 0%, <?= SYSTEM_COLOR_SECONDARY ?> 100%);
  --submenu-active: <?= SYSTEM_COLOR_PRIMARY ?>;
}
</style>

<!-- Overlay -->
<div class="sg-overlay" id="sgOverlay" onclick="sgCloseSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sg-sidebar" id="sgSidebar">

  <!-- Logo + user -->
  <div class="sg-logo-area">
    <div class="sg-logo-row">
      <img src="<?= $url ?>/<?= SITE_LOGO ?>" alt="Logo" class="sg-logo-img">
      <span class="sg-gym-name"><?= htmlspecialchars(NAME_GYM) ?></span>
    </div>
    <?php
      $foto = (!empty($foto_perfil) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $foto_perfil))
              ? $foto_perfil : 'assets/img/default-user.png';
    ?>
    <div class="sg-user-card">
      <img src="<?= URLBASE . '/' . $foto ?>" alt="Foto" class="sg-user-avatar">
      <div class="sg-user-info">
        <div class="sg-user-name"><?= htmlspecialchars($nombre . ' ' . $apellido) ?></div>
        <div class="sg-user-role"><?= htmlspecialchars($rolUser) ?></div>
      </div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="sg-nav">

    <span class="sg-section-label">Principal</span>

    <!-- Inicio -->
    <a href="<?= $url ?>/admin/" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-home"></i></span>
      <span class="sg-label">Inicio</span>
    </a>

    <!-- Clientes -->
    <?php if (isset($_SESSION["user_permissions"]) && (
        in_array('Ver Clientes', $_SESSION["user_permissions"]) ||
        in_array('Ver Clientes Pre-inscritos', $_SESSION["user_permissions"]) ||
        in_array('Ver Asistencias', $_SESSION["user_permissions"])
    )): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="far fa-address-book"></i></span>
      <span class="sg-label">Clientes</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu">
      <?php if (in_array('Ver Clientes', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/clients/" class="sg-subitem" onclick="sgCloseSidebar()">Todos los clientes</a>
        <a href="<?= $url ?>/admin/clients/client_search.php" class="sg-subitem" onclick="sgCloseSidebar()">Búsqueda avanzada</a>
      <?php endif; ?>
      <?php if (in_array('Ver Clientes Pre-inscritos', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/clients/pre-registered.php" class="sg-subitem" onclick="sgCloseSidebar()">Pre-inscritos</a>
      <?php endif; ?>
      <?php if (in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/clients/asistencias.php" class="sg-subitem" onclick="sgCloseSidebar()">Asistencias</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Mensajes pendientes -->
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Mensajes Pendientes', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/ws_outbox/" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fa fa-clock-o"></i></span>
      <span class="sg-label">Mensajes Pendientes</span>
    </a>
    <?php endif; ?>

    <!-- Valoraciones -->
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Manejar Valoraciones', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/valoraciones" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-heartbeat"></i></span>
      <span class="sg-label">Valoraciones</span>
    </a>
    <?php endif; ?>

    <!-- Planes -->
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Planes', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/plans" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-fire-alt"></i></span>
      <span class="sg-label">Planes</span>
    </a>
    <?php endif; ?>

    <!-- Rutinas -->
    <?php if (isset($_SESSION["user_permissions"]) && (
        in_array('Ver Ejercicios', $_SESSION["user_permissions"]) ||
        in_array('Ver Rutinas', $_SESSION["user_permissions"])
    )): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="material-icons" style="font-size:1rem">fitness_center</i></span>
      <span class="sg-label">Rutinas</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu">
      <?php if (in_array('Ver Rutinas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/routines/" class="sg-subitem" onclick="sgCloseSidebar()">Gestionar rutinas</a>
      <?php endif; ?>
      <?php if (in_array('Ver Ejercicios', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/routines/ejercicios.php" class="sg-subitem" onclick="sgCloseSidebar()">Ejercicios</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="sg-divider"></div>
    <span class="sg-section-label">Operaciones</span>

    <!-- Caja -->
    <?php if (isset($_SESSION["user_permissions"]) && (
        in_array('Usar Cajas', $_SESSION["user_permissions"]) ||
        in_array('Ver Todas las Cajas', $_SESSION["user_permissions"])
    )): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-cash-register"></i></span>
      <span class="sg-label">Caja</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu">
      <?php if (in_array('Usar Cajas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/caja/" class="sg-subitem" onclick="sgCloseSidebar()">Mi caja abierta</a>
        <a href="<?= $url ?>/admin/caja/cajas_list.php" class="sg-subitem" onclick="sgCloseSidebar()">Mis cajas cerradas</a>
      <?php endif; ?>
      <?php if (in_array('Ver Todas las Cajas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/caja/cajas_list_all.php" class="sg-subitem" onclick="sgCloseSidebar()">Todas las cajas</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Productos -->
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Editar Productos', $_SESSION["user_permissions"])): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-shopping-bag"></i></span>
      <span class="sg-label">Productos</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu">
      <a href="<?= $url ?>/admin/products" class="sg-subitem" onclick="sgCloseSidebar()">Stock</a>
      <?php if (in_array('Ver y Editar Bolsillos', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/products/pocket.php" class="sg-subitem" onclick="sgCloseSidebar()">Bolsillos</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Nómina -->
    <?php if (isset($_SESSION["user_permissions"]) && (
        in_array('Ver Nominas', $_SESSION["user_permissions"]) ||
        in_array('Pagar Nominas', $_SESSION["user_permissions"]) ||
        in_array('Ver Empleados', $_SESSION["user_permissions"])
    )): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="far fa-id-card"></i></span>
      <span class="sg-label">Nómina</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu">
      <?php if (in_array('Ver Empleados', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/payroll/employees.php" class="sg-subitem" onclick="sgCloseSidebar()">Empleados</a>
      <?php endif; ?>
      <?php if (in_array('Pagar Nominas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/payroll/" class="sg-subitem" onclick="sgCloseSidebar()">Pagar nómina</a>
      <?php endif; ?>
      <?php if (in_array('Ver Nominas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/payroll/all_payroll.php" class="sg-subitem" onclick="sgCloseSidebar()">Nóminas pagadas</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Contabilidad -->
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Manejar Contabilidad', $_SESSION["user_permissions"])): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-coins"></i></span>
      <span class="sg-label">Contabilidad</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu">
      <?php if (in_array('Ver Reportes Contabilidad', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/contabilidad/reporte.php" class="sg-subitem" onclick="sgCloseSidebar()">Reportes</a>
      <?php endif; ?>
      <?php if (in_array('Ver Egresos', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/contabilidad/expenses.php" class="sg-subitem" onclick="sgCloseSidebar()">Egresos</a>
      <?php endif; ?>
      <a href="<?= $url ?>/admin/contabilidad/invoices.php" class="sg-subitem" onclick="sgCloseSidebar()">Facturas</a>
      <?php if (in_array('Ver Pagos Pasarela', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/contabilidad/gateway_registration.php" class="sg-subitem" onclick="sgCloseSidebar()">Registros pasarela</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Herramientas -->
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Enviar WhatsApp Masivo', $_SESSION["user_permissions"])): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-tools"></i></span>
      <span class="sg-label">Herramientas</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu">
      <a href="<?= $url ?>/admin/tools/ws-send-masive" class="sg-subitem" onclick="sgCloseSidebar()">Envío masivo WhatsApp</a>
    </div>
    <?php endif; ?>

    <div class="sg-divider"></div>
    <span class="sg-section-label">Sistema</span>

    <!-- Usuarios -->
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Editar Usuarios', $_SESSION["user_permissions"])): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-user-cog"></i></span>
      <span class="sg-label">Usuarios</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu">
      <a href="<?= $url ?>/admin/users/" class="sg-subitem" onclick="sgCloseSidebar()">Todos</a>
      <?php if (in_array('Gestionar Roles', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/users/roles.php" class="sg-subitem" onclick="sgCloseSidebar()">Roles</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <a href="<?= $url ?>/admin/profile/" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-user"></i></span>
      <span class="sg-label">Mi Perfil</span>
    </a>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Estadisticas', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/statistics/" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fa fa-bar-chart"></i></span>
      <span class="sg-label">Estadísticas</span>
    </a>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Configurar Sistema', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/config/" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-cog"></i></span>
      <span class="sg-label">Configuraciones</span>
    </a>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Logs del Sistema', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/system/logs.php" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fa fa-history"></i></span>
      <span class="sg-label">Logs del Sistema</span>
    </a>
    <?php endif; ?>

    <a href="<?= $url ?>/admin/support/" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-headset"></i></span>
      <span class="sg-label">Soporte</span>
    </a>

    <a href="https://api.intermediahost.co" target="_blank" class="sg-item">
      <span class="sg-icon"><i class="fa fa-whatsapp"></i></span>
      <span class="sg-label">API WhatsApp</span>
    </a>

  </nav>

  <!-- Footer: dark mode + salir -->
  <div class="sg-footer">

    <!-- Dark mode toggle -->
    <div id="themeToggleContainer" class="sg-theme-row">
      <div class="sg-theme-icons">
        <i class="fas fa-sun"></i>
        <span style="flex:1; font-size:0.78rem;">Tema</span>
        <div class="form-check form-switch m-0" style="transform:scale(0.85)">
          <input class="form-check-input" type="checkbox" role="switch" id="themeToggle">
        </div>
        <i class="fas fa-moon"></i>
      </div>
    </div>

    <!-- Salir -->
    <?php if(!$caja_id): ?>
      <a href="<?= $url ?>/admin/login/logout.php" class="sg-item" style="color:#ef4444; margin-top:2px;" onclick="sgCloseSidebar()">
        <span class="sg-icon" style="color:#ef4444"><i class="fas fa-power-off"></i></span>
        <span class="sg-label">Salir</span>
      </a>
    <?php else: ?>
      <button class="sg-item" style="color:#ef4444; margin-top:2px;"
        data-bs-toggle="modal" data-bs-target="#modalCajaAbierta" onclick="sgCloseSidebar()">
        <span class="sg-icon" style="color:#ef4444"><i class="fas fa-power-off"></i></span>
        <span class="sg-label">Salir</span>
      </button>
    <?php endif; ?>

  </div>

</aside>

<!-- Hamburger -->
<button class="sg-hamburger" id="sgHamburger" onclick="sgOpenSidebar()">
  <i class="fas fa-bars"></i>
</button>

<!-- Modal caja abierta (igual que antes) -->
<div class="container">
  <div class="modal fade" id="modalCajaAbierta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Caja Abierta</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Tienes una caja abierta. Debes cerrarla antes de salir.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <a href="<?= $url ?>/admin/caja" class="btn btn-danger">Ir a Cerrar Caja</a>
        </div>
      </div>
    </div>
  </div>

  <?php require __DIR__ . '/caja_bar.php'; ?>


<!-- Loader global (igual que antes) -->
<div id="page-loader" class="loader-container">
  <div class="loader-circle"></div>
  <div class="mensaje-carga mensaje1">Estamos procesando la solicitud, por favor espera...</div>
  <div class="mensaje-carga mensaje2">Esto está tardando un poco... por favor sé paciente...</div>
  <div class="mensaje-carga mensaje3">Seguimos trabajando en la solicitud... no recargues la página...</div>
</div>

<!-- div fantasma: cierra el </div> heredado de menu-footer.php sin afectar el layout -->


<script>
// ── Sidebar open/close ───────────────────────────────────────
function sgOpenSidebar() {
  document.getElementById('sgSidebar').classList.add('mobile-open');
  document.getElementById('sgOverlay').classList.add('show');
}
function sgCloseSidebar() {
  document.getElementById('sgSidebar').classList.remove('mobile-open');
  document.getElementById('sgOverlay').classList.remove('show');
}

// ── Submenu toggle: solo abre uno a la vez, cierra si ya estaba abierto ──
function sgToggle(btn) {
  const submenu = btn.nextElementSibling;
  if (!submenu || !submenu.classList.contains('sg-submenu')) return;
  const isOpen = submenu.classList.contains('open');

  // Cerrar todos
  document.querySelectorAll('.sg-submenu.open').forEach(m => {
    m.classList.remove('open');
    m.previousElementSibling?.classList.remove('open');
  });

  // Si no estaba abierto, abrir este
  if (!isOpen) {
    submenu.classList.add('open');
    btn.classList.add('open');
  }
}

// ── Marcar item activo según URL actual ──────────────────────
(function markActive() {
  const path = window.location.pathname.replace(/\/$/, '') || '/';

  let bestMatch = null;
  let bestLen = 0;

  document.querySelectorAll('.sg-item[href], .sg-subitem[href]').forEach(el => {
    const href = el.getAttribute('href') || '';
    if (!href || href === '#') return;
    try {
      const elPath = new URL(href, location.origin).pathname.replace(/\/$/, '') || '/';
      // Evitar que "/" (inicio) coincida con todo
      if (elPath === '/' && path !== '/') return;
      if (path === elPath || path.startsWith(elPath + '/')) {
        if (elPath.length > bestLen) {
          bestLen = elPath.length;
          bestMatch = el;
        }
      }
    } catch(e) {}
  });

  if (bestMatch) {
    bestMatch.classList.add('active');
    const submenu = bestMatch.closest('.sg-submenu');
    if (submenu) {
      submenu.classList.add('open');
      submenu.previousElementSibling?.classList.add('open');
    }
  }
})();
</script>
		