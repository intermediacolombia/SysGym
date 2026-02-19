<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // función para guardar fallidos

$apiKey = $api_ws;
//$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';
$urlEndpoint = WA_API_URL.'/send';

/* ───────────── 1) MENSAJE DE CONSENTIMIENTO ───────────── */
$mensaje = $wa_consent;
$mensaje = str_replace(
    ["{nombres}", "{apellidos}"],
    [$data['nombres'], $data['apellidos']],
    $mensaje
);

/* ───────────── 2) GENERAR PDF TEMPORAL ───────────── */
// Carpeta temporal
$tempDir = __DIR__ . '/../pdf/archivos_temp/';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

// Nombre del archivo temporal
$pdfFilename = "consent_" . $formulario_id . ".pdf";
$pdfFilePath = $tempDir . $pdfFilename;
$pdfUrl      = $url . '/pdf/archivos_temp/' . $pdfFilename;

// Generar el PDF desde tu generador existente
$pdfSourceUrl = $url . '/pdf/?type=consent&id=' . $formulario_id;

// Descargar y guardar el PDF temporalmente
$pdfContent = file_get_contents($pdfSourceUrl);
if ($pdfContent === false) {
    echo 'Error: No se pudo generar el PDF de consentimiento.';
    exit;
}
file_put_contents($pdfFilePath, $pdfContent);

/* ───────────── 3) PREPARAR MENSAJE Y PAYLOAD ───────────── */
$data = [
    'phonenumber' => $data['dialCode'] . $data['telefono'],
    'text'        => $mensaje,
    'url'         => $pdfUrl
];

/* ───────────── 4) ENVÍO VIA CURL ───────────── */
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
$error    = curl_error($ch);
curl_close($ch);

/* ───────────── 5) VALIDAR RESPUESTA ───────────── */
$successFlag = false;
if (!$error && $httpCode >= 200 && $httpCode < 300) {
    $decoded = json_decode($response, true);
    $successFlag = !empty($decoded['success']);
}

/* ───────────── 6) MANEJO DE FALLOS ───────────── */
if (!$successFlag) {
    // Guarda registro y conserva el PDF
    saveFailedWSMessage($data['phonenumber'], $data['text'], $pdfUrl);
}

/* ───────────── 7) LIMPIEZA DE ARCHIVO TEMPORAL ───────────── */
/*if ($successFlag && file_exists($pdfFilePath)) {
    sleep(3); // esperar que 360 Messenger descargue
    unlink($pdfFilePath);
}*/

/* ───────────── 8) SALIDA ───────────── */
if ($error) {
    echo 'Error: ' . $error;
} else {
    echo "HTTP Code: $httpCode<br>";
    echo "Response: " . $response;
}
?>




