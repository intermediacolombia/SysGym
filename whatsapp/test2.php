<?php
/**
 * OPCIÓN 1: Bearer Token (más común en APIs REST)
 * Este es el estándar OAuth2
 */

$apiKey = '8veaR5zoXDZYkBRsHfo1jc7xitzKI9Fjy0U';

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.360messenger.com/v2/sendMessage',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array(
    'phonenumber' => '573147165269',
    'text' => 'Factura',
    'url' => 'https://sysgym.intermediacolombia.com/pdf/?type=invoice&id=2616'
  ),
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer ' . $apiKey  // ← Formato Bearer
  ),
));

$response = curl_exec($curl);
curl_close($curl);

echo $response;
?>
