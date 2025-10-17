<?php
/**
 * backup_db.php  ·  2025-06-16
 * — Dump, compresión .gz y retención de 5 copias —
 */

$debug = false;                   // ← pon true para ver detalles en CLI
ini_set('display_errors', $debug ? 1 : 0);
set_time_limit(0);
date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../../inc/config.php';   // $host, $dbname, $dbuser, $dbpass

/* --------------------------------------------------------------------------
 *  Rutas de respaldo
 * -------------------------------------------------------------------------- */
$backupPathOriginal   = '/home/activgym/sysgym.intermediacolombia.com/admin/bk/original/';
$backupPathCompressed = '/home/activgym/sysgym.intermediacolombia.com/admin/bk/compressed/';

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
 *  1) Archivo .cnf temporal (contraseña entre comillas + protocolo TCP)
 * -------------------------------------------------------------------------- */
$tmpCnf = tempnam(sys_get_temp_dir(), 'mysqldump_');
$passEsc = str_replace(['\\', '"'], ['\\\\', '\\"'], $dbpass);   // escapar \ y "
$cfg = <<<CNF
[client]
user     = "{$dbuser}"
password = "{$passEsc}"
host     = "{$host}"
protocol = TCP

[mysqldump]
user     = "{$dbuser}"
password = "{$passEsc}"
host     = "{$host}"
protocol = TCP
CNF;
file_put_contents($tmpCnf, $cfg);
chmod($tmpCnf, 0600);

/* --------------------------------------------------------------------------
 *  2) Ejecutar mysqldump
 * -------------------------------------------------------------------------- */
$cmdDump = sprintf(
    'mysqldump --defaults-extra-file=%s --routines --triggers --events ' .
    '--protocol=TCP %s > %s 2>&1',
    escapeshellarg($tmpCnf),
    escapeshellarg($dbname),
    escapeshellarg($dumpFile)
);

if ($debug) {
    echo "=== .cnf ===\n$cfg\n=== CMD ===\n$cmdDump\n";
}

exec($cmdDump, $outDump, $retDump);

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
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    foreach (array_slice($files, $keep) as $f) { @unlink($f); }
}

function registrarError(string $msg, array $detail): void
{
    error_log($msg . "\n" . implode("\n", $detail));
    // require '../bk/mailer/error-backup.php';
}

