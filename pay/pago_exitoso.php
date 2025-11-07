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

/* Tarjeta general */
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

/* Animación del check circular */
.icon-success {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 4px solid var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  position: relative;
  animation: pop 0.6s ease-in-out;
}

@keyframes pop {
  0% { transform: scale(0.5); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

.icon-success svg {
  width: 60px;
  height: 60px;
}

.icon-success .checkmark {
  stroke: var(--primary);
  stroke-width: 6;
  stroke-linecap: round;
  stroke-linejoin: round;
  fill: none;
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: draw 0.8s ease forwards 0.3s;
}

@keyframes draw {
  to { stroke-dashoffset: 0; }
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
      <!-- SVG animado del check -->
      <svg viewBox="0 0 52 52">
        <path class="checkmark" d="M14 27 l8 8 l16 -16" />
      </svg>
    </div>

    <h2>¡Pago realizado con éxito!</h2>
    <p>Hemos recibido tu pago.</p>
    <p>En unos instantes, nuestro sistema confirmará y renovará automáticamente tu membresía.</p>
    <p>Si ya se ha procesado, podrás ver tu nueva fecha de vencimiento actualizada en tu perfil.</p>
    <p><strong>Nota:</strong> si no recibiste la factura del pago, reclamala en la recepción del GYM.</p>

    <a href="<?= URLBASE; ?>/pay" class="btn btn-home">Volver al inicio</a>
  </div>
</body>
</html>




