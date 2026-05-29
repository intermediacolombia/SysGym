<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../inc/config.php';

try {

    // 1) Montar WHERE dinámico
    $where  = ['borrado = 0'];
    $params = [];

    // Campos que usamos con LIKE o con '*' para “no vacío”
    $likeFields = [
        'identificacion','nombres','apellidos','direccion','telefono',
        'email','rh','eps','fracturas','alergias',
        'enfermedades_actuales','observaciones',
        'contacto_emergencia','numero_emergencia'
    ];

    foreach ($likeFields as $f) {
        if (isset($_GET[$f]) && $_GET[$f] !== '') {
            if ($_GET[$f] === '*') {
                $where[] = "($f IS NOT NULL AND TRIM($f) <> '')";
            } else {
                $where[]       = "$f LIKE :$f";
                $params[":$f"] = "%".$_GET[$f]."%";
            }
        }
    }

    // Campos exactos
    foreach (['estado','genero','congelado','plan','plan_tipo'] as $f) {
        if (!empty($_GET[$f])) {
            $where[]       = "$f = :$f";
            $params[":$f"] = $_GET[$f];
        }
    }

    // Fecha exacta
    if (!empty($_GET['fecha_nacimiento'])) {
        $where[]                      = "fecha_nacimiento = :fecha_nacimiento";
        $params[':fecha_nacimiento']  = $_GET['fecha_nacimiento'];
    }

    // NUEVO: vencimiento_plan
    if (!empty($_GET['vencimiento_plan'])) {
        $where[]                     = "vencimiento_plan = :vencimiento_plan";
        $params[':vencimiento_plan'] = $_GET['vencimiento_plan'];
    }

    // 2) Ejecutar consulta
    $sql = "
        SELECT * FROM clientes
        WHERE ".implode(" AND ", $where)."
        ORDER BY identificacion DESC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Agregar plan_nombre
    foreach ($clientes as &$c) {
        if (!empty($c['plan'])) {
            if (($c['plan_tipo'] ?? 'plan') === 'tiquetera') {
                $p = db()->prepare("SELECT nombre FROM tiqueteras WHERE id = ? AND borrado = 0");
            } else {
                $p = db()->prepare("SELECT nombre FROM planes WHERE id = ? AND borrado = 0");
            }
            $p->execute([$c['plan']]);
            $c['plan_nombre'] = $p->fetchColumn() ?: '';
        } else {
            $c['plan_nombre'] = '';
        }
    }

    echo json_encode(['data' => $clientes]);

} catch (PDOException $e) {
    echo json_encode([
        'data' => [],
        'error' => $e->getMessage()
    ]);
}




