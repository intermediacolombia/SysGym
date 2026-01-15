<?php
/**
 * whatsapp/send_massive_pending.php
 * 
 * Envia UN SOLO mensaje por ejecucion del cron.
 * Limite maximo: 50 mensajes por dia.
 * 
 * Configuracion recomendada del cron:
 * - Cada 10 minutos para ~72 intentos/dia (pero maximo 50 envios)
 * - Cada 15 minutos para ~48 intentos/dia
 * - Cada 20 minutos para ~36 intentos/dia
 */

header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../whatsapp/save_failed_ws.php';

// ============================================================================
// CONFIGURACION
// ============================================================================
define('LIMITE_DIARIO', WA_MASS_LIMIT);          // Maximo de mensajes por dia
define('HORA_INICIO', WA_MASS_HOUR_START);       // Hora inicio (7 AM)
define('HORA_FIN', WA_MASS_HOUR_END);            // Hora fin (9 PM)
define('DIAS_PERMITIDOS', WA_MASS_DAYS);         // Dias permitidos (1=Lun, 7=Dom)

$apiKey      = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';
$baseUrl     = isset($url) ? rtrim($url, '/') : '';

// Fecha y hora actuales (usa zona horaria de config.php)
$fechaHoy = date('Y-m-d');
$horaActualStr = date('H:i:s');

// ============================================================================
// CREAR TABLA SI NO EXISTE
// ============================================================================
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

// ============================================================================
// CONTAR MENSAJES ENVIADOS HOY
// ============================================================================
function getMensajesHoy($fecha) {
    try {
        $sql = "SELECT COUNT(*) FROM ws_envios_log 
                WHERE fecha = :fecha";
        $stmt = db()->prepare($sql);
        $stmt->execute([':fecha' => $fecha]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        crearTablaEnvios();
        return 0;
    }
}

// ============================================================================
// REGISTRAR ENVIO
// ============================================================================
function registrarEnvio($telefono, $nombre, $mensaje, $resultado, $fecha, $hora, $errorDetalle = null) {
    try {
        $sql = "INSERT INTO ws_envios_log (telefono, nombre, mensaje, resultado, error_detalle, fecha, hora) 
                VALUES (:telefono, :nombre, :mensaje, :resultado, :error, :fecha, :hora)";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':telefono' => $telefono,
            ':nombre' => $nombre,
            ':mensaje' => mb_substr($mensaje, 0, 500),
            ':resultado' => $resultado,
            ':error' => $errorDetalle,
            ':fecha' => $fecha,
            ':hora' => $hora
        ]);
    } catch (PDOException $e) {
        // Silenciar
    }
}

// ============================================================================
// LIMPIAR LOGS ANTIGUOS (mas de 30 dias)
// ============================================================================
function limpiarLogsAntiguos($fecha) {
    try {
        $fecha30diasAtras = date('Y-m-d', strtotime($fecha . ' -30 days'));
        $sql = "DELETE FROM ws_envios_log WHERE fecha < :fecha";
        $stmt = db()->prepare($sql);
        $stmt->execute([':fecha' => $fecha30diasAtras]);
    } catch (PDOException $e) {
        // Silenciar
    }
}

// ============================================================================
// INICIO DEL SCRIPT
// ============================================================================

echo "===================================================================\n";
echo "[WS MASIVO] ENVIO MASIVO DE WHATSAPP - " . $fechaHoy . " " . $horaActualStr . "\n";
echo "===================================================================\n\n";

// Crear tabla si no existe
crearTablaEnvios();

// Limpiar logs antiguos (1 vez al dia aproximadamente)
if (rand(1, 100) <= 5) {
    limpiarLogsAntiguos($fechaHoy);
}

// ------------- VERIFICAR DIA DE LA SEMANA -------------
$diaActual = (int)date('N'); // 1=Lunes, 7=Domingo
$diasPermitidos = array_map('intval', explode(',', DIAS_PERMITIDOS));

if (!in_array($diaActual, $diasPermitidos)) {
    $nombresDias = [1=>'Lunes', 2=>'Martes', 3=>'Miercoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sabado', 7=>'Domingo'];
    $diasPermitidosNombres = array_map(function($d) use ($nombresDias) { 
        return $nombresDias[$d] ?? $d; 
    }, $diasPermitidos);
    
    echo "[DIA] Dia no permitido para envios\n";
    echo "      Dias permitidos: " . implode(', ', $diasPermitidosNombres) . "\n";
    echo "      Dia actual: " . ($nombresDias[$diaActual] ?? $diaActual) . "\n";
    exit;
}

// ------------- VERIFICAR HORARIO -------------
$horaActual = (int)date('G');
if ($horaActual < HORA_INICIO || $horaActual >= HORA_FIN) {
    echo "[HORA] Fuera de horario de envio\n";
    echo "       Horario permitido: " . HORA_INICIO . ":00 - " . HORA_FIN . ":00\n";
    echo "       Hora actual: " . date('H:i') . "\n";
    exit;
}

// ------------- VERIFICAR LIMITE DIARIO -------------
$enviadosHoy = getMensajesHoy($fechaHoy);
echo "[INFO] Mensajes enviados hoy: $enviadosHoy / " . LIMITE_DIARIO . "\n\n";

if ($enviadosHoy >= LIMITE_DIARIO) {
    echo "[LIMITE] LIMITE DIARIO ALCANZADO\n";
    echo "         Los envios continuaran manana.\n";
    exit;
}

// ------------- OBTENER UN MENSAJE PENDIENTE -------------
try {
    $sql = "SELECT id, nombre, telefono, mensaje, adjunto 
            FROM envios_masivos_ws 
            ORDER BY id ASC 
            LIMIT 1";
    
    $stmt = db()->prepare($sql);
    $stmt->execute();
    $pendiente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pendiente) {
        echo "[OK] No hay mensajes pendientes en la cola.\n";
        exit;
    }

} catch (PDOException $e) {
    echo "[ERROR] ERROR BD: " . $e->getMessage() . "\n";
    exit;
}

// ------------- PREPARAR DATOS -------------
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

echo "[PROCESO] Procesando mensaje:\n";
echo "          - ID: $id\n";
echo "          - Nombre: $nombre\n";
echo "          - Telefono: $telefono\n";
echo "          - Adjunto: " . ($adjuntoUrl ? 'Si' : 'No') . "\n\n";

// ------------- PREPARAR PAYLOAD -------------
$payload = [
    'phonenumber' => $telefono,
    'text'        => $mensaje
];

if (!empty($adjuntoUrl)) {
    $payload['url'] = $adjuntoUrl;
}

// ------------- ENVIAR MENSAJE -------------
echo "[ENVIO] Enviando mensaje...\n";

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

// ------------- VALIDAR RESULTADO -------------
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
        $errorDetalle = "API: " . ($decoded['error'] ?? 'Sin respuesta de exito');
    }
}

// ------------- PROCESAR RESULTADO -------------
$deleteSt = db()->prepare("DELETE FROM envios_masivos_ws WHERE id = :id");

if ($exito) {
    // EXITO: Eliminar de cola y registrar
    $deleteSt->execute([':id' => $id]);
    registrarEnvio($telefono, $nombre, $mensaje, 'enviado', $fechaHoy, $horaActualStr);
    
    echo "\n[OK] MENSAJE ENVIADO EXITOSAMENTE\n";
    echo "     Destinatario: $nombre\n";
    
} else {
    // FALLO: Guardar en ws_outbox, eliminar de cola y registrar
    saveFailedWSMessage($payload['phonenumber'], $payload['text'], $adjuntoUrl);
    $deleteSt->execute([':id' => $id]);
    registrarEnvio($telefono, $nombre, $mensaje, 'fallido', $fechaHoy, $horaActualStr, $errorDetalle);
    
    echo "\n[FAIL] ERROR AL ENVIAR\n";
    echo "       Destinatario: $nombre\n";
    echo "       Error: $errorDetalle\n";
    echo "       (Guardado en ws_outbox para reintento)\n";
}

// ------------- RESUMEN FINAL -------------
$pendientesRestantes = 0;
try {
    $stmt = db()->query("SELECT COUNT(*) FROM envios_masivos_ws");
    $pendientesRestantes = (int)$stmt->fetchColumn();
} catch (PDOException $e) {}

$enviadosHoyFinal = getMensajesHoy($fechaHoy);

echo "\n===================================================================\n";
echo "[RESUMEN]\n";
echo "  - Enviados hoy: $enviadosHoyFinal / " . LIMITE_DIARIO . "\n";
echo "  - Pendientes en cola: $pendientesRestantes\n";

if ($pendientesRestantes > 0) {
    $disponiblesHoy = LIMITE_DIARIO - $enviadosHoyFinal;
    $diasEstimados = ceil($pendientesRestantes / LIMITE_DIARIO);
    
    echo "  - Cupo restante hoy: $disponiblesHoy\n";
    echo "  - Dias estimados para completar: ~$diasEstimados\n";
}

echo "===================================================================\n";