<?php
require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';

header('Content-Type: application/json; charset=UTF-8');

function parse_days_allowed(): array {
    if (!defined('WA_MASS_DAYS') || trim((string)WA_MASS_DAYS) === '') {
        return []; 
    }
    $parts = array_filter(array_map('trim', explode(',', (string)WA_MASS_DAYS)));
    $days = [];
    foreach ($parts as $p) {
        $n = (int)$p;
        if ($n >= 1 && $n <= 7) $days[] = $n;
    }
    return array_values(array_unique($days));
}

function is_day_allowed(int $dayN, array $allowedDays): bool {
    if (empty($allowedDays)) return true;
    return in_array($dayN, $allowedDays, true);
}

function compute_next_start(DateTime $now, array $allowedDays, int $startH, int $endH): array {
    $tz = $now->getTimezone();
    $todayStr = $now->format('Y-m-d');
    
    // Creamos los objetos DateTime usando las horas enteras (7, 21, etc)
    $start = clone $now;
    $start->setTime($startH, 0, 0);
    
    $end = clone $now;
    $end->setTime($endH, 0, 0);

    $todayN = (int)$now->format('N');
    $dayAllowed = is_day_allowed($todayN, $allowedDays);

    // Si el día de hoy no está permitido
    if (!$dayAllowed) {
        $candidate = clone $start;
        $candidate->modify('+1 day');
        for ($i = 0; $i < 14; $i++) {
            if (is_day_allowed((int)$candidate->format('N'), $allowedDays)) {
                return ['in_window' => false, 'reason' => 'Día no permitido', 'next_start' => $candidate];
            }
            $candidate->modify('+1 day');
        }
    }

    // Si el día es permitido, revisamos la hora
    if ($now < $start) {
        return ['in_window' => false, 'reason' => 'Fuera de horario', 'next_start' => $start];
    }

    if ($now > $end) {
        $candidate = clone $start;
        $candidate->modify('+1 day');
        for ($i = 0; $i < 14; $i++) {
            if (is_day_allowed((int)$candidate->format('N'), $allowedDays)) {
                return ['in_window' => false, 'reason' => 'Horario finalizado', 'next_start' => $candidate];
            }
            $candidate->modify('+1 day');
        }
    }

    return ['in_window' => true, 'reason' => 'En ventana', 'next_start' => null];
}

try {
    $hoy = date('Y-m-d');

    // Enviados hoy
    $stmt = db()->prepare("SELECT COUNT(*) FROM ws_envios_log WHERE fecha = :hoy");
    $stmt->execute([':hoy' => $hoy]);
    $enviadosHoy = (int)$stmt->fetchColumn();

    // Pendientes en cola
    $stmt2 = db()->prepare("SELECT COUNT(*) FROM envios_masivos_ws");
    $stmt2->execute();
    $cola = (int)$stmt2->fetchColumn();

    // LEER CONSTANTES COMO ENTEROS (Tal cual están en tu config.php)
    $limiteDiario = defined('WA_MASS_LIMIT') ? (int)WA_MASS_LIMIT : 50;
    $startH = defined('WA_MASS_HOUR_START') ? (int)WA_MASS_HOUR_START : 8;
    $endH   = defined('WA_MASS_HOUR_END') ? (int)WA_MASS_HOUR_END : 20;
    $allowedDays = parse_days_allowed();

    $now = new DateTime('now');
    $win = compute_next_start($now, $allowedDays, $startH, $endH);

    $nextStartHuman = ($win['next_start'] instanceof DateTime) ? $win['next_start']->format('Y-m-d H:i') : null;

    echo json_encode([
        'status' => 'success',
        'enviados_hoy' => $enviadosHoy,
        'limite_diario' => $limiteDiario,
        'cola_pendiente' => $cola,
        'in_window' => (bool)$win['in_window'],
        'next_start_human' => $nextStartHuman
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}