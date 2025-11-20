<?php
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';

// Si no viene de un cambio de contraseña, no mostrar esta página
if (!isset($_SESSION['success'])) {
    header("Location: $url/admin/profile");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Contraseña Actualizada</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<style>
body {
    background: #f4f4f4;
    font-family: 'Poppins', sans-serif;
}

.card {
    max-width: 600px;
    margin: 80px auto;
    border-radius: 14px;
}

.step-text {
    font-size: 15px;
    font-weight: 500;
    color: #555;
}

.countdown {
    font-size: 42px;
    font-weight: 800;
    color: #007bff;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 6px solid #ddd;
    border-top: 6px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: auto;
}

@keyframes spin {
    100% { transform: rotate(360deg); }
}
</style>

</head>
<body>

<div class="card shadow-lg border-0">
    <div class="card-body text-center">

        <h3 class="mb-3">Contraseña Actualizada</h3>

        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
        </div>

        <div class="spinner mb-4"></div>

        <p id="stepMessage" class="step-text mb-2">Preparando proceso de seguridad…</p>

        <div class="countdown" id="timer">10</div>

        <p class="text-muted mt-3">
            Este proceso garantiza la seguridad de tu cuenta.
        </p>

        <a href="<?= $url ?>/admin/login/logout.php" class="btn btn-primary mt-3">
            Cerrar sesión ahora
        </a>

    </div>
</div>

<script>
// Mensajes animados
const messages = [
    "Destruyendo sesión actual…",
    "Eliminando tokens temporales…",
    "Sincronizando cambios…",
    "Actualizando datos internos…",
    "Validando integridad de seguridad…",
    "Cerrando sesión de forma segura…",
];

let msgIndex = 0;
const msgElement = document.getElementById("stepMessage");

setInterval(() => {
    if (msgIndex < messages.length) {
        msgElement.textContent = messages[msgIndex];
        msgIndex++;
    }
}, 1200);


// Contador regresivo
let timeLeft = 10; // segundos
const timerElement = document.getElementById("timer");

const countdown = setInterval(() => {
    timeLeft--;
    timerElement.textContent = timeLeft;

    if (timeLeft <= 0) {
        clearInterval(countdown);
        window.location.href = "<?= $url ?>/admin/login/logout.php";
    }
}, 1000);
</script>

</body>
</html>

