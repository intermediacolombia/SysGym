<?php
require_once __DIR__ . '/../../inc/config.php';

$q = $_GET['q'] ?? '';
$exclude = $_GET['exclude_id'] ?? 0;

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
  $stmt = $pdo->prepare("
    SELECT id, nombres, apellidos, identificacion 
    FROM clientes 
    WHERE borrado = 0
      AND id != :exclude
      AND (
        plan IS NULL 
        OR vencimiento_plan < $hoy
        OR estado = 'inactivo'
      )
      AND (
        identificacion LIKE :q 
        OR CONCAT(nombres, ' ', apellidos) LIKE :q
      )
    LIMIT 10
  ");
  $stmt->execute([
    ':q' => "%$q%",
    ':exclude' => $exclude
  ]);
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
  echo json_encode([]);
}

