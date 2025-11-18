<?php 
require_once __DIR__ . '/../inc/config.php';
require('config-mail.php');

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía el correo de restablecimiento de contraseña.
 *
 * @param string $email         Correo electrónico del destinatario.
 * @param string $nombreCompleto Nombre completo del usuario (nombre y apellido).
 * @param string $resetLink     Enlace para restablecer la contraseña.
 * @param string $url           URL base de la aplicación.
 * @param string $logo          Nombre del archivo de logo (usado en la imagen del correo).
 * @return bool                 True si se envió el correo; false en caso de error.
 */
function sendResetPasswordEmail($email, $nombreCompleto, $resetLink, $url, $logo) {
    $mail = new PHPMailer(true);

    try {
        // Configurar SMTP
        $mail->isSMTP();
        $mail->Host       = $GLOBALS['Host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $GLOBALS['Username'];
        $mail->Password   = $GLOBALS['Password'];
        $mail->Port       = $GLOBALS['Port'];
        $mail->CharSet    = 'UTF-8';

        // Configurar remitente y destinatario usando los parámetros recibidos
        $mail->setFrom($GLOBALS['from'], 'ActivGym');
        $mail->addAddress($email, $nombreCompleto);

        // Configurar el contenido del correo (HTML)
        $mail->isHTML(true);
        $mail->Subject = 'Restablece tu contraseña';

        $mensaje = '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablece tu contraseña - '.GYM_NAME.'</title>
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
      color: var(--system-color-primary);
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
      color: var(--system-color-primary);
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
      background-color: var(--system-color-primary);
      color: #ffffff;
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 5px;
      font-size: 16px;
      font-weight: bold;
    }
    .cta a:hover {
      background-color: #b3150b;
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
      color: var(--system-color-primary);
      text-decoration: none;
      margin: 0 5px;
    }
    .footer a:hover {
      text-decoration: underline;
    }
    .social-icons a {
      color: var(--system-color-primary);
      margin: 0 5px;
      font-size: 24px;
      text-decoration: none;
    }
    .social-icons a:hover {
      color: #b3150b;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="'.$_SERVER['DOCUMENT_ROOT'] . SITE_LOGO .'" alt="Logo '.GYM_NAME.'">
      <h1>Restablece tu contraseña</h1>
    </div>
    <div class="content">
      <h2>¡Hola, '.htmlspecialchars($nombreCompleto).'!</h2>
      <p>Recibes este correo porque has solicitado restablecer la contraseña de tu cuenta en '.GYM_NAME.'. Para continuar con el proceso, haz clic en el siguiente enlace:</p>
      <div class="cta">
        <a href="'.$resetLink.'" target="_blank">Restablecer Contraseña</a>
      </div>
      <p>Si el botón anterior no funciona, copia y pega la siguiente dirección en tu navegador:</p>
      <p>'.$resetLink.'</p>
      <p><strong>Nota:</strong> Este enlace expirará en 1 hora. Si no has solicitado restablecer tu contraseña, ignora este mensaje.</p>
      <p>Gracias,<br>El equipo de '.GYM_NAME.'</p>
    </div>
    <div class="footer">
      <p>&copy; '.date("Y").' '.GYM_NAME.'. Todos los derechos reservados.</p>
      <div class="social-icons">
        <a href="https://www.facebook.com/'.GYM_NAME.'" target="_blank" aria-label="Facebook">
          <i class="fab fa-facebook-f"></i>
        </a>
        <a href="https://www.instagram.com/'.GYM_NAME.'" target="_blank" aria-label="Instagram">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="https://www.tiktok.com/@'.GYM_NAME.'" target="_blank" aria-label="TikTok">
          <i class="fab fa-tiktok"></i>
        </a>
      </div>
      <p><a href="https://www.'.GYM_NAME.'.com" target="_blank">Visita nuestro sitio web</a></p>
    </div>
  </div>
</body>
</html>
';

        $mail->Body = $mensaje;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar el correo: {$mail->ErrorInfo}");
        return false;
    }
}
?>



