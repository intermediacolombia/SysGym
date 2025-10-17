<?php
require_once __DIR__ . '/../../inc/config.php';
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo json_encode(['error' => 'ID inválido']); exit;
}

$id = (int)$_GET['id'];

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8",
                 $dbuser,$dbpass,
                 [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

  $stmt = $pdo->prepare("SELECT * FROM valoraciones WHERE id=:id");
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  echo json_encode($row ?: ['error'=>'No encontrada']);

} catch(PDOException $e){
  echo json_encode(['error'=>$e->getMessage()]);
}
