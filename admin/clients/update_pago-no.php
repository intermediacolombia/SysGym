<?php 
require_once __DIR__ . '/../login/session.php';
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
    
    // Recibir datos de crédito (si se envían)
    $credit = (isset($_POST['credit']) && $_POST['credit'] === 'true') ? 1 : 0;
    $valorPagado = isset($_POST['valorPagado']) ? trim($_POST['valorPagado']) : null;
    $fechaPlazo = isset($_POST['fechaPlazo']) ? trim($_POST['fechaPlazo']) : null;
    
    // <-- NUEVA LÍNEA PARA CAPTURAR EL MÉTODO DE PAGO -->
    $payment_method = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';

    
    // ... (resto de tu código para actualizar el cliente, comparar fechas, etc.)

    // Definir la variable $hoy para la fecha actual
    $hoy = date('Y-m-d');
    
    // Incluir generate_factura.php (el cual ya debe incluir en su INSERT la columna payment_method)
    include('generate_factura.php');
    
    // ... (resto del código y respuesta JSON)
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}
?>





