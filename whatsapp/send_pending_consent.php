<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // para registrar fallos

$apiKey = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['cliente_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
    exit;
}

try {
    

    /* ──────────────────────────────────────────────
       CREAR TABLA tokens_consent SI NO EXISTE
    ────────────────────────────────────────────── */
    db()->exec("
        CREATE TABLE IF NOT EXISTS tokens_consent (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion DATETIME,
            usado TINYINT(1) DEFAULT 0,
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    /* ──────────────────────────────────────────────
       OBTENER CLIENTE
    ────────────────────────────────────────────── */
    $stmt = db()->prepare("SELECT id, nombres, apellidos, identificacion, telefono, dialCode 
                           FROM clientes WHERE id = :id AND borrado = 0 LIMIT 1");
    $stmt->execute([':id' => intval($_POST['cliente_id'])]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
        exit;
    }

    /* ──────────────────────────────────────────────
       VALIDAR SI YA EXISTE UN TOKEN PENDIENTE VÁLIDO
    ────────────────────────────────────────────── */
    $stmtCheck = db()->prepare("
        SELECT token, fecha_expiracion, 
               TIMESTAMPDIFF(MINUTE, NOW(), fecha_expiracion) as minutos_restantes,
               TIMESTAMPDIFF(HOUR, NOW(), fecha_expiracion) as horas_restantes
        FROM tokens_consent 
        WHERE cliente_id = :cliente_id 
        AND usado = 0 
        AND fecha_expiracion > NOW()
        ORDER BY fecha_creacion DESC
        LIMIT 1
    ");
    $stmtCheck->execute([':cliente_id' => $cliente['id']]);
    $tokenPendiente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($tokenPendiente) {
        $horas = $tokenPendiente['horas_restantes'];
        $minutos = $tokenPendiente['minutos_restantes'] % 60;
        
        $tiempoRestante = '';
        if ($horas > 0) {
            $tiempoRestante = $horas . ' hora' . ($horas > 1 ? 's' : '');
            if ($minutos > 0) {
                $tiempoRestante .= ' y ' . $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
            }
        } else {
            $tiempoRestante = $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
        }

        echo json_encode([
            'status' => 'warning',
            'message' => 'Ya existe un enlace pendiente de consentimiento para este cliente.',
            'tiempo_restante' => $tiempoRestante,
            'expira_en' => $tokenPendiente['fecha_expiracion'],
            'token_existente' => $tokenPendiente['token']
        ]);
        exit;
    }

    /* ──────────────────────────────────────────────
       CREAR TOKEN ALEATORIO Y REGISTRAR EN BD
    ────────────────────────────────────────────── */
    $token = bin2hex(random_bytes(16)); // token único de 32 caracteres
    $expiracion = date('Y-m-d H:i:s', strtotime('+48 hours'));

    $stmtToken = db()->prepare("
        INSERT INTO tokens_consent (cliente_id, token, fecha_expiracion)
        VALUES (:cliente_id, :token, :fecha_expiracion)
    ");
    $stmtToken->execute([
        ':cliente_id' => $cliente['id'],
        ':token' => $token,
        ':fecha_expiracion' => $expiracion
    ]);

    /* ──────────────────────────────────────────────
       GENERAR URL DE CONSENTIMIENTO
    ────────────────────────────────────────────── */
    $urlConsent = $url . '/consent-pending/?token=' . urlencode($token);

    /* ──────────────────────────────────────────────
       OBTENER PLANTILLA DEL MENSAJE
    ────────────────────────────────────────────── */
    $stmtMsg = db()->query("SELECT value FROM system_settings WHERE setting_name='wa_consent_pending' LIMIT 1");
    $mensajePlantilla = $stmtMsg->fetchColumn();
    if (!$mensajePlantilla) {
        $mensajePlantilla = $wa_consent_pending;
    }

    /* ──────────────────────────────────────────────
       PERSONALIZAR MENSAJE
    ────────────────────────────────────────────── */
    $mensaje = str_replace(
        ['{nombres}', '{apellidos}', '{cedula}', '{link}'],
        [$cliente['nombres'], $cliente['apellidos'], $cliente['identificacion'], $urlConsent],
        $mensajePlantilla
    );

    /* ──────────────────────────────────────────────
       ENVIAR POR WHATSAPP API
    ────────────────────────────────────────────── */
    $data = [
        'phonenumber' => $cliente['dialCode'] . $cliente['telefono'],
        'text' => $mensaje
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $urlEndpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    /* ──────────────────────────────────────────────
       VALIDAR RESPUESTA Y REGISTRAR FALLAS
    ────────────────────────────────────────────── */
    $success = false;
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        $success = !empty($decoded['success']);
    }

    if (!$success) {
        saveFailedWSMessage($data['phonenumber'], $data['text']);
    }

    /* ──────────────────────────────────────────────
       RESPUESTA FINAL
    ────────────────────────────────────────────── */
    echo json_encode([
        'status' => $success ? 'success' : 'error',
        'message' => $success
            ? 'Enlace de consentimiento enviado correctamente.'
            : 'No se pudo enviar por WhatsApp.',
        'token' => $token,
        'response' => $response
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno: ' . $e->getMessage()
    ]);
}
