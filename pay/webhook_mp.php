<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$data = json_decode(file_get_contents('php://input'), true);
file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s')." ".json_encode($data)."\n", FILE_APPEND);

// Solo procesar pagos aprobados
if (!empty($data['type']) && $data['type'] === 'payment' && !empty($data['data']['id'])) {
    $payment_id = $data['data']['id'];

    // Obtener info detallada del pago
    $client = new MercadoPago\Client\Payment\PaymentClient();
    $payment = $client->get($payment_id);

    if ($payment->status === 'approved') {
        // Buscar cliente por external_reference
        $ref = $payment->external_reference;
        if (preg_match('/pago_(\d+)_/', $ref, $match)) {
            $cliente_id = (int)$match[1];

            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $fecha_pago = date('Y-m-d');
            $fecha_venc = date('Y-m-d', strtotime('+30 days'));

            $stmt = $pdo->prepare("UPDATE clientes 
                                   SET pago_plan = :pago, vencimiento_plan = :venc, estado = 'activo'
                                   WHERE id = :id");
            $stmt->execute([
                ':pago' => $fecha_pago,
                ':venc' => $fecha_venc,
                ':id' => $cliente_id
            ]);
        }
    }
}
http_response_code(200);
