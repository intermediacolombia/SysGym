<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Manejar Valoraciones';
include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id']) || !is_numeric($data['id'])) {
  echo json_encode(['status'=>'error','message'=>'ID inválido']);
  exit;
}

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass,
                 [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $stmt = $pdo->prepare("UPDATE valoraciones SET borrado = 1 WHERE id = ?");
  $stmt->execute([$data['id']]);
  echo json_encode(['status'=>'success','message'=>'Valoración marcada como borrada']);
} catch (PDOException $e) {
  echo json_encode(['status'=>'error','message'=>'Error BD: '.$e->getMessage()]);
}
