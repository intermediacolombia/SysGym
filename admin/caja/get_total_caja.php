<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo json_encode(['status'=>'error', 'message'=>'Error en la conexión']);
    exit;
}

$caja_id = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;
if($caja_id <= 0){
    echo json_encode(['status'=>'error', 'message'=>'Caja no válida']);
    exit;
}


// Tu consulta original, sin modificaciones
$stmt = $pdo->prepare("SELECT payment_method, bank, IFNULL(SUM(valor), 0) as total 
                       FROM ventas 
                       WHERE caja_id = :caja_id 
                       GROUP BY payment_method, bank");
$stmt->execute([':caja_id' => $caja_id]);





$efectivo_total = 0;
$Transferencias = [
    "Bancolombia" => 0,
    "Daviplata"   => 0,
    "Nequi"       => 0,
    "Davivienda"  => 0
];

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    if($row['payment_method'] === 'Efectivo'){
        $efectivo_total += $row['total'];
    } elseif($row['payment_method'] === 'Transferencia'){
        $bank = $row['bank'];
        if(array_key_exists($bank, $Transferencias)){
            $Transferencias[$bank] = $row['total'];
        }
    }
}

// AHORA: obtener egresos usando payment_method = 'Egreso'
$stmtEgresos = $pdo->prepare("SELECT IFNULL(SUM(valor), 0) FROM ventas WHERE caja_id = :caja_id AND payment_method = 'Egreso'");
$stmtEgresos->execute([':caja_id' => $caja_id]);
$egresos_total = abs((float)$stmtEgresos->fetchColumn());



// Consultar bolsillos, excluyendo las ventas que no tienen producto asignado (producto_id IS NOT NULL)
/*$stmtBolsillos = $pdo->prepare("SELECT b.nombre AS bolsillo, IFNULL(SUM(v.valor), 0) AS total 
                               FROM ventas v 
                               LEFT JOIN productos p ON v.producto_id = p.id 
                               LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
                               WHERE v.caja_id = :caja_id AND v.producto_id IS NOT NULL 
                               GROUP BY b.nombre");
$stmtBolsillos->execute([':caja_id' => $caja_id]);*/

// Consultar bolsillos, excluyendo las ventas sin producto asignado y solo para pagos en Efectivo
$stmtBolsillos = $pdo->prepare("SELECT IFNULL(b.nombre, 'Facturas') AS bolsillo, IFNULL(SUM(v.valor), 0) AS total 
  FROM ventas v 
  LEFT JOIN productos p ON v.producto_id = p.id 
  LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
  WHERE v.caja_id = :caja_id 
    AND v.payment_method = 'Efectivo'
  GROUP BY IFNULL(b.nombre, 'Facturas')
");
$stmtBolsillos->execute([':caja_id' => $caja_id]);

/*$stmtBolsillos = $pdo->prepare("SELECT 
    CASE 
      WHEN UPPER(v.detalle) LIKE '%FACTURA%' THEN 'Caja General'
      ELSE IFNULL(b.nombre, 'Sin Asignar')
    END AS bolsillo,
    IFNULL(SUM(v.valor), 0) AS total
  FROM ventas v 
  LEFT JOIN productos p ON v.producto_id = p.id 
  LEFT JOIN bolsillos b ON p.id_bolsillo = b.id 
  WHERE v.caja_id = :caja_id 
    AND v.producto_id IS NOT NULL 
    AND v.payment_method = 'Efectivo'
  GROUP BY 
    CASE 
      WHEN UPPER(v.detalle) LIKE '%FACTURA%' THEN 'Caja General'
      ELSE IFNULL(b.nombre, 'Sin Asignar')
    END");
$stmtBolsillos->execute([':caja_id' => $caja_id]);*/


$bolsillos = [];
while($row = $stmtBolsillos->fetch(PDO::FETCH_ASSOC)){
    // Si no se encuentra un bolsillo asignado, se usa un nombre predeterminado (por ejemplo "Sin Asignar")
    $nombreBolsillo = $row['bolsillo'];
    $bolsillos[$nombreBolsillo] = $row['total'];
}




echo json_encode([
    'status' => 'success', 
    'efectivo' => $efectivo_total,
    'egresos' => $egresos_total,
    'Transferencias' => $Transferencias,
    'bolsillos'      => $bolsillos
	
]);


