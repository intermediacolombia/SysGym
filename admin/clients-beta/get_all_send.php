<?php
require_once __DIR__ . '/../../inc/config.php'; 
// Se asume que config.php define:
//   $api_ws    => Tu API key
//   $host      => Host de la base de datos
//   $dbuser    => Usuario de la base de datos
//   $dbpass    => Contraseña de la base de datos
//   $dbname    => Nombre de la base de datos

// 1. Definir el id del cliente a consultar a partir del parámetro GET
$client_id = isset($_GET['id']) ? intval($_GET['id']) : die("No se proporcionó el id del cliente.");

// 2. Conectar a la BD y obtener el teléfono del cliente con id=$client_id
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Error de conexión a la BD: " . $conn->connect_error);
}

$sql = "SELECT telefono FROM clientes WHERE id = $client_id LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $phone = $row['telefono'];
} else {
    die("Cliente con ID=$client_id no encontrado.");
}
$conn->close();

// 3. Llamar a la API para obtener los mensajes enviados para ese teléfono
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

// 4. Armar el JSON final con la estructura deseada y el teléfono consultado
$output = array(
    "success"    => true,
    "data"       => array(
        "count"         => isset($apiData['data']['data']) ? count($apiData['data']['data']) : 0,
        "pageCount"     => isset($apiData['data']['pageCount']) ? $apiData['data']['pageCount'] : 1,
        "page"          => isset($apiData['data']['page']) ? $apiData['data']['page'] : "N/A",
        "data"          => isset($apiData['data']['data']) ? $apiData['data']['data'] : array(),
        "phone_numbers" => array($phone)
    ),
    "statusCode" => 200,
    "timestamp"  => date('Y-m-d H:i:s')
);

header('Content-Type: application/json');
echo json_encode($output);
?>



