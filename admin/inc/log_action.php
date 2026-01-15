<?php
function log_action($accion, $descripcion = '', $modulo = null) {

    require_once __DIR__ . '/../../inc/config.php'; // ruta corregida

    // === Zona horaria ===
    date_default_timezone_set('America/Bogota');
    $fecha = date('Y-m-d');
    $hora  = date('H:i:s');

    // === Asegurar sesión activa ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // === Datos usuario ===
    $usuario_id     = $_SESSION['user']['id'] ?? null;
    $usuario_nombre = trim(($_SESSION['user']['nombre'] ?? '') . ' ' . ($_SESSION['user']['apellido'] ?? 'System'));
    $ip             = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $user_agent     = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';

    // === Insertar en BD usando db() optimizado ===
    try {
        $stmt = db()->prepare("
            INSERT INTO system_logs 
                (fecha, hora, usuario_id, usuario_nombre, accion, descripcion, modulo, ip, user_agent)
            VALUES 
                (:fecha, :hora, :usuario_id, :usuario_nombre, :accion, :descripcion, :modulo, :ip, :user_agent)
        ");

        $stmt->execute([
            ':fecha'          => $fecha,
            ':hora'           => $hora,
            ':usuario_id'     => $usuario_id,
            ':usuario_nombre' => $usuario_nombre,
            ':accion'         => $accion,
            ':descripcion'    => $descripcion,
            ':modulo'         => $modulo,
            ':ip'             => $ip,
            ':user_agent'     => $user_agent
        ]);

    } catch (Exception $e) {
        // evitar error 500, guardar fallback
        file_put_contents(__DIR__ . '/../logs/error_log.txt', 
            "[".$fecha." ".$hora."] ERROR DB LOG: ".$e->getMessage()."\n",
            FILE_APPEND
        );
    }

    // === Log alternativo en archivo ===
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);

    $logLine = sprintf("[%s %s] %s | %s | %s | %s\n",
        $fecha, $hora,
        $usuario_nombre ?: 'Invitado',
        $accion,
        $modulo ?: 'General',
        $descripcion
    );

    file_put_contents($logDir . 'system.log', $logLine, FILE_APPEND);
}
?>


