<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Falta el id del cliente.']);
        exit;
    }

    $id = trim($_POST['id']);

    try {
        // Obtener datos del cliente
        $stmt = db()->prepare("
            SELECT vencimiento_plan, fecha_congelado
            FROM clientes
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
            exit;
        }

        if (empty($cliente['fecha_congelado']) || empty($cliente['vencimiento_plan'])) {
            echo json_encode(['status' => 'error', 'message' => 'No se dispone de información de congelado para este plan.']);
            exit;
        }

        $fechaCongelado      = new DateTime($cliente['fecha_congelado']);
        $vencimientoOriginal = new DateTime($cliente['vencimiento_plan']);
        $daysAllowed         = (int)(DAYS_ALLOWED_FROZEN);

        // ── Días que estuvo congelado (desde fecha_congelado hasta HOY)
        $fechaHoy      = new DateTime($hoy);
        $diasCongelado = (int)$fechaCongelado->diff($fechaHoy)->days;

        // ── Regla de corrimiento:
        //    Si diasCongelado >= DAYS_ALLOWED_FROZEN (o DAYS_FROZEN = 0) → recalcular vencimiento
        //    Si diasCongelado < DAYS_ALLOWED_FROZEN → vencimiento NO se mueve
        $correrFechas = ($daysAllowed === 0) || ($diasCongelado >= $daysAllowed);

        if ($correrFechas) {
            // Lógica original: días restantes entre fecha_congelado y vencimiento,
            // luego se suman desde HOY
            $diasDiff      = $fechaCongelado->diff($vencimientoOriginal)->days;
            $diasRestantes = $diasDiff - 1;
            if ($diasRestantes < 0) {
                $diasRestantes = 0;
            }
            $nuevaFechaVencimiento = date('Y-m-d', strtotime($hoy . " +{$diasRestantes} days"));
        } else {
            // El plan estuvo congelado menos de DAYS_ALLOWED_FROZEN días → sin cambio
            $nuevaFechaVencimiento = $cliente['vencimiento_plan'];
        }

        // ── Actualizar cliente
        $stmt = db()->prepare("
            UPDATE clientes
            SET congelado        = 0,
                fecha_congelado  = NULL,
                vencimiento_plan = :nuevaFechaVencimiento,
                updated_at       = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'                   => $id,
            ':nuevaFechaVencimiento'=> $nuevaFechaVencimiento,
        ]);

        // ── LOG
        require_once __DIR__ . '/../inc/log_action.php';
        $desc = json_encode([
            'cliente_id'            => $id,
            'nuevaFechaVencimiento' => $nuevaFechaVencimiento,
            'dias_congelado'        => $diasCongelado,
            'corrimiento_aplicado'  => $correrFechas,
        ], JSON_UNESCAPED_UNICODE);
        log_action('Descongelar Plan', $desc, 'Clientes');

        echo json_encode(['status' => 'success', 'message' => 'Plan descongelado correctamente.']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al descongelar el plan: ' . $e->getMessage()]);
        exit;
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}
?>



