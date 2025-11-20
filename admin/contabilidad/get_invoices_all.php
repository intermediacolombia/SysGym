<?php
require_once __DIR__ . '/../../inc/config.php';
header('Content-Type: application/json');

// Consulta sin campo borrado
$stmt = db()->prepare("
    SELECT id, fecha, nombre_cliente, detalle, valor
    FROM facturas
    ORDER BY fecha DESC
");
$stmt->execute();
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para limitar texto a 30 caracteres
function limitarTexto($texto, $limite = 30) {
    $texto = htmlspecialchars($texto ?? '');

    if (strlen($texto) <= $limite) {
        return $texto;
    }

    return substr($texto, 0, $limite) . '...';
}

// Aplicar el recorte a cada fila
foreach ($facturas as &$f) {
    $f['detalle'] = limitarTexto($f['detalle'], 30);
}

echo json_encode(['data' => $facturas]);
?>


