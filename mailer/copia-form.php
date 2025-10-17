<?php include('../inc/plataform_config.php'); ?>
<?php
session_start();
require('config-mail.php');
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!isset($_GET['id'])) {
    die("ID no proporcionado.");
}

$formulario_id = intval($_GET['id']);
$pdfUrl = "https://app.quindiaires.com.co/pdf/?id=" . $formulario_id;

// Descargar el PDF desde la URL
$pdfContent = file_get_contents($pdfUrl);
if ($pdfContent === false) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Error',
        'text' => 'No se pudo descargar el PDF.'
    ];
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Guardar el PDF temporalmente
$attachmentsDir = $_SERVER['DOCUMENT_ROOT'] . '/attachments/';
if (!is_dir($attachmentsDir)) {
    mkdir($attachmentsDir, 0755, true);
}
$pdfFilePath = $attachmentsDir . 'formulario_' . $formulario_id . '.pdf';
file_put_contents($pdfFilePath, $pdfContent);

// Obtener información del cliente desde la BD
$host = 'localhost';
$dbname = 'quindia2_app';
$username = 'quindia2_app';
$password = 'm7@!~V;5qOXN';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT entidad, correo_principal, correo_secundario FROM mantenimiento_preventivo WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $formulario_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Error',
            'text' => 'No se encontró información del formulario.'
        ];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Error',
        'text' => 'Error al conectar a la base de datos.'
    ];
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Configurar PHPMailer
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $Host;
$mail->SMTPAuth = true;
$mail->Username = $Username;
$mail->Password = $Password;
$mail->Port = $Port;
$mail->CharSet = 'UTF-8';

$mail->setFrom($from, 'Quindiaires');
$entidad = !empty($data['entidad']) ? $data['entidad'] : 'Cliente';

if (!empty($data['correo_principal']) && filter_var($data['correo_principal'], FILTER_VALIDATE_EMAIL)) {
    $mail->addAddress($data['correo_principal'], $entidad);
}
if (!empty($data['correo_secundario']) && filter_var($data['correo_secundario'], FILTER_VALIDATE_EMAIL)) {
    $mail->addAddress($data['correo_secundario'], $entidad);
}

$mail->isHTML(true);
$mail->Subject = 'Copia Mantenimiento Preventivo - Numero ' . $formulario_id;
$mail->Body = '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mantenimiento Preventivo</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f3f3f3;
    }
    .container {
      max-width: 600px;
      margin: 20px auto;
      background-color: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    .header {
      background-color: #E0E0E0;
      color: #214A82;
      padding: 20px;
      text-align: center;
    }
    .header img {
      max-width: 150px;
      margin-bottom: 10px;
    }
    .header h1 {
      font-size: 22px;
      margin: 0;
    }
    .content {
      padding: 20px;
      color: #333333;
    }
    .content h2 {
      color: #214A82;
      font-size: 18px;
      margin-bottom: 10px;
    }
    .content p {
      line-height: 1.6;
      font-size: 16px;
      margin: 10px 0;
    }
    .cta {
      text-align: center;
      margin: 20px 0;
    }
    .cta a {
      background-color: #214A82;
      color: #ffffff;
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 5px;
      font-size: 16px;
      font-weight: bold;
    }
    .cta a:hover {
      background-color: #173660;
    }
    .footer {
      text-align: center;
      padding: 15px;
      font-size: 14px;
      color: #666666;
      background-color: #f9f9f9;
      border-top: 1px solid #eaeaea;
    }
    .footer a {
      color: #214A82;
      text-decoration: none;
    }
    .footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="https://app.quindiaires.com.co/pdf/images/logo.png" alt="Logo Quindiaires">
      <h1>Mantenimiento Preventivo '. $formulario_id .'</h1>
    </div>
    <div class="content">
      <h2>Estimado cliente,</h2>
      <p>Nos complace informarle que hemos generado un nuevo <strong>Mantenimiento Preventivo</strong> en nuestro sistema con el numero de formulario <strong>'. $formulario_id .'.</strong></p>
      <p>Puede encontrar su formulario adjunto en formato PDF para su revisión y archivo.</p>
      <p>Si tiene alguna pregunta o requiere asistencia, no dude en comunicarse con nosotros.</p>
	 <p>
  <strong>Cordialmente,</strong><br>
  <strong>Equipo de Soporte</strong><br>
  <em>Quindiaires</em>
  </p>

    </div>
    
    <div class="footer">
      <p>&copy; '.date('Y').' Quindiaires. Todos los derechos reservados.</p>
      <p><a href="https://www.quindiaires.com.co">Visite nuestro sitio web</a></p>
	  <!--<div class="cta">
      <a href="https://www.quindiaires.com.co">Visítanos</a>
    </div>-->
	<p style="font-size: 12px; color: #666;">
  Funciona gracias a la tecnología de  
  <a href="https://www.intermediahost.co" target="_blank" style="text-decoration: none; color: #666;"><strong>Intermedia Colombia</strong></a>
</p>


    </div>
  </div>
</body>
</html>';


// Adjuntar el PDF
$mail->addAttachment($pdfFilePath);

try {
    $mail->send();

    // Eliminar el archivo temporal
    if (file_exists($pdfFilePath)) {
        unlink($pdfFilePath);
    }

    $_SESSION['alert'] = [
        'icon' => 'success',
        'title' => 'Enviado',
        'text' => 'El correo se ha enviado correctamente.'
    ];
} catch (Exception $e) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Error',
        'text' => 'No se pudo enviar el correo: ' . $e->getMessage()
    ];
}

// Redireccionar de vuelta a la página anterior
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>