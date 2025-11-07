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
  --danger: #dc3545;
}
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  margin: 0;
}
.card {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0,0,0,0.15);
  padding: 40px 30px;
  text-align: center;
  max-width: 420px;
  width: 90%;
  animation: fadeIn 1s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.logo {
  display: block;
  margin: 0 auto 10px auto;
  max-width: 220px;
}
.icon {
  margin: 25px auto;
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: var(--danger);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: shake 0.8s ease-in-out;
}
.icon svg {
  width: 45px;
  height: 45px;
}
.icon svg line {
  stroke: #fff;
  stroke-width: 6;
  stroke-linecap: round;
}
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}
h2 {
  color: var(--danger);
  font-weight: 600;
}
p {
  color: #555;
  font-size: 15px;
}
.btn {
  border-radius: 30px;
  padding: 12px 30px;
  font-weight: 600;
  border: none;
  transition: 0.3s;
}
.btn-retry {
  background: var(--primary);
  color: #fff;
}
.btn-retry:hover {
  background: var(--secondary);
  color: var(--primary);
  transform: translateY(-2px);
}
.btn-home {
  background: #6c757d;
  color: #fff;
}
.btn-home:hover {
  background: #5c636a;
  transform: translateY(-2px);
}
</style>
</head>
<body>
  <div class="card">
    <img src="<?= $url . '/' . SITE_LOGO; ?>" alt="Logo" class="logo">

    <div class="icon">
      <svg viewBox="0 0 64 64">
        <line x1="20" y1="20" x2="44" y2="44"></line>
        <line x1="44" y1="20" x2="20" y2="44"></line>
      </svg>
    </div>

    <h2>Pago no completado</h2>
    <p>Tu transacción fue cancelada o falló.</p>
    <p>Puedes intentarlo nuevamente desde el botón siguiente:</p>

    <?php if ($cliente_id): ?>
      <a href="crear_pago.php?id=<?= urlencode($cliente_id) ?>" class="btn btn-retry mt-3">Reintentar pago</a>
    <?php endif; ?>

    <div class="mt-3">
      <a href="<?= URLBASE; ?>/pay" class="btn btn-home">Volver al inicio</a>
    </div>
  </div>
</body>
</html>


