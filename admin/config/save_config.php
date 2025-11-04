<?php
require_once __DIR__ . '/../login/session.php';
header('Content-Type: application/json');

try {
    // Conexión PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("SET NAMES utf8mb4");

    // Guardar todas las configuraciones dinámicamente
    foreach ($_POST as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_name, value, enabled)
            VALUES (:name, :value, 1)
            ON DUPLICATE KEY UPDATE value = :value, updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            ':name' => $key,
            ':value' => $value
        ]);
    }

    // Registrar acción (si existe el sistema de logs)
    if (file_exists(__DIR__ . '/../inc/log_action.php')) {
        require_once __DIR__ . '/../inc/log_action.php';
        $desc = json_encode(['accion' => 'Actualizó configuraciones del sistema'], JSON_UNESCAPED_UNICODE);
        log_action('Actualizar Configuraciones', $desc, 'Configuraciones');
    }

    echo json_encode(['success' => true, 'message' => 'Configuraciones guardadas correctamente.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
