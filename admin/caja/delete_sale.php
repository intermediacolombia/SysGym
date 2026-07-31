<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';



if (!isset($_POST['sale_id']) || !isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos']);
    exit;
}

$sale_id = intval($_POST['sale_id']);
$producto_id = intval($_POST['producto_id']);
$cantidad = intval($_POST['cantidad']);

// Verificar que la venta exista y obtener el creditoId (si existe)
$stmtSale = db()->prepare("SELECT v.valor, v.creditoId, v.caja_id, p.nombre AS detalle FROM ventas v LEFT JOIN productos p ON p.id = v.producto_id WHERE v.id = :id");
$stmtSale->execute([':id' => $sale_id]);
$sale = $stmtSale->fetch(PDO::FETCH_ASSOC);
if (!$sale) {
    echo json_encode(['status' => 'error', 'message' => 'Venta no encontrada']);
    exit;
}

$creditoId = $sale['creditoId']; // Obtener el ID del crédito asociado (puede ser NULL)

// Iniciar transacción
db()->beginTransaction();
try {
    // Eliminar la venta
    $stmtDelete = db()->prepare("DELETE FROM ventas WHERE id = :id");
    $stmtDelete->execute([':id' => $sale_id]);

    // Si existe un creditoId, eliminar el crédito asociado
    if ($creditoId) {
        $stmtDeleteCredito = db()->prepare("DELETE FROM creditos WHERE id = :creditoId");
        $stmtDeleteCredito->execute([':creditoId' => $creditoId]);
    }

    // Devolver la cantidad vendida al stock del producto
    $stmtUpdate = db()->prepare("UPDATE productos SET stock = stock + :cantidad WHERE id = :id");
    $stmtUpdate->execute([':cantidad' => $cantidad, ':id' => $producto_id]);

    // Consultar el nuevo stock del producto
    $stmtNew = db()->prepare("SELECT stock FROM productos WHERE id = :id");
    $stmtNew->execute([':id' => $producto_id]);
    $nuevoProducto = $stmtNew->fetch(PDO::FETCH_ASSOC);
    $nuevo_stock = $nuevoProducto ? $nuevoProducto['stock'] : 0;

    db()->commit();

    $stmtTotal = db()->prepare("SELECT IFNULL(SUM(valor), 0) AS total FROM ventas WHERE caja_id = :caja_id");
    $stmtTotal->execute([':caja_id' => $sale['caja_id']]);
    $total_caja = (int)$stmtTotal->fetchColumn();

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Venta borrada y stock actualizado' . ($creditoId ? '. Credito eliminado.' : ''),
        'nuevo_stock' => $nuevo_stock,
        'detalle'    => $sale['detalle'] ?? '',
        'valor'      => (int)$sale['valor'],
        'total_caja' => $total_caja
    ]);
} catch (Exception $e) {
    db()->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error al borrar la venta: ' . $e->getMessage()]);
}
?>
