<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo json_encode(['error' => 'ID inválido']); exit;
}

$id = (int)$_GET['id'];

try {

  $stmt = db()->prepare("SELECT * FROM valoraciones WHERE id=:id");
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  echo json_encode($row ?: ['error'=>'No encontrada']);

} catch(PDOException $e){
  echo json_encode(['error'=>$e->getMessage()]);
}
