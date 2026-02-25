<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Falta el id del cliente.']);
        exit;
    }

    $id          = trim($_POST['id']);
    $fechaInicio = trim($_POST['fecha_inicio'] ?? $hoy);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
        echo json_encode(['status' => 'error', 'message' => 'Formato de fecha inválido.']);
        exit;
    }

    try {
        $stmt = db()->prepare("
            SELECT id, estado, congelado, pago_plan, vencimiento_plan
            FROM clientes
            WHERE id = :id AND borrado = 0
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
            exit;
        }
        if ($cliente['congelado'] == 1) {
            echo json_encode(['status' => 'error', 'message' => 'El plan ya está congelado.']);
            exit;
        }
        if ($cliente['estado'] !== 'activo') {
            echo json_encode(['status' => 'error', 'message' => 'El cliente no está activo.']);
            exit;
        }

        $pagoPlan    = $cliente['pago_plan'];
        $vencimiento = $cliente['vencimiento_plan'];
        $daysAllowed = (int)(DAYS_ALLOWED_FROZEN);

        // ── Fecha mínima: max(hoy, pago_plan)
        // (la validación de última asistencia se hace en el frontend;
        //  el servidor confía en que la fecha enviada ya respeta ese mínimo)
        $minFecha = max($hoy, $pagoPlan);
        if ($fechaInicio < $minFecha) {
            echo json_encode([
                'status'  => 'error',
                'message' => "La fecha de congelamiento no puede ser anterior a $minFecha."
            ]);
            exit;
        }

        // ── Fecha máxima: vencimiento - DAYS_FROZEN días (o día anterior al vencimiento si DAYS_FROZEN = 0)
        $diasRestar  = $daysAllowed > 0 ? $daysAllowed : 1;
        $maxFecha    = date('Y-m-d', strtotime($vencimiento . " -$diasRestar days"));

        if ($fechaInicio > $maxFecha) {
            echo json_encode([
                'status'  => 'error',
                'message' => "La fecha de congelamiento no puede ser posterior a $maxFecha ($diasRestar días antes del vencimiento)."
            ]);
            exit;
        }

        // ── Regla DAYS_ALLOWED_FROZEN: días restantes desde HOY hasta vencimiento
        if ($daysAllowed > 0) {
            $diasRestantes = (int)((strtotime($vencimiento) - strtotime($hoy)) / 86400);
            if ($diasRestantes < $daysAllowed) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => "No se puede congelar: quedan $diasRestantes día(s) para el vencimiento y se requieren al menos $daysAllowed día(s)."
                ]);
                exit;
            }
        }

        // ── Congelar
        $stmt = db()->prepare("
            UPDATE clientes
            SET congelado       = 1,
                fecha_congelado = :fecha_congelado,
                updated_at      = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':fecha_congelado' => $fechaInicio,
            ':id'              => $id,
        ]);

        // ── LOG
        require_once __DIR__ . '/../inc/log_action.php';
        $desc = json_encode([
            'cliente_id'      => $id,
            'fecha_congelado' => $fechaInicio,
            'vencimiento'     => $vencimiento,
        ], JSON_UNESCAPED_UNICODE);
        log_action('Congelar Plan', $desc, 'Clientes');

        echo json_encode(['status' => 'success', 'message' => 'Plan congelado correctamente.']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al congelar el plan: ' . $e->getMessage()]);
        exit;
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}
?>
