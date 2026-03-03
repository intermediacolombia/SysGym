<?php
/**
 * send_valoracion.php
 * Envía por WhatsApp (360 Messenger) el enlace al PDF de la valoración.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/config.php';
require_once 'save_failed_ws.php';

/* ───────────── 0) PARÁMETROS ───────────── */
$valId = (int)($_POST['val_id'] ?? 0);
$cliId = (int)($_POST['cli_id'] ?? 0);
if (!$valId || !$cliId) {
    echo json_encode(['status'=>'error','msg'=>'IDs faltantes']); exit;
}

/* ───────────── 1) DATOS DEL CLIENTE ───────────── */
try {
    

    $sql = "SELECT nombres, apellidos, dialCode, telefono
            FROM clientes WHERE id = :id";
    $cli = db()->prepare($sql);
    $cli->execute([':id'=>$cliId]);
    $cli = $cli->fetch(PDO::FETCH_ASSOC);

    if (!$cli || empty($cli['telefono'])) {
        echo json_encode(['status'=>'error','msg'=>'Cliente no encontrado o sin teléfono']); exit;
    }
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]); exit;
}

/* ───────────── 2) GENERAR PDF TEMPORAL ───────────── */
$tempDir = __DIR__ . '/../pdf/archivos_temp/';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$pdfFilename = "valoracion_{$valId}.pdf";
$pdfFilePath = $tempDir . $pdfFilename;
$pdfUrl      = $url . '/pdf/archivos_temp/' . $pdfFilename;
$pdfSourceUrl = $url . '/pdf/?type=valoracion&id=' . $valId;

// Usar cURL en lugar de file_get_contents
$chPdf = curl_init($pdfSourceUrl);
curl_setopt_array($chPdf, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false, // solo si tienes problemas SSL en local
]);
$pdfContent = curl_exec($chPdf);
$pdfHttpCode = curl_getinfo($chPdf, CURLINFO_HTTP_CODE);
$pdfError    = curl_error($chPdf);
curl_close($chPdf);

if ($pdfContent === false || $pdfHttpCode !== 200) {
    echo json_encode(['status'=>'error','msg'=>"No se pudo generar el PDF. HTTP: $pdfHttpCode Error: $pdfError"]);
    exit;
}

$bytesWritten = file_put_contents($pdfFilePath, $pdfContent);
if (!$bytesWritten) {
    echo json_encode(['status'=>'error','msg'=>'No se pudo guardar el PDF en disco']);
    exit;
}

/* ───────────── 3) MENSAJE Y PAYLOAD ───────────── */
$mensaje = str_replace(
    ['{nombres}', '{apellidos}', '{url}'],
    [$cli['nombres'], $cli['apellidos'], $pdfUrl],
    $wa_valoracion
);

$payload = [
    'phonenumber' => $cli['dialCode'] . $cli['telefono'],
    'text'        => $mensaje,
    'url'         => $pdfUrl
];

/* ───────────── 4) ENVÍO cURL ───────────── */
$ch = curl_init(WA_API_URL . '/send');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $api_ws,
        'Content-Type: application/json',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

/* ───────────── 5) VALIDACIÓN Y GUARDADO ───────────── */
$successFlag = false;
if (!$error && $httpCode >= 200 && $httpCode < 300) {
    $decoded = json_decode($response, true);
    $successFlag = !empty($decoded['success']);
}

if ($successFlag) {
    echo json_encode(['status'=>'success','msg'=>'Enviado','response'=>$response]);
} else {
    saveFailedWSMessage($payload['phonenumber'], $payload['text'], $pdfUrl);

    if ($error) {
        echo json_encode(['status'=>'error','msg'=>$error]);
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(['status'=>'error','msg'=>'API respondió error','response'=>$response]);
    } else {
        echo json_encode(['status'=>'error','msg'=>"HTTP $httpCode",'response'=>$response]);
    }
}

/* ───────────── 6) ELIMINAR PDF TEMPORAL ───────────── */
/*if ($successFlag) {
    if (file_exists($pdfFilePath)) {
        // Esperar un momento para garantizar que 360 Messenger haya accedido
        sleep(3);
        unlink($pdfFilePath);
    }
}*/

?>












