<?php
/**
 * reset_webhook_logs.php
 * Limpia los logs y sesiones del webhook WS.
 * Ejecutar via cron a las 12:00 AM diariamente.
 */

$base = __DIR__ . '/../webhook/';

$archivos = [
    $base . 'webhook-ws.log'     => '',    // vaciar log
    $base . 'estados_ws.json'    => '[]',  // resetear sesiones
    $base . 'processed_ids.json' => '[]',  // resetear anti-duplicados
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
