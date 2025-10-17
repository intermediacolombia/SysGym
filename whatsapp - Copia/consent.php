<?php
require_once __DIR__ . '/../inc/config.php';
$apiKey = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

$mensaje = $wa_consent;

$mensaje = str_replace(
    array("{nombres}", "{apellidos}"),
    array($data['nombres'], $data['apellidos']),
    $mensaje
);


$data = array(
    'phonenumber' => $data['dialCode'] . $data['telefono'],
    'text'        => $mensaje,
    'url'         => $url.'/pdf/?type=consent&id='.$formulario_id
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

echo "HTTP Code: $httpCode<br>";
echo "Response: " . $response;
?>



