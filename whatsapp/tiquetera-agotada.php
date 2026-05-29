<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php';

// Variables esperadas del contexto que incluye este archivo:
// $cp_nombres, $cp_apellidos, $cp_dialCode, $cp_telefono, $cp_entradas

if (empty($wa_tiquetera_agotada)) return;

$apiKey      = $api_ws;
$urlEndpoint = rtrim(WA_API_URL, '/') . '/send';

$mensaje = str_replace(
    ['{nombres}', '{apellidos}', '{entradas}'],
    [$cp_nombres,  $cp_apellidos,  $cp_entradas],
    $wa_tiquetera_agotada
);

$data = [
    'phonenumber' => $cp_dialCode . $cp_telefono,
    'text'        => $mensaje,
];

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

$successFlag = false;
if (!$error && $httpCode >= 200 && $httpCode < 300) {
    $decoded     = json_decode($response, true);
    $successFlag = !empty($decoded['success']);
}

if (!$successFlag) {
    saveFailedWSMessage($data['phonenumber'], $data['text']);
}
