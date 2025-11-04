<?php


if (!isset($_GET['id'])) {
    echo "ID del formulario no proporcionado.";
    exit;
}

$formularioId = intval($_GET['id']); // Sanitiza el ID recibido por la URL

// Configuración de la base de datos
require_once __DIR__ . '/../inc/config.php';

try {
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obtener los datos del formulario y cliente relacionado
    $sql = "
        SELECT 
            f.id AS formulario_id,
            f.fecha,
            f.firma_digital_cliente,
            c.id AS cliente_id,
            c.identificacion,
            c.nombres,
            c.apellidos,
            c.direccion,
			c.dialCode,
            c.telefono,
            c.genero,
            c.email,
            c.fecha_nacimiento,
            c.rh,
            c.eps,
            c.fracturas,
            c.alergias,
            c.enfermedades_actuales,
            c.observaciones
        FROM formularios f
        INNER JOIN clientes c ON f.cliente_id = c.id
        WHERE f.id = :formulario_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':formulario_id' => $formularioId]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        echo "No se encontró ningún formulario con el ID proporcionado.";
        exit;
    }

    // Asignar valores del formulario a variables
    $formularioId = $registro['formulario_id'];
    $formFecha = $registro['fecha'];
    $formFirma = $registro['firma_digital_cliente'];

    // Asignar valores del cliente a variables
    $clienteId = $registro['cliente_id'];
    $clienteIdentificacion = $registro['identificacion'];
    $clienteNombres = $registro['nombres'];
    $clienteApellidos = $registro['apellidos'];
    $clienteDireccion = $registro['direccion'];
	$dialCode = $registro['dialCode'];
    $clienteTelefono = $registro['telefono'];
    $clienteGenero = $registro['genero'];
    $clienteEmail = $registro['email'];
    $clienteFechaNacimiento = $registro['fecha_nacimiento'];
    $clienteRh = $registro['rh'];
    $clienteEps = $registro['eps'];
    $clienteFracturas = $registro['fracturas'];
    $clienteAlergias = $registro['alergias'];
    $clienteEnfermedades = $registro['enfermedades_actuales'];
    $clienteObservaciones = $registro['observaciones'];

} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Datos del Formulario y Cliente</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1;
      margin: 20px;
      text-align: justify;
      font-size: 13px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }
    th {
      background-color: #f2f2f2;
    }
    h1 {
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <!-- Mostrar el logo usando el data URI generado -->
  <div style="text-align: center; margin-bottom: 20px;">
    <img src="<?php echo $logoDataURI; ?>" width="150" style="margin: 0; padding: 0;" alt="Logo">
  </div>

  <h4 style="text-align: center; color: #000;">CONSENTIMIENTO INFORMADO</h4>
	<strong>Fecha:</strong> <?php echo $formFecha; ?>
  <p><strong>ACTIV GYM</strong> ofrece servicios para la práctica de la actividad física y ejercicio individualizado, para esto dispone de instalaciones, equipos de alta calidad y talento humano profesional idóneo para guiar dichas prácticas con el fin de brindar seguridad y comodidad a los afiliados.</p>
  <p>Cualquier actividad física conlleva beneficios y algunos riesgos, principalmente de lesiones óseas y musculares, y en forma más rara, problemas cardiacos como infartos, arritmias y la muerte súbita. Con el fin de minimizar al máximo los riesgos y potencializar los beneficios del ejercicio físico, <strong>ACTIV GYM</strong> ofrece como centro de acondicionamiento físico, dirección y acompañamiento de:</p>
  <ul>
    <li>Valoración Física</li>
    <li>Orientación en Programas de Entrenamiento individualizado según aptitud física del afiliado.</li>
    <li>Asesoría Nutricional</li>
    <li>Equipos en óptimas condiciones</li>
  </ul>
  <p><strong>ACTIV GYM</strong>, no se hace responsable por los accidentes o enfermedades derivados de la omisión de las recomendaciones realizadas por el equipo interdisciplinario de profesionales ACTIV GYM en la práctica de la actividad física en sus instalaciones, responsabilidad que es asumida en su totalidad por el afiliado.</p>
  <p>En forma libre y voluntaria yo, como aparezco identificado al pie de este documento, declaro y certifico que entiendo que la actividad física que practico implica la posibilidad de sufrir lesiones y/o riesgos, según fui informado en detalle por parte de <strong>ACTIV GYM</strong>.</p>
  <p>Estoy de acuerdo con las recomendaciones, obligaciones y sugerencias arriba descritas en cuanto al cuidado de mi salud y las formas a seguir previas y durante la actividad y entiendo que no me eximo de la responsabilidad de atender tales recomendaciones y respetarlas para reducir todo riesgo al máximo posible y es mi obligación informar inmediatamente al personal asistencial o de profesores sobre dolor, incomodidad, fatiga u otro síntoma que considere que pueda afectar mi salud o la ponga en riesgo, los mismos que puedan presentarse antes, durante y después de mi participación en cualquiera de las actividades y servicios ofrecidos por ACTIV GYM.</p>
  <strong>
    <p>Exonero de toda responsabilidad a <strong>ACTIV GYM</strong>, por cualquier situación desencadenada por el NO cumplimiento de la instrucción que me fue dada y decido no asistir a la valoración clínica de <strong>ACTIV GYM</strong> que determina mi aptitud física para iniciar mi entrenamiento.</p>
    <p><strong>Nota:</strong> Después de cancelar, <strong>ACTIV GYM</strong> no hace devolución de dinero ni transferencias a otras personas y tampoco nos hacemos responsables de sus objetos personales.</p>
    <p>Declaro que he leído, entiendo y acepto los términos de este acuerdo en su totalidad.</p>
  </strong>
  <table border="1">
    <tr>
      <td><strong>NOMBRE:</strong></td>
      <td><?php echo htmlspecialchars($clienteNombres); ?> <?php echo htmlspecialchars($clienteApellidos); ?></td>
    </tr>
    <tr>
      <td><strong>DOCUMENTO DE IDENTIDAD:</strong></td>
      <td><?php echo htmlspecialchars($clienteIdentificacion); ?></td>
    </tr>
    <tr>
      <td><strong>DIRECCION:</strong></td>
      <td><?php echo htmlspecialchars($clienteDireccion); ?></td>
    </tr>
    <tr>
      <td><strong>TEL/ CELULAR:</strong></td>
      <td>+<?php echo htmlspecialchars($dialCode); ?><?php echo htmlspecialchars($clienteTelefono); ?></td>
    </tr>
    <tr>
      <td><strong>EMAIL:</strong></td>
      <td><?php echo str_replace('@', '&#64;', htmlspecialchars($clienteEmail)); ?></td>
    </tr>
  </table>
  <strong>FIRMA:</strong><br>
  <img src="<?php echo htmlspecialchars($formFirma); ?>" width="250px" alt="Firma">
  <!--fin lectura consentimiento-->
</body>
</html>





