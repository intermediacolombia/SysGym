<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\Client\Payment\PaymentClient;

$data = json_decode(file_get_contents('php://input'), true);
file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " " . json_encode($data) . "\n", FILE_APPEND);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* ==========================================================
       VERIFICAR TABLA PAGOS (auto-creación si no existe)
    =========================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pagos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            referencia VARCHAR(100) NOT NULL,
            monto DECIMAL(10,2) NOT NULL DEFAULT 0,
            estado ENUM('pending','approved','rejected') DEFAULT 'pending',
            metodo_pago VARCHAR(50) DEFAULT NULL,
            fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
            raw_response JSON NULL,
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " [DB ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    exit;
}

/* ==========================================================
   PROCESAR NOTIFICACIÓN MERCADO PAGO
========================================================== */
if (!empty($data['type']) && $data['type'] === 'payment' && !empty($data['data']['id'])) {
    try {
        $payment_id = $data['data']['id'];
        $client = new PaymentClient();
        $payment = $client->get($payment_id);

        $ref = $payment->external_reference ?? '';
        $estadoPago = $payment->status ?? 'unknown';
        $montoPago = $payment->transaction_amount ?? 0;

        // === Registrar siempre el pago (sin duplicar referencia) ===
        $stmt = $pdo->prepare("SELECT id FROM pagos WHERE referencia = :ref LIMIT 1");
        $stmt->execute([':ref' => $ref]);
        $existe = $stmt->fetchColumn();

        if (!$existe && preg_match('/pago_(\d+)_/', $ref, $match)) {
            $cliente_id = (int)$match[1];

            $stmtInsert = $pdo->prepare("
                INSERT INTO pagos (cliente_id, referencia, monto, estado, metodo_pago, raw_response)
                VALUES (:cid, :ref, :monto, :estado, 'mercadopago', :raw)
            ");
            $stmtInsert->execute([
                ':cid' => $cliente_id,
                ':ref' => $ref,
                ':monto' => $montoPago,
                ':estado' => $estadoPago,
                ':raw' => json_encode($payment, JSON_UNESCAPED_UNICODE)
            ]);
        }

        // === Procesar solo pagos aprobados ===
        if ($estadoPago === 'approved' && preg_match('/pago_(\d+)_/', $ref, $match)) {
            $cliente_id = (int)$match[1];

            // === Obtener datos del cliente y su plan ===
            $stmtPlan = $pdo->prepare("
                SELECT c.*, p.precio, p.dias, p.frecuencia
                FROM clientes c
                INNER JOIN planes p ON p.id = c.plan
                WHERE c.id = :id LIMIT 1
            ");
            $stmtPlan->execute([':id' => $cliente_id]);
            $cliente = $stmtPlan->fetch(PDO::FETCH_ASSOC);

            if ($cliente) {
                date_default_timezone_set('America/Bogota');
                $hoy = new DateTime('today');
                $vencAnterior = !empty($cliente['vencimiento_plan']) ? new DateTime($cliente['vencimiento_plan']) : null;
                $planDias = (int)$cliente['dias'];
                $planMeses = (int)$cliente['frecuencia'];

                $calcFechaFin = function (DateTime $inicio, int $meses, int $dias): DateTime {
                    $fin = clone $inicio;
                    if ($meses > 0) {
                        $fin->modify("+{$meses} months");
                        $fin->modify('-1 day');
                    }
                    if ($dias > 0) {
                        $fin->modify('+' . $dias . ' days');
                    }
                    return $fin;
                };

                $estaVencido = (!$vencAnterior) || ($vencAnterior <= $hoy);
                if ($estaVencido) {
                    $inicio = clone $hoy;
                } else {
                    $inicio = clone $vencAnterior;
                    $inicio->modify('+1 day');
                }

                $nuevoVenc = $calcFechaFin($inicio, $planMeses, $planDias);

                // === Actualizar cliente ===
                $stmtUpdate = $pdo->prepare("
                    UPDATE clientes 
                    SET pago_plan = :pago, vencimiento_plan = :venc, estado = 'activo', congelado = 0, updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    ':pago' => $hoy->format('Y-m-d'),
                    ':venc' => $nuevoVenc->format('Y-m-d'),
                    ':id' => $cliente_id
                ]);

                // === Registrar factura (usando tu archivo existente) ===
                $id = $cliente_id;
                $pago_plan = $hoy->format('Y-m-d');
                $vencimiento_plan = $nuevoVenc->format('Y-m-d');
                $payment_method = 'mercadopago';
                $bank = 'Mercado Pago';
                $credit = 0;
                $valorPagado = $montoPago;

                ob_start();
                include(__DIR__ . '/../admin/clients/generate_factura.php');
                ob_end_clean();

                // === Notificar al cliente (si aplica) ===
                if ((int)$cliente['notificaciones'] === 1) {
                    $cp_nombres          = $cliente['nombres'];
                    $cp_apellidos        = $cliente['apellidos'] ?? '';
                    $cp_dialCode         = $cliente['dialCode'];
                    $cp_telefono         = $cliente['telefono'];
                    $cp_pago_plan        = $pago_plan;
                    $cp_vencimiento_plan = $vencimiento_plan;
                    $facturaId           = null;

                    ob_start();
                    include(__DIR__ . '/../whatsapp/client-pay.php');
                    $clientPayResponse = ob_get_clean();

                    file_put_contents(__DIR__ . '/whatsapp_log.txt',
                        date('Y-m-d H:i:s') . " | WS Pago cliente $cliente_id | " . $clientPayResponse . "\n",
                        FILE_APPEND
                    );
                }

                file_put_contents(__DIR__ . '/webhook_log.txt',
                    date('Y-m-d H:i:s') . " ✅ Pago aprobado procesado cliente ID: $cliente_id\n",
                    FILE_APPEND
                );
            }
        } else {
            // Registrar en log los pagos no aprobados (pendientes o rechazados)
            file_put_contents(__DIR__ . '/webhook_log.txt',
                date('Y-m-d H:i:s') . " ⏳ Pago $estadoPago recibido (sin procesar factura): Ref $ref\n",
                FILE_APPEND
            );
        }

    } catch (Exception $ex) {
        file_put_contents(__DIR__ . '/webhook_log.txt',
            date('Y-m-d H:i:s') . " [PROCESS ERROR] " . $ex->getMessage() . "\n",
            FILE_APPEND
        );
    }
}

http_response_code(200);


