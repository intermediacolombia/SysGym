<?php
/**
 * resend_ws_outbox.php
 * Reintenta enviar los mensajes guardados en ws_outbox.
 * - Elimina de la tabla SOLO si la API responde success:true.
 * - Soporta ?limit=100 para procesar en lotes (default 200).
 */

header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../../inc/config.php'; // $host,$dbname,$dbuser,$dbpass,$api_ws
ws_outbox_migrate();

$apiKey      = $api_ws;

$urlEndpoint = rtrim(WA_API_URL, '/') . '/send';

$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

// Traer pendientes (en orden FIFO) - solo status=pending
$sql = "SELECT id, phonenumber, text, url FROM ws_outbox WHERE status = 'pending' ORDER BY id ASC LIMIT :lim";
$st  = db()->prepare($sql);
$st->bindValue(':lim', $limit, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No hay mensajes pendientes.\n";
    return;
}

$deleteSt   = db()->prepare("DELETE FROM ws_outbox WHERE id = :id");
$markInvSt  = db()->prepare("UPDATE ws_outbox SET status='invalid', last_error=:err WHERE id = :id");
$markErrSt  = db()->prepare("UPDATE ws_outbox SET last_error=:err WHERE id = :id");

$ok = 0;
$fail = 0;

foreach ($rows as $row) {
    $payload = [
        'phonenumber' => $row['phonenumber'],
        'text'        => $row['text'],
    ];
    if (!empty($row['url'])) {
        $payload['url'] = $row['url']; // se envía solo si existe
    }

    // cURL
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
        CURLOPT_SSL_VERIFYPEER => true,
        // Si tu sistema requiere CA bundle explícito:
        // CURLOPT_CAINFO         => '/etc/ssl/certs/ca-certificates.crt',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
    $error    = curl_error($ch) ?: null;
    curl_close($ch);

    $successFlag = false;
    $decoded = null;
    $permanentFail = false;
    $decoded = json_decode((string)$response, true);
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $successFlag = ws_sent_ok($decoded);
    }
    // ponytail: la API devolvio JSON con success:false (ej. numero no registrado) -> no reintentar
    if (!$successFlag && is_array($decoded) && array_key_exists('success', $decoded) && $decoded['success'] === false) {
        $permanentFail = true;
    }

    // ponytail: log temporal para capturar la respuesta real de la API
    @file_put_contents(
        __DIR__ . '/ws_api_debug.log',
        '[' . date('Y-m-d H:i:s') . "] id={$row['id']} http=$httpCode ok=" . ($successFlag?'1':'0')
            . ' resp=' . substr((string)$response, 0, 500) . "\n",
        FILE_APPEND
    );

    if ($successFlag) {
        $deleteSt->execute([':id' => $row['id']]);
        $ok++;
        echo "[OK] id={$row['id']} phone={$row['phonenumber']} http=$httpCode\n";
    } elseif ($permanentFail) {
        // Error permanente -> marcar invalid, no reintentar en loop
        $errMsg = $decoded['error'] ?? 'success:false';
        $markInvSt->execute([':err' => mb_substr($errMsg, 0, 255), ':id' => $row['id']]);
        $fail++;
        echo "[INVALID] id={$row['id']} phone={$row['phonenumber']} -> $errMsg\n";
    } else {
        $why = $error ? "curl: $error" : "http:$httpCode";
        $markErrSt->execute([':err' => mb_substr($why, 0, 255), ':id' => $row['id']]);
        $fail++;
        echo "[RETRY] id={$row['id']} phone={$row['phonenumber']} -> $why\n";
    }

    // (Opcional) pequeña pausa para no saturar la API
    // usleep(150000); // 150ms
}

echo "\nResumen: enviados OK=$ok, fallidos=$fail, total=" . count($rows) . "\n";




