<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // ← guardar fallidos

$apiKey      = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

// Template de recordatorio de pago
$messageTemplate = $wa_paymentReminder;

if (isset($reminders) && is_array($reminders)) {
    foreach ($reminders as $client) {
        // Variables del cliente
        $cp_nombres           = $client['nombres'];
        $cp_apellidos         = $client['apellidos'];
        $cp_dialCode          = $client['dialCode'];
        $cp_telefono          = $client['telefono'];
        $cp_fecha_vencimiento = $client['vencimiento_plan'];
        $cp_plan              = $client['plan'];         // nombre del plan
        $cp_valor_pago        = $client['valor_pago'];

        // Mensaje con placeholders
        $message = str_replace(
            array("{nombres}", "{apellidos}", "{fecha_vencimiento}", "{plan}", "{valor_pago}"),
            array($cp_nombres, $cp_apellidos, $cp_fecha_vencimiento, $cp_plan, $cp_valor_pago),
            $messageTemplate
        );

        // Payload
        $data = array(
            'phonenumber' => $cp_dialCode . $cp_telefono,
            'text'        => $message
            // sin URL en este recordatorio → guardaremos null si falla
        );

        // Envío
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

        // Éxito si JSON viene con {"success": true}
        $successFlag = false;
        if (!$error && $httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            $successFlag = !empty($decoded['success']);
        }

        // Si falla → guardar en ws_outbox (phone, text, url=null)
        if (!$successFlag) {
            saveFailedWSMessage($data['phonenumber'], $data['text'], null);
        }

        // Log (opcional)
        if ($error) {
            echo 'Error: ' . $error . "<br>";
        }
        echo "Cliente ID " . $client['id'] . " - HTTP Code: " . ($httpCode ?? 'n/a') . "<br>";
        echo "Response: " . ($response ?? '') . "<br><br>";
    }
}
?>

