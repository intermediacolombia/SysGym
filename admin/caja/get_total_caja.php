<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$caja_id = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;

if($caja_id <= 0){
    echo json_encode(['status'=>'error', 'message'=>'Caja no válida']);
    exit;
}

// Obtener bancos disponibles desde la configuración
$bancos = getBancosDisponibles();

// Inicializar array de transferencias con todos los bancos en 0
$Transferencias = [];
foreach($bancos as $banco) {
    $Transferencias[$banco] = 0;
}

// Consulta original para obtener ventas agrupadas por método y banco
$stmt = db()->prepare("SELECT payment_method, bank, IFNULL(SUM(valor), 0) as total 
                       FROM ventas 
                       WHERE caja_id = :caja_id 
                       GROUP BY payment_method, bank");
$stmt->execute([':caja_id' => $caja_id]);

$efectivo_total = 0;

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    if($row['payment_method'] === 'Efectivo'){
        $efectivo_total += $row['total'];
    } elseif($row['payment_method'] === 'Transferencia'){
        $bank = $row['bank'];
        // Solo agregar si el banco está en la lista configurada
        if(array_key_exists($bank, $Transferencias)){
            $Transferencias[$bank] = floatval($row['total']);
        }
    }
}

// Obtener egresos usando payment_method = 'Egreso'
$stmtEgresos = db()->prepare("SELECT IFNULL(SUM(valor), 0) FROM ventas WHERE caja_id = :caja_id AND payment_method = 'Egreso'");
$stmtEgresos->execute([':caja_id' => $caja_id]);
$egresos_total = abs((float)$stmtEgresos->fetchColumn());

// Consultar bolsillos, excluyendo las ventas sin producto asignado y solo para pagos en Efectivo
$stmtBolsillos = db()->prepare("SELECT IFNULL(b.nombre, 'Facturas') AS bolsillo, IFNULL(SUM(v.valor), 0) AS total 
  FROM ventas v 
  LEFT JOIN productos p ON v.producto_id = p.id 
  LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
  WHERE v.caja_id = :caja_id 
    AND v.payment_method = 'Efectivo'
  GROUP BY IFNULL(b.nombre, 'Facturas')
");
$stmtBolsillos->execute([':caja_id' => $caja_id]);

$bolsillos = [];
while($row = $stmtBolsillos->fetch(PDO::FETCH_ASSOC)){
    $nombreBolsillo = $row['bolsillo'];
    $bolsillos[$nombreBolsillo] = $row['total'];
}

echo json_encode([
    'status' => 'success', 
    'efectivo' => $efectivo_total,
    'egresos' => $egresos_total,
    'Transferencias' => $Transferencias,
    'bolsillos' => $bolsillos
]);

