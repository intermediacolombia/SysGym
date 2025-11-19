<?php
require_once __DIR__ . '/../../inc/config.php';
try {
    db();
} catch(PDOException $e) {
    echo json_encode(['data' => []]);
    exit;
}

if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    echo json_encode(['data' => []]);
    exit;
}

$client_id = $_GET['client_id'];

$stmt = db()->prepare("SELECT id, fecha, detalle, valor FROM facturas WHERE idCliente = :client_id");
$stmt->execute([':client_id' => $client_id]);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $facturas]);
?>
