<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');

$identificacion = trim($_GET['documento'] ?? '');
if (!$identificacion) {
    echo json_encode(null);
    exit;
}

try {
    $stmt = db()->prepare("
        SELECT
            c.id,
            CONCAT(c.nombres, ' ', c.apellidos) AS nombre_completo,
            c.identificacion AS documento,
            c.estado,
            c.congelado,
            c.vencimiento_plan,
            c.plan_tipo,
            c.tiquetera_factura_id,
            (
                SELECT COUNT(*)
                FROM asistencias a
                WHERE a.idCliente = c.id
                  AND a.fecha = :hoy
            ) AS ya_asistio
        FROM clientes c
        WHERE c.identificacion = :identificacion
          AND c.borrado = 0
        LIMIT 1
    ");
    $stmt->execute([
        ':identificacion' => $identificacion,
        ':hoy'            => $hoy,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(null);
        exit;
    }

    // Determinar estado real combinado
    $estadoFinal = $row['estado'];

    if ($row['congelado'] == 1) {
        $estadoFinal = 'congelado';
    } elseif ($row['vencimiento_plan'] < $hoy) {
        $estadoFinal = 'vencido';
    }

    // Info de tiquetera
    $row['entradas_consumidas'] = null;
    $row['limite_entradas']     = null;

    if ($row['plan_tipo'] === 'tiquetera' && !empty($row['tiquetera_factura_id'])) {
        $stmtLim = db()->prepare("
            SELECT t.limite_entradas
            FROM clientes c
            JOIN tiqueteras t ON t.id = c.plan
            WHERE c.id = :id AND t.borrado = 0
        ");
        $stmtLim->execute([':id' => $row['id']]);
        $limite = (int)$stmtLim->fetchColumn();

        $stmtCons = db()->prepare("
            SELECT COUNT(*) FROM tiquetera_consumos
            WHERE cliente_id = :id AND factura_id = :fid
        ");
        $stmtCons->execute([':id' => $row['id'], ':fid' => $row['tiquetera_factura_id']]);
        $consumidas = (int)$stmtCons->fetchColumn();

        $row['entradas_consumidas'] = $consumidas;
        $row['limite_entradas']     = $limite;

        if ($estadoFinal === 'activo' && $consumidas >= $limite) {
            $estadoFinal = 'agotada';
        }
    }

    $row['estado_real'] = $estadoFinal;

    echo json_encode($row);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}