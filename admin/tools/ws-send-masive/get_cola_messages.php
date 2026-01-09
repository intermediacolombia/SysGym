<?php
require_once __DIR__ . '/../../../inc/config.php';
header('Content-Type: application/json');

try {
    $stmt = db()->prepare("
        SELECT id, nombre, telefono, mensaje, adjunto
        FROM envios_masivos_ws
        ORDER BY id ASC
        LIMIT 100
    ");
    $stmt->execute();
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'mensajes' => $mensajes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}