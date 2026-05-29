<?php
require_once __DIR__ . '/../../inc/config.php';

$cliente_id = intval($_GET['cliente_id'] ?? 0);
if (!$cliente_id) { http_response_code(400); echo json_encode(['ok' => false]); exit; }

$stmt = db()->prepare("SELECT nombres, apellidos, dialCode, telefono FROM clientes WHERE id = :id AND borrado = 0 LIMIT 1");
$stmt->execute([':id' => $cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente || empty($cliente['telefono'])) { echo json_encode(['ok' => false, 'msg' => 'Cliente sin teléfono']); exit; }

// Obtener límite de entradas
$stmtLim = db()->prepare("SELECT t.limite_entradas FROM clientes c JOIN tiqueteras t ON t.id = c.plan WHERE c.id = :id AND t.borrado = 0");
$stmtLim->execute([':id' => $cliente_id]);
$limiteEntradas = (int)$stmtLim->fetchColumn();

$cp_nombres   = $cliente['nombres'];
$cp_apellidos = $cliente['apellidos'];
$cp_dialCode  = $cliente['dialCode'];
$cp_telefono  = $cliente['telefono'];
$cp_entradas  = $limiteEntradas;

ob_start();
include(__DIR__ . '/../../whatsapp/tiquetera-agotada.php');
ob_end_clean();

echo json_encode(['ok' => true]);
