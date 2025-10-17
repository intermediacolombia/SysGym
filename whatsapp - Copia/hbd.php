<?php

// Variables de la API de WhatsApp
$apiKey = $api_ws; // Definido en config.php
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

// Template de mensaje de cumpleaÃ±os
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
        // Si deseas adjuntar una URL, agrega la clave 'url'
        // 'url'      => $url.'/pdf/?type=consent&id='.$formulario_id
    );

    // Inicializar cURL y configurar la peticiÃ³n
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $urlEndpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        )
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if(curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch) . "<br>";
    }

    curl_close($ch);

    // Mostrar el resultado de cada envÃ­o (puedes registrar en un log o ajustar la salida)
    echo "Cliente ID " . $client['id'] . " - HTTP Code: $httpCode<br>";
    echo "Response: " . $response . "<br><br>";
}

?>