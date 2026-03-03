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
    $stmt = db()->prepare($sql);
    $stmt->execute([':id'=>$cliId]);
    $cli = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cli || empty($cli['telefono'])) {
        echo json_encode(['status'=>'error','msg'=>'Cliente no encontrado o sin teléfono']); exit;
    }
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]); exit;
}

/* ───────────── 2) GENERAR PDF TEMPORAL ───────────── */
$tempDir = __DIR__ . '/../pdf/archivos_temp/';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$pdfFilename  = "valoracion_{$valId}.pdf";
$pdfFilePath  = $tempDir . $pdfFilename;
$pdfUrl       = rtrim($url, '/') . '/pdf/archivos_temp/' . $pdfFilename;
$pdfSourceUrl = rtrim($url, '/') . '/pdf/?type=valoracion&id=' . $valId;

$pdfContent = file_get_contents($pdfSourceUrl);
if ($pdfContent === false || strlen($pdfContent) < 1000) {
    file_put_contents(
        __DIR__ . '/pdf_error_log.txt',
        "[" . date('Y-m-d H:i:s') . "] Error generando PDF valoracion: $pdfSourceUrl\n",
        FILE_APPEND
    );
    echo json_encode(['status'=>'error','msg'=>'No se pudo generar el PDF de la valoración']); exit;
}
file_put_contents($pdfFilePath, $pdfContent);

/* ───────────── 3) MENSAJE Y PAYLOAD ───────────── */
$mensaje = str_replace(
    ['{nombres}', '{apellidos}', '{url}'],
    [$cli['nombres'], $cli['apellidos'], $pdfUrl],
    $wa_valoracion
);

// ← fix principal: limpiar el teléfono igual que los scripts que funcionan
$telefonoPlano = preg_replace('/\D+/', '', ($cli['dialCode'] ?? '') . ($cli['telefono'] ?? ''));

$payload = [
    'phonenumber' => $telefonoPlano,
    'text'        => $mensaje,
    'url'         => $pdfUrl
];

/* ───────────── 4) ENVÍO cURL ───────────── */
$ch = curl_init(rtrim(WA_API_URL, '/') . '/send');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
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
    $decoded     = json_decode($response, true);
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
        sleep(3);
        unlink($pdfFilePath);
    }
}*/
?>












