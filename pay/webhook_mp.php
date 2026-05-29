<?php
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

/* ==========================================================
   REGISTRO INICIAL DEL EVENTO WEBHOOK
========================================================== */
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " " . json_encode($data) . "\n", FILE_APPEND);

try {
    
	
	
	
	
    /* ==========================================================
       VERIFICAR TABLA PAGOS (auto-creación si no existe)
    =========================================================== */
    db()->exec("
        CREATE TABLE IF NOT EXISTS pagos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            referencia VARCHAR(100) NOT NULL,
            monto DECIMAL(10,2) NOT NULL DEFAULT 0,
            estado ENUM('iniciado','pending','approved','rejected','unknown') DEFAULT 'pending',
            metodo_pago VARCHAR(50) DEFAULT 'mercadopago',
            fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
            raw_response JSON NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_referencia (referencia),
            INDEX idx_cliente (cliente_id),
            CONSTRAINT fk_pagos_clientes FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/webhook_log.txt',
        date('Y-m-d H:i:s') . " [DB ERROR] " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    http_response_code(500);
    exit;
}

/* ==========================================================
   PROCESAR NOTIFICACIÓN DE MERCADO PAGO
========================================================== */
if (!empty($data['type']) && $data['type'] === 'payment' && !empty($data['data']['id'])) {
    try {
        // Configurar credenciales (TOKEN DE LA MISMA CUENTA QUE crea la preferencia)
        MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

        $payment_id = $data['data']['id'];
        $client = new PaymentClient();
        $payment = $client->get($payment_id);

        $ref        = $payment->external_reference ?? '';
        $estadoPago = $payment->status ?? 'unknown';
        $montoPago  = $payment->transaction_amount ?? 0;

        /* ==========================================================
           ACTUALIZAR O INSERTAR REGISTRO EN PAGOS (UPsert)
        =========================================================== */
        if (preg_match('/pago_(\d+)_/', $ref, $match)) {
            $cliente_id = (int)$match[1];

            // ¿Existe ya el pago con esa referencia?
            $stmtCheck = db()->prepare("SELECT id FROM pagos WHERE referencia = :ref LIMIT 1");
            $stmtCheck->execute([':ref' => $ref]);
            $existe = $stmtCheck->fetchColumn();

            if ($existe) {
                // 🔄 Actualizar registro existente
                $stmtUpdatePago = db()->prepare("
    UPDATE pagos 
    SET estado = :estado, monto = :monto, raw_response = :raw, fecha_pago = :fecha
    WHERE referencia = :ref
");
$stmtUpdatePago->execute([
    ':estado' => $estadoPago,
    ':monto'  => $montoPago,
    ':raw'    => json_encode($payment, JSON_UNESCAPED_UNICODE),
    ':fecha'  => date('Y-m-d H:i:s'),
    ':ref'    => $ref
]);

                file_put_contents(__DIR__ . '/webhook_log.txt',
                    date('Y-m-d H:i:s') . " 🔄 Pago actualizado | Ref=$ref | Estado=$estadoPago | Monto=$montoPago\n",
                    FILE_APPEND
                );
            } else {
                // 🆕 Insertar nuevo registro
                $stmtInsert = db()->prepare("
					INSERT INTO pagos (cliente_id, referencia, monto, estado, metodo_pago, raw_response, fecha_pago)
					VALUES (:cid, :ref, :monto, :estado, 'mercadopago', :raw, :fecha)
				");
				$stmtInsert->execute([
					':cid'   => $cliente_id,
					':ref'   => $ref,
					':monto' => $montoPago,
					':estado'=> $estadoPago,
					':raw'   => json_encode($payment, JSON_UNESCAPED_UNICODE),
					':fecha' => date('Y-m-d H:i:s')
				]);

                file_put_contents(__DIR__ . '/webhook_log.txt',
                    date('Y-m-d H:i:s') . " 🆕 Pago insertado | Ref=$ref | Estado=$estadoPago | Monto=$montoPago\n",
                    FILE_APPEND
                );
            }
        }

        /* ==========================================================
           PROCESAR SOLO PAGOS APROBADOS
        =========================================================== */
        if ($estadoPago === 'approved' && preg_match('/pago_(\d+)_/', $ref, $match)) {
            $cliente_id = (int)$match[1];

            // === Obtener datos del cliente ===
            $stmtCliente = db()->prepare("SELECT * FROM clientes WHERE id = :id AND borrado = 0 LIMIT 1");
            $stmtCliente->execute([':id' => $cliente_id]);
            $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

            if ($cliente) {
                $esTiquetera = ($cliente['plan_tipo'] ?? 'plan') === 'tiquetera';

                // === Obtener datos del plan o tiquetera ===
                if ($esTiquetera) {
                    $stmtPlanData = db()->prepare("SELECT precio, vigencia AS dias, 0 AS frecuencia FROM tiqueteras WHERE id = :id AND borrado = 0");
                } else {
                    $stmtPlanData = db()->prepare("SELECT precio, dias, frecuencia FROM planes WHERE id = :id AND borrado = 0");
                }
                $stmtPlanData->execute([':id' => $cliente['plan']]);
                $planData = $stmtPlanData->fetch(PDO::FETCH_ASSOC);

                if (!$planData) {
                    file_put_contents(__DIR__ . '/webhook_log.txt',
                        date('Y-m-d H:i:s') . " [ERROR] Plan no encontrado para cliente $cliente_id\n", FILE_APPEND);
                    http_response_code(200); exit;
                }

                $hoy      = new DateTime('today');
                $planDias = (int)$planData['dias'];
                $planMeses = (int)$planData['frecuencia'];

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

                if ($esTiquetera) {
                    // Tiqueteras: siempre arrancan hoy
                    $inicio = clone $hoy;
                } else {
                    $vencAnterior = !empty($cliente['vencimiento_plan']) ? new DateTime($cliente['vencimiento_plan']) : null;
                    $estaVencido  = (!$vencAnterior) || ($vencAnterior <= $hoy);
                    $inicio       = $estaVencido ? clone $hoy : (clone $vencAnterior)->modify('+1 day');
                }

                $nuevoVenc = $calcFechaFin($inicio, $planMeses, $planDias);

                // === Actualizar cliente ===
                $stmtUpdate = db()->prepare("
                    UPDATE clientes
                    SET pago_plan = :pago, vencimiento_plan = :venc, estado = 'activo', congelado = 0, updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    ':pago' => $hoy->format('Y-m-d'),
                    ':venc' => $nuevoVenc->format('Y-m-d'),
                    ':id'   => $cliente_id
                ]);

                // === Registrar factura ===
                $id                  = $cliente_id;
                $pago_plan           = $hoy->format('Y-m-d');
                $vencimiento_plan    = $nuevoVenc->format('Y-m-d');
                $payment_method      = 'Pago en Linea';
                $bank                = 'Mercado Pago';
                $credit              = 0;
                $porcentajeAdicional = (float) ADDITIONAL_PERCENTAGE_PAYMENT;
                $valorPagado         = $montoPago;
                $nombre              = 'Pasarela Pago';

                ob_start();
                $facturaGenerada = include(__DIR__ . '/../admin/clients/generate_factura_pasarela.php');
                ob_end_clean();

                // === Si es tiquetera, vincular factura activa para conteo de entradas ===
                if ($esTiquetera && $facturaGenerada) {
                    $stmtTiqFact = db()->prepare("UPDATE clientes SET tiquetera_factura_id = :fid WHERE id = :id");
                    $stmtTiqFact->execute([':fid' => $facturaGenerada, ':id' => $cliente_id]);
                }

                // === Notificar por WhatsApp (si aplica) ===
                if ((int)$cliente['notificaciones'] === 1) {
                    $cp_nombres          = $cliente['nombres'];
                    $cp_apellidos        = $cliente['apellidos'] ?? '';
                    $cp_dialCode         = $cliente['dialCode'];
                    $cp_telefono         = $cliente['telefono'];
                    $cp_pago_plan        = $pago_plan;
                    $cp_vencimiento_plan = $vencimiento_plan;
                    //$facturaId           = null;

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
            // Registrar pagos no aprobados (pendientes / rechazados / unknown)
            file_put_contents(__DIR__ . '/webhook_log.txt',
                date('Y-m-d H:i:s') . " ⏳ Pago $estadoPago recibido (sin procesar factura): Ref $ref\n",
                FILE_APPEND
            );
        }

    } catch (Exception $ex) {
        $errorDetails = method_exists($ex, 'getApiResponse')
            ? json_encode($ex->getApiResponse(), JSON_UNESCAPED_UNICODE)
            : $ex->getMessage();

        file_put_contents(__DIR__ . '/webhook_log.txt',
            date('Y-m-d H:i:s') . " [PROCESS ERROR DETAILS] " . $errorDetails . "\n",
            FILE_APPEND
        );
    }
}

http_response_code(200);



