<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$cliente_id = intval($body['cliente_id'] ?? 0);

if (!$cliente_id) {
    echo json_encode(['success' => false, 'message' => 'ID de cliente inválido']);
    exit;
}

try {
    // Verificar que el cliente existe, no está borrado y su estado permite registro
    $check = db()->prepare("
        SELECT id, estado, congelado, vencimiento_plan, plan_tipo, tiquetera_factura_id
        FROM clientes
        WHERE id = :id AND borrado = 0
        LIMIT 1
    ");
    $check->execute([':id' => $cliente_id]);
    $cliente = $check->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
        exit;
    }

    // Doble validación server-side de estados bloqueantes
    if ($cliente['estado'] === 'inactivo') {
        echo json_encode(['success' => false, 'message' => 'El cliente está inactivo']);
        exit;
    }
    if ($cliente['congelado'] == 1) {
        echo json_encode(['success' => false, 'message' => 'El plan está congelado']);
        exit;
    }
    if ($cliente['vencimiento_plan'] < $hoy) {
        echo json_encode(['success' => false, 'message' => 'El plan está vencido']);
        exit;
    }

    // Validar límite de entradas para tiqueteras
    if (($cliente['plan_tipo'] ?? '') === 'tiquetera' && !empty($cliente['tiquetera_factura_id'])) {
        $facturaActiva = (int)$cliente['tiquetera_factura_id'];

        $stmtLimite = db()->prepare("
            SELECT t.limite_entradas
            FROM clientes c
            JOIN tiqueteras t ON t.id = c.plan
            WHERE c.id = :id AND t.borrado = 0
        ");
        $stmtLimite->execute([':id' => $cliente_id]);
        $limiteEntradas = (int)$stmtLimite->fetchColumn();

        $stmtConsumidas = db()->prepare("
            SELECT COUNT(*) FROM tiquetera_consumos
            WHERE cliente_id = :id AND factura_id = :fid
        ");
        $stmtConsumidas->execute([':id' => $cliente_id, ':fid' => $facturaActiva]);
        $consumidas = (int)$stmtConsumidas->fetchColumn();

        if ($consumidas >= $limiteEntradas) {
            echo json_encode([
                'success' => false,
                'message' => "Tiquetera agotada ($consumidas/$limiteEntradas entradas usadas)"
            ]);
            exit;
        }
    }

    $stmt = db()->prepare("
        INSERT INTO asistencias (idCliente, fecha, hora)
        VALUES (:id, :fecha, :hora)
    ");
    $ok = $stmt->execute([
        ':id'    => $cliente_id,
        ':fecha' => $hoy,
        ':hora'  => $hora,
    ]);

    if ($ok) {
        $asistenciaId = db()->lastInsertId();

        // Registrar consumo de tiquetera
        if (($cliente['plan_tipo'] ?? '') === 'tiquetera' && !empty($cliente['tiquetera_factura_id'])) {
            $facturaActiva = (int)$cliente['tiquetera_factura_id'];

            $stmtConsumo = db()->prepare("
                INSERT INTO tiquetera_consumos (cliente_id, factura_id, asistencia_id, fecha, hora)
                VALUES (:cid, :fid, :aid, :fecha, :hora)
            ");
            $stmtConsumo->execute([
                ':cid'   => $cliente_id,
                ':fid'   => $facturaActiva,
                ':aid'   => $asistenciaId,
                ':fecha' => $hoy,
                ':hora'  => $hora,
            ]);

        }

        echo json_encode([
            'success' => true,
            'hora'    => 'Registrada a las ' . date('h:i A', strtotime($hora)),
            'message' => ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo insertar el registro']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}