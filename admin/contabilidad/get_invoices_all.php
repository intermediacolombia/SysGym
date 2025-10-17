<?php
//include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../../inc/config.php'; // Ruta correcta de tu config

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['data' => []]);
    exit;
}

// Consulta actualizada SIN el campo "borrado"
$stmt = $pdo->prepare("
    SELECT id, fecha, nombre_cliente, detalle, valor 
    FROM facturas 
    ORDER BY fecha DESC
");
$stmt->execute();
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $facturas]);
?>

