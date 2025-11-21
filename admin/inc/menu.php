<!-- ===== LOADER GLOBAL ===== -->
<div id="page-loader" class="loader-container">
    <div class="loader-circle"></div>

    <div id="slow-loader-message"
         style="margin-top:15px; font-size:16px; color:#fff; text-align:center; display:none;">
        Estamos procesando la solicitud, por favor espera...
    </div>
</div>






<div class="menu">
        <div class="logo-container">
            <img src="<?php echo $url;?>/<?php echo SITE_LOGO;?>" alt="Logo">
			 <br><br>
	
			
			<?php
// Validar si existe foto
$foto = (!empty($foto_perfil) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $foto_perfil))
        ? $foto_perfil
        : 'assets/img/default-user.png';
?>
			
<center>
<img 
    src="<?php echo URLBASE . '/' . $foto; ?>" 
    class="foto-perfil-rounded"
    alt="Foto de usuario">
</center>

			
			
   	<?php echo htmlspecialchars($nombre . " " . $apellido); ?>
    <p><strong><?php echo htmlspecialchars($rolUser); ?></strong></p>
			
<!-- ====== SWITCH MODO OSCURO ====== -->
<div id="themeToggleContainer">
  <div class="theme-label">
    <i class="fas fa-adjust"></i>
    <span>Tema de la aplicación</span>
  </div>
  <div class="theme-options">
    <div class="option light">
      <i class="fas fa-sun"></i>
      <span>Claro</span>
    </div>
    <div class="form-check form-switch m-0">
      <input class="form-check-input" type="checkbox" role="switch" id="themeToggle">
    </div>
    <div class="option dark">
      <i class="fas fa-moon"></i>
      <span>Oscuro</span>
    </div>
  </div>
</div>


			
    <!-- Resto del contenido de tu dashboard -->  
        </div>      
	<a href="<?php echo $url;?>/admin/" onclick="closeSubmenus()"><i class="fas fa-home"></i> Inicio</a>
	
	<?php
// Verificar si el usuario tiene al menos uno de los permisos requeridos
if (
    isset($_SESSION["user_permissions"]) && 
    (in_array('Ver Clientes', $_SESSION["user_permissions"]) || in_array('Ver Clientes Pre-inscritos', $_SESSION["user_permissions"])|| in_array('Ver Asistencias', $_SESSION["user_permissions"]))
): 
?>
	<a href="#" class="has-submenu" onclick="toggleSubmenu(event)">
        <i class="far fa-address-book"></i>  Clientes <i class="fas fa-chevron-down"></i>
    </a>
    <div class="submenu">
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes', $_SESSION["user_permissions"])): ?>  
            <a href="<?php echo $url; ?>/admin/clients/" onclick="closeSubmenus()">- Todos
            </a> 
		<a href="<?php echo $url; ?>/admin/clients/client_search.php" onclick="closeSubmenus()">- Búsqueda Avanzada
            </a>  
        <?php endif; ?>
		
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Clientes Pre-inscritos', $_SESSION["user_permissions"])): ?>
            <a href="<?php echo $url; ?>/admin/clients/pre-registered.php" onclick="closeSubmenus()">- Pre Inscritos
            </a>
		<?php endif; ?>
		
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
            <a href="<?php echo $url; ?>/admin/clients/asistencias.php" onclick="closeSubmenus()">- Asistencias
            </a>
		<?php endif; ?>
		
    </div>	
	<?php endif; ?>
	
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Mensajes Pendientes', $_SESSION["user_permissions"])): ?>
	 <a href="<?php echo $url;?>/admin/ws_outbox/" onclick="closeSubmenus()"><i class="fa fa-clock-o"></i> Mensajes Pendientes</a>
	<?php endif; ?>
	
	
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Manejar Valoraciones', $_SESSION["user_permissions"])): ?>
	<a href="<?php echo $url;?>/admin/valoraciones" onclick="closeSubmenus()"><i class="fas fa-heartbeat"></i> Valoraciones</a>
	<?php endif; ?>
	
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Planes', $_SESSION["user_permissions"])): ?>
	<a href="<?php echo $url;?>/admin/plans" onclick="closeSubmenus()"><i class="fas fa-fire-alt"></i> Planes</a>
	<?php endif; ?>
	
	
	<?php
// Verificar si el usuario tiene al menos uno de los permisos requeridos
if (
    isset($_SESSION["user_permissions"]) && 
    (in_array('Ver Ejercicios', $_SESSION["user_permissions"]) || in_array('Ver Rutinas', $_SESSION["user_permissions"]))
): 
?>
	<a href="#" class="has-submenu" onclick="toggleSubmenu(event)">
        <i class="material-icons" style="font-size:16px">fitness_center</i> Rutinas <i class="fas fa-chevron-down"></i>
    </a>
    <div class="submenu">
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Rutinas', $_SESSION["user_permissions"])): ?>  
            <a href="<?php echo $url; ?>/admin/routines/" onclick="closeSubmenus()">- Gestionar
            </a>  
        <?php endif; ?>
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Ejercicios', $_SESSION["user_permissions"])): ?>
            <a href="<?php echo $url; ?>/admin/routines/ejercicios.php" onclick="closeSubmenus()">- Ejercicios
            </a>
		<?php endif; ?>
		
    </div>	
	<?php endif; ?>
	
	
	
	
	
	
	
	<?php
// Verificar si el usuario tiene al menos uno de los permisos requeridos
if (
    isset($_SESSION["user_permissions"]) && 
    (in_array('Usar Cajas', $_SESSION["user_permissions"]) || in_array('Ver Todas las Cajas', $_SESSION["user_permissions"]))
): 
?>
	<a href="#" class="has-submenu" onclick="toggleSubmenu(event)">
        <i class="fas fa-cash-register"></i> Caja <i class="fas fa-chevron-down"></i>
    </a>
    <div class="submenu">
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Usar Cajas', $_SESSION["user_permissions"])): ?>
            <a href="<?php echo $url; ?>/admin/caja/" onclick="closeSubmenus()">
                - Mi Caja Abierta
            </a>
            <a href="<?php echo $url; ?>/admin/caja/cajas_list.php" onclick="closeSubmenus()">
                - Mis Cajas Cerradas
            </a>
        <?php endif; ?>
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Todas las Cajas', $_SESSION["user_permissions"])): ?>  
            <a href="<?php echo $url; ?>/admin/caja/cajas_list_all.php" onclick="closeSubmenus()">- Todas las Cajas
            </a>  
        <?php endif; ?>
    </div>
<?php endif; ?>
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Editar Productos', $_SESSION["user_permissions"])): ?>
	<a href="#" class="has-submenu" onclick="toggleSubmenu(event)"><i class='fas fa-shopping-bag'></i> Productos <i class="fas fa-chevron-down"></i></a>
        <div class="submenu">
	<a href="<?php echo $url;?>/admin/products" onclick="closeSubmenus()">- Productos en Stock</a>
			
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Editar Bolsillos', $_SESSION["user_permissions"])): ?>
	<a href="<?php echo $url;?>/admin/products/pocket.php" onclick="closeSubmenus()">- Bolsillos</a>
	<?php endif; ?>
	</div>
	<?php endif; ?>	
	<?php
// Verificar si el usuario tiene al menos uno de los permisos requeridos
if (
    isset($_SESSION["user_permissions"]) && 
    (in_array('Ver Nominas', $_SESSION["user_permissions"]) || in_array('Pagar Nominas', $_SESSION["user_permissions"]) || in_array('Ver Empleados', $_SESSION["user_permissions"]))
): 
?>
	<a href="#" class="has-submenu" onclick="toggleSubmenu(event)">
        <i class="far fa-id-card"></i> Nomina <i class="fas fa-chevron-down"></i>
    </a>
    <div class="submenu">
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Empleados', $_SESSION["user_permissions"])): ?>  
            <a href="<?php echo $url; ?>/admin/payroll/employees.php" onclick="closeSubmenus()">- Empleados
            </a>  
        <?php endif; ?>
        <?php if (isset($_SESSION["user_permissions"]) && in_array('Pagar Nominas', $_SESSION["user_permissions"])): ?>
            <a href="<?php echo $url; ?>/admin/payroll/" onclick="closeSubmenus()">- Pagar Nomina
            </a>
		<?php endif; ?>
		<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Nominas', $_SESSION["user_permissions"])): ?>
		
            <a href="<?php echo $url; ?>/admin/payroll/all_payroll.php" onclick="closeSubmenus()">- Nominas Pagadas
            </a>
        <?php endif; ?>

    </div>	
	<?php endif; ?>
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Manejar Contabilidad', $_SESSION["user_permissions"])): ?>
	<a href="#" class="has-submenu" onclick="toggleSubmenu(event)"><i class='fas fa-coins'></i> Contabilidad <i class="fas fa-chevron-down"></i></a>
        <div class="submenu">			
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Reportes Contabilidad', $_SESSION["user_permissions"])): ?>		
            <a href="<?php echo $url; ?>/admin/contabilidad/reporte.php" onclick="closeSubmenus()">- Reportes
            </a>
        <?php endif; ?>
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Egresos', $_SESSION["user_permissions"])): ?>		
            <a href="<?php echo $url; ?>/admin/contabilidad/expenses.php" onclick="closeSubmenus()">- Egresos
            </a>
        <?php endif; ?>			
			<a href="<?php echo $url;?>/admin/contabilidad/invoices.php" onclick="closeSubmenus()">- Facturas</a>
			
			<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Pagos Pasarela', $_SESSION["user_permissions"])): ?>		
            <a href="<?php echo $url; ?>/admin/contabilidad/gateway_registration.php" onclick="closeSubmenus()">- Registros Pasarela
            </a>
        <?php endif; ?>
	</div>	
	<?php endif; ?>
	 <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver y Editar Usuarios', $_SESSION["user_permissions"])): ?>
	<a href="#" class="has-submenu" onclick="toggleSubmenu(event)"><i class='fas fa-user-cog'></i> Usuarios <i class="fas fa-chevron-down"></i></a>
        <div class="submenu">
	 <a href="<?php echo $url;?>/admin/users/" onclick="closeSubmenus()">- Todos</a>			
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Gestionar Roles', $_SESSION["user_permissions"])): ?>			
	<a href="<?php echo $url;?>/admin/users/roles.php" onclick="closeSubmenus()">- Roles</a>
	<?php endif; ?>
	</div>
	 <?php endif; ?>
	
	<a href="<?php echo $url;?>/admin/profile/" onclick="closeSubmenus()"><i class="fas fa-user"></i> Perfil</a>
	
	
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Estadisticas', $_SESSION["user_permissions"])): ?>
	 <a href="<?php echo $url;?>/admin/statistics/" onclick="closeSubmenus()"><i class="fa fa-bar-chart"></i> Estadisticas</a>
	<?php endif; ?>
	
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Configurar Sistema', $_SESSION["user_permissions"])): ?>
	<a href="<?php echo $url;?>/admin/config/" onclick="closeSubmenus()"><i class="fas fa-cog"></i> Configuraciones</a>
	<?php endif; ?> 
	
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Logs del Sistema', $_SESSION["user_permissions"])): ?>
	<a href="<?php echo $url;?>/admin/system/logs.php" onclick="closeSubmenus()"><i class="fa fa-history" aria-hidden="true"></i> Logs del Sistema</a>
	<?php endif; ?> 
	
    <a href="<?php echo $url;?>/admin/support/" onclick="closeSubmenus()"><i class="fas fa-headset"></i> Soporte </a>
    <a href="https://app.360messenger.com/index.php?rp=/login" target="_blank" onclick="closeSubmenus()"><i class="fa fa-whatsapp"></i> API </a>
	<!-- Si la caja está cerrada -->
<?php if(!$caja_id): ?>
  <a href="<?php echo $url; ?>/admin/login/logout.php" onclick="closeSubmenus()">
    <i class="fas fa-power-off"></i> Salir
  </a>
<?php else: ?>
  <!-- Si la caja está abierta, abre el modal usando data-bs-toggle en lugar de JavaScript manual -->
  <a href="#" data-bs-toggle="modal" data-bs-target="#modalCajaAbierta" onclick="closeSubmenus()">
    <i class="fas fa-power-off"></i> Salir
  </a>
<?php endif; ?>
	<!-- Modal si la caja está abierta -->
</div>
    <div class="container">		
	<div class="modal fade" id="modalCajaAbierta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Caja Abierta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Tienes una caja abierta. Debes cerrarla antes de salir.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <!-- Botón que dirige a /admin/caja (o donde cierras la caja) -->
        <a href="<?php echo $url; ?>/admin/caja" class="btn btn-danger">
          Ir a Cerrar Caja
        </a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/caja_bar.php';?>
		