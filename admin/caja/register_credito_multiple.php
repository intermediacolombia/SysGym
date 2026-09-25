<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

// Caja abierta
$stmtCaja = db()->prepare("SELECT id FROM cajas WHERE usuario_id = :uid AND estado = 1 LIMIT 1");
$stmtCaja->execute([':uid' => $id_user]);
$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
if (!$caja) { echo json_encode(['status'=>'error','message'=>'No tienes caja abierta']); exit; }
$caja_id = $caja['id'];

// Validar campos requeridos
$required = ['items','cliente_id','valor_pagado','fecha_limite','payment_method'];
foreach ($required as $f) {
    if (!isset($_POST[$f])) { echo json_encode(['status'=>'error','message'=>"Falta: $f"]); exit; }
}

$items        = json_decode($_POST['items'], true);
$cliente_id   = intval($_POST['cliente_id']);
$valor_pagado = floatval($_POST['valor_pagado']);
$fecha_limite = $_POST['fecha_limite'];
$method       = $_POST['payment_method'];
$bank         = $_POST['bank'] ?? '';

if (!is_array($items) || count($items) === 0) {
    echo json_encode(['status'=>'error','message'=>'Sin productos']); exit;
}
if ($fecha_limite < date('Y-m-d')) {
    echo json_encode(['status'=>'error','message'=>'Fecha límite no puede ser anterior a hoy']); exit;
}

// Calcular total
$total_venta = array_reduce($items, fn($s,$i) => $s + round($i['qty'] * $i['precio']), 0.0);
$credito_restante = $total_venta - $valor_pagado;
if ($credito_restante <= 0) {
    echo json_encode(['status'=>'error','message'=>'El abono debe ser menor al total']); exit;
}

// Verificar stock de todos antes de iniciar
foreach ($items as $item) {
    $st = db()->prepare("SELECT nombre, stock FROM productos WHERE id = :id AND borrado = 0");
    $st->execute([':id' => intval($item['pid'])]);
    $prod = $st->fetch(PDO::FETCH_ASSOC);
    if (!$prod) { echo json_encode(['status'=>'error','message'=>'Producto no encontrado: '.$item['nombre']]); exit; }
    if ($prod['stock'] < intval($item['qty'])) {
        echo json_encode(['status'=>'error','message'=>'Stock insuficiente: '.$prod['nombre']]); exit;
    }
}

db()->beginTransaction();
try {
    $descripcion = implode(', ', array_map(fn($i) => $i['nombre'], $items));

    // 1. Crear crédito único por el total restante
    $st = db()->prepare("INSERT INTO creditos (idCliente, fecha, valor, fecha_limite, descripcion, created_at, updated_at)
        VALUES (:idCliente, :fecha, :valor, :fecha_limite, :descripcion, NOW(), NOW())");
    $st->execute([
        ':idCliente'   => $cliente_id,
        ':fecha'       => date('Y-m-d'),
        ':valor'       => $credito_restante,
        ':fecha_limite'=> $fecha_limite,
        ':descripcion' => mb_substr($descripcion, 0, 255)
    ]);
    $credito_id = db()->lastInsertId();

    // 2. Registrar venta y descontar stock por cada ítem
    // El abono se distribuye proporcional — pero se registra 1 venta con valor_pagado en la primera,
    // el resto con 0 (el crédito cubre todo). Más simple: todo el abono va en la primera venta.
    $abono_restante = $valor_pagado;
    foreach ($items as $idx => $item) {
        $pid   = intval($item['pid']);
        $qty   = intval($item['qty']);
        $precio= floatval($item['precio']);
        $nombre= $item['nombre'];
        $valor_item = round($qty * $precio);

        // Abono proporcional: aplica hasta agotar el abono
        $abono_item = min($abono_restante, $valor_item);
        $abono_restante -= $abono_item;

        $st = db()->prepare("INSERT INTO ventas (caja_id, producto_id, detalle, cantidad, valor, fecha, hora, payment_method, bank, creditoId)
            VALUES (:caja_id, :pid, :detalle, :qty, :valor, :fecha, :hora, :method, :bank, :cid)");
        $st->execute([
            ':caja_id' => $caja_id,
            ':pid'     => $pid,
            ':detalle' => $nombre,
            ':qty'     => $qty,
            ':valor'   => $abono_item,
            ':fecha'   => $hoy,
            ':hora'    => $hora,
            ':method'  => $method,
            ':bank'    => $bank,
            ':cid'     => $credito_id
        ]);

        db()->prepare("UPDATE productos SET stock = stock - :qty WHERE id = :id")
             ->execute([':qty' => $qty, ':id' => $pid]);
    }

    // Log
    require_once __DIR__ . '/../inc/log_action.php';
    log_action('Registrar Credito Multiple', json_encode([
        'credito_id' => $credito_id,
        'cliente_id' => $cliente_id,
        'total'      => $total_venta,
        'abono'      => $valor_pagado,
        'restante'   => $credito_restante,
        'items'      => count($items)
    ], JSON_UNESCAPED_UNICODE), 'Caja');

    db()->commit();

    $stTotal = db()->prepare("SELECT IFNULL(SUM(valor),0) FROM ventas WHERE caja_id = :cid");
    $stTotal->execute([':cid' => $caja_id]);
    $total_caja = (int)$stTotal->fetchColumn();

    echo json_encode(['status'=>'success','total_caja'=>$total_caja]);

} catch (Exception $e) {
    db()->rollBack();
    echo json_encode(['status'=>'error','message'=>'Error: '.$e->getMessage()]);
}
?>
