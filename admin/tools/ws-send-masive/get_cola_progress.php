<?php
require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Fecha de hoy (ya usa la zona horaria de config.php)
    
    // Enviados hoy
    $stmt = db()->prepare("SELECT COUNT(*) FROM ws_envios_log WHERE fecha = :hoy AND resultado = 'enviado'");
    $stmt->execute([':hoy' => $hoy]);
    $enviadosHoy = (int)$stmt->fetchColumn();
    
    // Pendientes en cola
    $stmt2 = db()->prepare("SELECT COUNT(*) FROM envios_masivos_ws");
    $stmt2->execute();
    $cola = (int)$stmt2->fetchColumn();
    
    // Limite diario
    $limiteDiario = defined('WA_MASS_LIMIT') ? (int)WA_MASS_LIMIT : 50;
    
    echo json_encode([
        'status' => 'success',
        'enviados_hoy' => $enviadosHoy,
        'limite_diario' => $limiteDiario,
        'cola_pendiente' => $cola
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo calcular el progreso.'
    ]);
}