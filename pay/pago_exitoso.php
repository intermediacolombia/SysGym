<?php
require_once __DIR__ . '/../inc/config.php';
$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) die("Cliente no especificado.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pago exitoso - SysGym</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="text-center py-5">
  <div class="container">
    <h2 class="text-success mb-4">✅ ¡Pago realizado con éxito!</h2>
    <p>Tu pago fue recibido correctamente por <strong>Mercado Pago</strong>.</p>
    <p>En unos instantes, nuestro sistema confirmará y renovará automáticamente tu membresía.</p>
    <p>Si ya se ha procesado, podrás ver tu nueva fecha de vencimiento actualizada en tu perfil.</p>
    <a href="../index.php" class="btn btn-primary mt-3">Volver al inicio</a>
  </div>
</body>
</html>


