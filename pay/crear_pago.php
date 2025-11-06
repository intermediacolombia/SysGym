<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) die("Cliente no especificado.");

// === Conexión DB ===
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// === Obtener datos del cliente y plan ===
$stmt = $pdo->prepare("SELECT c.*, p.nombre AS plan_nombre, p.precio AS plan_precio 
                       FROM clientes c
                       LEFT JOIN planes p ON p.id = c.plan
                       WHERE c.id = :id LIMIT 1");
$stmt->execute([':id' => $cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cliente) die("Cliente no encontrado.");

// === Definir monto y descripción ===
$monto = (float)($cliente['plan_precio'] ?? 50000);
$descripcion = "Pago de mensualidad SysGym - Cliente #{$cliente_id}";

// === Crear preferencia ===
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
    "external_reference" => "pago_{$cliente_id}_" . time(),
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
