<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // función para guardar fallidos

$apiKey = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

/* ───────────── 1) MENSAJE DE PAGO GENERAL ───────────── */
$mensaje = $wa_client_pay_general;

// Reemplazar variables dinámicas en el mensaje
$mensaje = str_replace(
    ["{nombres}", "{apellidos}", "{fecha_pago}", "{valor}"],
    [
        $clientInfo['nombres'] ?? '',
        $clientInfo['apellidos'] ?? '',
        date('Y-m-d'),
        '$' . number_format($valor ?? 0, 0, ',', '.')
    ],
    $mensaje
);

/* ───────────── 2) GENERAR PDF TEMPORAL ───────────── */
// Carpeta temporal para guardar el PDF
$tempDir = __DIR__ . '/../pdf/archivos_temp/';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

// Nombre del archivo temporal
$pdfFilename = "invoice_" . $facturaId . ".pdf";
$pdfFilePath = $tempDir . $pdfFilename;
$pdfUrl      = rtrim($url, '/') . '/pdf/archivos_temp/' . $pdfFilename;

// URL del generador de PDF existente
$pdfSourceUrl = rtrim($url, '/') . '/pdf/?type=invoice&id=' . $facturaId;

// Descargar el PDF directamente
$pdfContent = file_get_contents($pdfSourceUrl);
if ($pdfContent === false || strlen($pdfContent) < 1000) {
    // Registrar error si el PDF no se generó correctamente
    file_put_contents(
        __DIR__ . '/pdf_error_log.txt',
        "[" . date('Y-m-d H:i:s') . "] Error generando PDF (masivo): $pdfSourceUrl\n",
        FILE_APPEND
    );
    echo 'Error: No se pudo generar el PDF de la factura general.';
    exit;
}
file_put_contents($pdfFilePath, $pdfContent);

/* ───────────── 3) PREPARAR MENSAJE Y PAYLOAD ───────────── */
$telefonoPlano = preg_replace('/\D+/', '', ($clientInfo['dialCode'] ?? '') . ($clientInfo['telefono'] ?? ''));

$data = [
    'phonenumber' => $telefonoPlano,
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

/* ───────────── 5) VALIDACIÓN DE RESPUESTA ───────────── */
$successFlag = false;
if (!$error && $httpCode >= 200 && $httpCode < 300) {
    $decoded = json_decode($response, true);
    $successFlag = !empty($decoded['success']);
}

/* ───────────── 6) MANEJO DE FALLOS ───────────── */
if (!$successFlag) {
    saveFailedWSMessage($data['phonenumber'], $data['text'], $pdfUrl);
}

/* ───────────── 7) OPCIONAL: mantener PDF para referencia ───────────── */
// Si quieres eliminarlo tras enviarlo exitosamente, descomenta:
// if ($successFlag && file_exists($pdfFilePath)) {
//     sleep(3);
//     unlink($pdfFilePath);
// }

/* ───────────── 8) SALIDA ───────────── */
if ($error) {
    echo 'Error: ' . $error;
} else {
    echo "HTTP Code: $httpCode<br>";
    echo "Response: " . $response;
}
?>









