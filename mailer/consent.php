<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/config-mail.php';


require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


$pdfUrl = $url . "/pdf/?type=consent&id=" . $formulario_id;

// Carpeta donde guardar temporalmente el PDF
$attachmentsDir = $_SERVER['DOCUMENT_ROOT'] . '/attachments/';
if (!is_dir($attachmentsDir)) {
    mkdir($attachmentsDir, 0755, true); // Crear la carpeta si no existe
}

// Ruta local donde se guardará el archivo
$pdfFilePath = $attachmentsDir . 'consentimiento_' . $formulario_id . '.pdf';

// Descargar el PDF desde la URL y guardarlo en el servidor
$pdfContent = file_get_contents($pdfUrl);
if ($pdfContent === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error al descargar el PDF']);
    exit;
}
file_put_contents($pdfFilePath, $pdfContent); // Guardar el PDF localmente

// Configurar PHPMailer
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $Host;
$mail->SMTPAuth = true;
$mail->Username = $Username;
$mail->Password = $Password;
$mail->Port = $Port;
$mail->CharSet = 'UTF-8';

// Configurar el correo
$mail->setFrom($from, ''.NAME_GYM.'');
// Validar que los correos existan, sean válidos y tengan un valor predeterminado en 'entidad'

//$nombre_apellido = $data['nombres'] . $data['apellidos'];

$cliente = !empty($data['nombres'].' '.$data['apellidos']) ? $data['nombres'].' '.$data['apellidos'] : 'Cliente';

$mail->addAddress($data['email'], $cliente);

$listaBcc = [
	//['correo' => 'formulariosquindiaires@gmail.com', 'nombre' => 'Formularios Quindiaires'],
    //['correo' => 'intermediacolombia@gmail.com', 'nombre' => 'Intermedia Colombia'],
    //['correo' => 'formulariosquindiaires@gmail.com', 'nombre' => 'Formularios Quindiaires']
];

foreach ($listaBcc as $bcc) {
    $mail->addBCC($bcc['correo'], $bcc['nombre']);
}


$mail->isHTML(true);
$mail->Subject = 'Bienvenido/a a '.NAME_GYM.' 💪';
$mail->Body = '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Consentimiento Informado - '.NAME_GYM.'</title>
  <!-- Font Awesome CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
      color: '.SYSTEM_COLOR_PRIMARY.';
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
      color: '.SYSTEM_COLOR_PRIMARY.';
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
      background-color: '.SYSTEM_COLOR_PRIMARY.';
      color: #ffffff;
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 5px;
      font-size: 16px;
      font-weight: bold;
    }
    .cta a:hover {
      background-color: '.SYSTEM_COLOR_SECONDARY.';
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
      color: '.SYSTEM_COLOR_PRIMARY.';
      text-decoration: none;
      margin: 0 5px;
    }
    .footer a:hover {
      text-decoration: underline;
    }
    .social-icons a {
      color: '.SYSTEM_COLOR_PRIMARY.';
      margin: 0 5px;
      font-size: 24px;
      text-decoration: none;
    }
    .social-icons a:hover {
      color: '.SYSTEM_COLOR_SECONDARY.';
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="'.URLBASE . SITE_LOGO .'" alt="Logo '.NAME_GYM.'">
      <h1>Consentimiento Informado</h1>
    </div>
    <div class="content">
      <h2>¡Bienvenido/a, '.$data['nombres'].'!</h2>
      <p>Nos alegra enormemente tenerte con nosotros en '.NAME_GYM.'. Adjuntamos a este correo una copia de tu <strong>Consentimiento Informado</strong> para que puedas revisarlo y conservarlo.</p>
      <p>Tu bienestar es nuestra prioridad y estamos aquí para acompañarte en cada paso hacia una vida más activa y saludable. Si tienes alguna duda o necesitas asistencia, no dudes en contactarnos. ¡Estamos siempre a tu lado con una sonrisa!</p>
      <div class="cta">
        <a href="#" target="_blank">Visítanos</a>
      </div>
    </div>
    <div class="footer">
      <p>&copy; '.date('Y').' '.NAME_GYM.'. Todos los derechos reservados.</p>
      <div class="social-icons">
        <a href="https://www.facebook.com/'.NAME_GYM.'" target="_blank" aria-label="Facebook">
          <i class="fab fa-facebook-f"></i>
        </a>
        <a href="https://www.instagram.com/'.NAME_GYM.'" target="_blank" aria-label="Instagram">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="https://www.tiktok.com/@'.NAME_GYM.'" target="_blank" aria-label="TikTok">
          <i class="fab fa-tiktok"></i>
        </a>
      </div>
      <p><a href="https://www.'.NAME_GYM.'.com" target="_blank">Visita nuestro sitio web</a></p>
    </div>
  </div>
</body>
</html>';


// Adjuntar el PDF descargado
$mail->addAttachment($pdfFilePath);

// Enviar el correo y manejar errores
try {
    $mail->send();
    //echo json_encode(['status' => 'success', 'message' => 'Correo enviado correctamente']);

    // (Opcional) Eliminar el archivo después de enviar el correo
    if (file_exists($pdfFilePath)) {
        unlink($pdfFilePath);
    }
} catch (Exception $e) {
   // echo json_encode(['status' => 'error', 'message' => 'Error al enviar el correo: ' . $e->getMessage()]);
}
?>