<?php
/**
 * whatsapp/creditReminder.php
 *
 * Recorre el array $creditReminders y envía un mensaje de WhatsApp a
 * cada cliente con su total adeudado y el desglose de créditos.
 *
 * Variables externas requeridas:
 *   $creditReminders  // generado por creditReminder.php
 *   $api_ws           // tu API-Key
 *   $wa_creditReminder // plantilla de mensaje con placeholders
 */

$apiKey      = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';
$messageTemplate = $wa_creditReminder;   // Ej.: "Hola {nombres} {apellidos}, debes $ {total_credito}. Detalle: {detalle_creditos}"

/*$messageTemplate = "Hola {nombres}, queriamos recordarte que tienes creditos pendientes con nosotros y los detalles estan a continuacion:
{detalle_creditos}
Total Deuda: $ {total_credito}.";*/

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
        );

        // Enviar vía cURL
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => $urlEndpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => array(
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            )
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch) . "<br>";
        }

        curl_close($ch);

        // Log del envío (opcional)
        echo "Cliente ID {$cli['id']} - HTTP Code: $httpCode<br>";
        echo "Response: $response<br><br>";
    }
}
?>
