<?php
/**
 * send_valoracion.php
 * Envía por WhatsApp (360 Messenger) el enlace al PDF de la valoración.
 * Entrada  POST: val_id   cli_id
 * Salida   JSON: {status, msg, response?}
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/config.php';              // ← $host, $dbuser, $dbpass, $api_ws, $url

/* ────────────────── 0) PARÁMETROS ────────────────── */
$valId = (int)($_POST['val_id'] ?? 0);   // id de la valoración (para el PDF)
$cliId = (int)($_POST['cli_id'] ?? 0);   // id del cliente
if (!$valId || !$cliId) {
    echo json_encode(['status'=>'error','msg'=>'IDs faltantes']); exit;
}

/* ────────────────── 1) DATOS DEL CLIENTE ─────────── */
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8",$dbuser,$dbpass,
                   [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

    $sql = "SELECT nombres, apellidos, dialCode, telefono
            FROM clientes WHERE id = :id";
    $cli = $pdo->prepare($sql);
    $cli->execute([':id'=>$cliId]);
    $cli = $cli->fetch(PDO::FETCH_ASSOC);

    if (!$cli || empty($cli['telefono'])) {
        echo json_encode(['status'=>'error','msg'=>'Cliente no encontrado o sin teléfono']); exit;
    }
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]); exit;
}

/* ────────────────── 2) MENSAJE Y PAYLOAD ─────────── */
$pdfUrl   = $url . '/pdf/?type=valoracion&id=' . $valId;

$mensaje = $wa_valoracion;

$mensaje  = str_replace(
              ['{nombres}', '{apellidos}', '{url}'],
              [$cli['nombres'], $cli['apellidos'], $pdfUrl],
               $mensaje
);

$payload  = [
    'phonenumber' => $cli['dialCode'] . $cli['telefono'],
    'text'        => $mensaje,
    'url'         => $pdfUrl                    // 360 Messenger interpreta “url” como adjunto
];

/* ────────────────── 3) ENVÍO cURL ───────────────── */
$ch = curl_init('https://api.360messenger.com/v2/sendMessage');
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

/* ────────────────── 4) RESPUESTA ─────────────────── */
if ($error) {
    echo json_encode(['status'=>'error','msg'=>$error]);     // fallo de cURL
} elseif ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['status'=>'success','msg'=>'Enviado','response'=>$response]);
} else {
    echo json_encode(['status'=>'error','msg'=>"HTTP $httpCode",'response'=>$response]);
}
?>











