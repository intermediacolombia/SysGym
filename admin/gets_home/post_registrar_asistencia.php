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

            // Notificar si se agotaron todas las entradas
            $stmtNuevoTotal = db()->prepare("SELECT COUNT(*) FROM tiquetera_consumos WHERE cliente_id = :id AND factura_id = :fid");
            $stmtNuevoTotal->execute([':id' => $cliente_id, ':fid' => $facturaActiva]);
            $totalAhora = (int)$stmtNuevoTotal->fetchColumn();

            error_log("[TIQ] cliente=$cliente_id factura=$facturaActiva totalAhora=$totalAhora limiteEntradas=$limiteEntradas");

            if ($totalAhora >= $limiteEntradas) {
                $stmtClienteWs = db()->prepare("SELECT nombres, apellidos, dialCode, telefono FROM clientes WHERE id = :id AND borrado = 0 LIMIT 1");
                $stmtClienteWs->execute([':id' => $cliente_id]);
                $clienteWs = $stmtClienteWs->fetch(PDO::FETCH_ASSOC);

                error_log("[TIQ] Agotada — telefono=" . ($clienteWs['telefono'] ?? 'VACIO') . " WA_API_URL=" . WA_API_URL . " api_ws=" . (empty($api_ws) ? 'VACIO' : 'OK'));

                if ($clienteWs && !empty($clienteWs['telefono'])) {
                    $plantilla = !empty($wa_tiquetera_agotada)
                        ? $wa_tiquetera_agotada
                        : 'Hola {nombres} 👋, has completado tus {entradas} entradas de tu tiquetera. Acércate a renovar para seguir entrenando 💪';

                    $mensaje = str_replace(
                        ['{nombres}', '{apellidos}', '{entradas}'],
                        [$clienteWs['nombres'], $clienteWs['apellidos'], $limiteEntradas],
                        $plantilla
                    );

                    $waCh = curl_init();
                    curl_setopt_array($waCh, [
                        CURLOPT_URL            => rtrim(WA_API_URL, '/') . '/send',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => json_encode([
                            'phonenumber' => $clienteWs['dialCode'] . $clienteWs['telefono'],
                            'text'        => $mensaje,
                        ], JSON_UNESCAPED_UNICODE),
                        CURLOPT_HTTPHEADER     => [
                            'Authorization: Bearer ' . $api_ws,
                            'Content-Type: application/json',
                            'Accept: application/json'
                        ]
                    ]);
                    $waResponse = curl_exec($waCh);
                    $waCode     = curl_getinfo($waCh, CURLINFO_HTTP_CODE);
                    $waError    = curl_error($waCh);
                    curl_close($waCh);

                    error_log("[TIQ] cURL httpCode=$waCode error=$waError response=$waResponse");

                    if ($waError || $waCode < 200 || $waCode >= 300 || empty(json_decode($waResponse, true)['success'])) {
                        require_once __DIR__ . '/../../whatsapp/save_failed_ws.php';
                        saveFailedWSMessage($clienteWs['dialCode'] . $clienteWs['telefono'], $mensaje);
                    }
                }
            }
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