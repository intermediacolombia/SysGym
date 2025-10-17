<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8",
                 $dbuser,$dbpass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch(PDOException $e){
  echo json_encode(['data'=>[]]); exit;
}

/*── parámetro opcional ─────────────────────────────*/
$fecha  = $_GET['fecha'] ?? '';           // '' = sin filtro
$where  = '';
$params = [];

if ($fecha !== '') {
  $where  = 'WHERE DATE(c.fecha_apertura) = :f';
  $params = [':f'=>$fecha];
}

/*── consulta ──────────────────────────────────────*/
$sql = "
  SELECT c.*,
         CONCAT(u.nombre,' ',u.apellido) AS usuario,
         DATE_FORMAT(c.hora_apertura,'%h:%i:%s %p') AS hora_apertura_formateada,
         DATE_FORMAT(c.hora_cierre  ,'%h:%i:%s %p') AS hora_cierre_formateada
  FROM   cajas c
  JOIN   usuarios u ON u.id = c.usuario_id
  $where
  ORDER BY c.fecha_cierre DESC, c.hora_cierre DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(['data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);



