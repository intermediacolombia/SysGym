<?php
/**
 * reset_webhook_logs.php
 * Ubicación: /admin/cron/reset_webhook_logs.php
 * Elimina los logs y sesiones del webhook WS.
 * Ejecutar via cron a las 12:00 AM diariamente.
 */

// __DIR__ = .../admin/cron → subir 2 niveles → raíz del proyecto
$base = dirname(__DIR__, 2) . '/webhook/';

$archivos = [
    $base . 'webhook-ws.log',
    $base . 'estados_ws.json',
    $base . 'processed_ids.json',
];

$resultados = [];
foreach ($archivos as $ruta) {
    if (file_exists($ruta)) {
        unlink($ruta)
            ? $resultados[] = "BORRADO: " . basename($ruta)
            : $resultados[] = "ERROR: " . basename($ruta);
    } else {
        $resultados[] = "NO EXISTE: " . basename($ruta);
    }
}

// Dejar constancia en un log separado
$logCron = $base . 'cron-reset.log';
file_put_contents($logCron, '[' . date('Y-m-d H:i:s') . '] ' . implode(', ', $resultados) . "\n", FILE_APPEND);

echo implode("\n", $resultados) . "\n";