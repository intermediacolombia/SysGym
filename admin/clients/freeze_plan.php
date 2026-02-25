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

    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
        echo json_encode(['status' => 'error', 'message' => 'Formato de fecha inválido.']);
        exit;
    }

    try {
        // Obtener datos actuales del cliente
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

        // ── La fecha elegida no puede ser antes del pago del plan
        if ($fechaInicio < $pagoPlan) {
            echo json_encode([
                'status'  => 'error',
                'message' => "La fecha de congelamiento no puede ser anterior a la fecha de pago del plan ($pagoPlan)."
            ]);
            exit;
        }

        // ── La fecha elegida no puede ser igual o posterior al vencimiento
        if ($fechaInicio >= $vencimiento) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'La fecha de congelamiento no puede ser igual o posterior al vencimiento del plan.'
            ]);
            exit;
        }

        // ── Regla DAYS_ALLOWED_FROZEN
        if ($daysAllowed > 0) {

            // Quedan menos de DAYS_ALLOWED_FROZEN días para el vencimiento → bloquear
            $diasRestantes = (int)((strtotime($vencimiento) - strtotime($hoy)) / 86400);
            if ($diasRestantes < $daysAllowed) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => "No se puede congelar: quedan $diasRestantes día(s) para el vencimiento y se requieren al menos $daysAllowed día(s)."
                ]);
                exit;
            }

            // La fecha elegida no puede superar pago_plan + DAYS_ALLOWED_FROZEN
            $maxFecha = date('Y-m-d', strtotime($pagoPlan . " +$daysAllowed days"));
            if ($fechaInicio > $maxFecha) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => "La fecha de inicio no puede ser posterior a $maxFecha ($daysAllowed días desde el pago del plan)."
                ]);
                exit;
            }
        }

        // ── Congelar con la fecha elegida
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
