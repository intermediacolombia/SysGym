<?php
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../whatsapp/save_failed_ws.php';

/**
 * Verifica y envía alertas de stock bajo para elementos del Almacén
 *
 * @param int $elemento_id - ID del elemento del almacén
 * @param float $stock_anterior - Stock antes del movimiento
 * @param float $nuevo_stock - Stock después del movimiento
 * @param string $api_ws - Token de la API de WhatsApp
 * @return void
 */
function check_almacen_stock_alert($elemento_id, $stock_anterior, $nuevo_stock, $api_ws) {
    try {
        $stmtAlert = db()->prepare("
            SELECT nombre, alerta_stock, stock_minimo
            FROM almacen_elementos
            WHERE id = :id AND borrado = 0
        ");
        $stmtAlert->execute([':id' => $elemento_id]);
        $elementoAlert = $stmtAlert->fetch(PDO::FETCH_ASSOC);

        if (
            !$elementoAlert ||
            (int)$elementoAlert['alerta_stock'] !== 1 ||
            $elementoAlert['stock_minimo'] === null
        ) {
            return;
        }

        $minimo = (float)$elementoAlert['stock_minimo'];

        // Solo enviar si estaba por encima y ahora está igual o por debajo del mínimo
        if (!($stock_anterior > $minimo && $nuevo_stock <= $minimo)) {
            return;
        }

        $nombre_elemento = $elementoAlert['nombre'];

        $stmtUsuarios = db()->query("
            SELECT nombre, apellido, dialCode, telefono
            FROM usuarios
            WHERE recibe_alertas_stock = 1
              AND telefono IS NOT NULL
              AND telefono != ''
        ");
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

        if (empty($usuarios)) {
            return;
        }

        foreach ($usuarios as $u) {
            $telefonoCompleto = $u['dialCode'] . $u['telefono'];
            $nombreUsuario = $u['nombre'];

            $mensaje = "Hola *$nombreUsuario*,\n"
                     . "⚠️ ALERTA DE STOCK ALMACÉN: El elemento *$nombre_elemento* ha llegado al stock mínimo establecido.\n"
                     . "Stock actual: *$nuevo_stock* (Mínimo: $minimo).";

            error_log("📢 Enviando alerta de stock de almacén a $telefonoCompleto → $mensaje");

            $urlEndpoint = rtrim(WA_API_URL, '/') . '/send';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $urlEndpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'phonenumber' => $telefonoCompleto,
                    'text' => $mensaje
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $api_ws,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            $successFlag = false;
            if (!$error && $httpCode >= 200 && $httpCode < 300) {
                $decoded = json_decode($response, true);
                $successFlag = !empty($decoded['success']);
            }

            if (!$successFlag) {
                saveFailedWSMessage($telefonoCompleto, $mensaje, null);
            }

            error_log("✅ HTTP: $httpCode | 📞 $telefonoCompleto | RESPUESTA: $response | ERROR: $error");
        }
    } catch (Exception $e) {
        error_log("❌ Error en check_almacen_stock_alert: " . $e->getMessage());
    }
}
