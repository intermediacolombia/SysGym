<?php
require_once __DIR__ . '/../../inc/config.php';
date_default_timezone_set('America/Bogota');

$hoy    = date('Y-m-d');
$hace7  = date('Y-m-d', strtotime("$hoy -7 days"));

try {

    /* ======================================================
       ASISTENCIAS ÚNICAS HOY
       ====================================================== */
    $stmt1 = db()->prepare("
        SELECT COUNT(DISTINCT idCliente)
        FROM asistencias
        WHERE fecha = :hoy
    ");
    $stmt1->execute([':hoy' => $hoy]);
    $hoyTotal = (int)$stmt1->fetchColumn();

    /* ======================================================
       ASISTENCIAS ÚNICAS HACE 7 DÍAS
       ====================================================== */
    $stmt2 = db()->prepare("
        SELECT COUNT(DISTINCT idCliente)
        FROM asistencias
        WHERE fecha = :f7
    ");
    $stmt2->execute([':f7' => $hace7]);
    $semanaTotal = (int)$stmt2->fetchColumn();

    /* ======================================================
       DIFERENCIA Y PORCENTAJE
       ====================================================== */
    $dif = $hoyTotal - $semanaTotal;

    if ($semanaTotal > 0) {
        $percent = round((($hoyTotal - $semanaTotal) / $semanaTotal) * 100, 1);
    } else {
        $percent = ($hoyTotal > 0 ? 100 : 0);
    }

    echo json_encode([
        "today"      => $hoyTotal,
        "last_week"  => $semanaTotal,
        "diff"       => $dif,
        "percent"    => $percent
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
