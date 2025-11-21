<?php
require_once __DIR__ . '/../../inc/config.php';

// Extraer el dominio correctamente (sin protocolo)
$cookieDomain = str_replace(['https://', 'http://', 'www.'], '', $url);
if ($cookieDomain === 'localhost' || strpos($cookieDomain, 'localhost:') === 0) {
    $cookieDomain = '';
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    $tiempoUnAno = 365 * 24 * 60 * 60;
    session_set_cookie_params([
        'lifetime' => $tiempoUnAno,
        'path'     => '/',
        'domain'   => $cookieDomain,
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    ini_set('session.gc_maxlifetime', $tiempoUnAno);
    session_start();
}

// Si existe la cookie "remember_me", elimina el token de la base de datos y la cookie
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    try {
        $stmt = db()->prepare("DELETE FROM user_tokens WHERE token = :token");
        $stmt->execute([':token' => $token]);
    } catch (PDOException $e) {
        error_log("Error al eliminar token remember_me: " . $e->getMessage());
    }
    
    // Limpiar la cookie con los mismos parámetros
    setcookie('remember_me', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => $cookieDomain,
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Obtener el ID de sesión antes de destruir
$sessionId = session_id();

// Destruir la sesión
session_unset();
session_destroy();

// Esperar a que se destruya completamente
sleep(1);

// Iniciar una nueva sesión para el mensaje flash
// Usar session_id para especificar un nuevo ID
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['success'] = 'Has cerrado sesión correctamente.';

// Redirigir al login
header("Location: $url/admin/login/");
exit();
?>

