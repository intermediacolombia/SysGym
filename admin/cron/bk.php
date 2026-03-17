<?php
/**
 * backup_db.php  ·  2025-06-16
 * — Dump, compresión .gz y retención de 5 copias —
 */

$debug = false;                   // ← pon true para ver detalles en CLI
ini_set('display_errors', $debug ? 1 : 0);
set_time_limit(0);
date_default_timezone_set('America/Bogota');

// Cargar config.php (que a su vez carga url_bd.php)
require_once __DIR__ . '/../../inc/config.php';

// Extraer variables de BD desde $GLOBALS (donde config.php las deja)
$host   = $GLOBALS['host']   ?? '';
$dbname = $GLOBALS['dbname'] ?? '';
$dbuser = $GLOBALS['dbuser'] ?? '';
$dbpass = $GLOBALS['dbpass'] ?? '';

// Validar que las variables estén disponibles
if (!$host || !$dbname || !$dbuser || !$dbpass) {
    die("Error: No se pudieron obtener las variables de conexión a la BD.");
}

if ($debug) {
    echo "=== Variables BD ===\n";
    echo "host:   $host\n";
    echo "dbname: $dbname\n";
    echo "dbuser: $dbuser\n";
    echo "dbpass: (oculta)\n\n";
}

/* --------------------------------------------------------------------------
 *  Rutas de respaldo
 * -------------------------------------------------------------------------- */
/* --------------------------------------------------------------------------
 *  Rutas de respaldo (relativas al archivo, independiente del dominio)
 * -------------------------------------------------------------------------- */
   $backupPathOriginal   = __DIR__ . '/../bk/original/';
    $backupPathCompressed = __DIR__ . '/../bk/compressed/';

foreach ([$backupPathOriginal, $backupPathCompressed] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        die("No se pudo crear la carpeta de backups: $dir");
    }
}

/* --------------------------------------------------------------------------
 *  Fechas y nombres
 * -------------------------------------------------------------------------- */
$stamp      = date('Y-m-d_H-i-s');
$baseName   = "{$dbname}_{$stamp}";
$dumpFile   = $backupPathOriginal   . $baseName . '.sql';
$dumpFileGz = $backupPathCompressed . $baseName . '.sql.gz';

/* --------------------------------------------------------------------------
 *  1) Archivo .cnf temporal (sin protocol=TCP para compatibilidad cPanel)
 * -------------------------------------------------------------------------- */
$tmpCnf  = tempnam(sys_get_temp_dir(), 'mysqldump_');
$passEsc = str_replace(['\\', '"'], ['\\\\', '\\"'], $dbpass);

$cfg = <<<CNF
[client]
user     = "{$dbuser}"
password = "{$passEsc}"
host     = "{$host}"

[mysqldump]
user     = "{$dbuser}"
password = "{$passEsc}"
host     = "{$host}"
CNF;

file_put_contents($tmpCnf, $cfg);
chmod($tmpCnf, 0600);

/* --------------------------------------------------------------------------
 *  2) Ejecutar mysqldump
 * -------------------------------------------------------------------------- */
$cmdDump = sprintf(
    'mysqldump --defaults-extra-file=%s --routines --triggers --events %s > %s 2>&1',
    escapeshellarg($tmpCnf),
    escapeshellarg($dbname),
    escapeshellarg($dumpFile)
);

if ($debug) {
    echo "=== .cnf ===\n$cfg\n=== CMD ===\n$cmdDump\n";
}

exec($cmdDump, $outDump, $retDump);

if ($debug && !empty($outDump)) {
    echo "=== Salida mysqldump ===\n" . implode("\n", $outDump) . "\n";
}

/* --------------------------------------------------------------------------
 *  3) Comprimir si el dump fue OK
 * -------------------------------------------------------------------------- */
if ($retDump === 0) {
    $cmdGzip = sprintf(
        'gzip -c %s > %s 2>&1',
        escapeshellarg($dumpFile),
        escapeshellarg($dumpFileGz)
    );
    exec($cmdGzip, $outGzip, $retGzip);

    if ($retGzip === 0) {
        limpiarAntiguos($backupPathOriginal,   '*.sql',    5);
        limpiarAntiguos($backupPathCompressed, '*.sql.gz', 5);
        // require '../bk/mailer/nuevo-backup.php';
        if ($debug) echo "✅ Backup completado: $dumpFileGz\n";
    } else {
        registrarError('Error al comprimir respaldo', $outGzip);
    }
} else {
    registrarError('Error al generar respaldo', $outDump);
}

/* --------------------------------------------------------------------------
 *  Limpieza
 * -------------------------------------------------------------------------- */
@unlink($tmpCnf);
exit;

/* ===================================================================== */
/* Auxiliares                                                            */
/* ===================================================================== */

function limpiarAntiguos(string $dir, string $pat, int $keep = 5): void
{
    $files = glob($dir . $pat);
    if (!$files) return;
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    foreach (array_slice($files, $keep) as $f) { @unlink($f); }
}

function registrarError(string $msg, array $detail): void
{
    error_log($msg . "\n" . implode("\n", $detail));
    // require '../bk/mailer/error-backup.php';
}

