<?php
require_once __DIR__ . '/../../../inc/config.php';
header('Content-Type: application/json');

try {
    $hoy = date('Y-m-d');

    // 1. Obtener cuántos se han enviado hoy ya
    $stmtEnv = db()->prepare("SELECT COUNT(*) FROM ws_envios_log WHERE fecha = :hoy");
    $stmtEnv->execute([':hoy' => $hoy]);
    $enviadosHoy = (int)$stmtEnv->fetchColumn();

    // 2. Obtener el límite diario configurado
    $limiteDiario = defined('WA_MASS_LIMIT') ? (int)WA_MASS_LIMIT : 50;

    // 3. Calcular cuántos cupos quedan para hoy
    $cuposRestantes = max(0, $limiteDiario - $enviadosHoy);

    // 4. Obtener solo los mensajes que se enviarán hoy (limitado por cupos restantes)
    $stmt = db()->prepare("
        SELECT id, nombre, telefono, mensaje, adjunto
        FROM envios_masivos_ws
        ORDER BY id ASC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $cuposRestantes, PDO::PARAM_INT);
    $stmt->execute();
    $mensajesHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'enviados_hoy' => $enviadosHoy,
        'limite_diario' => $limiteDiario,
        'cupos_restantes' => $cuposRestantes,
        'mensajes' => $mensajesHoy
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}