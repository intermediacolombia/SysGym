<?php
require_once __DIR__ . '/../login/session.php';
// Verificar que el usuario esté logueado
if (!isset($_SESSION['user'])) {
    header("Location: ../login/");
    exit();
}

$user = $_SESSION['user']; // Contiene, por ejemplo, id, nombre, apellido, correo, etc.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi Perfil</title>
  <?php include('../inc/header.php'); ?>
  
  <style>
    body { background-color: #f8f9fa; }
    .profile-card {
      max-width: 600px;
      margin: 30px auto;
      padding: 20px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .profile-card h2 { margin-bottom: 20px; }
    .profile-card .row + .row { margin-top: 10px; }
  </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1 class="mb-4">Mi Perfil</h1>
  </div>
</div>
<?php include('../inc/menu.php'); ?>

<div class="profile-card">

  <div class="text-center mb-4">
    <?php
    $foto = (!empty($user['foto_perfil']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $user['foto_perfil']))
        ? $user['foto_perfil']
        : 'assets/img/default-user.png';
    ?>

    <center><img src="<?php echo URLBASE . '/' . $foto; ?>" 
         class="foto-perfil-rounded" 
		 id="previewFotoPerfil"></center>


    <button class="btn btn-outline-primary mt-3" data-toggle="modal" data-target="#changePhotoModal">
      <i class="fas fa-camera"></i> Cambiar foto
    </button>
  </div>

  <h3 class="text-center mb-4 font-weight-bold">Información Personal</h3>

  <div class="row info-row">
    <div class="col-sm-4 font-weight-bold">Usuario:</div>
    <div class="col-sm-8"><?php echo htmlspecialchars($user['username']); ?></div>
  </div>

  <div class="row info-row">
    <div class="col-sm-4 font-weight-bold">Rol:</div>
    <div class="col-sm-8"><?php echo htmlspecialchars($rolUser); ?></div>
  </div>

  <div class="row info-row">
    <div class="col-sm-4 font-weight-bold">Nombre:</div>
    <div class="col-sm-8"><?php echo htmlspecialchars($user['nombre']); ?></div>
  </div>

  <div class="row info-row">
    <div class="col-sm-4 font-weight-bold">Apellido:</div>
    <div class="col-sm-8"><?php echo htmlspecialchars($user['apellido']); ?></div>
  </div>

  <div class="row info-row">
    <div class="col-sm-4 font-weight-bold">Correo:</div>
    <div class="col-sm-8"><?php echo htmlspecialchars($user['correo']); ?></div>
  </div>

  <div class="text-center mt-4">
      <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>

      <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>

      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#changePasswordModal">
        <i class='fas fa-key'></i> Cambiar Contraseña
      </button>
  </div>

</div>

<!-- Modal Cambiar Foto -->
<div class="modal fade" id="changePhotoModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" action="change_photo.php" enctype="multipart/form-data">

        <div class="modal-header">
          <h5 class="modal-title">Cambiar Foto de Perfil</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body">
			
          <div class="text-center mb-3">
            <center><img id="previewNuevaFoto" 
                 src="<?php echo URLBASE . '/' . $foto; ?>" 
                 class="foto-perfil-rounded">
				</center>
          </div>

          <div class="form-group">
            <label>Seleccionar nueva foto</label>
            <input type="file" class="form-control" id="nuevaFotoInput" name="foto_perfil" accept="image/*" required>
          </div>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Foto
          </button>
        </div>

      </form>
    </div>
  </div>
</div>


<!-- Modal para cambiar contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="changePasswordForm" method="post" action="change_password.php" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="changePasswordModalLabel">
 Cambiar Contraseña</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Contraseña Actual -->
          <div class="form-group">
            <label for="currentPassword">Contraseña Actual</label>
            <div class="input-group">
              <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#currentPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="invalid-feedback">Ingrese su contraseña actual.</div>
            </div>
          </div>
          <!-- Nueva Contraseña -->
          <div class="form-group">
            <label for="newPassword">Nueva Contraseña</label>
            <div class="input-group">
              <input type="password" class="form-control" id="newPassword" name="newPassword" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#newPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="invalid-feedback">Ingrese su nueva contraseña.</div>
            </div>
          </div>
          <!-- Confirmar Nueva Contraseña -->
          <div class="form-group">
            <label for="confirmNewPassword">Confirmar Nueva Contraseña</label>
            <div class="input-group">
              <input type="password" class="form-control" id="confirmNewPassword" name="confirmNewPassword" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirmNewPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="invalid-feedback">Confirme su nueva contraseña.</div>
            </div>
            <small id="passwordMismatch" class="form-text text-danger" style="display: none;">Las contraseñas no coinciden.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class='fas fa-key'></i> Cambiar Contraseña</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 (opcional, para mostrar alertas de error o éxito) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  // Alternar visibilidad de contraseña en el modal
  $('.toggle-password').on('click', function() {
    var targetSelector = $(this).data('target');
    var targetInput = $(targetSelector);
    if(targetInput.attr('type') === 'password') {
      targetInput.attr('type', 'text');
      $(this).html('<i class="fas fa-eye-slash"></i>');
    } else {
      targetInput.attr('type', 'password');
      $(this).html('<i class="fas fa-eye"></i>');
    }
  });
  
  // Validar en tiempo real que las nuevas contraseñas coincidan
  $('#confirmNewPassword').on('input', function() {
    var newPass = $('#newPassword').val();
    if(newPass !== $(this).val()) {
      $('#passwordMismatch').show();
    } else {
      $('#passwordMismatch').hide();
    }
  });
  
  // Validación de formulario con Bootstrap
  (function () {
    'use strict';
    window.addEventListener('load', function () {
      var forms = document.getElementsByClassName('needs-validation');
      Array.prototype.filter.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
          var newPass = $('#newPassword').val();
          var confPass = $('#confirmNewPassword').val();
          if(newPass !== confPass) {
            event.preventDefault();
            event.stopPropagation();
            $('#passwordMismatch').show();
          }
          form.classList.add('was-validated');
        }, false);
      });
    }, false);
  })();
	
	
	// PREVISUALIZAR NUEVA FOTO
$("#nuevaFotoInput").on("change", function () {
    const file = this.files[0];
    if (file) {
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#previewNuevaFoto").attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
    }
});

	
	
});
</script>
</body>
</html>

