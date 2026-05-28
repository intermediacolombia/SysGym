<?php
// Incluir archivo de configuración para la conexión a la base de datos
require_once __DIR__ . '/../inc/config.php';

// Verificar si se proporcionó el ID del cliente
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se proporcionó el ID del cliente.");
}

$id = trim($_GET['id']);

try {
    // Conexión a la base de datos
    

    // Obtener datos del cliente (solo si no está borrado)
    $stmt = db()->prepare("SELECT * FROM clientes WHERE id = :id AND borrado = 0");
    $stmt->execute([':id' => $id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        die("Error: Cliente no encontrado.");
    }

    // Calcular edad si la fecha de nacimiento es válida
    $edad = "";
    if (!empty($cliente['fecha_nacimiento'])) {
        $birthDate = new DateTime($cliente['fecha_nacimiento']);
        $today = new DateTime();
        $edad = $today->diff($birthDate)->y;
    }

    // Obtener datos del plan asignado (si existe)
    $planInfo = null;
    if (!empty($cliente['plan'])) {
        $stmtPlan = db()->prepare("SELECT * FROM planes WHERE id = :plan AND borrado = 0");
        $stmtPlan->execute([':plan' => $cliente['plan']]);
        $planInfo = $stmtPlan->fetch(PDO::FETCH_ASSOC);
    }

    // Validar si la fecha de vencimiento está vencida
    $vencido = false;
    if (!empty($cliente['vencimiento_plan'])) {
        $vencimientoDate = new DateTime($cliente['vencimiento_plan']);
        $today = new DateTime(date('Y-m-d'));
        if ($vencimientoDate < $today) {
            $vencido = true;
        }
    }
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

// Convertir automáticamente el logo a base64 usando la ruta local
$logoPath = $_SERVER['DOCUMENT_ROOT'] . SITE_LOGO;
// Para depurar, puedes descomentar la siguiente línea y verificar la ruta:
// echo "Ruta logo: " . $logoPath; exit;
if (!file_exists($logoPath)) {
    die("Error: No se encontró el logo en la ruta: " . $logoPath);
}
$logoData = base64_encode(file_get_contents($logoPath));
$logoDataURI = 'data:image/png;base64,' . $logoData;


// Determinar el género del cliente
$genero = $cliente['genero'] ?? 'otro'; // Por defecto, asumimos "otro" si no está definido

if ($genero === 'masculino') {
    $titulo = 'el usuario';
	$titulo2 = 'do';
} elseif ($genero === 'femenino') {
    $titulo = 'la usuaria';
	$titulo2 = 'da';
} else {
    $titulo = 'la/el usuario(a)'; // Para casos de género no binario u otro
	$titulo2 = 'do(a)';
}
?>


<?php
function formatearFechaEnEspanol($fecha) {
    // Array para traducir los meses al español
    $meses = [
        'January'   => 'enero',
        'February'  => 'febrero',
        'March'     => 'marzo',
        'April'     => 'abril',
        'May'       => 'mayo',
        'June'      => 'junio',
        'July'      => 'julio',
        'August'    => 'agosto',
        'September' => 'septiembre',
        'October'   => 'octubre',
        'November'  => 'noviembre',
        'December'  => 'diciembre',
    ];

    // Crear un objeto DateTime a partir de la fecha proporcionada
    $fechaObjeto = new DateTime($fecha);

    // Formatear la fecha usando DateTime::format()
    $fechaFormateada = $fechaObjeto->format('d \d\e F \d\e Y');

    // Reemplazar el nombre del mes en inglés por su equivalente en español
    return str_replace(array_keys($meses), array_values($meses), $fechaFormateada);
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certificado de Inscripción</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.5;
      margin: 20px;
      text-align: justify;
      font-size: 13px;
    }
    h1, h4 {
      text-align: center;
      margin-bottom: 20px;
    }
    p {
      margin-bottom: 10px;
    }
    strong {
      color: #000;
    }
    .logo {
      text-align: center;
      margin-bottom: 20px;
    }
    .firma {
      margin-top: 20px;
      text-align: center;
    }
    .firma img {
      max-width: 250px;
      height: auto;
    }
  </style>
</head>
<body>
  <!-- Mostrar el logo usando el data URI generado -->
  <div class="logo">
    <img src="<?php echo $logoDataURI; ?>" width="150" alt="Logo ACTIVGYM">
  </div>

  <h4 style="text-align: center; color: #000;">CERTIFICADO DE VINCULACIÓN</h4>

	
	
<p><strong>A QUIEN CORRESPONDA:</strong></p>
<P>
Por medio del presente, se certifica que <?php echo $titulo;?> <strong style="text-transform: uppercase"><?php echo htmlspecialchars($cliente['nombres'] . " " . $cliente['apellidos']); ?></strong>, identifica<?php echo $titulo2;?> con documento de identidad No. <strong><?php echo htmlspecialchars($cliente['identificacion']); ?></strong>, se encuentra vincula<?php echo $titulo2;?> activamente a nuestro gimnasio <strong>ACTIVGYM</strong> desde el <strong style="text-transform: uppercase"><?php echo formatearFechaEnEspanol($cliente['created_at']);?></strong> y su plan activo corresponde a 
    <strong style="text-transform: uppercase"><?php echo $planInfo ? htmlspecialchars($planInfo['nombre']) : 'Sin Plan Asignado'; ?></strong>, con una vigencia desde el <strong style="text-transform: uppercase"><?php 
  if (!empty($cliente['pago_plan'])) {
      echo formatearFechaEnEspanol($cliente['pago_plan']);
  } else {
      echo 'No registrado';
  }
  ?></strong> hasta el <strong style="text-transform: uppercase"><?php 
  if (!empty($cliente['vencimiento_plan'])) {
      echo formatearFechaEnEspanol($cliente['vencimiento_plan']);
  } else {
      echo 'No registrado';
  }
  ?>
</strong>.
</P>
Durante este tiempo, ha demostrado un compromiso constante con su entrenamiento, asistiendo de manera regular todos los días a nuestras instalaciones. Su participación ha sido destacada y disciplinada, cumpliendo con los horarios y reglamentos del gimnasio.

Este certificado se expide a solicitud del interesado(a) el día <strong style="text-transform: uppercase"><?php 
	
	echo formatearFechaEnEspanol($hoy);
	
?></strong>, con fines que estime convenientes.<br>
<br>


	
  <p style="text-align: left;">
    Atentamente,<br><br>

	<img src="data:image/png;base64,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" width="100px"><br>

    <strong>FRAN ARIAS</strong><br>
    Gerente ActivGym<br>
    Cra. 30 #30-146 a 30-170B<br>
	  
	  Tel. 322 431 8775<br>

	Armenia, Quindío
  </p>

  <!-- Firma (si aplica) -->
  
  
    

</body>
</html>