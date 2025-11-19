<?php
require_once __DIR__ . '/../../inc/config.php'; 

// 1. Definir el id del cliente a consultar a partir del parámetro GET
$client_id = isset($_GET['id']) ? intval($_GET['id']) : die("No se proporcionó el id del cliente.");

try {
    db();
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. Obtener teléfono
$stmt = db()->prepare("SELECT telefono FROM clientes WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $client_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Cliente con ID=$client_id no encontrado.");
}

$phone = $row['telefono'];

// 3. Llamar API
$apiKey = $api_ws;
$urlEndpoint = 'https://api.360messenger.com/v2/message/sentMessages?phonenumber=' . urlencode($phone);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $urlEndpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    )
));

$response = curl_exec($curl);
curl_close($curl);

$apiData = json_decode($response, true);

// 4. Preparar JSON final
$output = array(
    "success"    => true,
    "data"       => array(
        "count"         => isset($apiData['data']['data']) ? count($apiData['data']['data']) : 0,
        "pageCount"     => $apiData['data']['pageCount'] ?? 1,
        "page"          => $apiData['data']['page'] ?? "N/A",
        "data"          => $apiData['data']['data'] ?? array(),
        "phone_numbers" => array($phone)
    ),
    "statusCode" => 200,
    "timestamp"  => date('Y-m-d H:i:s')
);

header('Content-Type: application/json');
echo json_encode($output);




