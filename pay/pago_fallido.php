<?php
require_once __DIR__ . '/../inc/config.php';
$cliente_id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pago fallido - SysGym</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="text-center py-5">
  <div class="container">
    <h2 class="text-danger">❌ Pago no completado</h2>
    <p>Tu transacción fue cancelada o falló. Puedes intentarlo nuevamente.</p>
    <?php if ($cliente_id): ?>
      <a href="crear_pago.php?id=<?= urlencode($cliente_id) ?>" class="btn btn-success mt-3">
        <i class="fa fa-credit-card"></i> Reintentar pago
      </a>
    <?php endif; ?>
    <br><br>
    <a href="../index.php" class="btn btn-secondary">Volver al inicio</a>
  </div>
</body>
</html>
