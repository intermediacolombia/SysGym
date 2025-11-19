<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');


/* parámetros GET */
$estado = isset($_GET['estado']) ? (int)$_GET['estado'] : 0;     // 0 = cerrada
$fecha  = $_GET['fecha'] ?? '';                                  // '' = sin filtro

/* arma cláusula WHERE */
$where = "WHERE c.usuario_id = :usuario_id AND c.estado = :estado";
$params = [
  ':usuario_id' => $id_user,
  ':estado'     => $estado
];

if ($fecha !== '') {
  $where   .= " AND DATE(c.fecha_apertura) = :fecha";
  $params[':fecha'] = $fecha;
}

/* consulta */
$sql = "
  SELECT c.*,
         u.nombre AS usuario,
         DATE_FORMAT(c.hora_apertura,'%h:%i:%s %p') AS hora_apertura_formateada,
         DATE_FORMAT(c.hora_cierre  ,'%h:%i:%s %p') AS hora_cierre_formateada
    FROM cajas c
    JOIN usuarios u ON u.id = c.usuario_id
    $where
    ORDER BY c.fecha_cierre DESC, c.hora_cierre DESC
";

$stmt = db()->prepare($sql);
$stmt->execute($params);

echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
?>


