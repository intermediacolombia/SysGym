<?php
require_once __DIR__ . '/../inc/config.php';
$apiKey = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

// Mensaje de agradecimiento por el pago
$mensaje = $wa_client_pay;

/*
  Reemplazamos los placeholders {nombres}, {apellidos}, {fecha_pago} y {fecha_vencimiento}
  con los valores de $cp_nombres, $cp_apellidos, $cp_pago_plan y $cp_vencimiento_plan.
  Si no usas apellidos, puedes omitirlo.
*/
$mensaje = str_replace(
    array("{nombres}", "{apellidos}", "{fecha_pago}", "{fecha_vencimiento}"),
    array($cp_nombres, $cp_apellidos, $cp_pago_plan, $cp_vencimiento_plan),
    $mensaje
);

/*
  Construimos el array con los datos que se enviarán a la API.
  Asegúrate de que $cp_dialCode, $cp_telefono, $url y $formulario_id
  estén definidos en el script que hace el include de este archivo, si los necesitas.
*/
$data = array(
    'phonenumber' => $cp_dialCode . $cp_telefono,
    'text'        => $mensaje,
    // Si deseas adjuntar una URL, descomenta y define $url y $formulario_id:
    'url'         => $url.'/pdf/?type=invoice&id='.$facturaId
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







