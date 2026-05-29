<?php
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) die("Cliente no especificado.");

try {
	
	
    	
    // === Obtener datos del cliente ===
    $stmt = db()->prepare("SELECT * FROM clientes WHERE id = :id AND borrado = 0 LIMIT 1");
    $stmt->execute([':id' => $cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) die("Cliente no encontrado.");

    $esTiquetera = ($cliente['plan_tipo'] ?? 'plan') === 'tiquetera';

    // === Obtener datos del plan o tiquetera ===
    if ($esTiquetera) {
        $stmtPlan = db()->prepare("SELECT nombre, precio, limite_entradas FROM tiqueteras WHERE id = :id AND borrado = 0");
    } else {
        $stmtPlan = db()->prepare("SELECT nombre, precio FROM planes WHERE id = :id AND borrado = 0");
    }
    $stmtPlan->execute([':id' => $cliente['plan']]);
    $planInfo = $stmtPlan->fetch(PDO::FETCH_ASSOC);

    if (!$planInfo) die("Plan no encontrado.");

    // === Validación para tiqueteras: bloquear si aún tiene entradas y no ha vencido ===
    if ($esTiquetera) {
        $hoy = new DateTime('today');
        $venc = !empty($cliente['vencimiento_plan']) ? new DateTime($cliente['vencimiento_plan']) : null;
        $vencida = !$venc || $venc < $hoy;

        if (!$vencida && !empty($cliente['tiquetera_factura_id'])) {
            $stmtCons = db()->prepare("SELECT COUNT(*) FROM tiquetera_consumos WHERE cliente_id = :id AND factura_id = :fid");
            $stmtCons->execute([':id' => $cliente_id, ':fid' => $cliente['tiquetera_factura_id']]);
            $consumidas = (int)$stmtCons->fetchColumn();

            if ($consumidas < (int)$planInfo['limite_entradas']) {
                die("Aún tienes {$consumidas}/{$planInfo['limite_entradas']} entradas disponibles. Podrás renovar cuando las agotes o cuando venza tu tiquetera.");
            }
        }
    }

    // === Definir datos del pago ===
    $porcentajeAdicional = (float) ADDITIONAL_PERCENTAGE_PAYMENT;
    $monto = (float)($planInfo['precio'] ?? 0);
    if ($monto <= 0) $monto = 50000;

    if ($porcentajeAdicional > 0) {
        $monto = round($monto * (1 + ($porcentajeAdicional / 100)), 2);
    }

    $descripcion = "Pago Membresia - " . ($planInfo['nombre'] ?? 'Plan General');

    $referencia = "pago_{$cliente_id}_" . time();

    // === Registrar intento de pago en la base de datos ===
    $stmtCheck = db()->prepare("SELECT id FROM pagos WHERE referencia = :ref LIMIT 1");
    $stmtCheck->execute([':ref' => $referencia]);
    if (!$stmtCheck->fetchColumn()) {
        $stmtInsert = db()->prepare("
    INSERT INTO pagos (cliente_id, referencia, monto, estado, metodo_pago, raw_response, fecha_pago)
    VALUES (:cid, :ref, :monto, 'Pending', 'mercadopago', NULL, :fecha)
");
$stmtInsert->execute([
    ':cid' => $cliente_id,
    ':ref' => $referencia,
    ':monto' => $monto,
    ':fecha' => date('Y-m-d H:i:s')
]);
    }

    // === Crear preferencia de Mercado Pago ===
    $preferenceClient = new PreferenceClient();
    $preference = $preferenceClient->create([
        "items" => [[
            "title" => $descripcion,
            "quantity" => 1,
            "unit_price" => $monto,
            "currency_id" => "COP"
        ]],
        "payer" => [
            "name" => $cliente['nombres'],
            "surname" => $cliente['apellidos'],
            "email" => $cliente['email'] ?: "cliente@sysgym.com"
        ],
        "external_reference" => $referencia,
        "back_urls" => [
            "success" => $url . "/pay/pago_exitoso.php?id=" . $cliente_id,
            "failure" => $url . "/pay/pago_fallido.php?id=" . $cliente_id,
            "pending" => $url . "/pay/pago_pendiente.php?id=" . $cliente_id
        ],
        "auto_return" => "approved",
        "notification_url" => $url . "/pay/webhook_mp.php"
    ]);

    // === Redirigir al checkout ===
    header("Location: " . $preference->init_point);
    exit;

} catch (Exception $e) {
    die("Error al generar el pago: " . $e->getMessage());
}

