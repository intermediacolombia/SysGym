<?php 
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$permisopage = 'Editar Clientes';
include('../login/restriction.php');
// Verificar que se envíe el id del cliente
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No se proporcionó el id del cliente.";
    header("Location: index.php");
    exit;
}
$id = trim($_GET['id']);

// Obtener los planes disponibles (no borrados, estado activo) y con su frecuencia
try {
    $stmtPlan = db()->query("SELECT * FROM planes WHERE borrado = 0 AND estado = 'activo' ORDER BY nombre ASC");
    $planes = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $planes = [];
}
// Procesar el formulario (POST) para actualizar el cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificacion        = trim($_POST['identificacion']);
    $nombres               = trim($_POST['nombres']);
    $apellidos             = trim($_POST['apellidos']);
    $direccion             = trim($_POST['direccion']);
    $dialCode              = trim($_POST['dialCode']);  // Se guarda sin '+'
    $telefono              = trim($_POST['telefono']);
    $genero                = trim($_POST['genero']);
    $email                 = trim($_POST['email']);
    $fecha_nacimiento      = trim($_POST['fecha_nacimiento']);
	$contacto_emergencia   = trim($_POST['contacto_emergencia']);
	$dialCodeEmergencia 	= trim($_POST['dialCodeEmergencia']);
	$numero_emergencia     = trim($_POST['numero_emergencia']);
	$notificaciones		   = trim($_POST['notificaciones']);
	$rh                    = trim($_POST['rh']);
    $eps                   = trim($_POST['eps']);
    $estado                = trim($_POST['estado']); // Estado del cliente
    $fracturas             = trim($_POST['fracturas'])             ?: 'No';
    $alergias              = trim($_POST['alergias'])              ?: 'No';
    $enfermedades_actuales = trim($_POST['enfermedades_actuales']) ?: 'No';
    $observaciones         = trim($_POST['observaciones'])         ?: 'No';
    $plan                  = trim($_POST['plan']); // id del plan
    $pago_plan             = trim($_POST['pago_plan']); // nuevo campo: fecha de pago
    $vencimiento_plan      = trim($_POST['vencimiento_plan']);
	
	   // === MANEJO DE IMAGEN DE PERFIL ===
// 1. Consultar la imagen actual
$stmtImg = db()->prepare("SELECT imagen_perfil FROM clientes WHERE id = :id");
$stmtImg->execute([':id' => $id]);
$imagenPerfil = $stmtImg->fetchColumn();

// 2. Si se sube una imagen nueva (archivo o captura desde cámara)
if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] === UPLOAD_ERR_OK) {
    $nombreTmp = $_FILES['imagen_perfil']['tmp_name'];
    $nombreArchivo = 'cliente_' . $id . '_' . time() . '.jpg';
    $rutaDirectorio = __DIR__ . '/../../uploads/clientes/';
    $rutaDestino = $rutaDirectorio . $nombreArchivo;

    // Crear carpeta si no existe
    if (!is_dir($rutaDirectorio)) {
        mkdir($rutaDirectorio, 0775, true);
    }

    // Eliminar la imagen anterior si existe
    if (!empty($imagenPerfil) && file_exists($rutaDirectorio . $imagenPerfil)) {
        unlink($rutaDirectorio . $imagenPerfil);
    }

    // Mover la nueva imagen
    if (move_uploaded_file($nombreTmp, $rutaDestino)) {
        $imagenPerfil = $nombreArchivo;
    } else {
        $_SESSION['error'] = "No se pudo guardar la imagen del cliente.";
    }
}



	
	
    try {
        $stmt = db()->prepare("UPDATE clientes SET 
            identificacion = :identificacion,
            nombres = :nombres,
            apellidos = :apellidos,
			imagen_perfil = :imagen_perfil,
            direccion = :direccion,
            dialCode = :dialCode,
            telefono = :telefono,			
            genero = :genero,
            email = :email,
            fecha_nacimiento = :fecha_nacimiento,			
			contacto_emergencia = :contacto_emergencia,
			dialCodeEmergencia = :dialCodeEmergencia,
			numero_emergencia = :numero_emergencia,
			notificaciones = :notificaciones,
			rh = :rh,
            eps = :eps,
            estado = :estado,
            fracturas = :fracturas,
            alergias = :alergias,
            enfermedades_actuales = :enfermedades_actuales,
            observaciones = :observaciones,
            plan = :plan,
            pago_plan = :pago_plan,
            vencimiento_plan = :vencimiento_plan,
            updated_at = NOW()
            WHERE id = :id");
        $stmt->execute([
            ':identificacion'        => $identificacion,
            ':nombres'               => $nombres,
            ':apellidos'             => $apellidos,
			':imagen_perfil' => $imagenPerfil,
            ':direccion'             => $direccion,
            ':dialCode'              => $dialCode,
            ':telefono'              => $telefono,
            ':genero'                => $genero,
            ':email'                 => $email,
            ':fecha_nacimiento'      => $fecha_nacimiento,
			':contacto_emergencia'   => $contacto_emergencia,
			':dialCodeEmergencia' 	 => $dialCodeEmergencia,
			':numero_emergencia'	 => $numero_emergencia,
			':notificaciones'		 => $notificaciones,
			':rh'					 => $rh,
            ':eps'                   => $eps,
            ':estado'                => $estado,
            ':fracturas'             => $fracturas,
            ':alergias'              => $alergias,
            ':enfermedades_actuales' => $enfermedades_actuales,
            ':observaciones'         => $observaciones,
            ':plan'                  => $plan,
            ':pago_plan'             => $pago_plan,
            ':vencimiento_plan'      => $vencimiento_plan,
            ':id'                    => $id
        ]);
		
		
		
		
			// LOGS
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
			'cliente_id' => $id,
			'accion' => 'Cliente editado',
			'identificacion'        => $identificacion,
           'nombres'               => $nombres,
            'apellidos'             => $apellidos,
            'direccion'             => $direccion,
            'dialCode'              => $dialCode,
            'telefono'              => $telefono,
            'genero'                => $genero,
            'email'                 => $email,
            'fecha_nacimiento'      => $fecha_nacimiento,
			'contacto_emergencia'   => $contacto_emergencia,
			'dialCodeEmergencia' 	 => $dialCodeEmergencia,
			'numero_emergencia'	 => $numero_emergencia,
			'notificaciones'		 => $notificaciones,
			'rh'					 => $rh,
            'eps'                   => $eps,
            'estado'                => $estado,
            'fracturas'             => $fracturas,
            'alergias'              => $alergias,
            'enfermedades_actuales' => $enfermedades_actuales,
            'observaciones'         => $observaciones,
            'plan'                  => $plan,
            'pago_plan'             => $pago_plan,
            'vencimiento_plan'      => $vencimiento_plan
], JSON_UNESCAPED_UNICODE);

log_action('Editar Clientes', $desc, 'Clientes');
// END LOGS
		
		

		
		
        $_SESSION['success'] = "Cliente actualizado correctamente.";
        header("Location: detail.php?id=" . urlencode($id));
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar el cliente: " . $e->getMessage();
        header("Location: edit.php?id=" . urlencode($id));
        exit;
    }
} else {
    // Si es GET, obtener los datos del cliente para prellenar el formulario, solo si no está borrado
    try {
        $stmt = db()->prepare("SELECT * FROM clientes WHERE id = :id AND borrado = 0 AND congelado = 0");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
            $_SESSION['error'] = "Cliente no encontrado.";
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la consulta: " . $e->getMessage();
        header("Location: index.php");
        exit;
    }
}

$headerColor = ($cliente['estado'] === 'activo') ? '#28a745' : '#FD2D23';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Cliente</title>
  <?php include('../inc/header.php'); ?>
  <style>
  
    .section-title {
        font-size: 1.5rem;
        margin-top: 20px;
        margin-bottom: 10px;
        border-bottom: 2px solid #ddd;
        padding-bottom: 5px;
    }
    .card-header {
        background-color: <?php echo $headerColor; ?>;
        color: #fff;
    }
    .detail-item {
        margin-bottom: 0.5rem;
    }
    .badge-vencido {
        background-color: red;
        color: white;
        border-radius: 50px;
        padding: 5px 10px;
        font-size: 0.9rem;
        margin-left: 10px;
    }
    /* Estilo para los campos obligatorios */
    .form-label::after {
        content: " *";
        color: red;
    }
	  
	  
	  .profile-photo-container {
  position: relative;
  display: inline-block;
}
.profile-photo {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #fff;
  box-shadow: 0 0 8px rgba(0,0,0,0.25);
  transition: transform 0.2s ease-in-out;
}

.profile-photo:hover {
  transform: scale(1.05);
}

.modal-backdrop.show {
  opacity: 0.45 !important; /* Fondo más suave */
}
	  
	  /* Estilos para el modal de cámara */
#cameraModal .modal-body {
  padding: 1rem;
  overflow: hidden;
  background-color: #000;
}

#cameraStream, #cameraCanvas {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  display: block;
  margin: 0 auto;
}

#cameraCanvas {
  background-color: #000;
}

#cameraModal .modal-dialog {
  max-width: 600px;
}

#cameraModal .modal-footer {
  flex-wrap: wrap;
  gap: 0.5rem;
}

#cameraModal .modal-footer .btn {
  margin: 0;
}

/* Responsive para móviles */
@media (max-width: 576px) {
  #cameraModal .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }
  
  #cameraModal .modal-body {
    padding: 0.5rem;
  }
  
  #cameraModal .modal-footer .btn {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
  }
}


  </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1>Editar Cliente <?php echo htmlspecialchars($cliente['nombres'] . " " . $cliente['apellidos']); ?></h1>
  </div>
</div>
  <?php include('../inc/menu.php'); ?>
  <div class="container my-5">
   
    <div class="card shadow mb-4">
      <!-- Encabezado -->
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0"><i class="fa fa-user"></i> <?php echo htmlspecialchars($cliente['nombres'] . " " . $cliente['apellidos']); ?></h3>
        <span class="badge <?php echo ($cliente['estado'] === 'activo') ? 'bg-success' : 'bg-danger'; ?>">
          <?php echo ($cliente['estado'] === 'activo') ? 'Activo' : 'Inactivo'; ?>
        </span>
      </div>
      <div class="card-body">
        <!-- Formulario de Edición -->
        <form action="edit.php?id=<?php echo urlencode($id); ?>" method="post" id="editForm" enctype="multipart/form-data">
          <div class="row">
            <!-- Datos Personales -->
            <div class="col-md-6">
				
			<!-- Imagen de Perfil -->
			<div class="text-center mb-4">
			  <div class="profile-photo-container d-inline-block">
				<img id="previewImage" 
					 src="<?php echo !empty($cliente['imagen_perfil']) ? '../../uploads/clientes/' . htmlspecialchars($cliente['imagen_perfil']) : '../../assets/img/default-user.png'; ?>" 
					 alt="Foto de perfil" 
					 class="profile-photo shadow-sm">
			  </div>

			  <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
				<label for="imagen_perfil" class="btn btn-outline-primary btn-sm">
				  <i class="fa fa-upload"></i> Subir Archivo
				</label>
				<button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#cameraModal">
				  <i class="fa fa-camera"></i> Tomar Foto
				</button>
			  </div>

			  <input type="file" id="imagen_perfil" name="imagen_perfil" accept="image/*" hidden>
			</div>


				
              <div class="section-title"><i class="fa fa-id-card-o"></i> Datos Personales</div>
              <div class="mb-3">
                <label for="identificacion" class="form-label">Identificación</label>
                <input type="text" name="identificacion" id="identificacion" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['identificacion']); ?>" required>
              </div>
              <div class="mb-3">
                <label for="nombres" class="form-label">Nombres</label>
                <input type="text" name="nombres" id="nombres" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['nombres']); ?>" required>
              </div>
              <div class="mb-3">
                <label for="apellidos" class="form-label">Apellidos</label>
                <input type="text" name="apellidos" id="apellidos" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['apellidos']); ?>" required>
              </div>
              <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" name="direccion" id="direccion" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['direccion']); ?>" required>
              </div>
              <!-- Campo oculto para dialCode -->
              <input type="hidden" name="dialCode" id="dialCode" value="<?php echo htmlspecialchars($cliente['dialCode']); ?>">
				
              <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label><br>
                <input type="tel" name="telefono" id="telefono" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['telefono']); ?>" maxlength="13" required>
              </div>
              <div class="mb-3">
                <label for="genero" class="form-label">Género</label>
                <select class="form-control" id="genero" name="genero" required>
                  <option value="">Seleccione...</option>
                  <option value="masculino" <?php echo ($cliente['genero'] === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                  <option value="femenino" <?php echo ($cliente['genero'] === 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                  <option value="otro" <?php echo ($cliente['genero'] === 'otro') ? 'selected' : ''; ?>>Otro</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['email']); ?>">
              </div>
              <div class="mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="text" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['fecha_nacimiento']); ?>" required>
              </div>
				
				<div class="mb-3">
                <label for="contacto_emergencia" class="form-label">Contacto de Emergencia</label>
                <input type="text" name="contacto_emergencia" id="contacto_emergencia" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['contacto_emergencia']); ?>">
              </div>
				
				
			<div class="mb-3">
                <!-- Para el teléfono de emergencia -->
				<label for="numero_emergencia" class="form-label">Tel. Contacto de Emergencia</label>
<input type="tel" name="numero_emergencia" id="numero_emergencia" class="form-control" 
       value="<?php echo htmlspecialchars($cliente['numero_emergencia']); ?>" maxlength="11">
				
<input type="hidden" id="dialCodeEmergencia" name="dialCodeEmergencia" 
       value="<?php echo htmlspecialchars($cliente['dialCodeEmergencia']); ?>">

              </div>
				
			<div class="form-group">
  <!-- Campo oculto para enviar 0 si no se marca -->
  <input type="hidden" name="notificaciones" value="0">
  <label class="switch">
    <input type="checkbox" id="notificaciones" name="notificaciones" value="1" <?php echo (isset($cliente['notificaciones']) && $cliente['notificaciones'] == 1) ? 'checked' : ''; ?>>
    <span class="slider round"></span>
  </label>
  <span>Recibir Notificaciones Por WhatsApp?</span>
</div>

             
            </div>
            <!-- Plan y Datos Médicos -->
            <div class="col-md-6">
              <!-- Plan y Membresía -->
              <div class="section-title"><i class='fas fa-fire-alt'></i> Plan y Membresía</div>
              <div class="mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-control" id="estado" name="estado" required>
                  <option value="">Seleccione...</option>
                  <option value="activo" <?php echo ($cliente['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                  <option value="inactivo" <?php echo ($cliente['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
              </div>
              
				
				<!-- Después del campo "Plan" y antes de "Precio Plan" -->
<div class="mb-3">
                <label for="plan" class="form-label">Plan</label>
                <select class="form-control" id="plan" name="plan" required>
					
                  <option value="">Seleccione...</option>
<?php foreach ($planes as $plan): ?>
  <option value="<?php echo $plan['id']; ?>" 
          data-precio="<?php echo $plan['precio']; ?>" 
          data-frecuencia="<?php echo $plan['frecuencia']; ?>"
          data-dias="<?php echo $plan['dias']; ?>"
    <?php echo ($cliente['plan'] == $plan['id']) ? 'selected' : ''; ?>>
    <?php echo htmlspecialchars($plan['nombre']); ?>
  </option>
<?php endforeach; ?>

					
					
                </select>
              </div>
              <div class="mb-3">
                <label for="precio_mensual" class="form-label">Precio Plan</label>
                <input type="text" class="form-control" id="precio_mensual" name="precio_mensual" readonly>
              </div>
              <div class="mb-3">
                <label for="pago_plan" class="form-label">Fecha de Pago</label>
                <input type="text" class="form-control" id="pago_plan" name="pago_plan" 
                       value="<?php echo isset($cliente['pago_plan']) ? htmlspecialchars($cliente['pago_plan']) : ''; ?>">
              </div>
              <div class="mb-3">
                <label for="vencimiento_plan" class="form-label">Fecha de Vencimiento</label>
                <input type="text" class="form-control" id="vencimiento_plan" name="vencimiento_plan" 
                       value="<?php echo isset($cliente['vencimiento_plan']) ? htmlspecialchars($cliente['vencimiento_plan']) : ''; ?>">
              </div>
				
				
				
              <!-- Información Médica -->
              <div class="section-title"><i class="fa fa-user-md"></i> Información Médica</div>
				
				<div class="mb-3">
  <label for="rh" class="form-label">RH</label>
  <select name="rh" id="rh" class="form-control">
    <option value="">Seleccione...</option>
    <option value="O+" <?php echo ($cliente['rh'] === 'O+') ? 'selected' : ''; ?>>O+</option>
    <option value="O-" <?php echo ($cliente['rh'] === 'O-') ? 'selected' : ''; ?>>O-</option>
    <option value="A+" <?php echo ($cliente['rh'] === 'A+') ? 'selected' : ''; ?>>A+</option>
    <option value="A-" <?php echo ($cliente['rh'] === 'A-') ? 'selected' : ''; ?>>A-</option>
    <option value="B+" <?php echo ($cliente['rh'] === 'B+') ? 'selected' : ''; ?>>B+</option>
    <option value="B-" <?php echo ($cliente['rh'] === 'B-') ? 'selected' : ''; ?>>B-</option>
    <option value="AB+" <?php echo ($cliente['rh'] === 'AB+') ? 'selected' : ''; ?>>AB+</option>
    <option value="AB-" <?php echo ($cliente['rh'] === 'AB-') ? 'selected' : ''; ?>>AB-</option>
  </select>
</div>

				
				 <div class="mb-3">
                <label for="eps" class="form-label">EPS</label>
                <input type="text" name="eps" id="eps" class="form-control" 
                       value="<?php echo htmlspecialchars($cliente['eps']); ?>">
              </div>
				
              <div class="mb-3">
                <label for="fracturas" class="form-label">Fracturas</label>
                <textarea name="fracturas" id="fracturas" rows="2" class="form-control" required><?php echo ($cliente['fracturas'] ?: 'No'); ?></textarea>
              </div>
              <div class="mb-3">
                <label for="alergias" class="form-label">Alergias</label>
                <textarea name="alergias" id="alergias" rows="2" class="form-control" required><?php echo ($cliente['alergias'] ?: 'No'); ?></textarea>
              </div>
              <div class="mb-3">
                <label for="enfermedades_actuales" class="form-label">Enfermedades Actuales</label>
                <textarea name="enfermedades_actuales" id="enfermedades_actuales" rows="2" class="form-control" required><?php echo ($cliente['enfermedades_actuales'] ?: 'No'); ?></textarea>
              </div>
              <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="2" class="form-control" required><?php echo ($cliente['observaciones'] ?: 'No'); ?></textarea>
              </div>
            </div>
          </div>
          <!-- Botones del formulario -->
          <div class="mt-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
            <a href="detail.php?id=<?php echo urlencode($id); ?>" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
	 </div>
	  
	<!-- Modal para Cámara -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fa fa-camera"></i> Tomar Foto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <video id="cameraStream" width="100%" autoplay playsinline></video>
        <canvas id="cameraCanvas" style="display:none;"></canvas>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" id="captureBtn" class="btn btn-primary">
          <i class="fa fa-camera"></i> Capturar
        </button>
        <button type="button" id="acceptPhotoBtn" class="btn btn-success" style="display:none;">
          <i class="fa fa-check"></i> Aceptar Foto
        </button>
        <button type="button" id="retakePhotoBtn" class="btn btn-warning" style="display:none;">
          <i class="fa fa-repeat"></i> Repetir
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fa fa-times"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

    
    <?php
    // Consulta para obtener el formulario asociado al cliente
    $stmtForm = db()->prepare("SELECT id FROM formularios WHERE cliente_id = :cliente_id ORDER BY id DESC LIMIT 1");
    $stmtForm->execute([':cliente_id' => $id]);
    $formulario = $stmtForm->fetch(PDO::FETCH_ASSOC);
    $formId = $formulario ? $formulario['id'] : null;
    ?>
    
    
  </div>
  
  <?php include('../inc/menu-footer.php'); ?>
  
  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
  <script>
  $(document).ready(function() {
  <?php if(isset($_SESSION['success'])): ?>
    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: '<?php echo $_SESSION['success']; ?>'
    });
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
  <?php if(isset($_SESSION['error'])): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '<?php echo $_SESSION['error']; ?>'
    });
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  /********************************************************
   * TELÉFONO PRINCIPAL
   ********************************************************/
  var phoneInput = document.querySelector("#telefono");
  var iti = window.intlTelInput(phoneInput, {
    separateDialCode: true,
    nationalMode: false,
    formatOnDisplay: true,
    autoPlaceholder: "polite",
    initialCountry: "auto",
    geoIpLookup: function(callback) {
      $.get("https://ipinfo.io", function() {}, "jsonp").always(function(resp) {
        var countryCode = (resp && resp.country) ? resp.country : "us";
        callback(countryCode);
      });
    },
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
  });

  // Preseleccionar el teléfono si existe en la BD
  var storedDialCode = $("#dialCode").val();
  var storedPhone = $("#telefono").val();
  if (storedDialCode && storedPhone) {
    if (storedDialCode.charAt(0) !== '+') {
      storedDialCode = '+' + storedDialCode;
    }
    var fullNumber = storedDialCode + storedPhone;
    setTimeout(function() {
      iti.setNumber(fullNumber);
    }, 800);
  }

  /********************************************************
   * TELÉFONO DE EMERGENCIA
   ********************************************************/
  var emergencyInput = document.querySelector("#numero_emergencia");
  var itiEmergencia = window.intlTelInput(emergencyInput, {
    separateDialCode: true,
    nationalMode: false,
    formatOnDisplay: true,
    autoPlaceholder: "polite",
    initialCountry: "auto",
    geoIpLookup: function(callback) {
      $.get("https://ipinfo.io", function() {}, "jsonp").always(function(resp) {
        var countryCode = (resp && resp.country) ? resp.country : "us";
        callback(countryCode);
      });
    },
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
  });

  // Preseleccionar el teléfono de emergencia si existe en la BD
  var storedDialCodeEmergencia = $("#dialCodeEmergencia").val();
  var storedEmergencyPhone = $("#numero_emergencia").val();
  if (storedDialCodeEmergencia && storedEmergencyPhone) {
    if (storedDialCodeEmergencia.charAt(0) !== '+') {
      storedDialCodeEmergencia = '+' + storedDialCodeEmergencia;
    }
    var fullEmergencyNumber = storedDialCodeEmergencia + storedEmergencyPhone;
    setTimeout(function() {
      itiEmergencia.setNumber(fullEmergencyNumber);
    }, 800);
  }

  /********************************************************
   * AL ENVIAR EL FORMULARIO, ACTUALIZAR AMBOS DIAL CODES
   ********************************************************/
  $("#editForm").on("submit", function() {
    // Actualizar para teléfono principal
    var selectedCountry = iti.getSelectedCountryData();
    $("#dialCode").val(selectedCountry.dialCode);
    
    // Actualizar para teléfono de emergencia
    var selectedEmergencyCountry = itiEmergencia.getSelectedCountryData();
    $("#dialCodeEmergencia").val(selectedEmergencyCountry.dialCode);
  });

  /********************************************************
   * PLAN Y VENCIMIENTO
   ********************************************************/
  /*function updatePrecio() {
    var precio = $("#plan option:selected").data("precio");
    if (precio !== undefined) {
      $("#precio_mensual").val(precio);
    } else {
      $("#precio_mensual").val('');
    }
  }

  updatePrecio();
  $("#plan").on("change", function(){
    updatePrecio();
    updateVencimiento();
  });
  $("#pago_plan").on("change", function(){
    updateVencimiento();
  });*/
});

  </script>
	
<script>
$(function () {

  /* ============ 1. Instancias Flatpickr ============ */
  const fpNacimiento = flatpickr("#fecha_nacimiento", {
    dateFormat : "Y-m-d",
    altInput   : true,
    altFormat  : "d \\de F \\de Y",
    locale     : "es",
    maxDate    : "today"
  });

  const fpPago = flatpickr("#pago_plan", {
    dateFormat : "Y-m-d",
    altInput   : true,
    altFormat  : "d \\de F \\de Y",
    locale     : "es"
  });

  const fpVenci = flatpickr("#vencimiento_plan", {
    dateFormat : "Y-m-d",
    altInput   : true,
    altFormat  : "d \\de F \\de Y",
    locale     : "es"
  });

  /* ============ 2. Guardar valores originales ============ */
  const planOriginal = $("#plan").val();
  const pagoOriginal = $("#pago_plan").val();
  const vencimientoOriginal = $("#vencimiento_plan").val();

  /* ============ 3. Precio según plan ============ */
  function updatePrecio () {
    const precio = $("#plan option:selected").data("precio");
    $("#precio_mensual").val(precio !== undefined ? precio : "");
  }

  /* ============ 4. Calcular vencimiento ============ */
  function getPagoDate () {
    /* 1) Si Flatpickr ya tiene selectedDates úsalo */
    if (fpPago.selectedDates.length) return fpPago.selectedDates[0];

    /* 2) Si viene del servidor: lee el valor ISO del input */
    const iso = $("#pago_plan").val();
    return iso ? fpPago.parseDate(iso, "Y-m-d") : null;
  }

  function updateVencimiento () {
    const pagoDate = getPagoDate();
    if (!pagoDate) { fpVenci.clear(); return; }   // sin pago → vacío

    const $opt  = $("#plan option:selected");
    const meses = parseInt($opt.data("frecuencia")) || 0;  // meses
    const dias  = parseInt($opt.data("dias"))        || 0; // días

    /* pago + meses + (dias-1) */
    const due = new Date(pagoDate);
    due.setMonth(due.getMonth() + meses);
    due.setDate (due.getDate()  + dias - 1);

    /* setDate: actualiza valor oculto + visible */
    fpVenci.setDate(due, true);
  }

  /* ============ 5. Enlaces de eventos ============ */
  // Cambio de plan
  $("#plan").on("change", function () {
    updatePrecio();
    
    const planActual = $(this).val();
    
    // Si vuelve al plan original, restaurar fechas originales
    if (planActual === planOriginal) {
      if (pagoOriginal) {
        fpPago.setDate(pagoOriginal, true);
      } else {
        fpPago.clear();
      }
      
      if (vencimientoOriginal) {
        fpVenci.setDate(vencimientoOriginal, true);
      } else {
        fpVenci.clear();
      }
    } else {
      // Si cambia a un plan diferente, limpiar las fechas
      fpPago.clear();
      fpVenci.clear();
    }
  });

  // Cambio de fecha de pago vía calendario
  fpPago.config.onChange.push(function() {
    // Solo recalcular si NO estamos en el plan original
    if ($("#plan").val() !== planOriginal) {
      updateVencimiento();
    }
  });

  // Cambio de fecha de pago si el usuario escribe manualmente
  $("#pago_plan").on("input change", function() {
    // Solo recalcular si NO estamos en el plan original
    if ($("#plan").val() !== planOriginal) {
      updateVencimiento();
    }
  });

  /* ============ 6. Inicialización al cargar ============ */
  updatePrecio();
  // No recalcular nada al inicio, mantener los valores originales
});
</script>
	
	<script>
  function toTitleCase(str) {
    // Convertir todo a minúsculas
    str = str.toLowerCase();
    
    // Separar por espacios y convertir la primera letra de cada palabra
    const palabras = str.split(/\s+/).map(palabra => {
      if (!palabra) return palabra; // Para evitar problemas con cadenas vacías
      return palabra[0].toUpperCase() + palabra.slice(1);
    });
    
    // Unir las palabras de nuevo con espacios
    return palabras.join(' ');
  }

  const campos = ['nombres', 'apellidos', 'contacto_emergencia'];
  
  campos.forEach(id => {
    const input = document.getElementById(id);
    if (input) {
      input.addEventListener('input', function() {
        this.value = toTitleCase(this.value);
      });
    }
  });
</script>


	
<script>
  const emailField = document.getElementById('email');
  emailField.addEventListener('input', function() {
    this.value = this.value.toLowerCase();
  });
</script>
	
<script>
/* ==== Cámara ==== */
let videoStream = null;
const cameraModal = document.getElementById('cameraModal');
const video = document.getElementById('cameraStream');
const canvas = document.getElementById('cameraCanvas');
const captureBtn = document.getElementById('captureBtn');

// Función para detener la cámara
function stopCamera() {
  if (videoStream) {
    videoStream.getTracks().forEach(track => track.stop());
    videoStream = null;
  }
  if (video) {
    video.srcObject = null;
  }
}

// Función para limpiar el modal completamente
function cleanupModal() {
  stopCamera();
  
  // Eliminar todos los backdrops
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
  
  // Restaurar body
  document.body.classList.remove('modal-open');
  document.body.style.overflow = '';
  document.body.style.paddingRight = '';
  
  // Reiniciar elementos visuales
  if (video) {
    video.style.display = 'block';
  }
  if (canvas) {
    canvas.style.display = 'none';
  }
}

/* --- Abrir el modal --- */
cameraModal.addEventListener('show.bs.modal', async () => {
  // Limpiar primero cualquier estado anterior
  cleanupModal();
  
  // Asegurar que los botones estén en su estado inicial
  captureBtn.style.display = 'inline-block';
  document.getElementById('acceptPhotoBtn').style.display = 'none';
  document.getElementById('retakePhotoBtn').style.display = 'none';
  
  try {
    // Pequeño delay para asegurar que todo esté limpio
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Solicitar acceso a la cámara
    videoStream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: 'user' } 
    });
    video.srcObject = videoStream;
  } catch (err) {
    Swal.fire('Error', 'No se pudo acceder a la cámara: ' + err.message, 'error');
    const modalInstance = bootstrap.Modal.getInstance(cameraModal);
    if (modalInstance) modalInstance.hide();
  }
});

/* --- Capturar la foto --- */
let capturedDataUrl = null;

captureBtn.addEventListener('click', () => {
  if (!video.videoWidth || !video.videoHeight) {
    Swal.fire('Error', 'La cámara aún no está lista. Intenta de nuevo.', 'warning');
    return;
  }

  // Configurar canvas con las dimensiones del video
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  
  const ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  capturedDataUrl = canvas.toDataURL('image/jpeg', 0.95);
  
  // Mostrar la foto capturada en el canvas
  video.style.display = 'none';
  canvas.style.display = 'block';
  
  // Detener la cámara
  stopCamera();
  
  // Cambiar los botones
  captureBtn.style.display = 'none';
  document.getElementById('acceptPhotoBtn').style.display = 'inline-block';
  document.getElementById('retakePhotoBtn').style.display = 'inline-block';
});

/* --- Aceptar la foto --- */
document.getElementById('acceptPhotoBtn').addEventListener('click', () => {
  if (capturedDataUrl) {
    document.getElementById('previewImage').src = capturedDataUrl;
    
    // Crear archivo virtual para el formulario
    fetch(capturedDataUrl)
      .then(res => res.blob())
      .then(blob => {
        const file = new File([blob], "captura.jpg", { type: "image/jpeg" });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('imagen_perfil').files = dataTransfer.files;
      });
  }
  
  // Cerrar modal
  const modalInstance = bootstrap.Modal.getInstance(cameraModal);
  if (modalInstance) {
    modalInstance.hide();
  }
});

/* --- Repetir la foto --- */
document.getElementById('retakePhotoBtn').addEventListener('click', async () => {
  // Ocultar canvas y mostrar video
  canvas.style.display = 'none';
  video.style.display = 'block';
  
  // Restaurar botones
  captureBtn.style.display = 'inline-block';
  document.getElementById('acceptPhotoBtn').style.display = 'none';
  document.getElementById('retakePhotoBtn').style.display = 'none';
  
  // Reiniciar la cámara
  try {
    videoStream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: 'user' } 
    });
    video.srcObject = videoStream;
  } catch (err) {
    Swal.fire('Error', 'No se pudo reiniciar la cámara: ' + err.message, 'error');
  }
});

/* --- Cuando se cierra el modal --- */
cameraModal.addEventListener('hidden.bs.modal', () => {
  // Asegurar que la cámara se detiene
  stopCamera();
  
  // Limpiar después de la animación
  setTimeout(() => {
    cleanupModal();
  }, 350);
});

/* --- Cuando se oculta el modal (antes de hidden) --- */
cameraModal.addEventListener('hide.bs.modal', () => {
  stopCamera();
});



</script>

	
	<script>
/* ==== PREVIEW AL CARGAR ARCHIVO ==== */
document.getElementById('imagen_perfil').addEventListener('change', function (event) {
  const file = event.target.files[0];
  if (!file) return;

  // Solo permitir imágenes
  if (!file.type.startsWith('image/')) {
    Swal.fire('Archivo inválido', 'Por favor selecciona una imagen válida.', 'warning');
    this.value = ''; // limpiar input
    return;
  }

  const reader = new FileReader();
  reader.onload = function (e) {
    document.getElementById('previewImage').src = e.target.result;
  };
  reader.readAsDataURL(file);
});
</script>

	
</body>
</html>












