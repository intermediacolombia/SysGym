<?php
/**
 * reset_webhook_logs.php
 * Ubicación: /admin/cron/reset_webhook_logs.php
 * Limpia los logs y sesiones del webhook WS.
 * Ejecutar via cron a las 12:00 AM diariamente.
 */

// __DIR__ = /home/activgym/app.activgym.com.co/admin/cron
// webhook  = /home/activgym/app.activgym.com.co/webhook
$base = dirname(__DIR__, 2) . '/webhook/';

$archivos = [
    $base . 'webhook-ws.log'     => '',
    $base . 'estados_ws.json'    => '[]',
    $base . 'processed_ids.json' => '[]',
];

$resultados = [];
foreach ($archivos as $ruta => $contenido) {
    if (file_put_contents($ruta, $contenido) !== false) {
        $resultados[] = "OK: " . basename($ruta);
    } else {
        $resultados[] = "ERROR: " . basename($ruta);
    }
}

$log = '[' . date('Y-m-d H:i:s') . '] Reset diario: ' . implode(', ', $resultados) . "\n";
file_put_contents($base . 'webhook-ws.log', $log, FILE_APPEND);

echo $log;
