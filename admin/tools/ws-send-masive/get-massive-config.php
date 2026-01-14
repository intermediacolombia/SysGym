<?php
require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';

header('Content-Type: application/json');

try {
    echo json_encode([
        'status' => 'success',
        'dias' => WA_MASS_DAYS,
        'hora_inicio' => WA_MASS_HOUR_START,
        'hora_fin' => WA_MASS_HOUR_END,
        'limite' => WA_MASS_LIMIT
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'dias' => '1,2,3,4,5',
        'hora_inicio' => 7,
        'hora_fin' => 21,
        'limite' => 50
    ]);
}