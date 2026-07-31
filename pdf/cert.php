<?php
require_once __DIR__ . '/../inc/config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se proporcionó el ID del cliente.");
}

$id = trim($_GET['id']);

try {
    $stmt = db()->prepare("SELECT * FROM clientes WHERE id = :id AND borrado = 0");
    $stmt->execute([':id' => $id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        die("Error: Cliente no encontrado.");
    }

    $planInfo = null;
    if (!empty($cliente['plan'])) {
        $stmtPlan = db()->prepare("SELECT * FROM planes WHERE id = :plan AND borrado = 0");
        $stmtPlan->execute([':plan' => $cliente['plan']]);
        $planInfo = $stmtPlan->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

$logoDataURI = '';
$_siteLogo = defined('SITE_LOGO') ? SITE_LOGO : '';
if ($_siteLogo) {
    $logoPath = $_SERVER['DOCUMENT_ROOT'] . $_siteLogo;
    if (file_exists($logoPath) && is_file($logoPath)) {
        $logoData    = file_get_contents($logoPath);
        $logoExt     = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $logoMime    = in_array($logoExt, ['jpg','jpeg']) ? 'image/jpeg' : 'image/png';
        $logoDataURI = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
    }
}

$genero = $cliente['genero'] ?? 'otro';
if ($genero === 'masculino') { $titulo = 'el usuario'; $titulo2 = 'do'; }
elseif ($genero === 'femenino') { $titulo = 'la usuaria'; $titulo2 = 'da'; }
else { $titulo = 'la/el usuario(a)'; $titulo2 = 'do(a)'; }

$_certTemplate  = defined('CERT_TEMPLATE')  ? CERT_TEMPLATE  : '';
$_certFirmante  = defined('CERT_FIRMANTE')  ? CERT_FIRMANTE  : '';
$_certCargo     = defined('CERT_CARGO')     ? CERT_CARGO     : '';
$_nameGym       = defined('NAME_GYM')       ? NAME_GYM       : '';
$_telGym        = defined('TEL_GYM')        ? TEL_GYM        : '';
$_direccionGym  = defined('DIRECCION_GYM')  ? DIRECCION_GYM  : '';
$_ciudadGym     = defined('CIUDAD_GYM')     ? CIUDAD_GYM     : '';

$certFirmaURI = '';
$_certFirmaImg = defined('CERT_FIRMA_IMG') ? CERT_FIRMA_IMG : '';
if ($_certFirmaImg) {
    $firmaPath = $_SERVER['DOCUMENT_ROOT'] . $_certFirmaImg;
    if (file_exists($firmaPath)) {
        $ext  = strtolower(pathinfo($firmaPath, PATHINFO_EXTENSION));
        $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
        $certFirmaURI = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($firmaPath));
    }
}

function formatearFechaEnEspanol($fecha) {
    $meses = [
        'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
        'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
        'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
        'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre',
    ];
    $obj = new DateTime($fecha);
    return str_replace(array_keys($meses), array_values($meses), $obj->format('d \d\e F \d\e Y'));
}

function resolverCertTemplate($template, $cliente, $planInfo, $titulo, $titulo2) {
    $fechaVinculacion = !empty($cliente['created_at']) ? formatearFechaEnEspanol(substr($cliente['created_at'], 0, 10)) : 'No registrada';
    $fechaInicio      = !empty($cliente['pago_plan'])        ? strtoupper(formatearFechaEnEspanol($cliente['pago_plan']))        : 'No registrado';
    $fechaFin         = !empty($cliente['vencimiento_plan']) ? strtoupper(formatearFechaEnEspanol($cliente['vencimiento_plan'])) : 'No registrado';
    $plan             = $planInfo ? htmlspecialchars($planInfo['nombre']) : 'Sin Plan Asignado';

    $vars = [
        '{nombres}'           => htmlspecialchars($cliente['nombres']),
        '{apellidos}'         => htmlspecialchars($cliente['apellidos']),
        '{nombre_completo}'   => htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']),
        '{identificacion}'    => htmlspecialchars($cliente['identificacion']),
        '{nombre_gym}'        => htmlspecialchars($GLOBALS['_nameGym']),
        '{fecha_vinculacion}' => $fechaVinculacion,
        '{plan_activo}'       => $plan,
        '{fecha_inicio_plan}' => $fechaInicio,
        '{fecha_fin_plan}'    => $fechaFin,
        '{fecha_expedicion}'  => strtoupper(formatearFechaEnEspanol(date('Y-m-d'))),
        '{genero}'            => $titulo,
        '{genero2}'           => $titulo2,
    ];
    return str_replace(array_keys($vars), array_values($vars), $template);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Certificado de Vinculación</title>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.5; margin: 20px; text-align: justify; font-size: 13px; }
    h4 { text-align: center; margin-bottom: 20px; }
    p { margin-bottom: 10px; }
    strong { color: #000; }
    .logo { text-align: center; margin-bottom: 20px; }
  </style>
</head>
<body>

  <?php if ($logoDataURI): ?>
  <div class="logo">
    <img src="<?php echo $logoDataURI; ?>" width="150" alt="Logo">
  </div>
  <?php endif; ?>

  <h4 style="color: #000;">CERTIFICADO DE VINCULACIÓN</h4>

  <?php if (!empty($_certTemplate)): ?>
    <?php echo resolverCertTemplate($_certTemplate, $cliente, $planInfo, $titulo, $titulo2); ?>
  <?php else: ?>
    <p><strong>A QUIEN CORRESPONDA:</strong></p>
    <p>
      Por medio del presente, se certifica que <?php echo $titulo; ?>
      <strong style="text-transform: uppercase"><?php echo htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']); ?></strong>,
      identifica<?php echo $titulo2; ?> con documento de identidad No.
      <strong><?php echo htmlspecialchars($cliente['identificacion']); ?></strong>,
      se encuentra vincula<?php echo $titulo2; ?> activamente a nuestro gimnasio
      <strong><?php echo htmlspecialchars($_nameGym); ?></strong>
      desde el <strong style="text-transform: uppercase"><?php echo formatearFechaEnEspanol(substr($cliente['created_at'], 0, 10)); ?></strong>
      y su plan activo corresponde a
      <strong style="text-transform: uppercase"><?php echo $planInfo ? htmlspecialchars($planInfo['nombre']) : 'Sin Plan Asignado'; ?></strong>,
      con una vigencia desde el
      <strong style="text-transform: uppercase"><?php echo !empty($cliente['pago_plan']) ? formatearFechaEnEspanol($cliente['pago_plan']) : 'No registrado'; ?></strong>
      hasta el
      <strong style="text-transform: uppercase"><?php echo !empty($cliente['vencimiento_plan']) ? formatearFechaEnEspanol($cliente['vencimiento_plan']) : 'No registrado'; ?></strong>.
    </p>
    <p>Este certificado se expide a solicitud del interesado(a) el día
      <strong style="text-transform: uppercase"><?php echo formatearFechaEnEspanol($hoy); ?></strong>,
      con fines que estime convenientes.
    </p>
  <?php endif; ?>

  <p style="text-align: left;">
    Atentamente,<br><br>
    <?php if ($certFirmaURI): ?>
      <img src="<?php echo $certFirmaURI; ?>" style="max-width:200px;max-height:80px;display:block;margin-bottom:4px;"><br>
    <?php endif; ?>
    <strong><?php echo htmlspecialchars($_certFirmante); ?></strong><br>
    <?php echo htmlspecialchars($_certCargo); ?><br>
    <?php if ($_direccionGym): ?><?php echo htmlspecialchars($_direccionGym); ?><br><?php endif; ?>
    <?php if ($_telGym): ?>Tel. <?php echo htmlspecialchars($_telGym); ?><br><?php endif; ?>
    <?php echo htmlspecialchars($_ciudadGym); ?>
  </p>

</body>
</html>
