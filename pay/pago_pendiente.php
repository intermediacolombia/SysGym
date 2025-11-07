<?php
require_once __DIR__ . '/../inc/config.php';
$cliente_id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pago Pendiente - SysGym</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
:root {
  --primary: <?= SYSTEM_COLOR_PRIMARY; ?>;
  --secondary: <?= SYSTEM_COLOR_SECONDARY; ?>;
  --warning: #f4b400;
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
  background: var(--warning);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pulse 1.5s infinite;
}
.icon svg {
  width: 45px;
  height: 45px;
}
.icon svg line,
.icon svg circle {
  stroke: #fff;
  stroke-width: 6;
  stroke-linecap: round;
}
@keyframes pulse {
  0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244,180,0, 0.6); }
  70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(244,180,0, 0); }
  100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244,180,0, 0); }
}
h2 {
  color: var(--warning);
  font-weight: 600;
}
p {
  color: #555;
  font-size: 15px;
}
.btn-home {
  background: var(--primary);
  color: #fff;
  border-radius: 30px;
  padding: 12px 30px;
  border: none;
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
  <div class="card">
    <img src="<?= $url . '/' . SITE_LOGO; ?>" alt="Logo" class="logo">

    <div class="icon">
      <svg viewBox="0 0 64 64">
        <line x1="32" y1="18" x2="32" y2="36"></line>
        <circle cx="32" cy="46" r="2.5"></circle>
      </svg>
    </div>

    <h2>Pago pendiente</h2>
    <p>Tu transacción está en proceso de verificación.</p>
    <p>En cuanto sea aprobada, tu membresía se activará automáticamente.</p>
    <a href="../index.php" class="btn btn-home mt-3">Volver al inicio</a>
  </div>
</body>
</html>




