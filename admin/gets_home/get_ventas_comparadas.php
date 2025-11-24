<?php
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

$hoy = "2025-11-21";
$ayer = date('2025-11-21', strtotime('-1 day'));

try {

    // Total HOY (sin base)
    $stmt1 = db()->prepare("
        SELECT COALESCE(SUM(valor),0)
        FROM caja
        WHERE DATE(fecha) = :hoy
          AND tipo != 'base'
    ");
    $stmt1->execute([':hoy' => $hoy]);
    $ventasHoy = (float)$stmt1->fetchColumn();

    // Total AYER (sin base)
    $stmt2 = db()->prepare("
        SELECT COALESCE(SUM(valor),0)
        FROM caja
        WHERE DATE(fecha) = :ayer
          AND tipo != 'base'
    ");
    $stmt2->execute([':ayer' => $ayer]);
    $ventasAyer = (float)$stmt2->fetchColumn();

    // Diferencias
    $diff = $ventasHoy - $ventasAyer;
    $percent = ($ventasAyer > 0)
        ? round(($diff / $ventasAyer) * 100, 1)
        : ($ventasHoy > 0 ? 100 : 0);

    echo json_encode([
        "hoy" => $ventasHoy,
        "ayer" => $ventasAyer,
        "diferencia" => $diff,
        "percent" => $percent
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "hoy" => 0,
        "ayer" => 0,
        "diferencia" => 0,
        "percent" => 0,
        "error" => $e->getMessage()
    ]);
}
