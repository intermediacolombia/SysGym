<?php
require_once __DIR__ . '/../inc/config.php';
$cliente_id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pago pendiente - SysGym</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="text-center py-5">
  <div class="container">
    <h2 class="text-warning">⚠️ Pago pendiente</h2>
    <p>Tu transacción está en proceso de verificación. En cuanto sea aprobada, tu membresía se activará automáticamente.</p>
    <a href="../index.php" class="btn btn-secondary mt-3">Volver al inicio</a>
  </div>
</body>
</html>

