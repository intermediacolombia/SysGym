<?php
/**
 * whatsapp/send_massive_pending.php
 * 
 * Envía UN SOLO mensaje por ejecución del cron.
 * Límite máximo: 50 mensajes por día.
 * 
 * Configuración recomendada del cron:
 * - Cada 10 minutos para ~72 intentos/día (pero máximo 50 envíos)
 * - Cada 15 minutos para ~48 intentos/día
 * - Cada 20 minutos para ~36 intentos/día
 * 
 * Ejemplo cron cada 15 minutos:
*/

header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../whatsapp/save_failed_ws.php';

// ═══════════════════════════════════════════════════════════════════════════
// CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════════════════
define('LIMITE_DIARIO', 2);          // Máximo de mensajes por día
define('HORA_INICIO', 7);              // Hora inicio (7 AM)
define('HORA_FIN', 21);                // Hora fin (9 PM)

$apiKey      = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';
$baseUrl     = isset($url) ? rtrim($url, '/') : '';

// ═══════════════════════════════════════════════════════════════════════════
// CREAR TABLA SI NO EXISTE
// ═══════════════════════════════════════════════════════════════════════════
function crearTablaEnvios() {
    $sql = "CREATE TABLE IF NOT EXISTS ws_envios_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        telefono VARCHAR(20) NOT NULL,
        nombre VARCHAR(100) DEFAULT NULL,
        mensaje TEXT,
        resultado ENUM('enviado', 'fallido') NOT NULL,
        error_detalle VARCHAR(255) DEFAULT NULL,
        fecha DATE NOT NULL,
        hora TIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fecha (fecha),
        INDEX idx_resultado (resultado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        db()->exec($sql);
    } catch (PDOException $e) {
        // Tabla ya existe o error silenciado
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// CONTAR MENSAJES ENVIADOS HOY
// ═══════════════════════════════════════════════════════════════════════════
function getMensajesHoy() {
    try {
        $sql = "SELECT COUNT(*) FROM ws_envios_log 
                WHERE fecha = CURDATE() AND resultado = 'enviado'";
        $stmt = db()->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        crearTablaEnvios();
        return 0;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// REGISTRAR ENVÍO
// ═══════════════════════════════════════════════════════════════════════════
function registrarEnvio($telefono, $nombre, $mensaje, $resultado, $errorDetalle = null) {
    try {
        $sql = "INSERT INTO ws_envios_log (telefono, nombre, mensaje, resultado, error_detalle, fecha, hora) 
                VALUES (:telefono, :nombre, :mensaje, :resultado, :error, CURDATE(), CURTIME())";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':telefono' => $telefono,
            ':nombre' => $nombre,
            ':mensaje' => mb_substr($mensaje, 0, 500), // Solo guardar primeros 500 chars
            ':resultado' => $resultado,
            ':error' => $errorDetalle
        ]);
    } catch (PDOException $e) {
        // Silenciar
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// LIMPIAR LOGS ANTIGUOS (más de 30 días)
// ═══════════════════════════════════════════════════════════════════════════
function limpiarLogsAntiguos() {
    try {
        db()->exec("DELETE FROM ws_envios_log WHERE fecha < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    } catch (PDOException $e) {
        // Silenciar
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// INICIO DEL SCRIPT
// ═══════════════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════\n";
echo "📱 ENVÍO MASIVO DE WHATSAPP - " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Crear tabla si no existe
crearTablaEnvios();

// Limpiar logs antiguos (1 vez al día aproximadamente)
if (rand(1, 100) <= 5) {
    limpiarLogsAntiguos();
}

// ───────────── VERIFICAR HORARIO ─────────────
$horaActual = (int)date('G');
if ($horaActual < HORA_INICIO || $horaActual >= HORA_FIN) {
    echo "⏰ Fuera de horario de envío\n";
    echo "   Horario permitido: " . HORA_INICIO . ":00 - " . HORA_FIN . ":00\n";
    echo "   Hora actual: " . date('H:i') . "\n";
    exit;
}

// ───────────── VERIFICAR LÍMITE DIARIO ─────────────
$enviadosHoy = getMensajesHoy();
echo "📊 Mensajes enviados hoy: $enviadosHoy / " . LIMITE_DIARIO . "\n\n";

if ($enviadosHoy >= LIMITE_DIARIO) {
    echo "⚠️ LÍMITE DIARIO ALCANZADO\n";
    echo "   Los envíos continuarán mañana.\n";
    exit;
}

// ───────────── OBTENER UN MENSAJE PENDIENTE ─────────────
try {
    $sql = "SELECT id, nombre, telefono, mensaje, adjunto 
            FROM envios_masivos_ws 
            ORDER BY id ASC 
            LIMIT 1";
    
    $stmt = db()->prepare($sql);
    $stmt->execute();
    $pendiente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pendiente) {
        echo "✅ No hay mensajes pendientes en la cola.\n";
        exit;
    }

} catch (PDOException $e) {
    echo "❌ ERROR BD: " . $e->getMessage() . "\n";
    exit;
}

// ───────────── PREPARAR DATOS ─────────────
$id       = (int)$pendiente['id'];
$nombre   = $pendiente['nombre'];
$telefono = preg_replace('/[^0-9]/', '', $pendiente['telefono']);
$mensaje  = $pendiente['mensaje'];
$adjunto  = $pendiente['adjunto'] ?? null;

// Construir URL del adjunto
$adjuntoUrl = null;
if (!empty($adjunto)) {
    if (preg_match('/^https?:\/\//i', $adjunto)) {
        $adjuntoUrl = $adjunto;
    } else {
        $adjuntoUrl = $baseUrl . '/' . ltrim($adjunto, '/');
    }
}

echo "📤 Procesando mensaje:\n";
echo "   • ID: $id\n";
echo "   • Nombre: $nombre\n";
echo "   • Teléfono: $telefono\n";
echo "   • Adjunto: " . ($adjuntoUrl ? 'Sí' : 'No') . "\n\n";

// ───────────── PREPARAR PAYLOAD ─────────────
$payload = [
    'phonenumber' => $telefono,
    'text'        => $mensaje
];

if (!empty($adjuntoUrl)) {
    $payload['url'] = $adjuntoUrl;
}

// ───────────── ENVIAR MENSAJE ─────────────
echo "📡 Enviando mensaje...\n";

$ch = curl_init($urlEndpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
$curlError = curl_error($ch) ?: null;
curl_close($ch);

// ───────────── VALIDAR RESULTADO ─────────────
$exito = false;
$errorDetalle = null;

if ($curlError) {
    $errorDetalle = "CURL: $curlError";
} elseif ($httpCode < 200 || $httpCode >= 300) {
    $errorDetalle = "HTTP: $httpCode";
} else {
    $decoded = json_decode($response, true);
    if (!empty($decoded['success'])) {
        $exito = true;
    } else {
        $errorDetalle = "API: " . ($decoded['error'] ?? 'Sin respuesta de éxito');
    }
}

// ───────────── PROCESAR RESULTADO ─────────────
$deleteSt = db()->prepare("DELETE FROM envios_masivos_ws WHERE id = :id");

if ($exito) {
    // ÉXITO: Eliminar de cola y registrar
    $deleteSt->execute([':id' => $id]);
    registrarEnvio($telefono, $nombre, $mensaje, 'enviado');
    
    echo "\n✅ MENSAJE ENVIADO EXITOSAMENTE\n";
    echo "   Destinatario: $nombre\n";
    
} else {
    // FALLO: Guardar en ws_outbox, eliminar de cola y registrar
    saveFailedWSMessage($payload['phonenumber'], $payload['text'], $adjuntoUrl);
    $deleteSt->execute([':id' => $id]);
    registrarEnvio($telefono, $nombre, $mensaje, 'fallido', $errorDetalle);
    
    echo "\n❌ ERROR AL ENVIAR\n";
    echo "   Destinatario: $nombre\n";
    echo "   Error: $errorDetalle\n";
    echo "   (Guardado en ws_outbox para reintento)\n";
}

// ───────────── RESUMEN FINAL ─────────────
$pendientesRestantes = 0;
try {
    $stmt = db()->query("SELECT COUNT(*) FROM envios_masivos_ws");
    $pendientesRestantes = (int)$stmt->fetchColumn();
} catch (PDOException $e) {}

$enviadosHoyFinal = getMensajesHoy();

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📊 RESUMEN:\n";
echo "   • Enviados hoy: $enviadosHoyFinal / " . LIMITE_DIARIO . "\n";
echo "   • Pendientes en cola: $pendientesRestantes\n";

if ($pendientesRestantes > 0) {
    $disponiblesHoy = LIMITE_DIARIO - $enviadosHoyFinal;
    $diasEstimados = ceil($pendientesRestantes / LIMITE_DIARIO);
    
    echo "   • Cupo restante hoy: $disponiblesHoy\n";
    echo "   • Días estimados para completar: ~$diasEstimados\n";
}

echo "═══════════════════════════════════════════════════════════════\n";