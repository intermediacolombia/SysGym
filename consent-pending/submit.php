<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$token = $_POST['token'] ?? '';
$firma = $_POST['firma_digital_cliente'] ?? '';

if (!$token || !$firma) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
    exit;
}

try {
    

    /* ──────────────────────────────
      Crear tabla si no existe
    ────────────────────────────── */
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

    /* ──────────────────────────────
       Validar token
    ────────────────────────────── */
    $stmt = db()->prepare("
        SELECT tc.*, c.id AS cliente_id, c.nombres, c.apellidos, c.email, c.telefono, c.dialCode
        FROM tokens_consent tc
        JOIN clientes c ON c.id = tc.cliente_id
        WHERE tc.token = :token AND tc.usado = 0 AND tc.fecha_expiracion > NOW()
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        echo json_encode(['status' => 'error', 'message' => 'El enlace ha expirado o no es válido.']);
        exit;
    }

    $clienteId = $tokenData['cliente_id'];

    /* ──────────────────────────────
       Verificar si ya existe consentimiento
    ────────────────────────────── */
    $stmtCheck = db()->prepare("SELECT id FROM formularios WHERE cliente_id = :cid LIMIT 1");
    $stmtCheck->execute([':cid' => $clienteId]);
    $existe = $stmtCheck->fetch();

    if ($existe) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe un consentimiento firmado para este cliente.']);
        exit;
    }

    /* ──────────────────────────────
       Insertar consentimiento
    ────────────────────────────── */
    $stmtInsert = db()->prepare("
        INSERT INTO formularios (cliente_id, fecha, firma_digital_cliente)
        VALUES (:cid, NOW(), :firma)
    ");
    $stmtInsert->execute([
        ':cid'   => $clienteId,
        ':firma' => $firma
    ]);
    $formulario_id = db()->lastInsertId();

    /* ──────────────────────────────
       Marcar token como usado
    ────────────────────────────── */
    $stmtUpdate = db()->prepare("UPDATE tokens_consent SET usado = 1 WHERE token = :token");
    $stmtUpdate->execute([':token' => $token]);

    /* ─────────────────────────────
       Enviar notificaciones (si aplica)
    ────────────────────────────── */
    $data = [
        'nombres' => $tokenData['nombres'],
        'apellidos' => $tokenData['apellidos'],
        'email' => $tokenData['email'],
        'telefono' => $tokenData['telefono'],
        'dialCode' => $tokenData['dialCode'],
        'formulario_id' => $formulario_id
    ];

    // Correo (solo si hay email)
    if (!empty($data['email'])) {
        include_once __DIR__ . '/../mailer/consent.php';
    }

    // WhatsApp
    ob_start();
    include_once __DIR__ . '/../whatsapp/consent.php';
    ob_end_clean();

    echo json_encode([
        'status' => 'success',
        'message' => 'Consentimiento firmado y registrado correctamente.'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
}
