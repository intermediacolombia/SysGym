<?php
// edit.php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Manejar Valoraciones';
include('../login/restriction.php');

require_once __DIR__ . '/../../inc/config.php';



// 1) Si viene POST: actualizamos la valoración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['valoracion_id']) || !is_numeric($_POST['valoracion_id'])
        || !isset($_POST['peso'], $_POST['estatura'], $_POST['fecha'])
    ) {
        $_SESSION['error'] = 'Faltan datos obligatorios.';
        header('Location: index.php');
        exit;
    }
    $vid      = (int) $_POST['valoracion_id'];
    $peso     = floatval($_POST['peso']);
    $estatura = floatval($_POST['estatura']);
    $fecha    = $_POST['fecha'];
    $obs      = trim($_POST['observaciones']);
	// nuevos campos métricos
	$imc                = floatval($_POST['imc']);
	$porc_grasa         = floatval($_POST['porcentaje_grasa_corporal']);
	$porc_musculo       = floatval($_POST['porcentaje_musculo_esqueletico']);
	$met_basal          = intval($_POST['metabolismo_basal']);
	$edad_corporal      = intval($_POST['edad_corporal']);
	$niv_grasa_visceral = intval($_POST['nivel_grasa_visceral']);


    // campos sobre la silueta
    $campos = [
        'hombros','pecho','abdomen','cintura','cadera',
        'izq_brazo','izq_antebrazo','izq_muneca','izq_muslo_medio','izq_pantorrilla',
        'der_brazo','der_antebrazo','der_muneca','der_muslo_medio','der_pantorrilla'
    ];
    // preparar SETs
    $sets = [];
	$params = [];
    foreach ($campos as $f) {
        $sets[] = "$f = :$f";
        $params[":$f"] = isset($_POST['medida'][$f])
            ? floatval($_POST['medida'][$f])
            : null;
    }
    // añadir peso, estatura, fecha, observaciones
    $sets[] = "peso = :peso";
    $sets[] = "estatura = :estatura";
    $sets[] = "fecha = :fecha";
    $sets[] = "observaciones = :obs";
	$sets[] = "imc                       = :imc";
	$sets[] = "porcentaje_grasa_corporal  = :porc_grasa";
	$sets[] = "porcentaje_musculo_esqueletico = :porc_musculo";
	$sets[] = "metabolismo_basal          = :met_basal";
	$sets[] = "edad_corporal              = :edad_corporal";
	$sets[] = "nivel_grasa_visceral       = :niv_grasa";


    $sql = "UPDATE valoraciones SET " . implode(", ", $sets) . " WHERE id = :valoracion_id";
    $stmt = db()->prepare($sql);

    // vínculo de parámetros
   $params = [
  ':valoracion_id' => $vid,
  ':peso'          => $peso,
  ':estatura'      => $estatura,
  ':fecha'         => $fecha,
  ':obs'           => $obs,

  ':imc'           => $imc,
  ':porc_grasa'    => $porc_grasa,
  ':porc_musculo'  => $porc_musculo,
  ':met_basal'     => $met_basal,
  ':edad_corporal'=> $edad_corporal,
  ':niv_grasa'     => $niv_grasa_visceral,
] + $params;

    try {
        $stmt->execute($params);
		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'valoracion_id' => $vid,
    'accion'        => 'Valoración actualizada',
    'peso'          => $peso,
    'estatura'      => $estatura,
    'fecha'         => $fecha,
    'imc'           => $imc,
    'porcentaje_grasa_corporal'     => $porc_grasa,
    'porcentaje_musculo_esqueletico'=> $porc_musculo,
    'metabolismo_basal'             => $met_basal,
    'edad_corporal'                 => $edad_corporal,
    'nivel_grasa_visceral'          => $niv_grasa_visceral
], JSON_UNESCAPED_UNICODE);

log_action('Editar valoración', $desc, 'Valoraciones');
// END LOGS

		
		
        $_SESSION['success'] = 'Valoración actualizada correctamente.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error actualizando: ' . $e->getMessage();
    }
    //header('Location: edit.php?id=' . $vid);
    header('Location: index.php');
    exit;
}

// 2) Sino, GET: mostrar formulario
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID de valoración inválido.';
	header('Location: index.php');
    exit;
}
$vid = (int)$_GET['id'];
// cargar valoración
$stmt = db()->prepare("SELECT * FROM valoraciones WHERE id = :id AND borrado = 0");
$stmt->execute([':id' => $vid]);
$val = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$val) {
    $_SESSION['error'] = 'Valoración no encontrada';
	header('Location: index.php');
    exit;
}
// cargar cliente
$stmt = db()->prepare("SELECT id, nombres,apellidos,genero, fecha_nacimiento FROM clientes WHERE id = :cid");
$stmt->execute([':cid' => $val['cliente_id']]);
$cli = $stmt->fetch(PDO::FETCH_ASSOC);

$edad = '';
if (!empty($cli['fecha_nacimiento'])) {
    $nac = new DateTime($cli['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $nac->diff($hoy)->y;
}

$silueta = strtolower($cli['genero']) === 'femenino'
    ? '/admin/images/female2.png?'
    : '/admin/images/male2.png?';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Valoración — <?= htmlspecialchars("{$cli['nombres']} {$cli['apellidos']}") ?></title>
  <?php include('../inc/header.php'); ?>
  
	<style>
/* clases de nivel */
.bajo     { border-color: #5bc0de !important; background-color: #e1f5fe !important; }
.perfecto { border-color: #28a745 !important; background-color: #e6f4ea !important; }
.medio    { border-color: #ffc107 !important; background-color: #fff9e6 !important; }
.alto     { border-color: #dc3545 !important; background-color: #fdecea !important; }

/* envoltorio para input + unidad + flecha */
.input-level {
  position: relative;
}
/* flecha */
.input-level .level-icon {
  position: absolute;
  right: -30px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
  pointer-events: none;
}
/* colorea también la unidad */
.input-level.bajo     .input-group-text { color: #5bc0de !important; }
.input-level.perfecto .input-group-text { color: #28a745 !important; }
.input-level.medio    .input-group-text { color: #ffc107 !important; }
.input-level.alto     .input-group-text { color: #dc3545 !important; }
	 
	 /* cuando el wrapper recibe la clase bajo/perfecto/medio/alto */
.input-level.bajo    .form-control,
.input-level.bajo    .input-group-text {
  border-color:    #5bc0de !important;
  background-color:#e1f5fe !important;
  color:           #036f8b !important;
}
.input-level.perfecto .form-control,
.input-level.perfecto .input-group-text {
  border-color:    #28a745 !important;
  background-color:#e6f4ea !important;
  color:           #1a7f30 !important;
}
.input-level.medio   .form-control,
.input-level.medio   .input-group-text {
  border-color:    #ffc107 !important;
  background-color:#fff9e6 !important;
  color:           #b38900 !important;
}
.input-level.alto    .form-control,
.input-level.alto    .input-group-text {
  border-color:    #dc3545 !important;
  background-color:#fdecea !important;
  color:           #a71d2a !important;
}

/* colores de la flecha */
.level-icon.bajo     { color: #5bc0de; }
.level-icon.perfecto { color: #28a745; }
.level-icon.medio    { color: #ffc107; }
.level-icon.alto     { color: #dc3545; }

</style>
	
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
	<h1>Editar Valoración: <a href="/admin/clients/detail.php?id=<?= htmlspecialchars("{$cli['id']}") ?>" style="text-decoration: none; color: #000;"><?= htmlspecialchars("{$cli['nombres']} {$cli['apellidos']}") ?></a></h1>
	

	<button type="button" id="btnDeleteValoracion" class="btn btn-danger float-end">
  <i class="fa fa-trash"></i> Borrar Valoración
</button>
	
	</div>
	</div>
	
  <?php include('../inc/menu.php'); ?>
	<!-- Descargar Excel -->
  <a href="export_valoracion_excel.php?id=<?= $vid ?>" 
     class="btn btn-success float-end">
    <i class="fa fa-file-excel-o"></i> Exportar a Excel
  </a>
	

	<a href="/pdf/?type=valoracion&id=<?= $vid ?>" 
     class="btn btn-danger float-end" style="margin-right:  10px;">
    <i class="fa fa-file-pdf-o"></i> Exportar a PDF
  </a> 
	
	<!-- Enviar valoración por WhatsApp -->
<button type="button" id="btnSendWa"
        class="btn btn-success float-end me-2">
  <i class="fa fa-whatsapp"></i> Enviar a WhatsApp
</button>
	

	<br>
<br>

	
  <div class="container mt-4">
  

    <form method="post" action="edit.php" class="measurements-layout">
      <input type="hidden" name="valoracion_id" value="<?= $vid ?>">

      <!-- SILUETA -->
<div class="wrapper">
  <img src="<?= $silueta ?>" alt="Silueta corporal">

  <div class="label hombros">
    <input
      type="number"
      step="any"
      name="medida[hombros]"
      value="<?= htmlspecialchars($val['hombros']) ?>"
      placeholder="Hombros">
  </div>

  <div class="label pecho">
    <input
      type="number"
      step="any"
      name="medida[pecho]"
      value="<?= htmlspecialchars($val['pecho']) ?>"
      placeholder="Pecho">
  </div>

  <div class="label abdomen">
    <input
      type="number"
      step="any"
      name="medida[abdomen]"
      value="<?= htmlspecialchars($val['abdomen']) ?>"
      placeholder="Abdomen">
  </div>

  <div class="label cintura">
    <input
      type="number"
      step="any"
      name="medida[cintura]"
      value="<?= htmlspecialchars($val['cintura']) ?>"
      placeholder="Cintura">
  </div>

  <div class="label cadera">
    <input
      type="number"
      step="any"
      name="medida[cadera]"
      value="<?= htmlspecialchars($val['cadera']) ?>"
      placeholder="Cadera">
  </div>

  <div class="label izq-brazo">
    <input
      type="number"
      step="any"
      name="medida[izq_brazo]"
      value="<?= htmlspecialchars($val['izq_brazo']) ?>"
      placeholder="Brazo">
  </div>

  <div class="label izq-antebrazo">
    <input
      type="number"
      step="any"
      name="medida[izq_antebrazo]"
      value="<?= htmlspecialchars($val['izq_antebrazo']) ?>"
      placeholder="Antebrazo">
  </div>

  <div class="label izq-muneca">
    <input
      type="number"
      step="any"
      name="medida[izq_muneca]"
      value="<?= htmlspecialchars($val['izq_muneca']) ?>"
      placeholder="Muñeca">
  </div>

  <div class="label izq-muslo">
    <input
      type="number"
      step="any"
      name="medida[izq_muslo_medio]"
      value="<?= htmlspecialchars($val['izq_muslo_medio']) ?>"
      placeholder="Muslo medio">
  </div>

  <div class="label izq-pantorrilla">
    <input
      type="number"
      step="any"
      name="medida[izq_pantorrilla]"
      value="<?= htmlspecialchars($val['izq_pantorrilla']) ?>"
      placeholder="Pantorrilla">
  </div>

  <div class="label der-brazo">
    <input
      type="number"
      step="any"
      name="medida[der_brazo]"
      value="<?= htmlspecialchars($val['der_brazo']) ?>"
      placeholder="Brazo">
  </div>

  <div class="label der-antebrazo">
    <input
      type="number"
      step="any"
      name="medida[der_antebrazo]"
      value="<?= htmlspecialchars($val['der_antebrazo']) ?>"
      placeholder="Antebrazo">
  </div>

  <div class="label der-muneca">
    <input
      type="number"
      step="any"
      name="medida[der_muneca]"
      value="<?= htmlspecialchars($val['der_muneca']) ?>"
      placeholder="Muñeca">
  </div>

  <div class="label der-muslo">
    <input
      type="number"
      step="any"
      name="medida[der_muslo_medio]"
      value="<?= htmlspecialchars($val['der_muslo_medio']) ?>"
      placeholder="Muslo medio">
  </div>

  <div class="label der-pantorrilla">
    <input
      type="number"
      step="any"
      name="medida[der_pantorrilla]"
      value="<?= htmlspecialchars($val['der_pantorrilla']) ?>"
      placeholder="Pantorrilla">
  </div>
</div>


      <!-- DATOS GENERALES -->
      <div class="col-md-4">
		  
		<div class="mb-3">
          <label for="fecha" class="form-label">Fecha del registro:</label>
          <input id="fecha" name="fecha" type="text"
                 class="form-control"
                 value="<?= htmlspecialchars($val['fecha']) ?>" >
        </div>
		  
		  <div class="mb-3">
          <label for="edad" class="form-label">Edad</label>
			<div class="input-group">
          <input id="edad" name="edad" type="text" step="any"
                 class="form-control" placeholder="1.75"
                 value="<?php echo $edad;?>" readonly>
			<span class="input-group-text">Años</span>
        </div>
        </div>
		  
		  <div class="mb-3">
          <label for="estatura" class="form-label">Estatura</label>
			<div class="input-group">
          <input id="estatura" name="estatura" type="text" step="any"
                 class="form-control" placeholder="1.75"
                 value="<?= htmlspecialchars($val['estatura']) ?>" >
			<span class="input-group-text">m</span>
        </div>
        </div>
		  
        <div class="mb-3">
          <label for="peso" class="form-label">Peso</label>
		<div class="input-group">
          <input id="peso" name="peso" type="text" step="any"
                 class="form-control" placeholder="55.7" lang="en"
                 value="<?= htmlspecialchars($val['peso']) ?>" >
			<span class="input-group-text">Kg</span>
        </div>
        </div>
		  
		  
		  
        
        
		  
		  <!-- IMC -->
<div class="mb-3">
  <label for="imc" class="form-label">IMC</label>
	<div class="input-group">
  <input id="imc" name="imc"
         type="text" step="any"
         class="form-control"
         value="<?= htmlspecialchars($val['imc']) ?>">
		<span class="input-group-text">kg/m²</span>
</div>
</div>

<!-- % Grasa Corporal -->
<div class="mb-3">
  <label for="porcentaje_grasa_corporal" class="form-label">Porcentaje de Grasa Corporal</label>
	<div class="input-group">
  <input id="porcentaje_grasa_corporal" name="porcentaje_grasa_corporal"
         type="text" step="any" class="form-control"
         value="<?= htmlspecialchars($val['porcentaje_grasa_corporal']) ?>">
		<span class="input-group-text">%</span>
</div>
</div>

<!-- % Músculo Esquelético -->
<div class="mb-3">
  <label for="porcentaje_musculo_esqueletico" class="form-label">Porcentaje de Músculo Esquelético</label>
	<div class="input-group">
  <input id="porcentaje_musculo_esqueletico" name="porcentaje_musculo_esqueletico"
         type="text" step="any" class="form-control"
         value="<?= htmlspecialchars($val['porcentaje_musculo_esqueletico']) ?>">
		<span class="input-group-text">%</span>
</div>
</div>

<!-- Metabolismo Basal -->
<div class="mb-3">
  <label for="metabolismo_basal" class="form-label">Metabolismo Basal</label>
	<div class="input-group">
  <input id="metabolismo_basal" name="metabolismo_basal"
         type="text" step="1" class="form-control"
         value="<?= htmlspecialchars($val['metabolismo_basal']) ?>">
		<span class="input-group-text">kcal</span>
</div>
</div>

<!-- Edad Corporal -->
<div class="mb-3">
  <label for="edad_corporal" class="form-label">Edad Corporal</label>
	<div class="input-group">
  <input id="edad_corporal" name="edad_corporal"
         type="text" step="1" class="form-control"
         value="<?= htmlspecialchars($val['edad_corporal']) ?>">
		<span class="input-group-text">años</span>
</div>
</div>

<!-- Nivel Grasa Visceral -->
<div class="mb-3">
  <label for="nivel_grasa_visceral" class="form-label">Nivel Grasa Visceral:</label>
	<div class="input-group">
  <input id="nivel_grasa_visceral" name="nivel_grasa_visceral"
         type="text" step="1" class="form-control"
         value="<?= htmlspecialchars($val['nivel_grasa_visceral']) ?>">
		<span class="input-group-text">nivel</span>
</div>
</div>

		  
        <div class="mb-3">
          <label for="observaciones" class="form-label">Observaciones:</label>
          <textarea id="observaciones" name="observaciones"
                    class="form-control" rows="4"
                    placeholder="Notas…"><?= htmlspecialchars($val['observaciones']) ?></textarea>
        </div>
        <div class="text-center mt-4">
          <div class="text-center mt-4">
  <button type="submit" class="btn btn-primary">
    <i class="fa fa-save"></i> Actualizar
  </button>
  <a href="javascript:history.back()" class="btn btn-secondary">
    <i class="fa fa-times"></i> Cancelar
  </a>
</div>

			
			
			
			
        </div>
      </div>
    </form>
  </div>

  <?php include('../inc/menu-footer.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
    <?php if(!empty($_SESSION['success'])): ?>
    Swal.fire({
      icon: 'success',
      title: '¡Listo!',
      text: '<?= $_SESSION['success'] ?>'
    });
    <?php unset($_SESSION['success']); endif; ?>
    <?php if(!empty($_SESSION['error'])): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '<?= $_SESSION['error'] ?>'
    });
    <?php unset($_SESSION['error']); endif; ?>
  </script>
	
	<script>
  document.getElementById('btnDeleteValoracion').addEventListener('click', function(){
    Swal.fire({
      title: '¿Estás seguro?',
      text: 'Esta acción marcará esta valoración como borrada.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, borrar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('delete_valoracion.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: <?= $vid ?> })
        })
        .then(res => res.json())
        .then(json => {
          if (json.status === 'success') {
            Swal.fire('Borrado', json.message, 'success')
              .then(() => window.location = 'index.php'); // o donde listar
          } else {
            Swal.fire('Error', json.message, 'error');
          }
        })
        .catch(() => Swal.fire('Error', 'No se pudo borrar.', 'error'));
      }
    });
  });
</script>

	<script>
$(function(){
  // 1) Género y edad real (inyecta desde PHP)
  const gender  = '<?= strtolower($cli['genero'] ?? "masculino") ?>' === 'femenino'
                ? 'femenino' : 'masculino';
  const realAge = <?= isset($edad) ? $edad : 0 ?>;

  // 2) Funciones de clasificación
  const classify = {
    peso: v => {
      const h = parseFloat($('#estatura').val());
      if (!v || isNaN(v) || !h || isNaN(h)) return null;
      return classify.imc(v/(h*h));
    },
    imc: v => {
      if (!v || isNaN(v)) return null;
      if (v < 18.5) return 'bajo';
      if (v < 25)   return 'perfecto';
      if (v < 30)   return 'medio';
                     return 'alto';
    },
    porcentaje_grasa_corporal: v => {
      if (!v || isNaN(v)) return null;
      if (gender==='femenino') {
        if (v<16) return 'bajo';
        if (v<24) return 'perfecto';
        if (v<31) return 'medio';
                  return 'alto';
      } else {
        if (v<8)  return 'bajo';
        if (v<20) return 'perfecto';
        if (v<25) return 'medio';
                  return 'alto';
      }
    },
    porcentaje_musculo_esqueletico: v => {
      if (!v || isNaN(v)) return null;
      if (gender==='femenino') {
        if (v<23) return 'bajo';
        if (v<30) return 'perfecto';
        if (v<36) return 'medio';
                  return 'alto';
      } else {
        if (v<33) return 'bajo';
        if (v<39) return 'perfecto';
        if (v<46) return 'medio';
                  return 'alto';
      }
    },
    nivel_grasa_visceral: v => {
      if (!v || isNaN(v)) return null;
      if (v<=1)    return 'bajo';
      if (v<=9)    return 'perfecto';
      if (v<=15)   return 'medio';
                     return 'alto';
    },
    edad_corporal: v => {
      if (!v || isNaN(v)) return null;
      const diff = v - realAge;
      if (diff < -3)    return 'bajo';
      if (diff <= 3)    return 'perfecto';
      if (diff <= 10)   return 'medio';
                        return 'alto';
    }
  };

  // 3) Montar wrappers y listeners para cada campo
  $.each(classify, function(name, fn){
    const $field = $(`input[name="${name}"]`);
    if (!$field.length) return;
    const $grp = $field.closest('.input-group');
    if (!$grp.length) return;

    // envolver en .input-level y añadir icono
    $grp.wrap('<div class="input-level"></div>');
    $grp.parent().append('<i class="level-icon fa"></i>');

    // al cambiar
    $field.on('input', function(){
      const val   = parseFloat(this.value);
      const nivel = fn(val);
      const $wrap = $grp.parent();
      const $icon = $wrap.find('.level-icon');

      // limpiar clases previas
      $wrap.removeClass('bajo perfecto medio alto');
      $icon.removeClass('bajo perfecto medio alto fa-arrow-down fa-arrow-right fa-arrow-up fa-flip-horizontal');

      if (!nivel) return;

      // aplicar estilo
      $wrap.addClass(nivel);
      $icon.addClass(nivel);

      // flecha
      let arrow = 'fa-arrow-right';
      if (nivel==='bajo')  arrow = 'fa-arrow-down';
      if (nivel==='medio') arrow = 'fa-arrow-up';
      if (nivel==='alto')  {
        arrow = 'fa-arrow-up';
        $icon.addClass('fa-flip-horizontal');
      }
      $icon.addClass(`fa ${arrow}`);
    });
  });

  // 4) Recalcular IMC cuando cambie peso o estatura
  /*$('#peso, #estatura').on('input', function(){
    const w   = parseFloat($('#peso').val());
    const h   = parseFloat($('#estatura').val());
    const imc = (w>0 && h>0) ? w/(h*h) : NaN;
    $('input[name="imc"]')
      .val(isNaN(imc) ? '' : imc.toFixed(2))
      .trigger('input');
    $('input[name="peso"]').trigger('input');
  });*/

  // 5) —¡IMPORTANTE!— disparar todos los inputs al cargar la página
  // 5) —¡IMPORTANTE!— disparar todos los inputs al cargar la página
['peso','imc','porcentaje_grasa_corporal',
 'porcentaje_musculo_esqueletico',
 'nivel_grasa_visceral','edad_corporal']
  .forEach(name => $(`input[name="${name}"]`).trigger('input'));

});
</script>



<script>
document.getElementById('btnSendWa').addEventListener('click', ()=>{
  Swal.fire({
      title:'¿Enviar la valoración por WhatsApp?',
      icon:'question',
      showCancelButton:true,
      confirmButtonText:'Sí, enviar',
      cancelButtonText:'Cancelar'
  }).then(r=>{
     if(!r.isConfirmed) return;

     Swal.showLoading();
     fetch('../../whatsapp/valoracion.php', {           /* misma carpeta del edit */
        method : 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body   : 'val_id='+encodeURIComponent(<?= $vid ?>)+
                 '&cli_id='+encodeURIComponent(<?= $cli['id'] ?>)
     })
     .then(r=>r.json())
     .then(j=>{
        Swal.close();
        if(j.status==='success'){
            Swal.fire('Enviado',
                      'La valoración se envió al WhatsApp del cliente.',
                      'success');
        }else{
            Swal.fire('Error', j.msg || 'No se pudo enviar.', 'error');
        }
     })
     .catch(()=>Swal.fire('Error',
                 'No se pudo contactar con el servidor.', 'error'));
  });
});
</script>
	
	<script>
		
	const fpFecha = flatpickr("#fecha", {
    dateFormat : "Y-m-d",
    altInput   : true,
    altFormat  : "d \\de F \\de Y",
    locale     : "es",
    maxDate    : "today"
  });
	</script>


</body>
</html>


