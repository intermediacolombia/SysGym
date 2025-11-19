<?php
// send_invoice.php

require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = $_POST['invoice_id'];
    
    try {               
        // Consulta para obtener los datos de la factura y el cliente
        $stmt = db()->prepare("
            SELECT 
                c.nombres AS cp_nombres,
                c.apellidos AS cp_apellidos,
                c.dialCode AS cp_dialCode,
                c.telefono AS cp_telefono, 
                f.fecha_inicio AS cp_pago_plan,
                f.fecha_vencimiento AS cp_vencimiento_plan,
                f.id AS facturaId
            FROM facturas f
            JOIN clientes c ON f.idCliente = c.id
            WHERE f.id = :invoice_id AND c.borrado = 0
        ");

        // Ejecutar la consulta con el parámetro
        $stmt->execute([':invoice_id' => $invoice_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Asignar los valores a las variables
            $cp_nombres = $result['cp_nombres'];
            $cp_apellidos = $result['cp_apellidos'];
            $cp_dialCode = $result['cp_dialCode'];
            $cp_telefono = $result['cp_telefono']; // El teléfono se deja como está
            $cp_pago_plan = $result['cp_pago_plan'];
            $cp_vencimiento_plan = $result['cp_vencimiento_plan'];
            $facturaId = $result['facturaId'];

            // Incluir el archivo que envía el mensaje por WhatsApp
            ob_start(); // Iniciar el buffer de salida para capturar cualquier salida de client-pay.php
            include('../../whatsapp/client-pay.php');
            ob_end_clean(); // Limpiar el buffer de salida (para evitar que interfiera con la respuesta JSON)
			
			// LOGS
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
					'nombres' => $cp_nombres,
                    'apellidos' => $cp_apellidos,
                    'dialCode' => $cp_dialCode,
                    'telefono' => $cp_telefono, // Teléfono sin modificar
                    'fecha_inicio' => $cp_pago_plan,
                    'fecha_vencimiento' => $cp_vencimiento_plan,
                    'id_factura' => $facturaId,
					'accion' => 'Reenviar Factura'
			
], JSON_UNESCAPED_UNICODE);

log_action('Reenviar Factura', $desc, 'Clientes');
// END LOGS

            // Devolver los datos en formato JSON con un mensaje de éxito
            echo json_encode([
                'status' => 'success',
                'message' => 'Factura enviada correctamente al cliente.',
                'data' => [
                    'nombres' => $cp_nombres,
                    'apellidos' => $cp_apellidos,
                    'dialCode' => $cp_dialCode,
                    'telefono' => $cp_telefono, // Teléfono sin modificar
                    'fecha_inicio' => $cp_pago_plan,
                    'fecha_vencimiento' => $cp_vencimiento_plan,
                    'id_factura' => $facturaId
                ]
            ]);
        } else {
            // Si no se encuentra la factura o el cliente
            echo json_encode([
                'status' => 'error',
                'message' => 'Factura o cliente no encontrado.'
            ]);
        }
    } catch (PDOException $e) {
        // Manejo de errores de la base de datos
        echo json_encode([
            'status' => 'error',
            'message' => 'Error en la base de datos: ' . $e->getMessage()
        ]);
    }
}
?>