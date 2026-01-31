<?php
/**
 * SCRIPT DE PRUEBA - API 360MESSENGER
 * Prueba simple para enviar mensaje con archivo adjunto
 */

// ==================== CONFIGURACIÓN ====================
$apiKey = '8veaR5zoXDZYkBRsHfo1jc7xitzKI9Fjy0U';
$urlEndpoint = 'https://api.360messenger.com/v2/sendMessage';

// DATOS DE PRUEBA (MODIFICA ESTOS)
$phoneNumber = '573147165269'; // Tu número con código de país (sin + ni espacios)
$mensaje = 'Prueba de envío de PDF desde PHP';
$pdfUrl = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf'; // PDF de prueba público

// ==================== PREPARAR DATOS ====================
$data = [
    'phonenumber' => $phoneNumber,
    'text'        => $mensaje,
    'url'         => $pdfUrl
];

echo "=== DATOS A ENVIAR ===\n";
echo "Teléfono: " . $data['phonenumber'] . "\n";
echo "Mensaje: " . $data['text'] . "\n";
echo "URL: " . $data['url'] . "\n\n";

// ==================== ENVÍO VIA CURL ====================
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $urlEndpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// ==================== RESULTADOS ====================
echo "=== RESPUESTA ===\n";
echo "HTTP Code: " . $httpCode . "\n";

if ($error) {
    echo "❌ Error CURL: " . $error . "\n";
} else {
    echo "✅ Respuesta API:\n";
    echo $response . "\n\n";
    
    // Decodificar JSON para análisis
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "=== ANÁLISIS ===\n";
        echo "Success: " . (isset($decoded['success']) ? ($decoded['success'] ? 'SÍ' : 'NO') : 'N/A') . "\n";
        
        if (isset($decoded['message'])) {
            echo "Mensaje: " . $decoded['message'] . "\n";
        }
        
        if (isset($decoded['error'])) {
            echo "Error: " . $decoded['error'] . "\n";
        }
    }
}

echo "\n=== FIN DE PRUEBA ===\n";
?>