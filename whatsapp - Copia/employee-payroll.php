<?php
require_once __DIR__ . '/../inc/config.php';
$apiKey = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

// Mensaje de agradecimiento por el pago de nómina
$mensaje = "Hola {nombres} {apellidos},\n\nTu nómina ha sido procesada con éxito.\n\nDetalles:\n- Fecha de inicio: {fecha_pago}\n- Fecha de fin: {fecha_vencimiento}\n- Total días laborados: {total_dias}\n- Valor pagado: ${valor_pagado}\n\nGracias por tu dedicación.";

// Reemplazamos los placeholders con los valores correspondientes
$mensaje = str_replace(
    array("{nombres}", "{apellidos}", "{fecha_pago}", "{fecha_vencimiento}", "{total_dias}", "{valor_pagado}"),
    array($cp_nombres, $cp_apellidos, $cp_pago_plan, $cp_vencimiento_plan, $dias_pagados, number_format($valor_pagado, 0, ',', '.')),
    $mensaje
);

// Construimos el array con los datos que se enviarán a la API
$data = array(
    'phonenumber' => $cp_dialCode . $cp_telefono,
    'text'        => $mensaje,
    // Si deseas adjuntar una URL, descomenta y define $url y $formulario_id:
    'url'         => $url.'/pdf/?type=payroll&id='.$facturaId
);

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
    echo 'Error: ' . curl_error($ch);
}

curl_close($ch);

// Opcional: mostrar la respuesta de la API
echo "HTTP Code: $httpCode<br>";
echo "Response: " . $response;
?>







