<!-- ============================================================
     MENÚ LATERAL MODERNO — reemplaza inc/menu.php
     ============================================================ -->

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
  --nav-active-bg: linear-gradient(135deg, <?= SYSTEM_COLOR_PRIMARY ?> 0%, <?= SYSTEM_COLOR_SECONDARY ?> 100%);
  --submenu-active: <?= SYSTEM_COLOR_PRIMARY ?>;
}
</style>

<div class="sg-overlay" id="sgOverlay" onclick="sgCloseSidebar()"></div>

<aside class="sg-sidebar" id="sgSidebar">

  <!-- Logo + user -->
  <div class="sg-logo-area">
    <div class="sg-logo-row" style="flex-direction:column; align-items:center; gap:0.4rem; margin-bottom:1rem;">
      <img src="<?= $url ?>/<?= SITE_LOGO ?>" alt="Logo" class="sg-logo-img" style="height:52px;">
      <span class="sg-gym-name" style="max-width:100%; text-align:center; font-size:0.85rem;">SysGym V.2.0.1</span>
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

  <nav class="sg-nav">

    <span class="sg-section-label">Principal</span>

    <a href="<?= $url ?>/admin/" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-home"></i></span>
      <span class="sg-label">Inicio</span>
    </a>

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
    <div class="sg-submenu"><div>
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
    </div></div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Mensajes Pendientes', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/ws_outbox/" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fa fa-clock-o"></i></span>
      <span class="sg-label">Mensajes Pendientes</span>
    </a>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Manejar Valoraciones', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/valoraciones" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-heartbeat"></i></span>
      <span class="sg-label">Valoraciones</span>
    </a>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Planes', $_SESSION["user_permissions"])): ?>
    <a href="<?= $url ?>/admin/plans" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-fire-alt"></i></span>
      <span class="sg-label">Planes</span>
    </a>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && (
        in_array('Ver Ejercicios', $_SESSION["user_permissions"]) ||
        in_array('Ver Rutinas', $_SESSION["user_permissions"])
    )): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="material-icons" style="font-size:1rem">fitness_center</i></span>
      <span class="sg-label">Rutinas</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu"><div>
      <?php if (in_array('Ver Rutinas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/routines/" class="sg-subitem" onclick="sgCloseSidebar()">Gestionar rutinas</a>
      <?php endif; ?>
      <?php if (in_array('Ver Ejercicios', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/routines/ejercicios.php" class="sg-subitem" onclick="sgCloseSidebar()">Ejercicios</a>
      <?php endif; ?>
    </div></div>
    <?php endif; ?>

    <div class="sg-divider"></div>
    <span class="sg-section-label">Operaciones</span>

    <?php if (isset($_SESSION["user_permissions"]) && (
        in_array('Usar Cajas', $_SESSION["user_permissions"]) ||
        in_array('Ver Todas las Cajas', $_SESSION["user_permissions"])
    )): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-cash-register"></i></span>
      <span class="sg-label">Caja</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu"><div>
      <?php if (in_array('Usar Cajas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/caja/" class="sg-subitem" onclick="sgCloseSidebar()">Mi caja abierta</a>
        <a href="<?= $url ?>/admin/caja/cajas_list.php" class="sg-subitem" onclick="sgCloseSidebar()">Mis cajas cerradas</a>
      <?php endif; ?>
      <?php if (in_array('Ver Todas las Cajas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/caja/cajas_list_all.php" class="sg-subitem" onclick="sgCloseSidebar()">Todas las cajas</a>
      <?php endif; ?>
    </div></div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Editar Productos', $_SESSION["user_permissions"])): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-shopping-bag"></i></span>
      <span class="sg-label">Productos</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu"><div>
      <a href="<?= $url ?>/admin/products" class="sg-subitem" onclick="sgCloseSidebar()">Stock</a>
      <?php if (in_array('Ver y Editar Bolsillos', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/products/pocket.php" class="sg-subitem" onclick="sgCloseSidebar()">Bolsillos</a>
      <?php endif; ?>
    </div></div>
    <?php endif; ?>

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
    <div class="sg-submenu"><div>
      <?php if (in_array('Ver Empleados', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/payroll/employees.php" class="sg-subitem" onclick="sgCloseSidebar()">Empleados</a>
      <?php endif; ?>
      <?php if (in_array('Pagar Nominas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/payroll/" class="sg-subitem" onclick="sgCloseSidebar()">Pagar nómina</a>
      <?php endif; ?>
      <?php if (in_array('Ver Nominas', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/payroll/all_payroll.php" class="sg-subitem" onclick="sgCloseSidebar()">Nóminas pagadas</a>
      <?php endif; ?>
    </div></div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Manejar Contabilidad', $_SESSION["user_permissions"])): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-coins"></i></span>
      <span class="sg-label">Contabilidad</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu"><div>
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
    </div></div>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Enviar WhatsApp Masivo', $_SESSION["user_permissions"])): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-tools"></i></span>
      <span class="sg-label">Herramientas</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu"><div>
      <a href="<?= $url ?>/admin/tools/ws-send-masive" class="sg-subitem" onclick="sgCloseSidebar()">Envío masivo WhatsApp</a>
    </div></div>
    <?php endif; ?>

    <div class="sg-divider"></div>
    <span class="sg-section-label">Sistema</span>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Editar Usuarios', $_SESSION["user_permissions"])): ?>
    <button class="sg-item" onclick="sgToggle(this)">
      <span class="sg-icon"><i class="fas fa-user-cog"></i></span>
      <span class="sg-label">Usuarios</span>
      <i class="fas fa-chevron-down sg-chevron"></i>
    </button>
    <div class="sg-submenu"><div>
      <a href="<?= $url ?>/admin/users/" class="sg-subitem" onclick="sgCloseSidebar()">Todos</a>
      <?php if (in_array('Gestionar Roles', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/users/roles.php" class="sg-subitem" onclick="sgCloseSidebar()">Roles</a>
      <?php endif; ?>
    </div></div>
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

  <!-- Footer -->
  <div class="sg-footer">
    <div id="themeToggleContainer" class="sg-theme-row" style="justify-content:space-between; align-items:center; padding:0.5rem 0.75rem;">
      <div style="display:flex; align-items:center; gap:0.5rem;">
        <i class="fas fa-sun" id="themeLabelIcon" style="font-size:0.8rem; color:#f59e0b;"></i>
        <span style="font-size:0.78rem; font-weight:500; color:var(--nav-text);">Tema</span>
      </div>
      <div class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" role="switch" id="themeToggle" style="width:36px; height:20px; cursor:pointer;">
      </div>
    </div>

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

<button class="sg-hamburger" id="sgHamburger" onclick="sgOpenSidebar()">
  <i class="fas fa-bars"></i>
</button>

<script>
function sgOpenSidebar() {
  document.getElementById('sgSidebar').classList.add('mobile-open');
  document.getElementById('sgOverlay').classList.add('show');
}
function sgCloseSidebar() {
  document.getElementById('sgSidebar').classList.remove('mobile-open');
  document.getElementById('sgOverlay').classList.remove('show');
}

function sgToggle(btn) {
  const submenu = btn.nextElementSibling;
  if (!submenu || !submenu.classList.contains('sg-submenu')) return;
  const isOpen = submenu.classList.contains('open');

  // Cerrar todos excepto el actual y los que tienen hijo activo
  document.querySelectorAll('.sg-submenu.open').forEach(m => {
    if (m === submenu) return; // no tocar el actual todavía
    if (m.querySelector('.sg-subitem.active')) return;
    m.classList.remove('open');
    const prev = m.previousElementSibling;
    if (prev) prev.classList.remove('open');
  });

  // Alternar el actual
  if (isOpen) {
    submenu.classList.remove('open');
    btn.classList.remove('open');
  } else {
    submenu.classList.add('open');
    btn.classList.add('open');
  }
}

(function markActive() {
  const path = window.location.pathname.replace(/\/$/, '') || '/';
  let bestMatch = null;
  let bestLen   = 0;

  document.querySelectorAll('.sg-item[href], .sg-subitem[href]').forEach(el => {
    const href = el.getAttribute('href') || '';
    if (!href || href === '#') return;
    try {
      const elPath = new URL(href, location.origin).pathname.replace(/\/$/, '') || '/';
      if (elPath === '/' && path !== '/') return;
      if (path === elPath || path.startsWith(elPath + '/')) {
        if (elPath.length > bestLen) {
          bestLen   = elPath.length;
          bestMatch = el;
        }
      }
    } catch(e) {}
  });

  if (!bestMatch) return;

  const inSubmenu = bestMatch.classList.contains('sg-subitem');

  if (inSubmenu) {
    bestMatch.classList.add('active');
    const submenu = bestMatch.closest('.sg-submenu');
    if (submenu) {
      submenu.classList.add('open');
      const parentBtn = submenu.previousElementSibling;
      if (parentBtn && parentBtn.classList.contains('sg-item')) {
        parentBtn.classList.add('open', 'active-parent');
      }
    }
  } else {
    bestMatch.classList.add('active');
    document.querySelectorAll('.sg-submenu').forEach(m => {
      if (!m.querySelector('.sg-subitem.active')) {
        m.classList.remove('open');
        const prev = m.previousElementSibling;
        if (prev) prev.classList.remove('open');
      }
    });
  }
})();
</script>

<div class="container">