<?php 
require_once __DIR__ . '/../login/session.php'; 
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');

?>

<?php
// Consulta para obtener los roles activos
try {
    $stmtRoles = db()->prepare("SELECT * FROM roles WHERE borrado = 0");
    $stmtRoles->execute();
    $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $roles = [];
}
    
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Usuarios</title>
  <?php include('../inc/header.php'); ?>
  
 
  
  <style>
    /* Estilos de la tabla según lo solicitado */
    #formularios tbody tr,
    #formularios tbody tr td {
      cursor: pointer !important;
      transition: background-color 0.1s ease;
    }
    #formularios thead th {
      background-color: #214A82;
      color: white;
    }
    #formularios tbody tr:hover {
      background-color: #4972AA !important;
    }
    #formularios tbody tr:hover td {
      color: white !important;
    }
    /* La columna de acción no dispara redirección */
    .no-click {
      cursor: default !important;
    }
  </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Listado de Usuarios</h1>
	  <button type="button" class="btn btn-primary float-end" data-toggle="modal" data-target="#newUserModal">
      <i class="fas fa-user-plus"></i> Nuevo Usuario
    </button>
  </div>
</div>
<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
  
  
  <!-- Botón Nuevo Usuario -->
  <div class="mb-3">
    
  </div>
  
  <!-- Alertas de Bootstrap para mensajes de sesión -->
  <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  <?php endif; ?>
  <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  <?php endif; ?>
  
	
	
	
  <!-- Tabla de usuarios -->
  <table id="formularios" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>Nombre de Usuario</th>
        <th>Nombre</th>
        <th>Apellido</th>
		 <th>Teléfono</th>
        <th>Correo</th>
        <th>Rol</th>
        <th>Estado</th>
		  

        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Datos de conexión
      require_once __DIR__ . '/../../inc/config.php';
try {
         
          // Seleccionar usuarios que no estén borrados (borrado = 0)
          //$sql = "SELECT * FROM usuarios WHERE borrado = 0";
	// Consulta para obtener los usuarios con el nombre del rol
    $sql = "SELECT u.*, r.name AS rol, u.estado 
            FROM usuarios u 
            LEFT JOIN roles r ON u.rol_id = r.id 
            WHERE u.borrado = 0";
    $stmt = db()->query($sql);
	
          $stmt = db()->query($sql);
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<tr class="user-row" 
					  data-id="' . $row['id'] . '"
					  data-username="' . htmlspecialchars($row['username']) . '"
					  data-nombre="' . htmlspecialchars($row['nombre']) . '"
					  data-apellido="' . htmlspecialchars($row['apellido']) . '"
					  data-correo="' . htmlspecialchars($row['correo']) . '"
					  data-rol="' . htmlspecialchars($row['rol_id']) . '"
					  data-estado="' . htmlspecialchars($row['estado']) . '"
					  data-dialcode="' . htmlspecialchars($row['dialcode']) . '"
					  data-telefono="' . htmlspecialchars($row['telefono']) . '"
					  data-recibe_alertas_stock="' . (int)$row['recibe_alertas_stock'] . '">';

              echo '<td>' . htmlspecialchars($row['username']) . '</td>';
              echo '<td>' . htmlspecialchars($row['nombre']) . '</td>';
              echo '<td>' . htmlspecialchars($row['apellido']) . '</td>';
			  echo '<td>' . htmlspecialchars($row['dialcode'] . ' ' . $row['telefono']) . '</td>';

              echo '<td>' . htmlspecialchars($row['correo']) . '</td>';
               //echo '<td>' . htmlspecialchars($row['rol'] ?? 'Sin Rol') . '</td>';
			  echo '<td>' . htmlspecialchars($row['rol']) . '</td>';
              echo '<td>';
              if ($row['estado'] == 0) {
                  echo '<span class="badge badge-success">Activo</span>';
              } else {
                  echo '<span class="badge badge-danger">Inactivo</span>';
              }
              echo '</td>';
			  
              // Botón de borrar
              echo '<td class="no-click text-center"><button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $row['id'] . '"><i class="fas fa-trash"></i></button></td>';
              echo '</tr>';
          }
      } catch (PDOException $e) {
          echo '<tr><td colspan="7">Error: ' . $e->getMessage() . '</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>

<!-- Modal para Nuevo Usuario -->
<div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-labelledby="newUserModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" action="new.php" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="newUserModalLabel">Nuevo Usuario</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Campos de registro -->
          <div class="form-group">
            <label for="newNombre">Nombre</label>
            <input type="text" class="form-control" id="newNombre" name="nombre" required>
            <div class="invalid-feedback">Ingrese su nombre.</div>
          </div>
          <div class="form-group">
            <label for="newApellido">Apellido</label>
            <input type="text" class="form-control" id="newApellido" name="apellido" required>
            <div class="invalid-feedback">Ingrese su apellido.</div>
          </div>
			
			<input type="hidden" id="newDialcode" name="dialcode" value="+57">
<input type="tel" class="form-control" id="newTelefono" name="telefono">
<div class="form-check form-switch mt-2">
  <input class="form-check-input" type="checkbox" id="newRecibeAlertas" name="recibe_alertas_stock">
  <label class="form-check-label" for="newRecibeAlertas">Recibe notificaciones de pocos productos en stock</label>
</div>

			
          <div class="form-group">
            <label for="newCorreo">Correo</label>
            <input type="email" class="form-control" id="newCorreo" name="correo" required>
            <div class="invalid-feedback">Ingrese un correo válido.</div>
          </div>
          <div class="form-group">
            <label for="newUsername">Nombre de Usuario</label>
            <input type="text" class="form-control" id="newUsername" name="username" required oninput="this.value = this.value.toLowerCase()">
            <div class="invalid-feedback">Ingrese un nombre de usuario.</div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="newPassword">Contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control" id="newPassword" name="password" required>
                <div class="input-group-append">
                  <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#newPassword">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="invalid-feedback">Ingrese una contraseña.</div>
              </div>
            </div>
            <div class="form-group col-md-6">
              <label for="newConfirmPassword">Confirmar Contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control" id="newConfirmPassword" name="confirm_password" required>
                <div class="input-group-append">
                  <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#newConfirmPassword">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="invalid-feedback">Confirme la contraseña.</div>
              </div>
              <small id="newPasswordHelp" class="form-text text-danger" style="display: none;">Las contraseñas no coinciden.</small>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="newRol">Rol</label>
			
				<select class="form-control" id="newRol" name="rol" required>
    <option value="">Seleccione un rol</option>
    <?php foreach ($roles as $role): ?>
        <option value="<?php echo htmlspecialchars($role['id']); ?>">
            <?php echo htmlspecialchars($role['name']); ?>
        </option>
    <?php endforeach; ?>
</select>
				
              <!--<select class="form-control" id="newRol" name="rol" required>
                <option value="">Seleccione un rol</option>
                <option value="admin">Administrador</option>
                <option value="user">Usuario</option>
              </select>-->
				
				
              <div class="invalid-feedback">Seleccione un rol.</div>
            </div>
            <div class="form-group col-md-6">
              <label for="newEstado">Estado</label>
              <select class="form-control" id="newEstado" name="estado" required>
                <option value="">Seleccione un estado</option>
                <option value="0">Activo</option>
                <option value="1">Inactivo</option>
              </select>
              <div class="invalid-feedback">Seleccione un estado.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="newRegisterBtn" disabled><i class="fa fa-save"></i> Registrar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para Editar Usuario -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
 <div class="modal-dialog" role="document">
   <div class="modal-content">
     <form method="post" action="update_user.php" class="needs-validation" novalidate id="editForm">
       <div class="modal-header">
         <h5 class="modal-title" id="editModalLabel">Editar Usuario</h5>
         <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
           <span aria-hidden="true">&times;</span>
         </button>
       </div>
       <div class="modal-body">
         <!-- Campo oculto para el ID -->
         <input type="hidden" id="editId" name="id">
         <div class="form-group">
           <label for="editUsername">Nombre de Usuario</label>
           <input type="text" class="form-control" id="editUsername" name="username" readonly>
           <small class="form-text text-muted">El nombre de usuario no se puede modificar.</small>
         </div>
         <div class="form-group">
           <label for="editNombre">Nombre</label>
           <input type="text" class="form-control" id="editNombre" name="nombre" required>
           <div class="invalid-feedback">Ingrese el nombre.</div>
         </div>
         <div class="form-group">
           <label for="editApellido">Apellido</label>
           <input type="text" class="form-control" id="editApellido" name="apellido" required>
           <div class="invalid-feedback">Ingrese el apellido.</div>
         </div>
		   
		   <input type="hidden" id="editDialcode" name="dialcode" value="+57">
<input type="tel" class="form-control" id="editTelefono" name="telefono">
<div class="form-check form-switch mt-2">
  <input class="form-check-input" type="checkbox" id="editRecibeAlertas" name="recibe_alertas_stock">
  <label class="form-check-label" for="editRecibeAlertas">Recibe notificaciones de pocos productos en stock</label>
</div>

				
		   
         <div class="form-group">
           <label for="editCorreo">Correo</label>
           <input type="email" class="form-control" id="editCorreo" name="correo" required>
           <div class="invalid-feedback">Ingrese un correo válido.</div>
         </div>
         <div class="form-group">
           <label for="editRol">Rol</label>
           
			<select class="form-control" id="editRol" name="rol" required>
    <option value="">Seleccione un rol</option>
    <?php foreach ($roles as $role): ?>
        <option value="<?php echo htmlspecialchars($role['id']); ?>"
            <?php if (isset($row['rol_id']) && (int)$row['rol_id'] === (int)$role['id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($role['name']); ?>
        </option>
    <?php endforeach; ?>
</select>
<div class="invalid-feedback">Seleccione un rol.</div>
			 
			 <!--<select class="form-control" id="editRol" name="rol" required>
             <option value="">Seleccione un rol</option>
             <option value="admin">Administrador</option>
             <option value="user">Usuario</option>
           </select>-->
			 
			 
           <div class="invalid-feedback">Seleccione un rol.</div>
         </div>
         <div class="form-group">
           <label for="editEstado">Estado</label>
           <select class="form-control" id="editEstado" name="estado" required>
             <option value="">Seleccione un estado</option>
             <option value="0">Activo</option>
             <option value="1">Inactivo</option>
           </select>
           <div class="invalid-feedback">Seleccione un estado.</div>
         </div>
         <!-- Campos de cambio de contraseña -->
         <div class="form-group">
           <label for="editPassword">Nueva Contraseña (dejar en blanco para no cambiar)</label>
           <div class="input-group">
             <input type="password" class="form-control" id="editPassword" name="password">
             <div class="input-group-append">
               <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#editPassword">
                 <i class="fas fa-eye"></i>
               </button>
             </div>
           </div>
         </div>
         <div class="form-group">
           <label for="editConfirmPassword">Confirmar Nueva Contraseña</label>
           <div class="input-group">
             <input type="password" class="form-control" id="editConfirmPassword" name="confirm_password">
             <div class="input-group-append">
               <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#editConfirmPassword">
                 <i class="fas fa-eye"></i>
               </button>
             </div>
           </div>
           <small id="editPasswordHelp" class="form-text text-danger" style="display: none;">Las contraseñas no coinciden.</small>
         </div>
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
         <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar Cambios</button>
       </div>
     </form>
   </div>
 </div>
</div>

<?php include('../inc/menu-footer.php'); ?>
  
<script>
$(document).ready(function() {
  // ==========================================================
  // DATATABLE
  // ==========================================================
  var table = $('#formularios').DataTable({
    "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
    "order": [[0, "asc"]],
    "pageLength": 50
  });

  // ==========================================================
  // INTL-TEL-INPUT NUEVO USUARIO
  // ==========================================================
  let itiNew;
  const newInput = document.querySelector("#newTelefono");
  if (newInput && window.intlTelInput) {
    itiNew = window.intlTelInput(newInput, {
      initialCountry: "co",
      preferredCountries: ["co", "us", "es"],
      separateDialCode: true,
      nationalMode: false,
    });

    newInput.addEventListener('countrychange', function() {
      $('#newDialcode').val('+' + itiNew.getSelectedCountryData().dialCode);
    });
    $('#newDialcode').val('+' + itiNew.getSelectedCountryData().dialCode);
  }

  // ==========================================================
  // INTL-TEL-INPUT EDITAR USUARIO
  // ==========================================================
  let itiEdit; // global para editar

// ==========================================================
// ABRIR MODAL DE EDICIÓN AL HACER CLICK EN UNA FILA
// ==========================================================
$('#formularios tbody').on('click', 'tr.user-row', function(e) {
  if ($(e.target).closest('.delete-btn').length > 0) return;

  var id = $(this).data('id');
  var username = $(this).data('username');
  var nombre = $(this).data('nombre');
  var apellido = $(this).data('apellido');
  var correo = $(this).data('correo');
  var rol = $(this).data('rol');
  var estado = $(this).data('estado');
  var dialcode = $(this).data('dialcode');
  var telefono = $(this).data('telefono');
  var recibe_alertas_stock = $(this).data('recibe_alertas_stock');

  // Rellenar campos base
  $('#editId').val(id);
  $('#editUsername').val(username);
  $('#editNombre').val(nombre);
  $('#editApellido').val(apellido);
  $('#editCorreo').val(correo);
  $('#editRol').val(rol);
  $('#editEstado').val(estado);
  $('#editRecibeAlertas').prop('checked', recibe_alertas_stock == 1);

  // Limpiar contraseñas
  $('#editPassword, #editConfirmPassword').val('');
  $('#editPasswordHelp').hide();

  // Modal visible primero
  $('#editModal').modal('show');

  // ==========================================================
  // CONFIGURAR INTL-TEL-INPUT EN EDICIÓN
  // ==========================================================
  const editInput = document.querySelector("#editTelefono");
  if (!editInput || !window.intlTelInput) return;

  // Limpiar contenido previo del input
  editInput.value = "";

  // Destruir instancia previa si existe
  if (window.itiEdit && typeof window.itiEdit.destroy === 'function') {
    window.itiEdit.destroy();
  }

  // Crear nueva instancia
  window.itiEdit = window.intlTelInput(editInput, {
    initialCountry: "auto",
    preferredCountries: ["co", "us", "es"],
    separateDialCode: true,
    nationalMode: false,
    geoIpLookup: function(callback) {
      callback('co');
    }
  });

  // Cuando cambie el país, actualizar el hidden
  editInput.addEventListener('countrychange', function() {
    const data = window.itiEdit.getSelectedCountryData();
    $('#editDialcode').val('+' + data.dialCode);
  });

  // Esperar a que el plugin termine de cargar para setear país y número
  setTimeout(function() {
    if (dialcode && typeof window.itiEdit.getCountryData === 'function') {
      const country = window.itiEdit.getCountryData().find(c => "+" + c.dialCode === dialcode);
      if (country) {
        window.itiEdit.setCountry(country.iso2);
      } else {
        window.itiEdit.setCountry("co");
      }
    } else {
      window.itiEdit.setCountry("co");
    }

    // Establecer el número completo
    if (telefono) {
      window.itiEdit.setNumber(dialcode + telefono);
    }

    // Actualizar hidden
    const data = window.itiEdit.getSelectedCountryData();
    $('#editDialcode').val('+' + data.dialCode);
  }, 250);
});

  // ==========================================================
  // CERRAR MODAL CORRECTAMENTE
  // ==========================================================
  $('#editModal .close, #editModal .btn-secondary').on('click', function(e) {
    e.stopPropagation();
    $('#editModal').modal('hide');
  });

  // ==========================================================
  // TOGGLE DE CONTRASEÑA
  // ==========================================================
  $('.toggle-password').on('click', function() {
    var targetSelector = $(this).data('target');
    var targetInput = $(targetSelector);
    if (targetInput.attr('type') === 'password') {
      targetInput.attr('type', 'text');
      $(this).html('<i class="fas fa-eye-slash"></i>');
    } else {
      targetInput.attr('type', 'password');
      $(this).html('<i class="fas fa-eye"></i>');
    }
  });

  // ==========================================================
  // VALIDAR CONTRASEÑAS NUEVO USUARIO
  // ==========================================================
  $('#newConfirmPassword').on('input', function() {
    var pass = $('#newPassword').val();
    if (pass !== $(this).val()) {
      $('#newPasswordHelp').show();
    } else {
      $('#newPasswordHelp').hide();
    }
    updateNewRegisterButton();
  });

  function updateNewRegisterButton() {
    var isValid = true;
    var requiredFields = [
      'newNombre', 'newApellido', 'newCorreo', 'newUsername',
      'newPassword', 'newRol', 'newEstado', 'newTelefono'
    ];
    requiredFields.forEach(function(id) {
      var elem = document.getElementById(id);
      if (!elem || !elem.value.trim()) {
        isValid = false;
      }
    });
    if ($('#newPassword').val() !== $('#newConfirmPassword').val()) {
      isValid = false;
    }
    document.getElementById('newRegisterBtn').disabled = !isValid;
  }
  $('#newUserModal input, #newUserModal select').on('input change', updateNewRegisterButton);

  // ==========================================================
  // CONFIRMACIÓN DE ELIMINACIÓN
  // ==========================================================
  $('#formularios').on('click', '.delete-btn', function(e) {
    e.stopPropagation();
    var id = $(this).data('id');
    Swal.fire({
      title: '¿Está seguro?',
      text: "Esta acción borrará el usuario.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, borrar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location = "dele_user.php?id=" + id;
      }
    });
  });

  // ==========================================================
  // VALIDAR CONTRASEÑAS EN EDICIÓN
  // ==========================================================
  $('#editModal form').on('submit', function(event) {
    var pass = $('#editPassword').val();
    var conf = $('#editConfirmPassword').val();
    if (pass !== '' && pass !== conf) {
      event.preventDefault();
      $('#editPasswordHelp').show();
      return false;
    }
  });
});


</script>	
</body>
</html>