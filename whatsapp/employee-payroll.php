<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // función para guardar fallidos

$apiKey = $api_ws;
$urlEndpoint = WA_API_URL;

/* ───────────── 1) MENSAJE DE NÓMINA ───────────── */
$mensaje = "Hola {nombres} {apellidos},\n\nTu nómina ha sido procesada con éxito.\n\nDetalles:\n- Fecha de inicio: {fecha_pago}\n- Fecha de fin: {fecha_vencimiento}\n- Total días laborados: {total_dias}\n- Valor pagado: ${valor_pagado}\n\nGracias por tu dedicación.";

$mensaje = str_replace(
    ["{nombres}", "{apellidos}", "{fecha_pago}", "{fecha_vencimiento}", "{total_dias}", "{valor_pagado}"],
    [$cp_nombres, $cp_apellidos, $cp_pago_plan, $cp_vencimiento_plan, $dias_pagados, number_format($valor_pagado, 0, ',', '.')],
    $mensaje
);

/* ───────────── 2) GENERAR PDF TEMPORAL ───────────── */
// Carpeta temporal para guardar el PDF
$tempDir = __DIR__ . '/../pdf/archivos_temp/';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

// Nombre del archivo temporal
$pdfFilename = "payroll_" . $facturaId . ".pdf";
$pdfFilePath = $tempDir . $pdfFilename;
$pdfUrl      = $url . '/pdf/archivos_temp/' . $pdfFilename;

// Generar el PDF desde el generador existente
$pdfSourceUrl = $url . '/pdf/?type=payroll&id=' . $facturaId;

// Descargar y guardar el PDF temporal
$pdfContent = file_get_contents($pdfSourceUrl);
if ($pdfContent === false) {
    echo 'Error: No se pudo generar el PDF de nómina.';
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



if (!$successFlag) {
    echo "DEBUG - Guardando fallido con: '" . $data['phonenumber'] . "'<br>";
    saveFailedWSMessage($data['phonenumber'], $data['text'], $pdfUrl);
}

/* ───────────── 6) MANEJO DE FALLOS ───────────── */
if (!$successFlag) {
    // Guardar registro del mensaje fallido (mantiene el PDF para reenviar)
    saveFailedWSMessage($data['phonenumber'], $data['text'], $pdfUrl);
}

/* ───────────── 7) LIMPIEZA DE ARCHIVO TEMPORAL ───────────── */
/*if ($successFlag && file_exists($pdfFilePath)) {
    sleep(3); // esperar que WhatsApp lo descargue
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








