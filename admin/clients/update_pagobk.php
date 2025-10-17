<?php 
require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Verificar que se envíen los datos mínimos requeridos
    if(!isset($_POST['id']) || !isset($_POST['pago_plan']) || !isset($_POST['vencimiento_plan'])){
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos.']);
        exit;
    }
    
    $id = trim($_POST['id']);
    $newPagoPlan = trim($_POST['pago_plan']);           // Fecha de pago enviada
    $newVencimientoPlan = trim($_POST['vencimiento_plan']); // Fecha de vencimiento enviada
    
    // Recibir los datos de crédito (si se envían)
    // Se espera que 'credit' se envíe como "true" si se seleccionó crédito
    $credit = (isset($_POST['credit']) && $_POST['credit'] === 'true') ? 1 : 0;
    $valorPagado = isset($_POST['valorPagado']) ? trim($_POST['valorPagado']) : null;
    $fechaPlazo = isset($_POST['fechaPlazo']) ? trim($_POST['fechaPlazo']) : null;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Primero, obtener las fechas actuales almacenadas para el cliente
        $stmtCheck = $pdo->prepare("SELECT pago_plan, vencimiento_plan FROM clientes WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $clientDates = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        // Comparar las fechas nuevas con las almacenadas:
        // Si la nueva fecha es menor o igual que la almacenada, se conserva la almacenada.
        if (!empty($clientDates['pago_plan']) && strtotime($newPagoPlan) <= strtotime($clientDates['pago_plan'])) {
            $pago_plan = $clientDates['pago_plan'];
        } else {
            $pago_plan = $newPagoPlan;
        }
        
        if (!empty($clientDates['vencimiento_plan']) && strtotime($newVencimientoPlan) <= strtotime($clientDates['vencimiento_plan'])) {
            $vencimiento_plan = $clientDates['vencimiento_plan'];
        } else {
            $vencimiento_plan = $newVencimientoPlan;
        }
        
        // Actualizar las fechas en la tabla clientes (solo se actualizarán si las nuevas son mayores)
        $stmt = $pdo->prepare("UPDATE clientes SET 
            pago_plan = :pago_plan, 
            vencimiento_plan = :vencimiento_plan, 
            estado = 'activo', 
            updated_at = NOW() 
            WHERE id = :id");
        $stmt->execute([
            ':pago_plan' => $pago_plan,
            ':vencimiento_plan' => $vencimiento_plan,
            ':id' => $id
        ]);
        
        // Obtener datos del cliente para pasar a generate_factura.php y client-pay.php
        $stmtSelect = $pdo->prepare("SELECT nombres, dialCode, telefono, notificaciones FROM clientes WHERE id = :id");
        $stmtSelect->execute([':id' => $id]);
        $clientData = $stmtSelect->fetch(PDO::FETCH_ASSOC);
        
        $nombres = $clientData['nombres'];
        $dialCode = $clientData['dialCode'];
        $telefono = $clientData['telefono'];
        $notificaciones = $clientData['notificaciones'];
        
        // Variables para que client-pay.php y generate_factura.php las reciban
        $cp_nombres = $nombres;
        $cp_dialCode = $dialCode;
        $cp_telefono = $telefono;
        $cp_pago_plan = $pago_plan;
        $cp_vencimiento_plan = $vencimiento_plan;
        
        // Definir la variable $hoy para la fecha actual
        $hoy = date('Y-m-d');
		
		$payment_method = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';
		$bank = isset($_POST['bank']) ? trim($_POST['bank']) : '';
//$bank_selection = $_POST['bank_selection'];


        
        // Incluir generate_factura.php, el cual maneja:
        // - Si NO se seleccionó crédito: insertar en facturas el valor completo del plan.
        // - Si se seleccionó crédito: insertar en facturas el valor pagado y en creditos el saldo restante.
		
        include('generate_factura.php');
		include('../caja/register_generic_sale.php');
		
        
        if ($notificaciones == 1 || $notificaciones == 0) {        
            // Incluir client-pay.php (capturando su salida si es necesario)
            ob_start();
            include('../../whatsapp/client-pay.php');
            $clientPayResponse = ob_get_clean();        
        }
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Pago registrado correctamente. Se generó la factura y, si aplica, se creó el crédito.',
            'nombres' => $nombres,
            'dialCode' => $dialCode,
            'telefono' => $telefono,
            'pago_plan' => $pago_plan,
            'vencimiento_plan' => $vencimiento_plan,
            'client_pay_response' => isset($clientPayResponse) ? $clientPayResponse : ''
        ]);
        exit;
		
		
		
		
		// Consultar nuevamente el cliente para obtener las fechas actualizadas
$stmtUpdated = $pdo->prepare("SELECT pago_plan, vencimiento_plan FROM clientes WHERE id = :id");
$stmtUpdated->execute([':id' => $id]);
$updatedData = $stmtUpdated->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success', 
    'message' => 'Pago registrado correctamente. Se generó la factura y, si aplica, se creó el crédito.',
    'pago_plan' => $updatedData['pago_plan'],
    'vencimiento_plan' => $updatedData['vencimiento_plan'],
    // Puedes incluir otros datos si es necesario
]);
exit;
		
		
		
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la actualización: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}
?>




