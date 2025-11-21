<?php
// session.php
require_once __DIR__ . '/../../inc/config.php';

// Extraer el dominio correctamente (sin protocolo)
$cookieDomain = str_replace(['https://', 'http://', 'www.'], '', $url);
if ($cookieDomain === 'localhost' || strpos($cookieDomain, 'localhost:') === 0) {
    $cookieDomain = '';
}

// Configurar los parámetros de la cookie de sesión
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

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restaurar sesión desde cookie "remember_me" si no existe sesión activa
if (!isset($_SESSION["user"]) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $now = time();
    
    try {
        $stmt = db()->prepare("SELECT user_id FROM user_tokens WHERE token = :token AND expires_at > :now LIMIT 1");
        $stmt->execute([
            ':token' => $token,
            ':now'   => $now
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $stmtUser = db()->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
            $stmtUser->execute([':id' => $result['user_id']]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user["borrado"] != 1 && $user["estado"] != 1) {
                $_SESSION["user"] = $user;
                
                // Regenerar el token para mayor seguridad
                $newToken = bin2hex(random_bytes(16));
                $newExpiry = $now + (30 * 24 * 60 * 60);
                
                $updateStmt = db()->prepare("UPDATE user_tokens SET token = :newToken, expires_at = :newExpiry WHERE token = :oldToken");
                $updateStmt->execute([
                    ':newToken'  => $newToken,
                    ':newExpiry' => $newExpiry,
                    ':oldToken'  => $token
                ]);
                
                setcookie('remember_me', $newToken, [
                    'expires'  => $newExpiry,
                    'path'     => '/',
                    'domain'   => $cookieDomain,
                    'secure'   => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                // Usuario inactivo o borrado, limpiar cookie
                setcookie('remember_me', '', [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'domain'   => $cookieDomain,
                    'secure'   => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                // Limpiar la base de datos
                if ($result) {
                    db()->prepare("DELETE FROM user_tokens WHERE user_id = :user_id")->execute([':user_id' => $result['user_id']]);
                }
            }
        } else {
            // Token expirado o inválido, limpiar cookie
            setcookie('remember_me', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'domain'   => $cookieDomain,
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Error en restauración de sesión: " . $e->getMessage());
        // Limpiar cookie en caso de error
        setcookie('remember_me', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => $cookieDomain,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user"])) {
    // Guardar la URL a la que intentó acceder como parámetro GET
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: $url/admin/login?redirect=$currentUrl");
    exit();
}

// Obtener datos del usuario para usarlos en la aplicación
$id_user   = $_SESSION["user"]["id"];
$nombre    = $_SESSION["user"]["nombre"];
$apellido  = $_SESSION["user"]["apellido"];
$rol_id    = $_SESSION["user"]["rol_id"];
$foto_perfil = $_SESSION["user"]["foto_perfil"] ?? null;

// Consultar el nombre del rol
try {
    $stmtRol = db()->prepare("SELECT name FROM roles WHERE id = :rol_id LIMIT 1");
    $stmtRol->execute([':rol_id' => $rol_id]);
    $rolData = $stmtRol->fetch(PDO::FETCH_ASSOC);
    $rolUser = $rolData ? $rolData['name'] : 'Sin Rol';
} catch (PDOException $e) {
    error_log("Error al obtener rol: " . $e->getMessage());
    $rolUser = 'Sin Rol';
}

// Consultar los permisos asociados al rol del usuario
try {
    $stmtPermisos = db()->prepare("SELECT p.name AS permission_name 
                                   FROM role_permissions rp 
                                   JOIN permissions p ON rp.permission_id = p.id 
                                   WHERE rp.role_id = :rol_id");
    $stmtPermisos->execute([':rol_id' => $rol_id]);
    $permisos = $stmtPermisos->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error al obtener permisos: " . $e->getMessage());
    $permisos = [];
}

// Guardar los permisos en una variable accesible globalmente
$_SESSION["user_permissions"] = $permisos;

// Obtener la caja abierta para el usuario actual
try {
    $stmtCaja = db()->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
    $stmtCaja->execute([':usuario_id' => $id_user]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
    $caja_id = $caja ? $caja['id'] : null;
} catch (PDOException $e) {
    error_log("Error al obtener caja: " . $e->getMessage());
    $caja_id = null;
}
?>