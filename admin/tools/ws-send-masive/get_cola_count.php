<?php
require_once __DIR__ . '/../../../inc/config.php';
header('Content-Type: application/json');

try {
    $stmt = db()->prepare("SELECT COUNT(*) as total FROM envios_masivos_ws");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => (int)$result['total']]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
