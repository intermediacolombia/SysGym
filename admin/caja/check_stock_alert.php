<?php
/**
 * Función para verificar y enviar alertas de stock bajo
 * 
 * @param PDO $pdo - Conexión a la base de datos
 * @param int $producto_id - ID del producto a verificar
 * @param int $nuevo_stock - Nuevo stock después de la venta
 * @param string $api_ws - Token de la API de WhatsApp
 * @return void
 */
// test_alert.php
require_once __DIR__ . '/../../inc/config.php';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Simular producto con stock crítico
check_stock_alert($pdo, 10, 60, $api_ws);


function check_stock_alert($pdo, $producto_id, $nuevo_stock, $api_ws) {
    try {
        // Verificar si el producto tiene alerta activada
        $stmtAlert = $pdo->prepare("
            SELECT *
            FROM productos 
            WHERE id = :id AND borrado = 0
        ");
        $stmtAlert->execute([':id' => $producto_id]);
        $productoAlert = $stmtAlert->fetch(PDO::FETCH_ASSOC);

        // Si no hay alerta configurada o no se alcanzó el mínimo, salir
        if (
            !$productoAlert ||
            (int)$productoAlert['alerta_stock'] !== 1 ||
            $nuevo_stock > (int)$productoAlert['minimo_stock']
        ) {
            return;
        }

        $nombre_producto = $productoAlert['nombre'];
        $minimo = (int)$productoAlert['minimo_stock'];

        // Obtener usuarios que deben recibir alerta
        $stmtUsuarios = $pdo->query("
            SELECT dialCode, telefono 
            FROM usuarios 
            WHERE recibir_alerta_stock = 1 
              AND telefono IS NOT NULL 
              AND telefono != ''
        ");
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

        // Si no hay usuarios para notificar, salir
        if (empty($usuarios)) {
            return;
        }

        // Preparar mensaje
        $mensaje = "⚠️ ALERTA DE STOCK: El producto '$nombre_producto' ha llegado al stock mínimo. Stock actual: $nuevo_stock unidades (Mínimo: $minimo).";

        // Enviar mensaje a cada usuario
        foreach ($usuarios as $u) {
            $telefonoCompleto = $u['dialCode'] . $u['telefono'];

            // Enviar vía API 360Messenger
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.360messenger.com/v2/sendMessage',
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
            curl_close($ch);
            
            // Log opcional para debugging (descomentar si necesitas revisar)
            // error_log("WhatsApp Alert - Producto: $nombre_producto, Usuario: $telefonoCompleto, HTTP: $httpCode");
        }
        
    } catch (Exception $e) {
        // Log del error pero no interrumpir el flujo
        error_log("Error en check_stock_alert: " . $e->getMessage());
    }
}
?>