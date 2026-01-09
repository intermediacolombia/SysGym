<?php
/**
 * resend_ws_outbox.php
 * Reintenta enviar los mensajes guardados en ws_outbox.
 * - Elimina de la tabla SOLO si la API responde success:true.
 * - Soporta ?limit=100 para procesar en lotes (default 200).
 */

header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../../inc/config.php'; // $host,$dbname,$dbuser,$dbpass,$api_ws
$apiKey      = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

// Traer pendientes (en orden FIFO)
$sql = "SELECT id, phonenumber, text, url FROM ws_outbox ORDER BY id ASC LIMIT :lim";
$st  = db()->prepare($sql);
$st->bindValue(':lim', $limit, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No hay mensajes pendientes.\n";
    exit;
}

$deleteSt = db()->prepare("DELETE FROM ws_outbox WHERE id = :id");

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
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        $successFlag = !empty($decoded['success']); // éxito real
    }

    if ($successFlag) {
        // borrar definitivamente
        $deleteSt->execute([':id' => $row['id']]);
        $ok++;
        echo "[OK] id={$row['id']} phone={$row['phonenumber']} http=$httpCode\n";
    } else {
        $fail++;
        $why = $error ? "curl: $error" : "http:$httpCode resp:$response";
        echo "[FAIL] id={$row['id']} phone={$row['phonenumber']} -> $why\n";
    }

    // (Opcional) pequeña pausa para no saturar la API
    // usleep(150000); // 150ms
}

echo "\nResumen: enviados OK=$ok, fallidos=$fail, total=" . count($rows) . "\n";




