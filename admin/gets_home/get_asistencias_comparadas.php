<?php
require_once __DIR__ . '/../../inc/config.php';
date_default_timezone_set('America/Bogota');

$hoy   = date('Y-m-d');
$hora  = date('H:i:s'); // ya existe en tu config, pero por si acaso
$hace7 = date('Y-m-d', strtotime("$hoy -7 days"));

try {

    /* ======================================================
       ASISTENCIAS ÚNICAS HOY HASTA LA HORA ACTUAL
       ====================================================== */
    $stmt1 = db()->prepare("
        SELECT COUNT(DISTINCT idCliente)
        FROM asistencias
        WHERE fecha = :hoy
          AND hora <= :hora
    ");
    $stmt1->execute([
        ':hoy'  => $hoy,
        ':hora' => $hora
    ]);
    $hoyTotal = (int)$stmt1->fetchColumn();

    /* ======================================================
       ASISTENCIAS ÚNICAS HACE 7 DÍAS HASTA LA MISMA HORA
       ====================================================== */
    $stmt2 = db()->prepare("
        SELECT COUNT(DISTINCT idCliente)
        FROM asistencias
        WHERE fecha = :f7
          AND hora <= :hora
    ");
    $stmt2->execute([
        ':f7'   => $hace7,
        ':hora' => $hora
    ]);
    $semanaTotal = (int)$stmt2->fetchColumn();


    /* ======================================================
       DIFERENCIA Y PORCENTAJE
       ====================================================== */
    $dif = $hoyTotal - $semanaTotal;

    if ($semanaTotal > 0) {
        $percent = round(($dif / $semanaTotal) * 100, 1);
    } else {
        $percent = ($hoyTotal > 0 ? 100 : 0);
    }

    echo json_encode([
        "today"      => $hoyTotal,
        "last_week"  => $semanaTotal,
        "diff"       => $dif,
        "percent"    => $percent,
        "hora_actual" => $hora
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "today"      => 0,
        "last_week"  => 0,
        "diff"       => 0,
        "percent"    => 0,
        "error"      => $e->getMessage()
    ]);
}

