<?php
// resend_consent.php

require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Usamos GET para pruebas
    $clientId = $_POST['client_id']; // Obtener el ID del cliente desde la URL
    
    try {      
        // Obtener datos del cliente
        $stmtClient = db()->prepare("
            SELECT 
                nombres,
                apellidos,
                dialCode,
                telefono
            FROM clientes
            WHERE id = :client_id AND borrado = 0
        ");
        $stmtClient->execute([':client_id' => $clientId]);
        $cliente = $stmtClient->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cliente no encontrado.'
            ]);
            exit;
        }

        // Obtener el ID del formulario más reciente del cliente
        $stmtForm = db()->prepare("
            SELECT id 
            FROM formularios 
            WHERE cliente_id = :client_id 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmtForm->execute([':client_id' => $clientId]);
        $formulario = $stmtForm->fetch(PDO::FETCH_ASSOC);

        if (!$formulario) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No hay formulario asociado al cliente.'
            ]);
            exit;
        }

        // Preparar los datos para el script de WhatsApp
        $data = [
            'nombres' => $cliente['nombres'],
            'apellidos' => $cliente['apellidos'],
            'dialCode' => $cliente['dialCode'],
            'telefono' => $cliente['telefono']
        ];

        // Asignar el ID del formulario a una variable global
        $formulario_id = $formulario['id'];

        // Incluir el script que envía el mensaje por WhatsApp
        ob_start(); // Capturar cualquier salida adicional
        include('../../whatsapp/consent.php'); // Asegúrate de que este archivo use $formulario_id
        ob_end_clean(); // Limpiar el buffer de salida
		
		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
			'nombres' => $cliente['nombres'],
            'apellidos' => $cliente['apellidos'],
            'dialCode' => $cliente['dialCode'],
            'telefono' => $cliente['telefono'],
			'accion' => 'Reenviar Consentimiento'
			
], JSON_UNESCAPED_UNICODE);

log_action('Reenviar Consentimiento', $desc, 'Clientes');
// END LOGS

        // Devolver respuesta de éxito
        echo json_encode([
            'status' => 'success',
            'message' => 'Consentimiento reenviado correctamente.'
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error en la base de datos: ' . $e->getMessage()
        ]);
    }
} else {
    // Si el método no es GET, devolver un error
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido. Usa POST.'
    ]);
}
?>