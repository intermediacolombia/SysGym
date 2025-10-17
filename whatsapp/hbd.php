<?php
// Variables de la API de WhatsApp
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // ← función saveFailedWSMessage(phone, text, url)

$apiKey = $api_ws; // Definido en config.php
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

// Template de mensaje de cumpleaños
$mensajeTemplate = $wa_hbd;

// Recorrer cada cliente y enviar un mensaje personalizado
foreach ($birthdays as $client) {
    // Asignar las variables para este cliente
    $cp_nombres    = $client['nombres'];
    $cp_apellidos  = $client['apellidos'];
    $cp_dialCode   = $client['dialCode'];
    $cp_telefono   = $client['telefono'];

    // Reemplazar los placeholders en el mensaje
    $mensaje = str_replace(
        array("{nombres}", "{apellidos}"),
        array($cp_nombres, $cp_apellidos),
        $mensajeTemplate
    );

    // Construir el array de datos para la API
    $data = array(
        'phonenumber' => $cp_dialCode . $cp_telefono,
        'text'        => $mensaje
        // 'url' => $url . '/pdf/?type=consent&id=' . $formulario_id
    );

    // Inicializar cURL y configurar la petición
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => $urlEndpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        )
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    // Validar éxito por JSON: {"success": true}
    $successFlag = false;
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        $successFlag = !empty($decoded['success']);
    }

    // Si falla → guardar en ws_outbox (solo phone, text, url=null)
    if (!$successFlag) {
        saveFailedWSMessage($data['phonenumber'], $data['text'], null);
    }

    // Mostrar el resultado de cada envío (opcional)
    if ($error) {
        echo 'Error: ' . $error . "<br>";
    }
    echo "Cliente ID " . $client['id'] . " - HTTP Code: " . ($httpCode ?? 'n/a') . "<br>";
    echo "Response: " . ($response ?? '') . "<br><br>";
}
?>
