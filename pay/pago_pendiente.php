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
    <h2 class="text-warning">⚠️ Pago pendiente de aprobación</h2>
    <p>Tu transacción se ha registrado correctamente, pero aún <strong>no fue confirmada por Mercado Pago</strong>.</p>
    <p>En cuanto el pago sea aprobado, tu membresía será activada automáticamente.</p>
    <?php if ($cliente_id): ?>
      <a href="../pay/verificar_mensualidad.php?identificacion=<?= urlencode($cliente_id) ?>" class="btn btn-outline-primary mt-3">
        <i class="fa fa-sync"></i> Verificar estado
      </a>
    <?php endif; ?>
    <br><br>
    <a href="../index.php" class="btn btn-secondary">Volver al inicio</a>
  </div>
</body>
</html>
