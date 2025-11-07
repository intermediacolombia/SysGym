<?php
require_once __DIR__ . '/../inc/config.php';
$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) die("Cliente no especificado.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pago Exitoso - SysGym</title>
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

.card-success {
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

.icon-success {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  animation: pop 0.5s ease-in-out;
}

.icon-success i {
  color: #fff;
  font-size: 40px;
}

@keyframes pop {
  0% { transform: scale(0.5); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

h2 {
  color: var(--primary);
  font-weight: 600;
  margin-bottom: 10px;
}

p {
  color: #555;
  font-size: 15px;
  margin-bottom: 10px;
}

.btn-home {
  margin-top: 25px;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 30px;
  padding: 12px 30px;
  font-weight: 600;
  transition: 0.3s;
}

.btn-home:hover {
  background: var(--secondary);
  color: var(--primary);
  transform: translateY(-2px);
}
</style>
</head>

<body>
  <div class="card-success">
    <img src="<?= $url . '/' . SITE_LOGO; ?>" alt="Logo" class="logo">

    <div class="icon-success">
      <i class="bi bi-check-lg"></i>
    </div>

    <h2>¡Pago realizado con éxito!</h2>
    <p>Tu pago fue recibido correctamente por <strong>Mercado Pago</strong>.</p>
    <p>En unos instantes, nuestro sistema confirmará y renovará automáticamente tu membresía.</p>
    <p>Si ya se ha procesado, podrás ver tu nueva fecha de vencimiento actualizada en tu perfil.</p>

    <a href="../index.php" class="btn btn-home">Volver al inicio</a>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.js"></script>
</body>
</html>



