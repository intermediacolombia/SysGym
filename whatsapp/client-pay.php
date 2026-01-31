<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // función para guardar fallidos

$apiKey = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

/* ───────────── 1) VALIDAR QUE EXISTA facturaId ───────────── */
if (empty($facturaId)) {
    echo 'Error: No se proporcionó ID de factura.';
    exit;
}

/* ───────────── 2) MENSAJE DE FACTURA ───────────── */
$mensaje = $wa_client_pay;
$mensaje = str_replace(
    ["{nombres}", "{apellidos}", "{fecha_pago}", "{fecha_vencimiento}"],
    [$cp_nombres, $cp_apellidos, $cp_pago_plan, $cp_vencimiento_plan],
    $mensaje
);

/* ───────────── 3) GENERAR PDF TEMPORAL ───────────── */
// Carpeta temporal para guardar el PDF
$tempDir = __DIR__ . '/../pdf/archivos_temp/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Nombre LIMPIO del archivo temporal (sin espacios ni caracteres especiales)
$pdfFilename = "invoice_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $facturaId) . "_" . time() . ".pdf";
$pdfFilePath = $tempDir . $pdfFilename;
$pdfUrl      = $url . '/pdf/archivos_temp/' . $pdfFilename;

// Generar el PDF desde el generador existente
$pdfSourceUrl = $url . '/pdf/?type=invoice&id=' . urlencode($facturaId);

// Configurar contexto para file_get_contents con timeout
$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'ignore_errors' => true
    ]
]);

// Descargar y guardar el PDF temporalmente
$pdfContent = @file_get_contents($pdfSourceUrl, false, $context);

if ($pdfContent === false || empty($pdfContent)) {
    echo 'Error: No se pudo generar el PDF de la factura.';
    echo '<br>URL intentada: ' . $pdfSourceUrl;
    exit;
}

// Guardar el PDF con el nombre correcto
$saveResult = file_put_contents($pdfFilePath, $pdfContent);
if ($saveResult === false) {
    echo 'Error: No se pudo guardar el PDF en el servidor.';
    exit;
}

// Verificar que el archivo se guardó correctamente
if (!file_exists($pdfFilePath) || filesize($pdfFilePath) === 0) {
    echo 'Error: El archivo PDF no se guardó correctamente.';
    exit;
}

/* ───────────── 4) PREPARAR MENSAJE Y PAYLOAD ───────────── */
$phoneNumber = $cp_dialCode . $cp_telefono;

// Validar número de teléfono
if (empty($phoneNumber) || strlen($phoneNumber) < 10) {
    echo 'Error: Número de teléfono inválido.';
    exit;
}

$data = [
    'phonenumber' => $phoneNumber,
    'text'        => $mensaje,
    'url'         => $pdfSourceUrl
];

/* ───────────── 5) ENVÍO VIA CURL ───────────── */
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
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

/* ───────────── 6) VALIDACIÓN DE RESPUESTA ───────────── */
$successFlag = false;
$errorMessage = '';

if ($error) {
    $errorMessage = "Error CURL: " . $error;
} elseif ($httpCode < 200 || $httpCode >= 300) {
    $errorMessage = "HTTP Error Code: " . $httpCode;
} else {
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMessage = "Error al decodificar JSON: " . json_last_error_msg();
    } elseif (empty($decoded['success'])) {
        $errorMessage = "La API respondió sin éxito: " . print_r($decoded, true);
    } else {
        $successFlag = true;
    }
}

/* ───────────── 7) MANEJO DE FALLOS ───────────── */
if (!$successFlag) {
    // Guarda registro en BD (mantiene el PDF para reenviar)
    saveFailedWSMessage($phoneNumber, $mensaje, $pdfUrl);
    
    echo "❌ Error al enviar mensaje de WhatsApp<br>";
    echo "Detalles: " . htmlspecialchars($errorMessage) . "<br>";
    echo "Respuesta API: " . htmlspecialchars($response) . "<br>";
} else {
    echo "✅ Mensaje enviado exitosamente<br>";
    echo "Número: " . htmlspecialchars($phoneNumber) . "<br>";
    echo "PDF generado: " . htmlspecialchars($pdfFilename) . "<br>";
    
    /* ───────────── 8) LIMPIEZA DE ARCHIVO TEMPORAL (OPCIONAL) ───────────── */
    // Si deseas eliminar el PDF después de enviarlo con éxito:
    // Esperar unos segundos para asegurar que la API lo descargó
    sleep(5);
    if (file_exists($pdfFilePath)) {
        unlink($pdfFilePath);
        echo "🗑️ PDF temporal eliminado<br>";
    }
}

/* ───────────── 9) LOG DE DEBUG (OPCIONAL - COMENTAR EN PRODUCCIÓN) ───────────── */
// echo "<pre>";
// echo "PDF Path: " . $pdfFilePath . "\n";
// echo "PDF URL: " . $pdfUrl . "\n";
// echo "File exists: " . (file_exists($pdfFilePath) ? 'YES' : 'NO') . "\n";
// echo "File size: " . (file_exists($pdfFilePath) ? filesize($pdfFilePath) : 0) . " bytes\n";
// echo "</pre>";

?>








