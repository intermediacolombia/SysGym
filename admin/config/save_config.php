<?php
require_once __DIR__ . '/../login/session.php';
header('Content-Type: application/json');

try {
    
    db()->exec("SET NAMES utf8mb4");

    // === GUARDAR ARCHIVOS ===
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $files = ['system_logo', 'system_favicon', 'consent_firma_img'];
    foreach ($files as $fileKey) {
        if (!empty($_FILES[$fileKey]['name'])) {
            $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
            $filename = $fileKey . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
                $stmt = db()->prepare("
                    INSERT INTO system_settings (setting_name, value, enabled)
                    VALUES (:name, :value, 1)
                    ON DUPLICATE KEY UPDATE value = :value, updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([':name' => $fileKey, ':value' => $filename]);
            }
        }
    }

    // === GUARDAR CAMPOS DE TEXTO ===
    foreach ($_POST as $key => $value) {
        $stmt = db()->prepare("
            INSERT INTO system_settings (setting_name, value, enabled)
            VALUES (:name, :value, 1)
            ON DUPLICATE KEY UPDATE value = :value, updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([':name' => $key, ':value' => $value]);
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

