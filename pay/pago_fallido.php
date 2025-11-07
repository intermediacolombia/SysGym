<?php
require_once __DIR__ . '/../inc/config.php';
$cliente_id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pago Fallido - SysGym</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
:root {
  --primary: <?= SYSTEM_COLOR_PRIMARY; ?>;
  --secondary: <?= SYSTEM_COLOR_SECONDARY; ?>;
}

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: #333;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
}

.card-failed {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0,0,0,0.15);
  padding: 40px 30px;
  max-width: 420px;
  width: 100%;
  text-align: center;
  animation: fadeIn 1s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.logo {
  width: 180px;
  margin-bottom: 15px;
}

.icon-failed {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #dc3545;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  animation: shake 0.6s ease-in-out;
}

.icon-failed i {
  color: #fff;
  font-size: 40px;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}

h2 {
  color: #dc3545;
  font-weight: 600;
  margin-bottom: 10px;
}

p {
  color: #555;
  font-size: 15px;
  margin-bottom: 10px;
}

.btn-retry, .btn-home {
  border-radius: 30px;
  padding: 12px 30px;
  font-weight: 600;
  transition: 0.3s;
}

.btn-retry {
  background: var(--primary);
  color: #fff;
  border: none;
}

.btn-retry:hover {
  background: var(--secondary);
  color: var(--primary);
  transform: translateY(-2px);
}

.btn-home {
  background: #6c757d;
  color: #fff;
  border: none;
}

.btn-home:hover {
  background: #5c636a;
  transform: translateY(-2px);
}
</style>
</head>

<body>
  <div class="card-failed">
    <img src="<?= $url . '/' . SITE_LOGO; ?>" alt="Logo" class="logo">

    <div class="icon-failed">
      <i class="bi bi-x-lg"></i>
    </div>

    <h2>Pago no completado</h2>
    <p>Tu transacción fue cancelada o falló.</p>
    <p>Puedes intentarlo nuevamente desde el botón siguiente:</p>

    <?php if ($cliente_id): ?>
      <a href="crear_pago.php?id=<?= urlencode($cliente_id) ?>" class="btn btn-retry mt-3">
        <i class="bi bi-credit-card"></i> Reintentar pago
      </a>
    <?php endif; ?>

    <div class="mt-3">
      <a href="../index.php" class="btn btn-home">Volver al inicio</a>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.js"></script>
</body>
</html>

