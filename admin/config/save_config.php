<?php
require_once __DIR__ . '/../login/session.php';
header('Content-Type: application/json');

try {
    db()->exec("SET NAMES utf8mb4");

    // Upsert robusto: UPDATE si existe, INSERT si no — sin depender de UNIQUE constraint
    $upsert = function($key, $value) {
        $check = db()->prepare("SELECT id FROM system_settings WHERE setting_name = :name LIMIT 1");
        $check->execute([':name' => $key]);
        $row = $check->fetch();
        if ($row) {
            $stmt = db()->prepare("UPDATE system_settings SET value = :value, enabled = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([':value' => $value, ':id' => $row['id']]);
        } else {
            $stmt = db()->prepare("INSERT INTO system_settings (setting_name, value, enabled) VALUES (:name, :value, 1)");
            $stmt->execute([':name' => $key, ':value' => $value]);
        }
    };

    // === GUARDAR ARCHIVOS ===
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $files = ['system_logo', 'system_favicon', 'consent_firma_img', 'cert_firma_img'];
    foreach ($files as $fileKey) {
        if (!empty($_FILES[$fileKey]['name'])) {
            $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
            $filename = $fileKey . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
                $upsert($fileKey, $filename);
            }
        }
    }

    // === GUARDAR CAMPOS DE TEXTO ===
    foreach ($_POST as $key => $value) {
        $upsert($key, $value);
    }

    // Log opcional
    if (file_exists(__DIR__ . '/../inc/log_action.php')) {
        require_once __DIR__ . '/../inc/log_action.php';
        $desc = json_encode(['accion' => 'Actualizó Configuraciones del Sistema'], JSON_UNESCAPED_UNICODE);
        log_action('Actualizar Configuraciones', $desc, 'Configuraciones');
    }

    echo json_encode(['success' => true, 'message' => 'Configuraciones guardadas correctamente.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
