<?php

$apiKey = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

// Template de mensaje de recordatorio de pago, incluyendo el nombre del plan
$messageTemplate = $wa_paymentReminder;


if (isset($reminders) && is_array($reminders)) {
    foreach ($reminders as $client) {
        // Variables para este cliente
        $cp_nombres           = $client['nombres'];
        $cp_apellidos         = $client['apellidos'];
        $cp_dialCode          = $client['dialCode'];
        $cp_telefono          = $client['telefono'];
        $cp_fecha_vencimiento = $client['vencimiento_plan'];
        $cp_plan              = $client['plan'];  // nombre del plan
		$cp_valor_pago = $client['valor_pago'];

        
        // Reemplazar los placeholders en el mensaje
        $message = str_replace(
    array("{nombres}", "{apellidos}", "{fecha_vencimiento}", "{plan}", "{valor_pago}"),
    array($cp_nombres, $cp_apellidos, $cp_fecha_vencimiento, $cp_plan, $cp_valor_pago),
    $messageTemplate
);

        
        // Construir el array de datos para la API
        $data = array(
            'phonenumber' => $cp_dialCode . $cp_telefono,
            'text'        => $message
        );
        
        // Enviar el mensaje vía cURL a la API de WhatsApp
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
        
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch) . "<br>";
        }
        
        curl_close($ch);
        
        // Mostrar el resultado de cada envío (opcional)
        echo "Cliente ID " . $client['id'] . " - HTTP Code: $httpCode<br>";
        echo "Response: " . $response . "<br><br>";
    }
}
?>
