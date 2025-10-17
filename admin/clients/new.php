<?php
/**
 * new.php  ·  Alta de clientes con verificación de documento único
 */

require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';
$permisopage = 'Agregar Clientes';
include('../login/restriction.php');

/* ------------------------------------------------------
 * 1. Conexión y planes disponibles
 * ---------------------------------------------------- */
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtPlan = $pdo->query("SELECT * FROM planes WHERE borrado = 0 AND estado = 'activo' ORDER BY nombre ASC");
    $planes   = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error en la conexión: " . $e->getMessage();
    header('Location: index.php');
    exit;
}

/* ------------------------------------------------------
 * 2. Alta (POST)
 * ---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar / normalizar entradas
    $identificacion        = trim($_POST['identificacion']);
    $nombres               = trim($_POST['nombres']);
    $apellidos             = trim($_POST['apellidos']);
    $direccion             = trim($_POST['direccion']);
    $dialCode              = trim($_POST['dialCode']);
    $telefono              = trim($_POST['telefono']);
    $genero                = trim($_POST['genero']);
    $email                 = trim($_POST['email']);
    $fecha_nacimiento      = trim($_POST['fecha_nacimiento']);

    $contacto_emergencia   = trim($_POST['contacto_emergencia']);
    $dialCodeEmergencia    = trim($_POST['dialCodeEmergencia']);
    $numero_emergencia     = trim($_POST['numero_emergencia']);

    $notificaciones        = isset($_POST['notificaciones']) ? 1 : 0;
    $rh                    = trim($_POST['rh']);
    $eps                   = trim($_POST['eps']);
    $estado                = trim($_POST['estado']);

    $fracturas             = trim($_POST['fracturas'])             ?: 'No';
    $alergias              = trim($_POST['alergias'])              ?: 'No';
    $enfermedades_actuales = trim($_POST['enfermedades_actuales']) ?: 'No';
    $observaciones         = trim($_POST['observaciones'])         ?: 'No';

    $plan                  = trim($_POST['plan']);   // id del plan
    $pago_plan             = trim($_POST['pago_plan']);
    $vencimiento_plan      = trim($_POST['vencimiento_plan']);

    /* ---------------- Verificar documento único ---------------- */
    try {
        $stmtDup = $pdo->prepare("SELECT id FROM clientes WHERE identificacion = :identificacion AND borrado = 0 LIMIT 1");
        $stmtDup->execute([':identificacion' => $identificacion]);
        if ($stmtDup->fetchColumn()) {
            $_SESSION['error'] = 'Ya existe un cliente con esa identificación.';
            header('Location: new.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error al comprobar duplicados: ' . $e->getMessage();
        header('Location: new.php');
        exit;
    }

    /* ---------------- Insertar nuevo cliente ------------------- */
    try {
        $stmt = $pdo->prepare("INSERT INTO clientes (
            identificacion,nombres,apellidos,direccion,dialCode,telefono,genero,email,fecha_nacimiento,
            contacto_emergencia,dialCodeEmergencia,numero_emergencia,notificaciones,rh,eps,estado,
            fracturas,alergias,enfermedades_actuales,observaciones,plan,pago_plan,vencimiento_plan,
            created_at,updated_at)
            VALUES ( :identificacion,:nombres,:apellidos,:direccion,:dialCode,:telefono,:genero,:email,:fecha_nacimiento,
                     :contacto_emergencia,:dialCodeEmergencia,:numero_emergencia,:notificaciones,:rh,:eps,:estado,
                     :fracturas,:alergias,:enfermedades_actuales,:observaciones,:plan,:pago_plan,:vencimiento_plan,
                     NOW(),NOW())");

        $stmt->execute([
            ':identificacion'        => $identificacion,
            ':nombres'               => $nombres,
            ':apellidos'             => $apellidos,
            ':direccion'             => $direccion,
            ':dialCode'              => $dialCode,
            ':telefono'              => $telefono,
            ':genero'                => $genero,
            ':email'                 => $email,
            ':fecha_nacimiento'      => $fecha_nacimiento,
            ':contacto_emergencia'   => $contacto_emergencia,
            ':dialCodeEmergencia'    => $dialCodeEmergencia,
            ':numero_emergencia'     => $numero_emergencia,
            ':notificaciones'        => $notificaciones,
            ':rh'                    => $rh,
            ':eps'                   => $eps,
            ':estado'                => $estado,
            ':fracturas'             => $fracturas,
            ':alergias'              => $alergias,
            ':enfermedades_actuales' => $enfermedades_actuales,
            ':observaciones'         => $observaciones,
            ':plan'                  => $plan,
            ':pago_plan'             => $pago_plan,
            ':vencimiento_plan'      => $vencimiento_plan
        ]);

        $id = $pdo->lastInsertId();
		
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
            'dialCodeEmergencia'    => $dialCodeEmergencia,
            'numero_emergencia'     => $numero_emergencia,
            'notificaciones'        => $notificaciones,
            'rh'                    => $rh,
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

log_action('Crear Clientes', $desc, 'Clientes');
// END LOGS
		
        $_SESSION['success'] = 'Cliente creado satisfactoriamente.';
        header('Location: detail.php?id=' . urlencode($id));
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error al crear el cliente: ' . $e->getMessage();
        header('Location: new.php');
        exit;
    }
}

/* ------------------------------------------------------
 * 3. Plantilla formulario (sin cambios)
 * ---------------------------------------------------- */
$headerColor = '#007bff';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Cliente</title>
  <?php include('../inc/header.php'); ?>
  <style>
    .section-title{font-size:1.5rem;margin-top:20px;margin-bottom:10px;border-bottom:2px solid #ddd;padding-bottom:5px}
    .card-header{background-color:<?= $headerColor ?>;color:#fff}
    .form-label::after{content:" *";color:red}
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css"/>

</head>
<body>
<div class="container" style="padding:0;background:rgba(0,0,0,0)">
  <div class="portada"><h1>Agregar Nuevo Cliente</h1></div>
</div>
<?php include('../inc/menu.php'); ?>
<div class="container my-5">
  <div class="card shadow mb-4">
    <div class="card-header"><h3 class="mb-0"><i class="fa fa-user-plus"></i> Datos del Cliente</h3></div>
    <div class="card-body">
      <form action="new.php" method="post" id="addForm">
        <div class="row">
          <!-- Columna izquierda -->
          <div class="col-md-6">
            <div class="section-title"><i class="fa fa-id-card-o"></i> Datos Personales</div>
            <div class="mb-3"><label for="identificacion" class="form-label">Identificación</label>
              <input type="text" name="identificacion" id="identificacion" class="form-control" required></div>
            <div class="mb-3"><label for="nombres" class="form-label">Nombres</label>
              <input type="text" name="nombres" id="nombres" class="form-control" required></div>
            <div class="mb-3"><label for="apellidos" class="form-label">Apellidos</label>
              <input type="text" name="apellidos" id="apellidos" class="form-control" required></div>
            <div class="mb-3"><label for="direccion" class="form-label">Dirección</label>
              <input type="text" name="direccion" id="direccion" class="form-control" required></div>

            <input type="hidden" name="dialCode" id="dialCode">
            <div class="mb-3"><label for="telefono" class="form-label">Teléfono</label>
              <input type="tel" name="telefono" id="telefono" class="form-control" maxlength="13" required></div>
            <div class="mb-3"><label for="genero" class="form-label">Género</label>
              <select class="form-control" id="genero" name="genero" required>
                <option value="">Seleccione...</option>
                <option value="masculino">Masculino</option>
                <option value="femenino">Femenino</option>
                <option value="otro">Otro</option>
              </select></div>
            <div class="mb-3"><label for="email" class="form-label">Email</label>
              <input type="email" name="email" id="email" class="form-control"></div>
            <div class="mb-3"><label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
              <input type="text" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" required></div>

            <div class="mb-3"><label for="contacto_emergencia" class="form-label">Contacto Emergencia</label>
              <input type="text" name="contacto_emergencia" id="contacto_emergencia" class="form-control"></div>
            <div class="mb-3"><label for="numero_emergencia" class="form-label">Tel. Emergencia</label>
              <input type="tel" name="numero_emergencia" id="numero_emergencia" class="form-control" maxlength="11"></div>
              <input type="hidden" id="dialCodeEmergencia" name="dialCodeEmergencia">

            <div class="form-group">
              <input type="hidden" name="notificaciones" value="0">
              <label class="switch">
                <input type="checkbox" id="notificaciones" name="notificaciones" value="1">
                <span class="slider round"></span>
              </label>
              <span>Recibir Notificaciones Por WhatsApp?</span>
            </div>
          </div>

          <!-- Columna derecha -->
          <div class="col-md-6">
            <div class="section-title"><i class="fas fa-fire-alt"></i> Plan y Membresía</div>
            <div class="mb-3"><label for="estado" class="form-label">Estado</label>
              <select class="form-control" id="estado" name="estado" required>
                <option value="">Seleccione...</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select></div>
            <div class="mb-3"><label for="plan" class="form-label">Plan</label>
              <select class="form-control" id="plan" name="plan" required>
                <option value="">Seleccione...</option>
<?php foreach ($planes as $plan): ?>
                <option value="<?= $plan['id'] ?>" data-precio="<?= $plan['precio'] ?>" data-frecuencia="<?= $plan['frecuencia'] ?>" data-dias="<?= $plan['dias'] ?>">
                  <?= htmlspecialchars($plan['nombre']) ?>
                </option>
<?php endforeach; ?>
              </select></div>
            <div class="mb-3"><label for="precio_mensual" class="form-label">Precio Plan</label>
              <input type="text" id="precio_mensual" class="form-control" readonly></div>
            <div class="mb-3"><label for="pago_plan" class="form-label">Fecha de Pago</label>
              <input type="text" id="pago_plan" name="pago_plan" class="form-control"></div>
            <div class="mb-3"><label for="vencimiento_plan" class="form-label">Fecha de Vencimiento</label>
              <input type="text" id="vencimiento_plan" name="vencimiento_plan" class="form-control"></div>

            <div class="section-title"><i class="fa fa-user-md"></i> Información Médica</div>
            <div class="mb-3"><label for="rh" class="form-label">RH</label>
              <select name="rh" id="rh" class="form-control">
                <option value="">Seleccione...</option>
                <option value="O+">O+</option><option value="O-">O-</option>
                <option value="A+">A+</option><option value="A-">A-</option>
                <option value="B+">B+</option><option value="B-">B-</option>
                <option value="AB+">AB+</option><option value="AB-">AB-</option>
              </select></div>
            <div class="mb-3"><label for="eps" class="form-label">EPS</label>
              <input type="text" name="eps" id="eps" class="form-control"></div>
            <div class="mb-3"><label for="fracturas" class="form-label">Fracturas</label>
              <textarea name="fracturas" id="fracturas" rows="2" class="form-control">No</textarea></div>
            <div class="mb-3"><label for="alergias" class="form-label">Alergias</label>
              <textarea name="alergias" id="alergias" rows="2" class="form-control">No</textarea></div>

            <div class="mb-3"><label for="enfermedades_actuales" class="form-label">Enfermedades Actuales</label>
              <textarea name="enfermedades_actuales" id="enfermedades_actuales" rows="2" class="form-control">No</textarea></div>
            <div class="mb-3"><label for="observaciones" class="form-label">Observaciones</label>
              <textarea name="observaciones" id="observaciones" rows="2" class="form-control">No</textarea></div>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
          <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include('../inc/menu-footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
<script>
$(function(){
  /* Alerta de sesión */
  <?php if(isset($_SESSION['success'])){ ?>
    Swal.fire({icon:'success',title:'Éxito',text:'<?= $_SESSION['success'] ?>'});
    <?php unset($_SESSION['success']); } ?>
  <?php if(isset($_SESSION['error'])){ ?>
    Swal.fire({icon:'error',title:'Error',text:'<?= $_SESSION['error'] ?>'});
    <?php unset($_SESSION['error']); } ?>

  /******************  INTL‑TEL‑INPUT (principal)  ******************/
  const iti = window.intlTelInput(document.querySelector('#telefono'),{
    separateDialCode:true,nationalMode:false,formatOnDisplay:true,initialCountry:'co',utilsScript:'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js'
  });
  const itiE = window.intlTelInput(document.querySelector('#numero_emergencia'),{
    separateDialCode:true,nationalMode:false,formatOnDisplay:true,initialCountry:'co',utilsScript:'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js'
  });
  $('#addForm').on('submit',()=>{
    $('#dialCode').val(iti.getSelectedCountryData().dialCode);
    $('#dialCodeEmergencia').val(itiE.getSelectedCountryData().dialCode);
  });

  /******************  FLATPICKR  ******************/
  const fpNac  = flatpickr('#fecha_nacimiento',{dateFormat:'Y-m-d',altInput:true,altFormat:'d \\de F \\de Y',locale:'es',maxDate:'today'});
  const fpPago = flatpickr('#pago_plan',{dateFormat:'Y-m-d',altInput:true,altFormat:'d \\de F \\de Y',locale:'es'});
  const fpVenc = flatpickr('#vencimiento_plan',{dateFormat:'Y-m-d',altInput:true,altFormat:'d \\de F \\de Y',locale:'es'});

  /******************  Precio & vencimiento  ******************/
  function updatePrecio(){ const p=$('#plan option:selected').data('precio'); $('#precio_mensual').val(p??''); }
  function getPagoDate(){ if(fpPago.selectedDates.length) return fpPago.selectedDates[0]; const iso=$('#pago_plan').val(); return iso?fpPago.parseDate(iso,'Y-m-d'):null; }
  function updateVenc(){
    const pago=getPagoDate(); if(!pago){ fpVenc.clear(); return; }
    const $o=$('#plan option:selected'); const meses=parseInt($o.data('frecuencia'))||0; const dias=parseInt($o.data('dias'))||0;
    const due=new Date(pago); due.setMonth(due.getMonth()+meses); due.setDate(due.getDate()+dias-1); fpVenc.setDate(due,true);
  }
  $('#plan').on('change',()=>{updatePrecio();updateVenc();});
  fpPago.config.onChange.push(updateVenc);
  $('#pago_plan').on('input change',updateVenc);
  updatePrecio();

  /******************  TitleCase & email lowercase  ******************/
  const toTC=s=>s.toLowerCase().split(/\s+/).map(w=>w? w[0].toUpperCase()+w.slice(1):w).join(' ');
  ['nombres','apellidos','contacto_emergencia'].forEach(id=>{ $('#'+id).on('input',function(){ this.value=toTC(this.value); }); });
  $('#email').on('input',function(){ this.value=this.value.toLowerCase();});
});
</script>
</body>
</html>
