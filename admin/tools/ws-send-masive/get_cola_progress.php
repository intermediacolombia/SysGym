<?php
require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';

header('Content-Type: application/json; charset=UTF-8');

function parse_days_allowed(): array {
    if (!defined('WA_MASS_DAYS') || trim((string)WA_MASS_DAYS) === '') {
        return []; // vacío = todos los días permitidos
    }

    $parts = array_filter(array_map('trim', explode(',', (string)WA_MASS_DAYS)));
    $days = [];
    foreach ($parts as $p) {
        $n = (int)$p;
        if ($n >= 1 && $n <= 7) $days[] = $n;
    }
    $days = array_values(array_unique($days));
    sort($days);
    return $days;
}

function is_day_allowed(int $dayN, array $allowedDays): bool {
    if (empty($allowedDays)) return true; // sin configuración => todos permitidos
    return in_array($dayN, $allowedDays, true);
}

function compute_next_start(DateTime $now, array $allowedDays, string $startHHMM, string $endHHMM): array {
    // Devuelve:
    // - in_window (bool)
    // - reason (string)
    // - next_start (DateTime|null)

    $tz = $now->getTimezone();

    // Construir DateTime de inicio y fin para "hoy"
    $todayStr = $now->format('Y-m-d');
    $start = DateTime::createFromFormat('Y-m-d H:i', $todayStr . ' ' . $startHHMM, $tz);
    $end   = DateTime::createFromFormat('Y-m-d H:i', $todayStr . ' ' . $endHHMM, $tz);

    // Si hay error parseando horas, consideramos siempre permitido
    if (!$start || !$end) {
        return [
            'in_window' => true,
            'reason' => 'Horario inválido o no configurado',
            'next_start' => null
        ];
    }

    $todayN = (int)$now->format('N'); // 1..7
    $dayAllowed = is_day_allowed($todayN, $allowedDays);

    // Caso simple: ventana normal (start <= end) (recomendado)
    $normalWindow = ($start <= $end);

    if ($normalWindow) {
        if (!$dayAllowed) {
            // Buscar próximo día permitido a las WA_MASS_HOUR_START
            $candidate = clone $now;
            $candidate->setTime((int)substr($startHHMM,0,2), (int)substr($startHHMM,3,2), 0);

            // empezamos desde mañana (porque hoy no está permitido)
            $candidate->modify('+1 day');
            for ($i = 0; $i < 14; $i++) {
                $candN = (int)$candidate->format('N');
                if (is_day_allowed($candN, $allowedDays)) {
                    return [
                        'in_window' => false,
                        'reason' => 'Día no permitido',
                        'next_start' => $candidate
                    ];
                }
                $candidate->modify('+1 day');
            }

            return [
                'in_window' => false,
                'reason' => 'Día no permitido (no se encontró próximo día en el rango)',
                'next_start' => null
            ];
        }

        // Día permitido: revisar hora
        if ($now < $start) {
            return [
                'in_window' => false,
                'reason' => 'Aún no inicia el horario de envío',
                'next_start' => $start
            ];
        }

        if ($now > $end) {
            // Buscar próximo día permitido desde mañana
            $candidate = clone $now;
            $candidate->setTime((int)substr($startHHMM,0,2), (int)substr($startHHMM,3,2), 0);
            $candidate->modify('+1 day');

            for ($i = 0; $i < 14; $i++) {
                $candN = (int)$candidate->format('N');
                if (is_day_allowed($candN, $allowedDays)) {
                    return [
                        'in_window' => false,
                        'reason' => 'Horario finalizado por hoy',
                        'next_start' => $candidate
                    ];
                }
                $candidate->modify('+1 day');
            }

            return [
                'in_window' => false,
                'reason' => 'Horario finalizado por hoy (no se encontró próximo día en el rango)',
                'next_start' => null
            ];
        }

        // Está dentro del rango y el día es permitido
        return [
            'in_window' => true,
            'reason' => 'En ventana de envío',
            'next_start' => null
        ];
    }

    // Ventana "cruza medianoche" (start > end). Si no usas esto, puedes ignorarlo.
    // Ej: 22:00 a 06:00
    // Aquí consideramos que si hoy es permitido, la ventana es:
    // [start hoy -> 23:59] U [00:00 -> end mañana]
    // Es más complejo con WA_MASS_DAYS; lo dejamos con comportamiento razonable.
    if (!$dayAllowed) {
        // Mismo criterio: próximo día permitido a la hora start
        $candidate = clone $now;
        $candidate->setTime((int)substr($startHHMM,0,2), (int)substr($startHHMM,3,2), 0);
        $candidate->modify('+1 day');

        for ($i = 0; $i < 14; $i++) {
            $candN = (int)$candidate->format('N');
            if (is_day_allowed($candN, $allowedDays)) {
                return [
                    'in_window' => false,
                    'reason' => 'Día no permitido',
                    'next_start' => $candidate
                ];
            }
            $candidate->modify('+1 day');
        }

        return [
            'in_window' => false,
            'reason' => 'Día no permitido (no se encontró próximo día en el rango)',
            'next_start' => null
        ];
    }

    // En ventana cruzando medianoche
    // Si ahora >= start (hoy) OR ahora <= end (hoy) (ojo: end es hoy temprano)
    if ($now >= $start || $now <= $end) {
        return [
            'in_window' => true,
            'reason' => 'En ventana de envío (cruza medianoche)',
            'next_start' => null
        ];
    }

    // Fuera de ventana: el próximo start es hoy a start (si aún no llegó) o mañana a start
    if ($now < $start) {
        return [
            'in_window' => false,
            'reason' => 'Aún no inicia el horario de envío',
            'next_start' => $start
        ];
    }

    $candidate = clone $now;
    $candidate->setTime((int)substr($startHHMM,0,2), (int)substr($startHHMM,3,2), 0);
    $candidate->modify('+1 day');

    return [
        'in_window' => false,
        'reason' => 'Fuera de horario',
        'next_start' => $candidate
    ];
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

    // Config
    $limiteDiario = defined('WA_MASS_LIMIT') ? (int)WA_MASS_LIMIT : 50;
    $startHHMM = defined('WA_MASS_HOUR_START') ? (string)WA_MASS_HOUR_START : '08:00';
    $endHHMM   = defined('WA_MASS_HOUR_END') ? (string)WA_MASS_HOUR_END : '20:00';
    $allowedDays = parse_days_allowed();

    $now = new DateTime('now');
    $win = compute_next_start($now, $allowedDays, $startHHMM, $endHHMM);

    $nextStartIso = null;
    $nextStartHuman = null;
    if ($win['next_start'] instanceof DateTime) {
        $nextStartIso = $win['next_start']->format(DateTime::ATOM);
        $nextStartHuman = $win['next_start']->format('Y-m-d H:i');
    }

    echo json_encode([
        'status' => 'success',
        'enviados_hoy' => $enviadosHoy,
        'limite_diario' => $limiteDiario,
        'cola_pendiente' => $cola,

        // extras para UI
        'in_window' => (bool)$win['in_window'],
        'window_reason' => (string)$win['reason'],
        'next_start_iso' => $nextStartIso,
        'next_start_human' => $nextStartHuman,
        'config' => [
            'hour_start' => $startHHMM,
            'hour_end' => $endHHMM,
            'days_allowed' => $allowedDays // [] = todos
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo calcular el progreso.'
    ]);
}