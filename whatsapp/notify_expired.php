<?php
// notify_expired.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // ← función saveFailedWSMessage(phone, text, url|null)

$apiKey = $api_ws; // Se obtiene desde config.php
$urlEndpoint = WA_API_URL;

// Template de mensaje de notificación por plan vencido
$messageTemplate = $wa_notify_expired;

if (isset($reminders) && is_array($reminders)) {
    foreach ($reminders as $client) {
        // Extraer datos para este cliente
        $cp_nombres           = $client['nombres'];
        $cp_apellidos         = $client['apellidos'];
        $cp_dialCode          = $client['dialCode'];
        $cp_telefono          = $client['telefono'];
        $cp_fecha_vencimiento = $client['vencimiento_plan'];
		$url_pay  			  = URLBASE.'/pay/?doc='.$client['identificacion'];
        // Reemplazar los placeholders en el mensaje
        $message = str_replace(
            array("{nombres}", "{apellidos}", "{fecha_vencimiento}" , "{url_pago}"),
            array($cp_nombres, $cp_apellidos, $cp_fecha_vencimiento, $url_pay),
            $messageTemplate
        );

        // Datos para la API
        $data = array(
            'phonenumber' => $cp_dialCode . $cp_telefono,
            'text'        => $message
            // No hay URL para este aviso → guardamos null si falla
        );

        // Envío cURL
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

        // Si falla → guardar en ws_outbox (phone, text, url=null)
        if (!$successFlag) {
            saveFailedWSMessage($data['phonenumber'], $data['text'], null);
        }

        // Logs (opcional)
        if ($error) {
            echo 'Error: ' . $error . "<br>";
        }
        echo "Cliente ID " . $client['id'] . " - HTTP Code: " . ($httpCode ?? 'n/a') . "<br>";
        echo "Response: " . ($response ?? '') . "<br><br>";
    }
} else {
    echo "No se encontraron clientes para notificar.";
}

