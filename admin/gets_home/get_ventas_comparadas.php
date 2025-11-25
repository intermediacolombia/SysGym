<?php
require_once __DIR__ . '/../../inc/config.php';
date_default_timezone_set('America/Bogota');

// $hoy y $hora vienen desde config.php
// Ejemplo:
// $hoy  = date('Y-m-d');
// $hora = date('H:i:s');

$ayer = date('Y-m-d', strtotime("$hoy -1 day"));

try {

    /* ===========================
       TOTAL HOY — ventas hasta la hora actual
       =========================== */
    $stmt1 = db()->prepare("
        SELECT COALESCE(SUM(valor), 0)
        FROM ventas
        WHERE fecha = :hoy
          AND hora <= :hora
    ");
    $stmt1->execute([
        ':hoy'  => $hoy,
        ':hora' => $hora
    ]);
    $ventasHoy = (float)$stmt1->fetchColumn();


    /* ===========================
       TOTAL AYER — ventas hasta la misma hora
       =========================== */
    $stmt2 = db()->prepare("
        SELECT COALESCE(SUM(valor), 0)
        FROM ventas
        WHERE fecha = :ayer
          AND hora <= :hora
    ");
    $stmt2->execute([
        ':ayer' => $ayer,
        ':hora' => $hora
    ]);
    $ventasAyer = (float)$stmt2->fetchColumn();


    /* ===========================
       DIFERENCIA Y PORCENTAJE
       =========================== */
    $diff = $ventasHoy - $ventasAyer;

    if ($ventasAyer > 0) {
        $percent = round(($diff / $ventasAyer) * 100, 1);
    } else {
        $percent = ($ventasHoy > 0 ? 100 : 0);
    }

    echo json_encode([
        "hoy"        => $ventasHoy,
        "ayer"       => $ventasAyer,
        "diferencia" => $diff,
        "percent"    => $percent,
        "debug"      => [
            "hoy"         => $hoy,
            "ayer"        => $ayer,
            "hora"        => $hora
        ]
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "hoy"        => 0,
        "ayer"       => 0,
        "diferencia" => 0,
        "percent"    => 0,
        "error"      => $e->getMessage()
    ]);
}



