<?php
require_once __DIR__ . '/../../inc/config.php';
date_default_timezone_set('America/Bogota');

// FECHA FIJA PARA PRUEBAS
$hoy  = "2025-11-21";
$ayer = date('Y-m-d', strtotime("$hoy -1 day"));

try {

    // TOTAL HOY → suma de total_vendido de todas las cajas abiertas ese día
    $stmt1 = db()->prepare("
        SELECT COALESCE(SUM(total_vendido), 0)
        FROM cajas
        WHERE fecha_apertura = :hoy
          AND borrado = 0
    ");
    $stmt1->execute([':hoy' => $hoy]);
    $ventasHoy = (float)$stmt1->fetchColumn();

    // TOTAL AYER
    $stmt2 = db()->prepare("
        SELECT COALESCE(SUM(total_vendido), 0)
        FROM cajas
        WHERE fecha_apertura = :ayer
          AND borrado = 0
    ");
    $stmt2->execute([':ayer' => $ayer]);
    $ventasAyer = (float)$stmt2->fetchColumn();

    // CÁLCULOS
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

