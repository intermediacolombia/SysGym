<?php
// new.php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Manejar Valoraciones';
include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8",
                   $dbuser,$dbpass,
                   [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch(PDOException $e){
    die("Error BD: ".$e->getMessage());
}

// 1) Si viene POST: guardamos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // validamos mínimos
    if (empty($_POST['cliente_id']) || !is_numeric($_POST['cliente_id'])
        || !isset($_POST['peso'], $_POST['estatura'], $_POST['fecha'])) {
        $_SESSION['error'] = 'Faltan datos obligatorios.';
        header('Location: index.php');
        exit;
    }

    $c        = (int) $_POST['cliente_id'];
    $peso     = floatval($_POST['peso']);
    $estatura = floatval($_POST['estatura']);
    $fecha    = $_POST['fecha'];
    $obs      = trim($_POST['observaciones']);

    // Nuevos campos métricos
    $imc                       = isset($_POST['imc'])                       ? floatval($_POST['imc'])                       : null;
    $porcentaje_grasa          = isset($_POST['porcentaje_grasa_corporal'])   ? floatval($_POST['porcentaje_grasa_corporal']) : null;
    $porcentaje_musculo        = isset($_POST['porcentaje_musculo_esqueletico'])? floatval($_POST['porcentaje_musculo_esqueletico']) : null;
    $metabolismo_basal         = isset($_POST['metabolismo_basal'])          ? intval($_POST['metabolismo_basal'])           : null;
    $edad_corporal             = isset($_POST['edad_corporal'])              ? intval($_POST['edad_corporal'])               : null;
    $nivel_grasa_visceral      = isset($_POST['nivel_grasa_visceral'])       ? intval($_POST['nivel_grasa_visceral'])        : null;

    // campos silueta (sin cambios)
    $campos = [
      'hombros','pecho','abdomen','cintura','cadera',
      'izq_brazo','izq_antebrazo','izq_muneca','izq_muslo_medio','izq_pantorrilla',
      'der_brazo','der_antebrazo','der_muneca','der_muslo_medio','der_pantorrilla'
    ];
    $val = [];
    foreach ($campos as $f) {
      $val[$f] = isset($_POST['medida'][$f]) 
                  ? floatval($_POST['medida'][$f]) 
                  : null;
    }

    // construimos el INSERT incluyendo las nuevas columnas
    $sql = "INSERT INTO valoraciones (
        cliente_id,
        " . implode(',', $campos) . ",
        peso, estatura, fecha, observaciones,
        imc, porcentaje_grasa_corporal, porcentaje_musculo_esqueletico,
        metabolismo_basal, edad_corporal, nivel_grasa_visceral,
        created_at
    ) VALUES (
        :cliente_id,
        :" . implode(",:", $campos) . ",
        :peso, :estatura, :fecha, :obs,
        :imc, :porc_grasa, :porc_musculo, :met_basal, :edad_corporal, :niv_grasa,
        NOW()
    )";

    $stmt = $pdo->prepare($sql);

    // bind de parámetros
    $params = [
      ':cliente_id' => $c,
      ':peso'       => $peso,
      ':estatura'   => $estatura,
      ':fecha'      => $fecha,
      ':obs'        => $obs,

      ':imc'            => $imc,
      ':porc_grasa'     => $porcentaje_grasa,
      ':porc_musculo'   => $porcentaje_musculo,
      ':met_basal'      => $metabolismo_basal,
      ':edad_corporal'  => $edad_corporal,
      ':niv_grasa'      => $nivel_grasa_visceral,
    ];

    // agregamos los valores de la silueta
    foreach ($campos as $f) {
      $params[":" . $f] = $val[$f];
    }

    try {
      $stmt->execute($params);
		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$valoracion_id = $pdo->lastInsertId();

$desc = json_encode([
    'valoracion_id' => $valoracion_id,
    'cliente_id'    => $c,
    'accion'        => 'Valoración creada',
    'peso'          => $peso,
    'estatura'      => $estatura,
    'fecha'         => $fecha,
    'imc'           => $imc,
    'porcentaje_grasa_corporal'      => $porcentaje_grasa,
    'porcentaje_musculo_esqueletico' => $porcentaje_musculo,
    'metabolismo_basal'              => $metabolismo_basal,
    'edad_corporal'                  => $edad_corporal,
    'nivel_grasa_visceral'           => $nivel_grasa_visceral,
    'usuario'       => $_SESSION['user']['nombre'] ?? 'Desconocido'
], JSON_UNESCAPED_UNICODE);

log_action('Crear valoración', $desc, 'Valoraciones');
// END LOGS

		
		
      $_SESSION['success'] = 'Valoración guardada correctamente.';
    } catch (PDOException $e) {
      $_SESSION['error'] = 'Error guardando: ' . $e->getMessage();
    }

    header('Location: index.php');
    exit;
}


// 2) Si no hay cliente_id: formulario de búsqueda
if (empty($_GET['cliente_id'])) {
    $q = $_GET['q'] ?? '';
    $clientes = [];
    if ($q!=='') {
      $like = "%$q%";
      $stmt = $pdo->prepare(
        "SELECT id,identificacion,nombres,apellidos, genero 
         FROM clientes 
         WHERE borrado=0 
           AND (identificacion LIKE :lk OR nombres LIKE :lk OR apellidos LIKE :lk)
         LIMIT 20"
      );
      $stmt->execute([':lk'=>$like]);
      $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
	
	
	// justo después de la conexión PDO, antes de cualquier salida HTML
/*if (isset($_GET['ajax']) && $_GET['ajax'] === 'clientes') {
    $q = $_GET['q'] ?? '';
    $like = "%$q%";
    $stmt = $pdo->prepare(
      "SELECT id, identificacion, nombres, apellidos, estado 
         FROM clientes 
         WHERE borrado = 0 
           AND (identificacion LIKE :lk OR nombres LIKE :lk OR apellidos LIKE :lk) AND estado = 'activo'
         LIMIT 20"
    );
    $stmt->execute([':lk' => $like]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}*/
	
	// justo después de la conexión PDO, antes de cualquier salida HTML
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clientes') {
    $q    = $_GET['q'] ?? '';
    $like = "%$q%";
    $stmt = $pdo->prepare("
      SELECT 
        id,
        identificacion,
        nombres,
        apellidos,
        estado
      FROM clientes
      WHERE borrado = 0
        AND estado = 'activo'
        AND (
          identificacion LIKE :lk
          OR CONCAT(nombres, ' ', apellidos) LIKE :lk
        )
      LIMIT 20
    ");
    $stmt->execute([':lk' => $like]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}


	
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Buscar Cliente</title>
  <?php include('../inc/header.php'); ?>
  <style>
    .search-container { max-width: 600px; margin: 60px auto; }
    .list-group-item + .list-group-item { margin-top: .5rem; }
	 
	 .input-group>:not(:first-child):not(.dropdown-menu):not(.valid-tooltip):not(.valid-feedback):not(.invalid-tooltip):not(.invalid-feedback) {
    background-color: #E21F0C!important;
    color: #fff;
}
  </style>
	
	





</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
	<h1>Nueva Valoración</h1>
	</div>
	</div>
	
  <?php include('../inc/menu.php'); ?>
  <div class="container search-container">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Selecciona un Cliente</h5>

        <?php if(!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger"><?=$_SESSION['error']; unset($_SESSION['error']);?></div>
        <?php endif; ?>

        <!-- campo de búsqueda en tiempo real -->
        <div class="mb-4 input-group input-group-sm">
          <input 
            id="searchInput"
            type="text" 
            class="form-control" 
            placeholder="Documento, Nombre o Apellidos" 
            autocomplete="off">
          <button id="clearBtn" class="btn btn-outline-secondary" type="button">&times;</button>
        </div>

        <!-- contenedor donde pintaremos resultados -->
        <ul id="resultsList" class="list-group list-group-flush"></ul>
      </div>
    </div>
  </div>
  <?php include('../inc/menu-footer.php'); ?>

  <!-- JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(function(){
      let timer = null;
      const $input = $('#searchInput');
      const $results = $('#resultsList');
      const $clear  = $('#clearBtn');

      function render(items) {
        if (!items.length) {
          $results.html('<li class="list-group-item text-center text-muted">No se encontraron clientes.</li>');
          return;
        }
        $results.empty();
        items.forEach(c => {
          $results.append(`
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong>${c.identificacion}</strong><br>
                ${c.nombres} ${c.apellidos}
              </div>
              <a href="new.php?cliente_id=${c.id}"
                 class="btn btn-outline-success btn-sm">Seleccionar</a>
            </li>
          `);
        });
      }

      $input.on('input', function(){
        clearTimeout(timer);
        const q = $(this).val().trim();
        if (!q) {
          $results.empty();
          return;
        }
        timer = setTimeout(() => {
          $.getJSON('new.php', { ajax: 'clientes', q }, render);
        }, 300);
      });

      $clear.on('click', function(){
        $input.val('').trigger('input');
      });
    });
  </script>
</body>
</html>
<?php
    exit;
}

// 3) Si llegamos aquí, hay cliente_id: mostramos el formulario
$cid = (int)$_GET['cliente_id'];
// cargar datos del cliente para mostrar su nombre arriba...
$stmt = $pdo->prepare("
    SELECT nombres, apellidos, genero, fecha_nacimiento, estado, borrado
    FROM clientes
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $cid]);
$cli = $stmt->fetch(PDO::FETCH_ASSOC);

//$hoy = date('Y-m-d');

// Después de hacer fetch() de $cli:
$edad = '';
if (!empty($cli['fecha_nacimiento'])) {
    $nac = new DateTime($cli['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $nac->diff($hoy)->y;
}



// si no existe, está borrado o inactivo, redirige con error
if (
    !$cli
    || $cli['borrado'] == 1
    || strtolower($cli['estado']) !== 'activo'
) {
    $_SESSION['error'] = 'No se puede realizar la valoración: cliente inactivo o borrado.';
    header('Location: index.php');
    exit;
}

$silueta = (strtolower($cli['genero']) === 'femenino')
  ? '/admin/images/female2.png?'.time()
  : '/admin/images/male2.png?'.time();

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Medidas Corporales</title>
  <?php include('../inc/header.php'); ?>
  <!-- Bootstrap CSS cargado desde tu header -->
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
      <h1>Nueva Valoración: <?= htmlspecialchars("{$cli['nombres']} {$cli['apellidos']}") ?></h1>
      
    </div>
  </div>

  <?php include('../inc/menu.php'); ?>

  <div class="container mt-4">
    <form method="post" action="new.php" class="measurements-layout">
      <input type="hidden" name="cliente_id" value="<?= $cid ?>">

      <!-- SILUETA -->
      <div class="wrapper">
        <img src="<?= $silueta ?>" alt="Silueta corporal">
        <div class="label hombros"><input type="number" step="any" name="medida[hombros]" placeholder="Hombros"></div>
        <div class="label pecho"><input type="number" step="any" name="medida[pecho]" placeholder="Pecho"></div>
        <div class="label abdomen"><input type="number" step="any"step="any" name="medida[abdomen]" placeholder="Abdomen"></div>
        <div class="label cintura"><input type="number" step="any"step="any" name="medida[cintura]" placeholder="Cintura"></div>
        <div class="label cadera"><input type="number" step="any"step="any" name="medida[cadera]" placeholder="Cadera"></div>
        <div class="label izq-brazo"><input type="number" step="any"step="any" name="medida[izq_brazo]" placeholder="Brazo"></div>
        <div class="label izq-antebrazo"><input type="number" step="any"step="any" name="medida[izq_antebrazo]" placeholder="Antebrazo"></div>
        <div class="label izq-muneca"><input type="number" step="any"step="any" name="medida[izq_muneca]" placeholder="Muñeca"></div>
        <div class="label izq-muslo"><input type="number" step="any"step="any" name="medida[izq_muslo_medio]" placeholder="Muslo medio"></div>
        <div class="label izq-pantorrilla"><input type="number" step="any"step="any" name="medida[izq_pantorrilla]" placeholder="Pantorrilla"></div>
        <div class="label der-brazo"><input type="number" step="any"step="any" name="medida[der_brazo]" placeholder="Brazo"></div>
        <div class="label der-antebrazo"><input type="number" step="any"step="any" name="medida[der_antebrazo]" placeholder="Antebrazo"></div>
        <div class="label der-muneca"><input type="number" step="any"step="any" name="medida[der_muneca]" placeholder="Muñeca"></div>
        <div class="label der-muslo"><input type="number" step="any"step="any" name="medida[der_muslo_medio]" placeholder="Muslo medio"></div>
        <div class="label der-pantorrilla"><input type="number" step="any"step="any" name="medida[der_pantorrilla]" placeholder="Pantorrilla"></div>
      </div>

      <!-- DATOS GENERALES -->

  <!-- columna de datos generales -->
  <div class="col-md-4">
	  
	  
    <!-- Fecha -->
    <div class="mb-3">
      <label for="fecha" class="form-label">Fecha del registro</label>
      <input id="fecha" name="fecha" type="text" class="form-control" value="<?= htmlspecialchars($hoy, ENT_QUOTES) ?>">
    </div>
	  
	  <!-- Estatura -->
    <div class="mb-3">
      <label for="edad" class="form-label">Edad</label>
      <div class="input-group">
        <input id="edad" name="edad" type="number" step="any" class="form-control" value="<?php echo $edad;?>" readonly>
        <span class="input-group-text">Años</span>
      </div>
    </div>
	  
	  <div class="mb-3">
      <label for="estatura" class="form-label">Estatura</label>
      <div class="input-group">
        <input id="estatura" name="estatura" type="number" step="any" class="form-control" >
        <span class="input-group-text">m</span>
      </div>
    </div>
	  
    <!-- Peso -->
    <div class="mb-3">
      <label for="peso" class="form-label">Peso</label>
      <div class="input-group">
        <input id="peso" name="peso" type="number" step="any" class="form-control" >
        <span class="input-group-text">Kg</span>
      </div>
    </div>
	  




    

	  
  <!-- IMC -->
  <div class="mb-3">
    <label for="imc" class="form-label">IMC</label>
    <div class="input-group">
      <input id="imc" name="imc" type="number" step="any" class="form-control">
      <span class="input-group-text">kg/m²</span>
    </div>
  </div>

  <!-- Porcentaje de Grasa Corporal -->
  <div class="mb-3">
    <label for="porcentaje_grasa_corporal" class="form-label">Porcentaje de Grasa Corporal</label>
    <div class="input-group">
      <input id="porcentaje_grasa_corporal" name="porcentaje_grasa_corporal" type="number" step="any" class="form-control">
      <span class="input-group-text">%</span>
    </div>
  </div>

  <!-- Porcentaje de Músculo Esquelético -->
  <div class="mb-3">
    <label for="porcentaje_musculo_esqueletico" class="form-label">Porcentaje de Músculo Esquelético</label>
    <div class="input-group">
      <input id="porcentaje_musculo_esqueletico" name="porcentaje_musculo_esqueletico" type="number" step="any" class="form-control">
      <span class="input-group-text">%</span>
    </div>
  </div>

  <!-- Metabolismo Basal -->
  <div class="mb-3">
    <label for="metabolismo_basal" class="form-label">Metabolismo Basal</label>
    <div class="input-group">
      <input id="metabolismo_basal" name="metabolismo_basal" type="number" step="any" class="form-control">
      <span class="input-group-text">kcal</span>
    </div>
  </div>

  <!-- Edad Corporal -->
  <div class="mb-3">
    <label for="edad_corporal" class="form-label">Edad Corporal</label>
    <div class="input-group">
      <input id="edad_corporal" name="edad_corporal" type="number" step="1" class="form-control">
      <span class="input-group-text">años</span>
    </div>
  </div>

  <!-- Nivel de Grasa Visceral -->
  <div class="mb-3">
    <label for="nivel_grasa_visceral" class="form-label">Nivel de Grasa Visceral</label>
    <div class="input-group">
      <input id="nivel_grasa_visceral" name="nivel_grasa_visceral" type="number" step="any" class="form-control">
      <span class="input-group-text">nivel</span>
    </div>
  </div>

  <!-- Observaciones -->
  <div class="mb-3">
    <label for="observaciones" class="form-label">Observaciones</label>
    <textarea id="observaciones" name="observaciones" class="form-control" rows="4"></textarea>
  </div>

  <!-- Botón Guardar -->
  <div class="text-center mt-4">
    <div class="text-center mt-4">
  <button type="submit" class="btn btn-primary">
    <i class="fa fa-save"></i> Guardar
  </button>
  <a href="index.php" class="btn btn-secondary">
    <i class="fa fa-times"></i> Cancelar
  </a>
</div>

  </div>
</div>

    </form>
  </div>

  <?php include('../inc/menu-footer.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	
	<?php if(!empty($_SESSION['success'])): ?>
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <script>
        Swal.fire({
          icon: 'success',
          title: '¡Listo!',
          text: '<?= $_SESSION['success'] ?>'
        });
      </script>
      <?php unset($_SESSION['success']); endif; ?>

    <?php if(!empty($_SESSION['error'])): ?>
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <script>
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: '<?= $_SESSION['error'] ?>'
        });
      </script>
      <?php unset($_SESSION['error']); endif; ?>
	
	<script>
$(function(){
  // Género y edad real (inyectar desde PHP)
  const gender = '<?= strtolower($cli['genero'] ?? "masculino") ?>' === 'femenino'
               ? 'femenino' : 'masculino';
  const realAge = <?= isset($edad) ? $edad : 0 ?>;

  // Funciones de clasificación
  const classify = {
    imc: v => {
      if (!v||isNaN(v)) return null;
      if (v < 18.5) return 'bajo';
      if (v < 25)   return 'perfecto';
      if (v < 30)   return 'medio';
                     return 'alto';
    },
    peso: v => { // usa IMC internamente
      const h = parseFloat($('#estatura').val());
      if (!v||isNaN(v)||!h||isNaN(h)) return null;
      return classify.imc(v/(h*h));
    },
    porcentaje_grasa_corporal: v => {
      if (!v||isNaN(v)) return null;
      if (gender==='femenino') {
        if (v < 16) return 'bajo';
        if (v < 24) return 'perfecto';
        if (v < 31) return 'medio';
                    return 'alto';
      } else {
        if (v < 8)  return 'bajo';
        if (v < 20) return 'perfecto';
        if (v < 25) return 'medio';
                    return 'alto';
      }
    },
    porcentaje_musculo_esqueletico: v => {
      if (!v||isNaN(v)) return null;
      if (gender==='femenino') {
        if (v < 23) return 'bajo';
        if (v < 30) return 'perfecto';
        if (v < 36) return 'medio';
                    return 'alto';
      } else {
        if (v < 33) return 'bajo';
        if (v < 39) return 'perfecto';
        if (v < 46) return 'medio';
                    return 'alto';
      }
    },
    nivel_grasa_visceral: v => {
      if (!v||isNaN(v)) return null;
      if (v <= 1)    return 'bajo';
      if (v <= 9)    return 'perfecto';
      if (v <= 15)   return 'medio';
                     return 'alto';
    },
    edad_corporal: v => {
      if (!v||isNaN(v)) return null;
      const diff = v - realAge;
      if (diff < -3)      return 'bajo';     // edad corporal significativamente menor
      if (diff <= 3)      return 'perfecto'; // +-3 años
      if (diff <= 10)     return 'medio';    // hasta +10 años
                          return 'alto';     // muy superior
    }
  };

  // Envuelve cada .input-group y añade el listener
  Object.keys(classify).forEach(name => {
    const $input = $(`input[name="${name}"]`);
    const $grp   = $input.closest('.input-group');
    if (!$grp.length) return;
    // crear wrapper
    $grp.wrap('<div class="input-level"></div>');
    $grp.parent().append('<i class="level-icon fa"></i>');

    // listener
    $grp.find('input').on('input', function(){
      const val   = parseFloat(this.value);
      const nivel = classify[name](val);
      const $wrap = $grp.parent();
      const $icon = $wrap.find('.level-icon');

      // limpiar
      $wrap.removeClass('bajo perfecto medio alto');
      $icon .removeClass('bajo perfecto medio alto fa-arrow-down fa-arrow-right fa-arrow-up fa-flip-horizontal');

      if (!nivel) return;

      // aplicar estilos
      $wrap.addClass(nivel);
      $icon.addClass(nivel);

      // escoger flecha
      let arrow = 'fa-arrow-right';
      if (nivel === 'bajo')  arrow = 'fa-arrow-down';
      if (nivel === 'medio') arrow = 'fa-arrow-up';
      if (nivel === 'alto')  {
        arrow = 'fa-arrow-up';
        $icon.addClass('fa-flip-horizontal');
      }
      $icon.addClass(`fa ${arrow}`);
    });
  });

  // Recalcular IMC al cambiar peso o estatura
  $('#peso, #estatura').on('input', function(){
    const h = parseFloat($('#peso').val());
    const w = parseFloat($('#estatura').val());
    const imc = (h>0 && w>0) ? h/(w*w) : NaN;
    $('input[name="imc"]').val(isNaN(imc)? '' : imc.toFixed(2))
                         .trigger('input');
  });
});
</script>
	
	<script>
		
	const fpFecha = flatpickr("#fecha", {
    dateFormat : "Y-m-d",
    altInput   : true,
    altFormat  : "d \\de F \\de Y",
    locale     : "es",
    maxDate    : "today",
    minDate    : "today"
  });
	</script>


</body>
</html>








