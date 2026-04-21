<?php
require_once __DIR__ . '/../../inc/config.php';

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

    $usuarios = db()->prepare("
        SELECT id, nombre, apellido, dialcode, telefono
        FROM usuarios
        WHERE borrado = 0
          AND estado = 1
          AND recibe_alertas_stock = 1
    ");
    $usuarios->execute();
    $users = $usuarios->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "No hay usuarios con alertas de stock activadas.\n";
        exit;
    }

    $apiKey = $api_ws;
    $urlEndpoint = rtrim(WA_API_URL, '/') . '/send';

    $mensajeBase = "*ALERTA DE VENCIMIENTO DE PRODUCTO*\n\n";
    $mensajeBase .= "El siguiente producto esta proximo a vencer:\n\n";

    foreach ($products as $product) {
        $dias = $product['dias_restantes'];
        $msg = $mensajeBase;
        $msg .= "▸ *Producto:* {$product['nombre']}\n";
        $msg .= "▸ *Fecha de vencimiento:* {$product['fecha_vencimiento']}\n";
        $msg .= "▸ *Dias restantes:* {$dias} dia(s)\n";
        $msg .= "▸ *Stock actual:* {$product['stock']} unidades\n\n";
        $msg .= "Por favor revisar el inventario.";

        foreach ($users as $user) {
            $telefono = $user['dialCode'] . $user['telefono'];

            $data = array(
                'phonenumber' => $telefono,
                'text' => $msg
            );

            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => $urlEndpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                )
            ));

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
                require_once __DIR__ . '/../../whatsapp/save_failed_ws.php';
                saveFailedWSMessage($telefono, $msg, null);
            }

            echo "Producto: {$product['nombre']} -> Usuario: {$user['nombre']} {$user['apellido']} - HTTP: $httpCode\n";
        }

        $checkExists = db()->prepare("
            SELECT id FROM alertas_productos_vencimiento
            WHERE producto_id = :pid AND DATE(fecha_alerta) = CURDATE()
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
    echo "Error: " . $e->getMessage() . "\n";
}
?>
