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
function check_stock_alert($pdo, $producto_id, $nuevo_stock, $api_ws) {
    try {
        // Verificar si el producto tiene alerta activada
        $stmtAlert = $pdo->prepare("
            SELECT nombre, alerta_stock, minimo_stock 
            FROM productos 
            WHERE id = :id AND borrado = 0
        ");
        $stmtAlert->execute([':id' => $producto_id]);
        $productoAlert = $stmtAlert->fetch(PDO::FETCH_ASSOC);

        // 🚫 Validaciones: salir si no aplica alerta
        if (
            !$productoAlert ||                                      // no existe el producto
            (int)$productoAlert['alerta_stock'] !== 1 ||             // alerta desactivada
            $nuevo_stock != (int)$productoAlert['minimo_stock']      // aún no llega justo al mínimo
        ) {
            return; // no enviar nada
        }

		

		
        $nombre_producto = $productoAlert['nombre'];
        $minimo = (int)$productoAlert['minimo_stock'];

        // Obtener usuarios que deben recibir alerta
        $stmtUsuarios = $pdo->query("
            SELECT nombre, apellido, dialCode, telefono
            FROM usuarios 
            WHERE recibe_alertas_stock = 1 
              AND telefono IS NOT NULL 
              AND telefono != ''
        ");
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
		
        // Si no hay usuarios para notificar, salir
        if (empty($usuarios)) {
            return;
        }

        

        // Enviar mensaje a cada usuario
        foreach ($usuarios as $u) {
            $telefonoCompleto = $u['dialCode'] . $u['telefono'];
			$nombreUsuario = $u['nombres'];
			
			// Preparar mensaje
        	$mensaje = "Hola $nombreUsuario,\n\n"
			     . "⚠️ ALERTA DE STOCK: El producto *$nombre_producto* ha llegado al stock mínimo establecido en el sistema.\n"
                 . "Stock actual: *$nuevo_stock* unidades.";
			
			
            // Log previo (útil para pruebas)
            error_log("📢 Enviando alerta de stock a $telefonoCompleto → $mensaje");

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
            $error    = curl_error($ch);
            curl_close($ch);

            // Registrar en log para debugging
            error_log("✅ HTTP: $httpCode | 📞 $telefonoCompleto | RESPUESTA: $response | ERROR: $error");
        }
        
    } catch (Exception $e) {
        error_log("❌ Error en check_stock_alert: " . $e->getMessage());
    }
}

?>