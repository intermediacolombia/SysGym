<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // función para guardar fallidos

$apiKey = $api_ws;
$urlEndpoint = WA_API_URL;

/* ───────────── 1) MENSAJE DE FACTURA ───────────── */
$mensaje = $wa_client_pay;
$mensaje = str_replace(
    ["{nombres}", "{apellidos}", "{fecha_pago}", "{fecha_vencimiento}"],
    [$cp_nombres, $cp_apellidos, $cp_pago_plan, $cp_vencimiento_plan],
    $mensaje
);

/* ───────────── 2) GENERAR PDF TEMPORAL ───────────── */
// Carpeta temporal para guardar el PDF
$tempDir = __DIR__ . '/../pdf/archivos_temp/';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

// Nombre del archivo temporal
$pdfFilename = "invoice_" . $facturaId . ".pdf";
$pdfFilePath = $tempDir . $pdfFilename;
$pdfUrl      = $url . '/pdf/archivos_temp/' . $pdfFilename;

// Generar el PDF desde el generador existente
$pdfSourceUrl = $url . '/pdf/?type=invoice&id=' . $facturaId;

// Descargar y guardar el PDF temporalmente
$pdfContent = file_get_contents($pdfSourceUrl);
if ($pdfContent === false) {
    echo 'Error: No se pudo generar el PDF de la factura.';
    exit;
}
file_put_contents($pdfFilePath, $pdfContent);

/* ───────────── 3) PREPARAR MENSAJE Y PAYLOAD ───────────── */
$data = [
    'phonenumber' => $cp_dialCode . $cp_telefono,
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
    // Guarda registro en BD (mantiene el PDF para reenviar)
    saveFailedWSMessage($data['phonenumber'], $data['text'], $pdfUrl);
}

/* ───────────── 7) LIMPIEZA DE ARCHIVO TEMPORAL ───────────── */
/*if ($successFlag && file_exists($pdfFilePath)) {
    sleep(3); // esperar unos segundos antes de borrar
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








