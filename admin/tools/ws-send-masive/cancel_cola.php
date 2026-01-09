<?php
require_once __DIR__ . '/../../inc/config.php';
header('Content-Type: application/json');

try {
    $stmt = db()->prepare("SELECT COUNT(*) as total FROM envios_masivos_ws");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = db()->prepare("DELETE FROM envios_masivos_ws");
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'eliminados' => (int)$count
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}