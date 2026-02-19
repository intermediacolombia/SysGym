<?php
require_once __DIR__ . '/../../inc/config.php'; 

// 1. Definir el id del cliente a consultar a partir del parámetro GET
$client_id = isset($_GET['id']) ? intval($_GET['id']) : die("No se proporcionó el id del cliente.");

// 2. Obtener teléfono desde tu BD local
$stmt = db()->prepare("SELECT telefono FROM clientes WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $client_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Cliente con ID=$client_id no encontrado.");
}

$phone = $row['telefono'];

// 3. Llamar a TU API en vez de 360messenger
$apiKey = $api_ws; // tu API Key que ya usas con /api/send
$baseUrl = rtrim(WA_API_URL, '/') . '/messages-by-phone';
$urlEndpoint = $baseUrl . '?phonenumber=' . urlencode($phone);

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

// 4. Preparar JSON final (MISMA estructura que tenías antes)
$output = array(
    "success"    => $apiData['success'] ?? false,
    "data"       => array(
        "count"         => $apiData['data']['count']     ?? 0,
        "pageCount"     => $apiData['data']['pageCount'] ?? 1,
        "page"          => $apiData['data']['page']      ?? "N/A",
        "data"          => $apiData['data']['data']      ?? array(),
        "phone_numbers" => $apiData['data']['phone_numbers'] ?? array($phone)
    ),
    "statusCode" => $apiData['statusCode'] ?? 200,
    "timestamp"  => date('Y-m-d H:i:s')
);

header('Content-Type: application/json');
echo json_encode($output);



