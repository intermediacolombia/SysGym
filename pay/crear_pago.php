<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) die("Cliente no especificado.");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // === Obtener datos del cliente y su plan ===
    $stmt = $pdo->prepare("
        SELECT c.*, p.nombre AS plan_nombre, p.precio AS plan_precio
        FROM clientes c
        LEFT JOIN planes p ON p.id = c.plan
        WHERE c.id = :id LIMIT 1
    ");
    $stmt->execute([':id' => $cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) die("Cliente no encontrado.");

    // === Definir datos del pago ===
    // Datos del pago
	
	$porcentajeAdicional = ADDITIONAL_PERCENTAGE_PAYMENT;
	$monto = (float)($cliente['plan_precio'] ?? 0);
	if ($monto <= 0) $monto = 50000; // valor base si el plan no tiene precio

	// === Aplicar incremento dinámico ===
	if ($porcentajeAdicional > 0) {
		$monto = round($monto * (1 + ($porcentajeAdicional / 100)), 2);
	}

	$descripcion = "Pago Membresia - " . ($cliente['plan_nombre'] ?? 'Plan General');

    $referencia = "pago_{$cliente_id}_" . time();

    // === Registrar intento de pago en la base de datos ===
    $stmtCheck = $pdo->prepare("SELECT id FROM pagos WHERE referencia = :ref LIMIT 1");
    $stmtCheck->execute([':ref' => $referencia]);
    if (!$stmtCheck->fetchColumn()) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO pagos (cliente_id, referencia, monto, estado, metodo_pago, raw_response)
            VALUES (:cid, :ref, :monto, 'iniciado', 'mercadopago', NULL)
        ");
        $stmtInsert->execute([
            ':cid' => $cliente_id,
            ':ref' => $referencia,
            ':monto' => $monto
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

