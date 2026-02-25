<!-- ============================================================
     MENÚ LATERAL MODERNO — reemplaza inc/menu.php
     Mantiene toda la lógica PHP original: permisos, submenús,
     dark mode, caja, foto de perfil, logout modal.
     ============================================================ -->

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ── Empujar contenido para no quedar bajo el sidebar ───────── */
@media (min-width: 992px) {
  .container,
  .container-fluid,
  .container-lg,
  .container-xl,
  .portada {
    margin-left: calc(var(--sidebar-w) + 1rem) !important;
    max-width: calc(100% - var(--sidebar-w) - 2rem) !important;
  }
}

/* ── Reset base ─────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

:root {
  --sidebar-w: 272px;
  --sidebar-bg: #ffffff;
  --sidebar-border: #f0f2f7;
  --nav-active-bg: linear-gradient(135deg, <?= SYSTEM_COLOR_PRIMARY ?> 0%, <?= SYSTEM_COLOR_SECONDARY ?> 100%);
  --nav-active-shadow: 0 4px 14px rgba(0,0,0,0.18);
  --nav-hover-bg: #f6f8fd;
  --nav-text: #5a6478;
  --nav-text-active: #ffffff;
  --nav-icon: #9aa3b5;
  --submenu-bg: #f8faff;
  --submenu-text: #6b7894;
  --submenu-active: <?= SYSTEM_COLOR_PRIMARY ?>;
  --section-label: #b0b8cc;
  --divider: #eef0f6;
  --header-h: 0px;
  --font: 'Plus Jakarta Sans', sans-serif;
  --transition: 0.22s cubic-bezier(0.4,0,0.2,1);
}

body.dark-mode {
  --sidebar-bg: #141b2d;
  --sidebar-border: #1e2a42;
  --nav-hover-bg: #1a2540;
  --nav-text: #8a95ae;
  --nav-icon: #5a6a8a;
  --submenu-bg: #111827;
  --submenu-text: #7080a0;
  --section-label: #3a4a6a;
  --divider: #1e2a42;
}

/* ── Sidebar wrapper ────────────────────────────────────────── */
.sg-sidebar {
  position: fixed;
  left: 0; top: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--sidebar-bg);
  border-right: 1px solid var(--sidebar-border);
  box-shadow: 4px 0 24px rgba(0,0,0,0.06);
  display: flex;
  flex-direction: column;
  z-index: 1040;
  font-family: var(--font);
  transition: transform var(--transition), box-shadow var(--transition);
  overflow: hidden;
}

/* ── Logo / Gym header ──────────────────────────────────────── */
.sg-logo-area {
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid var(--divider);
  flex-shrink: 0;
}
.sg-logo-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
.sg-logo-img {
  height: 36px;
  width: auto;
  object-fit: contain;
  border-radius: 8px;
}
.sg-gym-name {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--nav-text);
  letter-spacing: -0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 160px;
}

/* ── User card ──────────────────────────────────────────────── */
.sg-user-card {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.6rem 0.75rem;
  background: var(--nav-hover-bg);
  border-radius: 14px;
  border: 1px solid var(--divider);
}
.sg-user-avatar {
  width: 40px; height: 40px;
  border-radius: 12px;
  object-fit: cover;
  flex-shrink: 0;
  border: 2px solid var(--divider);
}
.sg-user-info { flex: 1; min-width: 0; }
.sg-user-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--nav-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sg-user-role {
  font-size: 0.72rem;
  color: var(--section-label);
  font-weight: 500;
}

/* ── Nav scroll area ────────────────────────────────────────── */
.sg-nav {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem 0.85rem;
  scrollbar-width: thin;
  scrollbar-color: var(--divider) transparent;
}
.sg-nav::-webkit-scrollbar { width: 4px; }
.sg-nav::-webkit-scrollbar-thumb { background: var(--divider); border-radius: 4px; }

/* ── Section label ──────────────────────────────────────────── */
.sg-section-label {
  font-size: 0.67rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--section-label);
  padding: 1rem 0.6rem 0.3rem;
  display: block;
}

/* ── Nav item ───────────────────────────────────────────────── */
.sg-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.62rem 0.85rem;
  border-radius: 12px;
  cursor: pointer;
  transition: background var(--transition), color var(--transition), box-shadow var(--transition);
  color: var(--nav-text);
  text-decoration: none;
  font-size: 0.845rem;
  font-weight: 500;
  position: relative;
  margin-bottom: 2px;
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
}
.sg-item:hover {
  background: var(--nav-hover-bg);
  color: var(--submenu-active);
  text-decoration: none;
}
.sg-item.active {
  background: var(--nav-active-bg);
  color: var(--nav-text-active) !important;
  box-shadow: var(--nav-active-shadow);
}
.sg-item.active .sg-icon,
.sg-item.active .sg-chevron { color: rgba(255,255,255,0.85) !important; }

/* ── Icon ───────────────────────────────────────────────────── */
.sg-icon {
  width: 20px; height: 20px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.95rem;
  color: var(--nav-icon);
  flex-shrink: 0;
  transition: color var(--transition);
}
.sg-item:hover .sg-icon { color: var(--submenu-active); }

/* ── Label text ─────────────────────────────────────────────── */
.sg-label { flex: 1; line-height: 1; }

/* ── Chevron ────────────────────────────────────────────────── */
.sg-chevron {
  font-size: 0.65rem;
  color: var(--nav-icon);
  transition: transform var(--transition);
  margin-left: auto;
}
.sg-item.open .sg-chevron { transform: rotate(180deg); }

/* ── Submenu ────────────────────────────────────────────────── */
.sg-submenu {
  display: none;
  flex-direction: column;
  background: var(--submenu-bg);
  border-radius: 10px;
  margin: 2px 0 4px 0;
  padding: 4px 0;
  border: 1px solid var(--divider);
  overflow: hidden;
}
.sg-submenu.open { display: flex; }

.sg-subitem {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.85rem 0.5rem 2.5rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--submenu-text);
  text-decoration: none;
  transition: color var(--transition), background var(--transition);
  border-left: 2px solid transparent;
  margin: 1px 0.5rem;
  border-radius: 8px;
}
.sg-subitem:hover {
  color: var(--submenu-active);
  background: var(--nav-hover-bg);
  text-decoration: none;
}
.sg-subitem::before {
  content: '';
  width: 5px; height: 5px;
  border-radius: 50%;
  background: currentColor;
  opacity: 0.4;
  flex-shrink: 0;
  position: absolute;
  left: 2rem;
}
.sg-subitem { position: relative; }

/* ── Divider ────────────────────────────────────────────────── */
.sg-divider {
  height: 1px;
  background: var(--divider);
  margin: 0.5rem 0.6rem;
}

/* ── Footer (dark mode + salir) ─────────────────────────────── */
.sg-footer {
  padding: 0.85rem;
  border-top: 1px solid var(--divider);
  flex-shrink: 0;
}

/* ── Theme toggle ───────────────────────────────────────────── */
.sg-theme-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.6rem;
  border-radius: 10px;
  background: var(--nav-hover-bg);
  border: 1px solid var(--divider);
  margin-bottom: 0.6rem;
}
.sg-theme-icons {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex: 1;
  font-size: 0.78rem;
  color: var(--nav-text);
  font-weight: 500;
}
.sg-theme-icons i { font-size: 0.8rem; color: var(--nav-icon); }

/* ── Overlay mobile ─────────────────────────────────────────── */
.sg-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  z-index: 1039;
  backdrop-filter: blur(2px);
}
.sg-overlay.show { display: block; }

/* ── Hamburger button ───────────────────────────────────────── */
.sg-hamburger {
  display: none;
  position: fixed;
  top: 1rem; left: 1rem;
  z-index: 1041;
  width: 44px; height: 44px;
  border-radius: 12px;
  background: var(--nav-active-bg);
  border: none;
  color: #fff;
  font-size: 1.1rem;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  align-items: center;
  justify-content: center;
}

/* ── Main content push ──────────────────────────────────────── */
.main-content-push {
  margin-left: var(--sidebar-w);
  transition: margin var(--transition);
}

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 991px) {
  .sg-sidebar { transform: translateX(-100%); }
  .sg-sidebar.mobile-open { transform: translateX(0); box-shadow: 8px 0 40px rgba(0,0,0,0.15); }
  .main-content-push { margin-left: 0 !important; }
  .sg-hamburger { display: flex; }
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
</div>

<!-- Loader global (igual que antes) -->
<div id="page-loader" class="loader-container">
  <div class="loader-circle"></div>
  <div class="mensaje-carga mensaje1">Estamos procesando la solicitud, por favor espera...</div>
  <div class="mensaje-carga mensaje2">Esto está tardando un poco... por favor sé paciente...</div>
  <div class="mensaje-carga mensaje3">Seguimos trabajando en la solicitud... no recargues la página...</div>
</div>

<!-- div fantasma: cierra el </div> heredado de menu-footer.php sin afectar el layout -->
<div style="display:none">

<script>
// ── Aplicar margin-left al body para que el contenido no quede bajo el sidebar ──
(function applyBodyMargin() {
  const style = document.createElement('style');
  style.textContent = `
    @media (min-width: 992px) {
      body > .container,
      body > .container-fluid,
      body > .container-lg,
      body > .container-xl,
      .portada,
      .main-content-push {
        margin-left: var(--sidebar-w, 272px) !important;
      }
    }
  `;
  document.head.appendChild(style);
})();

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
		