<?php
/**
 * whatsapp/send_massive_pending.php
 * 
 * Envía hasta 10 mensajes masivos pendientes desde envios_masivos_ws.
 * - Si falla: guarda en ws_outbox usando saveFailedWSMessage()
 * - Si tiene éxito: elimina el registro de envios_masivos_ws
 * 
 * Uso: ejecutar manualmente o vía cron cada 5 minutos
 */

header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../whatsapp/save_failed_ws.php'; // saveFailedWSMessage($phone, $text, $url)

$apiKey      = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

// Base URL para convertir rutas relativas a URLs públicas
$baseUrl = isset($url) ? rtrim($url, '/') : '';

/* ───────────── 1) TRAER 10 PENDIENTES ───────────── */
try {
    $sql = "SELECT id, nombre, telefono, mensaje, adjunto 
            FROM envios_masivos_ws 
            ORDER BY id ASC 
            LIMIT 10";
    
    $stmt = db()->prepare($sql);
    $stmt->execute();
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$pendientes) {
        echo "No hay mensajes pendientes.\n";
        exit;
    }

} catch (PDOException $e) {
    echo "ERROR BD: " . $e->getMessage() . "\n";
    exit;
}

/* ───────────── 2) PREPARAR DELETE ───────────── */
$deleteSt = db()->prepare("DELETE FROM envios_masivos_ws WHERE id = :id");

$ok = 0;
$fail = 0;

/* ───────────── 3) ENVIAR CADA MENSAJE ───────────── */
foreach ($pendientes as $row) {
    $id       = (int)$row['id'];
    $nombre   = $row['nombre'];
    $telefono = preg_replace('/[^0-9]/', '', $row['telefono']); // limpiar
    $mensaje  = $row['mensaje'];
    $adjunto  = $row['adjunto'] ?? null;

    // Construir URL pública del adjunto si existe
    $adjuntoUrl = null;
    if (!empty($adjunto)) {
        if (preg_match('/^https?:\/\//i', $adjunto)) {
            // Ya es URL completa
            $adjuntoUrl = $adjunto;
        } else {
            // Es ruta relativa, convertir a URL pública
            $adjuntoUrl = $baseUrl . '/' . ltrim($adjunto, '/');
        }
    }

    /* ───────────── 4) PAYLOAD ───────────── */
    $payload = [
        'phonenumber' => $telefono,
        'text'        => $mensaje
    ];

    // Agregar URL solo si existe (igual que send_valoracion.php)
    if (!empty($adjuntoUrl)) {
        $payload['url'] = $adjuntoUrl;
    }

    /* ───────────── 5) ENVÍO cURL ───────────── */
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
    $error    = curl_error($ch) ?: null;
    curl_close($ch);

    /* ───────────── 6) VALIDAR ÉXITO ───────────── */
    $successFlag = false;
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        $successFlag = !empty($decoded['success']);
    }

    /* ───────────── 7) PROCESAR RESULTADO ───────────── */
    if ($successFlag) {
        // ÉXITO: eliminar de la tabla
        $deleteSt->execute([':id' => $id]);
        $ok++;
        echo "[OK] id=$id nombre='$nombre' phone=$telefono http=$httpCode\n";
    } else {
        // FALLO: guardar en ws_outbox
        saveFailedWSMessage($payload['phonenumber'], $payload['text'], $adjuntoUrl);
        $fail++;
        
        $why = $error ? "curl: $error" : "http:$httpCode resp:$response";
        echo "[FAIL] id=$id nombre='$nombre' phone=$telefono -> $why\n";
    }

    // Pausa opcional para no saturar la API (150ms entre mensajes)
    usleep(150000);
}

/* ───────────── 8) RESUMEN ───────────── */
echo "\n═══════════════════════════════════════\n";
echo "Resumen del envío masivo:\n";
echo "  ✓ Enviados OK: $ok\n";
echo "  ✗ Fallidos: $fail\n";
echo "  Total procesados: " . count($pendientes) . "\n";
echo "═══════════════════════════════════════\n";