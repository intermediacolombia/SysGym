<?php
/**
 * whatsapp/send_ws_alert.php
 *
 * Envía un mensaje fijo de alerta de bajo stock a un número específico (formato internacional sin +).
 *
 * Requiere:
 *   sendWSAlert($telefono, $mensaje)
 */

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/save_failed_ws.php';

function sendWSAlert($telefono, $mensaje) {
    global $api_ws; // tu API Key

    $urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

    $data = array(
        'phonenumber' => $telefono,
        'text'        => $mensaje
    );

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => $urlEndpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => array(
            'Authorization: Bearer ' . $api_ws,
            'Content-Type: application/json',
            'Accept: application/json'
        )
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    $successFlag = false;
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        $successFlag = !empty($decoded['success']);
    }

    if (!$successFlag) {
        saveFailedWSMessage($telefono, $mensaje, null);
    }

    return $successFlag;
}


