<?php
require_once __DIR__ . '/../inc/config.php';
$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) die("Cliente no especificado.");

// Conexión DB
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Registrar nuevo pago
$fecha_pago = date('Y-m-d');
$fecha_vencimiento = date('Y-m-d', strtotime('+30 days'));

$stmt = $pdo->prepare("UPDATE clientes 
                       SET pago_plan = :pago, vencimiento_plan = :venc, estado = 'activo'
                       WHERE id = :id");
$stmt->execute([
    ':pago' => $fecha_pago,
    ':venc' => $fecha_vencimiento,
    ':id' => $cliente_id
]);

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
    <h2 class="text-success">✅ Pago registrado correctamente</h2>
    <p>Tu mensualidad ha sido activada hasta el <strong><?= $fecha_vencimiento ?></strong>.</p>
    <a href="../index.php" class="btn btn-primary">Volver al inicio</a>
  </div>
</body>
</html>
