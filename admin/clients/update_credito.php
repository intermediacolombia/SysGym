<?php
require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($_POST['credit_id']) || empty($_POST['credit_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Falta el ID del crédito.']);
    exit;
}

// Recibir datos
$credit_id = trim($_POST['credit_id']);
$pago = isset($_POST['pago']) ? floatval(trim($_POST['pago'])) : 0; // Monto abonado
$fecha_limite = isset($_POST['fecha_limite']) ? trim($_POST['fecha_limite']) : null;
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
$fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : null; // Opcional

// Recibir método de pago y banco
$payment_method = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';
$bank = isset($_POST['bank']) ? trim($_POST['bank']) : '';

// Conexión a la BD
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// 1. Obtener el crédito actual y datos relacionados
$stmt = $pdo->prepare("SELECT valor, idCliente, factura_id, fecha, descripcion FROM creditos WHERE id = :credit_id");
$stmt->execute([':credit_id' => $credit_id]);
$credit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$credit) {
    echo json_encode(['status' => 'error', 'message' => 'Crédito no encontrado.']);
    exit;
}

$current_valor = floatval($credit['valor']);

// 2. Calcular el nuevo saldo sin quedar negativo
$new_valor = $current_valor - $pago;
if ($new_valor < 0) {
    $new_valor = 0;
}

// 3. Marcar el crédito original como pagado (estado = 1)
$stmtUpdate = $pdo->prepare("UPDATE creditos SET estado = 1 WHERE id = :credit_id");
$stmtUpdate->execute([':credit_id' => $credit_id]);

$new_credit_id = null;
// 4. Si queda saldo, crear un nuevo crédito con ese saldo restante
if ($new_valor > 0) {
    $stmtInsert = $pdo->prepare("INSERT INTO creditos 
        (idCliente, factura_id, fecha, valor, fecha_limite, estado, descripcion)
        VALUES (:idCliente, :factura_id, :fecha, :valor, :fecha_limite, 0, :descripcion)");
    $stmtInsert->execute([
        ':idCliente'    => $credit['idCliente'],
        ':factura_id'   => $credit['factura_id'], // Se conserva la misma factura_id según la lógica
        ':fecha'        => date('Y-m-d'),
        ':valor'        => $new_valor,
        ':fecha_limite' => $fecha_limite,
        ':descripcion'  => $descripcion ? $descripcion : $credit['descripcion']
    ]);
    $new_credit_id = $pdo->lastInsertId();
}

// 5. Obtener la información del cliente para generar la factura
$stmtClient = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
$stmtClient->execute([':id' => $credit['idCliente']]);
$clientInfo = $stmtClient->fetch(PDO::FETCH_ASSOC);
if (!$clientInfo) {
    echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado para la factura.']);
    exit;
}

// 6. Preparar variables para la factura general
$id_cliente = $credit['idCliente'];
$hoy = date('Y-m-d');  // Fecha actual

// Concatenar nombres y apellidos si existen
$nombre_cliente = isset($clientInfo['apellidos']) 
    ? $clientInfo['nombres'] . ' ' . $clientInfo['apellidos'] 
    : $clientInfo['nombres'];

$identificacion = $clientInfo['identificacion'];
$telefono = '+' . $clientInfo['dialCode'] . ' ' . $clientInfo['telefono'];
$direccion = $clientInfo['direccion'];

// Usar la descripción del crédito original como detalle
$detalle = $credit['descripcion'];
// El valor de la factura será el monto abonado al crédito
$valor_factura = $pago;

// Obtener el usuario de la sesión
$user = isset($_SESSION['user']) 
    ? $_SESSION['user']['nombre'] . ' ' . $_SESSION['user']['apellido'] 
    : '';

// Variables que espera generate_factura_general.php
$id = $id_cliente;
$nombre_cliente = $nombre_cliente;
$identificacion = $identificacion;
$telefono = $telefono;
$direccion = $direccion;
$detalle = $detalle;
$valor = $valor_factura;
$payment_method = $payment_method;  // Método de pago recibido
$bank = $bank;                      // Banco recibido
$user = $user;

// 7. Incluir generate_factura_general.php para generar la factura mínima  
// Este archivo debe insertar la factura en la tabla "facturas" (incluyendo payment_method, bank y user)
// y retornar el ID de la factura recién creada.
$facturaIdNueva = include('generate_factura_general.php');

$facturaValor = $valor;
include('../caja/register_generic_sale.php');


if (!$facturaIdNueva) {
    echo json_encode(['status' => 'error', 'message' => 'Error al generar la factura general.']);
    exit;
}

// 8. Si se creó un nuevo crédito (con saldo restante) se actualizan ambas tablas para vincularlas
if ($new_credit_id) {
    // Actualizar la factura para guardar el ID del nuevo crédito en la columna "credito_id"
    $stmtUpdFac = $pdo->prepare("UPDATE facturas SET credito_id = :credito_id WHERE id = :factura_id");
    $stmtUpdFac->execute([
        ':credito_id' => $new_credit_id,
        ':factura_id' => $facturaIdNueva
    ]);
    
    // Actualizar el nuevo crédito para asociarlo a la nueva factura (campo "factura_id")
    $stmtUpdCred = $pdo->prepare("UPDATE creditos SET factura_id = :factura_id WHERE id = :credit_id");
    $stmtUpdCred->execute([
        ':factura_id' => $facturaIdNueva,
        ':credit_id'  => $new_credit_id
    ]);
}

$notificaciones = $clientInfo['notificaciones'];
$facturaId = $facturaIdNueva;
// 9. (Opcional) Si el sistema de notificaciones está activo, incluir el envío de mensaje WhatsApp
if (isset($notificaciones) && ($notificaciones == 1 || $notificaciones == 0)) {
    ob_start();
    include('../../whatsapp/client-pay-general.php');
    $clientPayResponse = ob_get_clean();
}

	// LOGS
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
			'nombre_cliente' => $nombre_cliente,
			'factura' => $facturaIdNueva,
			'id' => $id,
			'nombre_cliente' => $nombre_cliente,
			'identificacion' => $identificacion,
			'telefono' => $telefono,
			'direccion' => $direccion,
			'detalle' => $detalle,
			'valor' => $valor,
			'payment_method' => $payment_method,  // Método de pago recibido
			'bank' => $bank,                      // Banco recibido
			'user' => $user		
			
], JSON_UNESCAPED_UNICODE);

log_action('Pagar Credito', $desc, 'Clientes');
// END LOGS

// 10. Enviar respuesta final
echo json_encode([
    'status' => 'success',
    'message' => 'Crédito actualizado correctamente .',
    'nuevo_valor' => $new_valor,
    'estado' => ($new_valor == 0) ? 1 : 0,
    'new_credit_id' => $new_credit_id,
    'factura_nueva' => $facturaIdNueva,
    'client_pay_response' => isset($clientPayResponse) ? $clientPayResponse : ''
]);
exit;
?>







