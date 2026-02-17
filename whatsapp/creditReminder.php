<?php
/**
 * whatsapp/creditReminder.php
 *
 * Recorre el array $creditReminders y envía un mensaje de WhatsApp a
 * cada cliente con su total adeudado y el desglose de créditos.
 *
 * Variables externas requeridas:
 *   $creditReminders   // generado por creditReminder.php
 *   $api_ws            // tu API-Key
 *   $wa_creditReminder // plantilla de mensaje con placeholders
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php'; // ← agrega la función para guardar fallidos

$apiKey          = $api_ws;
$urlEndpoint     = WA_API_URL;
$messageTemplate = $wa_creditReminder; // "Hola {nombres} {apellidos}, debes $ {total_credito}. Detalle: {detalle_creditos}"

if (isset($creditReminders) && is_array($creditReminders)) {
    foreach ($creditReminders as $cli) {
        // Datos del cliente
        $cp_nombres          = $cli['nombres'];
        $cp_apellidos        = $cli['apellidos'];
        $cp_dialCode         = $cli['dialCode'];
        $cp_telefono         = $cli['telefono'];
        $cp_total_credito    = number_format($cli['total_credito'], 0, ',', '.'); // Ej.: 50.000
        $cp_detalle_creditos = $cli['detalle_creditos'];

        // Reemplazar placeholders
        $message = str_replace(
            array('{nombres}', '{apellidos}', '{total_credito}', '{detalle_creditos}'),
            array($cp_nombres,  $cp_apellidos,  $cp_total_credito,  $cp_detalle_creditos),
            $messageTemplate
        );

        // Cuerpo de la petición
        $data = array(
            'phonenumber' => $cp_dialCode . $cp_telefono,
            'text'        => $message
            // no hay URL en este recordatorio → guardaremos null en la tabla
        );

        // Enviar vía cURL
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

        // Log del envío (opcional, conservado)
        if ($error) {
            echo 'Error: ' . $error . "<br>";
        }
        echo "Cliente ID {$cli['id']} - HTTP Code: " . ($httpCode ?? 'n/a') . "<br>";
        echo "Response: " . ($response ?? '') . "<br><br>";
    }
}
?>

