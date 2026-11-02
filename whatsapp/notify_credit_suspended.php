<?php
// notify_credit_suspended.php
// Espera un array $suspended con: id, nombres, apellidos, dialCode, telefono, identificacion, total_credito, detalle_creditos
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php';

if (empty($wa_credit_suspended) || !isset($suspended) || !is_array($suspended)) {
    echo "Sin mensaje configurado o sin clientes."; return;
}

$apiKey      = $api_ws;
$urlEndpoint = rtrim(WA_API_URL, '/') . '/send';

foreach ($suspended as $c) {
    $url_pay = URLBASE . '/pay/?doc=' . $c['identificacion'];

    $message = str_replace(
        ['{nombres}', '{apellidos}', '{total_credito}', '{detalle_creditos}', '{url_pago}'],
        [$c['nombres'], $c['apellidos'], number_format((float)$c['total_credito'], 0, ',', '.'), $c['detalle_creditos'] ?? '', $url_pay],
        $wa_credit_suspended
    );

    $data = ['phonenumber' => $c['dialCode'] . $c['telefono'], 'text' => $message];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $urlEndpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    $ok = false;
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $ok = ws_sent_ok(json_decode($response, true));
    }
    if (!$ok) saveFailedWSMessage($data['phonenumber'], $data['text'], null);

    echo "Cliente {$c['id']} - HTTP {$httpCode}<br>";
}
