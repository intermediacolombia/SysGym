<?php
require_once __DIR__ . '/../../inc/config.php';

$ayer = date('Y-m-d', strtotime("$hoy -1 day"));

try {

    /* ===========================
       TOTAL HOY — tabla ventas
       =========================== */
    $stmt1 = db()->prepare("
        SELECT COALESCE(SUM(valor), 0)
        FROM ventas
        WHERE fecha = :hoy
    ");
    $stmt1->execute([':hoy' => $hoy]);
    $ventasHoy = (float)$stmt1->fetchColumn();


    /* ===========================
       TOTAL AYER — tabla ventas
       =========================== */
    $stmt2 = db()->prepare("
        SELECT COALESCE(SUM(valor), 0)
        FROM ventas
        WHERE fecha = :ayer
    ");
    $stmt2->execute([':ayer' => $ayer]);
    $ventasAyer = (float)$stmt2->fetchColumn();


    /* ===========================
       DIFERENCIAS
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
            "hoy"  => $hoy,
            "ayer" => $ayer
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


