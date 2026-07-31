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

    $stmt = db()->prepare($sql);
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
	
  <?php echo CONSENT;?>
	
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
  <table style="width:100%;margin-top:30px;">
    <tr>
      <td style="width:50%;padding:10px;vertical-align:bottom;">
        <strong>FIRMA DEL CLIENTE:</strong><br>
        <img src="<?php echo htmlspecialchars($formFirma); ?>" width="200px" alt="Firma cliente">
      </td>
      <td style="width:50%;padding:10px;vertical-align:bottom;text-align:center;">
        <?php if (CONSENT_FIRMA_IMG && file_exists($_SERVER['DOCUMENT_ROOT'] . CONSENT_FIRMA_IMG)): ?>
          <?php
            $firmaPath = $_SERVER['DOCUMENT_ROOT'] . CONSENT_FIRMA_IMG;
            $firmaExt = strtolower(pathinfo($firmaPath, PATHINFO_EXTENSION));
            $firmaMime = ($firmaExt === 'png') ? 'image/png' : 'image/jpeg';
            $firmaURI = 'data:' . $firmaMime . ';base64,' . base64_encode(file_get_contents($firmaPath));
          ?>
          <img src="<?= $firmaURI ?>" width="200px" alt="Firma responsable"><br>
        <?php else: ?>
          <br><br><br>
        <?php endif; ?>
        <?php if (CONSENT_FIRMANTE): ?>
          <strong><?= htmlspecialchars(CONSENT_FIRMANTE) ?></strong><br>
          <span style="font-size:11px;">Responsable</span>
        <?php endif; ?>
      </td>
    </tr>
  </table>
  <!--fin lectura consentimiento-->
</body>
</html>





