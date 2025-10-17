<?php
require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ids'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron créditos válidos.']);
    exit;
}

$ids = is_array($_POST['ids']) ? $_POST['ids'] : explode(',', $_POST['ids']);
$payment_method = trim($_POST['paymentMethod'] ?? '');
$bank = trim($_POST['bank'] ?? '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $pdo->beginTransaction();

    $creditosPagados = [];
    $totalPagado = 0;
    $idCliente = null;
    $clienteInfo = null;

    // === 1. Recolectar información de los créditos seleccionados ===
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT c.id, c.idCliente, c.valor, c.descripcion, c.fecha_limite,
               cli.nombres, cli.apellidos, cli.identificacion, cli.dialCode, cli.telefono, cli.direccion, cli.notificaciones
        FROM creditos c
        INNER JOIN clientes cli ON cli.id = c.idCliente
        WHERE c.id IN ($placeholders) AND c.estado = 0
    ");
    $stmt->execute($ids);
    $creditos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$creditos) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron créditos válidos.']);
        exit;
    }

    // Asumimos que todos pertenecen al mismo cliente
    $clienteInfo = $creditos[0];
    $idCliente = $clienteInfo['idCliente'];

    // === 2. Calcular total y marcar cada crédito como pagado ===
    foreach ($creditos as $credito) {
        $totalPagado += (float)$credito['valor'];
        $creditosPagados[] = $credito;
        $pdo->prepare("UPDATE creditos SET estado = 1, updated_at = NOW() WHERE id = ?")->execute([$credito['id']]);
    }

    // === 3. Generar la factura única ===
    $hoy = date('Y-m-d');
    $nombre_cliente = trim($clienteInfo['nombres'] . ' ' . $clienteInfo['apellidos']);
    $identificacion = $clienteInfo['identificacion'];
    $telefono = '+' . $clienteInfo['dialCode'] . ' ' . $clienteInfo['telefono'];
    $direccion = $clienteInfo['direccion'];
    $detalle = "Pago de créditos:\n";
    foreach ($creditosPagados as $c) {
        $detalle .= "- {$c['descripcion']} ({$c['fecha_limite']}) — $" . number_format($c['valor'], 0, ',', '.') . "\n";
    }

    $valor = $totalPagado;
    $user = isset($_SESSION['user']) ? $_SESSION['user']['nombre'] . ' ' . $_SESSION['user']['apellido'] : '';
    $id = $idCliente;

    // === 4. Crear la factura ===
    $facturaIdNueva = include(__DIR__ . '/generate_factura_general.php');
    $facturaId = $facturaIdNueva;

    // === 5. Registrar la venta en caja ===
    $stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE usuario_id = :u AND estado = 1 LIMIT 1");
    $stmtCaja->execute([':u' => $id_user]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

    if ($caja) {
        $stmtVenta = $pdo->prepare("INSERT INTO ventas 
            (caja_id, detalle, cantidad, valor, payment_method, bank, fecha, hora)
            VALUES (:caja, :detalle, 1, :valor, :metodo, :banco, :fecha, :hora)");
        $stmtVenta->execute([
            ':caja' => $caja['id'],
            ':detalle' => "Factura #$facturaIdNueva",
            ':valor' => $totalPagado,
            ':metodo' => $payment_method,
            ':banco' => $bank,
            ':fecha' => $hoy,
            ':hora' => date('H:i:s')
        ]);
    }

    // === 6. Vincular los créditos a la factura ===
    $stmtVincular = $pdo->prepare("UPDATE creditos SET factura_id = ? WHERE id = ?");
    foreach ($creditosPagados as $c) {
        $stmtVincular->execute([$facturaIdNueva, $c['id']]);
    }

    // ✅ Confirmar la transacción antes de generar PDF / enviar WhatsApp
    $pdo->commit();

    // === 7. Enviar WhatsApp con la factura (si aplica) ===
    if ((int)$clienteInfo['notificaciones'] === 1 || (int)$clienteInfo['notificaciones'] === 0) {

        // Variables que el client-pay-general.php necesita
        $clientInfo = [
            'nombres'        => $clienteInfo['nombres'],
            'apellidos'      => $clienteInfo['apellidos'],
            'telefono'       => $clienteInfo['telefono'],
            'dialCode'       => $clienteInfo['dialCode'],
            'direccion'      => $clienteInfo['direccion'],
            'identificacion' => $clienteInfo['identificacion']
        ];

        $facturaId = $facturaIdNueva;
        $valor     = $totalPagado;
        $hoy       = date('Y-m-d'); // Fecha para el mensaje

        ob_start();
        include(__DIR__ . '/../../whatsapp/client-pay-general.php');
        $clientPayResponse = ob_get_clean();

        // Registrar log del envío
        file_put_contents(__DIR__ . '/whatsapp_log.txt',
            "[" . date('Y-m-d H:i:s') . "] Pago masivo cliente ID: $idCliente | Factura #$facturaId | Monto: $valor | Respuesta: $clientPayResponse\n",
            FILE_APPEND
        );
    }

	
	
	// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
					'nombres'        => $clienteInfo['nombres'],
            		'apellidos'      => $clienteInfo['apellidos'],
					'valor' => $valor,
					'detalle' => $detalle, 
					'accion' => 'Creditos Pagados',			
		], JSON_UNESCAPED_UNICODE);
		log_action('Pago Masivo Credito', $desc, 'Clientes');
	// END LOGS
	
    // === 8. Respuesta final ===
    echo json_encode([
        'status' => 'success',
        'message' => "Pago masivo aplicado correctamente. Total pagado: $" . number_format($totalPagado, 0, ',', '.'),
        'factura_id' => $facturaIdNueva
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>

