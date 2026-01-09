<?php
// get_creditos.php
require_once __DIR__ . '/../../inc/config.php';



if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    error_log("get_creditos.php: No se recibió client_id.");
    header('Content-Type: application/json');
    echo json_encode(["data" => []]);
    exit;
}

$client_id = intval($_GET['client_id']);
error_log("get_creditos.php: client_id = " . $client_id);

$stmt = db()->prepare("SELECT * FROM creditos WHERE idCliente = :client_id AND estado = 0 AND deleted = 0 ORDER BY id DESC");
$stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
$stmt->execute();
$creditos = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(["data" => $creditos]);
?>


