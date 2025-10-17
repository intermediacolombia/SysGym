<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo json_encode(['data'=>[]]);
    exit;
}

$caja_id = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;
if($caja_id <= 0){
    echo json_encode(['data'=>[]]);
    exit;
}

$stmt = $pdo->prepare("SELECT *,  DATE_FORMAT(hora, '%h:%i:%s %p') AS hora_formateada FROM ventas WHERE caja_id = :caja_id ORDER BY id DESC");
$stmt->execute([':caja_id' => $caja_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['data' => $data]);
?>

