<?php
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../whatsapp/save_failed_ws.php';

date_default_timezone_set('America/Bogota');

try {
    $stmt = db()->prepare("
        SELECT p.id, p.nombre, p.fecha_vencimiento, p.stock,
               DATEDIFF(p.fecha_vencimiento, CURDATE()) as dias_restantes
        FROM productos p
        WHERE p.borrado = 0
          AND p.tiene_fecha_vencimiento = 1
          AND p.fecha_vencimiento IS NOT NULL
          AND DATEDIFF(p.fecha_vencimiento, CURDATE()) <= 30
          AND DATEDIFF(p.fecha_vencimiento, CURDATE()) >= 0
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) {
        echo "No hay productos por vencer en los proximos 30 dias.\n";
        exit;
    }

    $usuarios = db()->query("
        SELECT nombre, apellido, dialCode, telefono
        FROM usuarios 
        WHERE recibe_alertas_stock = 1 
          AND telefono IS NOT NULL 
          AND telefono != ''
    ");
    $users = $usuarios->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "No hay usuarios con alertas de stock activadas.\n";
        exit;
    }

    $apiKey = $api_ws;
    $urlEndpoint = rtrim(WA_API_URL, '/') . '/send';

    foreach ($products as $product) {
        $dias = $product['dias_restantes'];
        $nombre_producto = $product['nombre'];
        $fecha_vencimiento = $product['fecha_vencimiento'];
        $stock_actual = $product['stock'];

        $mensaje = "⚠️ *ALERTA DE VENCIMIENTO DE PRODUCTO*\n\n"
                 . "El producto *$nombre_producto* esta proximo a vencer.\n"
                 . "📅 *Fecha de vencimiento:* $fecha_vencimiento\n"
                 . "⏰ *Dias restantes:* $dias dia(s)\n"
                 . "📦 *Stock actual:* $stock_actual unidades\n\n"
                 . "Por favor revisar el inventario.";

        foreach ($users as $u) {
            $telefonoCompleto = $u['dialCode'] . $u['telefono'];
            $nombreUsuario = $u['nombre'];

            error_log("📢 Enviando alerta de vencimiento a $telefonoCompleto → $mensaje");

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
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
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
            echo "Producto: $nombre_producto -> Usuario: $nombreUsuario - HTTP: $httpCode\n";
        }

        $checkExists = db()->prepare("
            SELECT id FROM alertas_productos_vencimiento
            WHERE producto_id = :pid
        ");
        $checkExists->execute([':pid' => $product['id']]);
        if (!$checkExists->fetch()) {
            $insertAlert = db()->prepare("
                INSERT INTO alertas_productos_vencimiento (producto_id, dias_restantes, fecha_alerta, enviada)
                VALUES (:pid, :dias, NOW(), 1)
            ");
            $insertAlert->execute([
                ':pid' => $product['id'],
                ':dias' => $dias
            ]);
        }
    }

    echo "Proceso completado.\n";

} catch (PDOException $e) {
    error_log("❌ Error en productExpirationAlert: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>