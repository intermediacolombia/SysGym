<?php
require_once __DIR__ . '/../../inc/config.php';
db();

$stmt = db()->prepare("
  SELECT id, fecha, peso, estatura, imc,
         porcentaje_grasa_corporal,
         porcentaje_musculo_esqueletico,
         metabolismo_basal, edad_corporal,
         nivel_grasa_visceral
    FROM valoraciones
   WHERE cliente_id = :cid AND borrado = 0 
ORDER BY fecha DESC
");
$stmt->execute([':cid'=>$_GET['cliente_id']]);
echo json_encode(['data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
