<?php
function log_action($accion, $descripcion = '', $modulo = null) {
    global $pdo; // usa la conexión global

    // === Zona horaria Bogotá ===
    date_default_timezone_set('America/Bogota');
    $fecha = date('Y-m-d');
    $hora  = date('H:i:s');

    // === Conexión a la base de datos si no existe ===
    if (!isset($pdo)) {
        require __DIR__ . '/../../inc/config.php';
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    // === Datos del usuario y entorno ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $usuario_id = $_SESSION['user']['id'] ?? null;
    $usuario_nombre = trim(($_SESSION['user']['nombre'] ?? '') . ' ' . ($_SESSION['user']['apellido'] ?? ''));
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';

    // === Insertar registro en la base de datos ===
    $stmt = $pdo->prepare("INSERT INTO system_logs 
        (fecha, hora, usuario_id, usuario_nombre, accion, descripcion, modulo, ip, user_agent)
        VALUES (:fecha, :hora, :usuario_id, :usuario_nombre, :accion, :descripcion, :modulo, :ip, :user_agent)");

    $stmt->execute([
        ':fecha' => $fecha,
        ':hora' => $hora,
        ':usuario_id' => $usuario_id,
        ':usuario_nombre' => $usuario_nombre,
        ':accion' => $accion,
        ':descripcion' => $descripcion,
        ':modulo' => $modulo,
        ':ip' => $ip,
        ':user_agent' => $user_agent
    ]);

    // === Log alternativo en archivo físico ===
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

