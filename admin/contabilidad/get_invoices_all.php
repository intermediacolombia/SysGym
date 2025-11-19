<?php
//include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../../inc/config.php'; // Ruta correcta de tu config
// Consulta actualizada SIN el campo "borrado"
$stmt = db()->prepare("
    SELECT id, fecha, nombre_cliente, detalle, valor 
    FROM facturas 
    ORDER BY fecha DESC
");
$stmt->execute();
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $facturas]);
?>

