<?php
// 3) CREAR ESTE ARCHIVO NUEVO: get_cola_progress.php
// DÓNDE: en la MISMA carpeta donde están get_cola_count.php y get_cola_messages.php

require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Enviados hoy (según tu log)
    $stmt = db()->prepare("SELECT COUNT(*) FROM ws_envios_log WHERE fecha = CURDATE() AND resultado = 'enviado'");
    $stmt->execute();
    $enviadosHoy = (int)$stmt->fetchColumn();

    // Pendientes en cola
    $stmt2 = db()->prepare("SELECT COUNT(*) FROM envios_masivos_ws");
    $stmt2->execute();
    $cola = (int)$stmt2->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'enviados_hoy' => $enviadosHoy,
        'limite_diario' => (int)WA_MASS_LIMIT,
        'cola_pendiente' => $cola
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo calcular el progreso.'
    ]);
}