<?php
// Activar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir sesión y configuración
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$caja_id = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;
if ($caja_id <= 0) {
    die("Caja no válida");
}

// === BANCOS DINÁMICOS ===
$bancosDisponibles = getBancosDisponibles();

// Inicializar array de transferencias con todos los bancos en 0
$Transferencias = [];
foreach ($bancosDisponibles as $banco) {
    $Transferencias[$banco] = 0;
}

// Consulta para agrupar las ventas por método de pago y banco
$sql = "SELECT payment_method, bank, IFNULL(SUM(valor), 0) as total 
        FROM ventas 
        WHERE caja_id = :caja_id 
        GROUP BY payment_method, bank";
$stmt = db()->prepare($sql);
$stmt->execute([':caja_id' => $caja_id]);

// Inicializar variable para efectivo
$efectivo = 0;

// Recorrer resultados y asignar los totales
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['payment_method'] === 'Efectivo') {
        $efectivo += $row['total'];
    } elseif ($row['payment_method'] === 'Transferencia') {
        $bank = $row['bank'];
        if (array_key_exists($bank, $Transferencias)) {
            $Transferencias[$bank] += floatval($row['total']);
        }
    }
}

// Calcular total de transferencias dinámicamente
$totalTransferencias = array_sum($Transferencias);
$totalVendido = $efectivo + $totalTransferencias;

// Imprimir los resultados en pantalla
echo "<h3>Resultados de Ventas (Caja ID: $caja_id)</h3>";
echo "Efectivo: $" . number_format($efectivo, 0, '', '.') . "<br><br>";
echo "Transferencias:<br>";
foreach ($Transferencias as $bank => $total) {
    echo "- $bank: $" . number_format($total, 0, '', '.') . "<br>";
}
echo "<br>Total Vendido: $" . number_format($totalVendido, 0, '', '.');
?>
