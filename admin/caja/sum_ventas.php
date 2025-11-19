<?php
// Activar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir sesión y configuración (ajusta las rutas según tu estructura)
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

try {
    db();
} catch(PDOException $e){
    die("Error en la conexión: " . $e->getMessage());
}

$caja_id = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;
if($caja_id <= 0){
    die("Caja no válida");
}

// Consulta para agrupar las ventas por método de pago y banco
$sql = "SELECT payment_method, bank, IFNULL(SUM(valor), 0) as total 
        FROM ventas 
        WHERE caja_id = :caja_id 
        GROUP BY payment_method, bank";
$stmt = db()->prepare($sql);
$stmt->execute([':caja_id' => $caja_id]);

// Inicializar variables para los totales
$efectivo = 0;
$Transferencias = [
    'Bancolombia' => 0,
    'Daviplata'   => 0,
    'Nequi'       => 0,
    'Davivienda'  => 0,
];

// Recorrer resultados y asignar los totales a variables
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    if($row['payment_method'] === 'Efectivo'){
        $efectivo += $row['total'];
    } elseif($row['payment_method'] === 'Transferencia'){
        $bank = $row['bank'];
        if(array_key_exists($bank, $Transferencias)){
            $Transferencias[$bank] += $row['total'];
        }
    }
}

// Ahora, por ejemplo, puedes calcular totales generales
$totalTransferencias = $Transferencias['Bancolombia'] +
                       $Transferencias['Daviplata'] +
                       $Transferencias['Nequi'] +
                       $Transferencias['Davivienda'];

$totalVendido = $efectivo + $totalTransferencias;

// Imprimir los resultados en pantalla
echo "<h3>Resultados de Ventas (Caja ID: $caja_id)</h3>";
echo "Efectivo: $" . number_format($efectivo, 0, '', '.') . "<br><br>";

echo "Transferencias:<br>";
foreach($Transferencias as $bank => $total){
    echo "- $bank: $" . number_format($total, 0, '', '.') . "<br>";
}

echo "<br>Total Vendido: $" . number_format($totalVendido, 0, '', '.');
?>
