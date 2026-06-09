<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

db()->exec("CREATE TABLE IF NOT EXISTS `cliente_rutina_semana` (
    `id`         int            NOT NULL AUTO_INCREMENT,
    `cliente_id` int            NOT NULL,
    `dia_semana` enum('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
    `rutina_id`  int            NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cliente_dia` (`cliente_id`, `dia_semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/* ── GET ─────────────────────────────────────────────── */
if ($method === 'GET') {
    $cliente_id = (int)($_GET['cliente_id'] ?? 0);
    if (!$cliente_id) { echo json_encode(['error' => 'missing client']); exit; }

    if ($action === 'get') {
        $stmt = db()->prepare("SELECT dia_semana, rutina_id FROM cliente_rutina_semana WHERE cliente_id = :id");
        $stmt->execute([':id' => $cliente_id]);
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtR = db()->query("SELECT id, nombre, descripcion FROM rutinas WHERE estado = 'activo' AND borrado = 0 ORDER BY nombre ASC");
        $rutinas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['asignaciones' => $asignaciones, 'rutinas' => $rutinas]);
        exit;
    }

    if ($action === 'get_ejercicios_rutina') {
        $rutina_id = (int)($_GET['rutina_id'] ?? 0);
        $stmt = db()->prepare("
            SELECT e.nombre, re.repeticiones, re.series, re.duracion, re.descanso, re.orden
            FROM rutina_ejercicio re
            JOIN ejercicios e ON e.id = re.ejercicio_id
            WHERE re.rutina_id = :id
            ORDER BY re.orden ASC
        ");
        $stmt->execute([':id' => $rutina_id]);
        echo json_encode(['ejercicios' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
}

/* ── POST ────────────────────────────────────────────── */
if ($method === 'POST') {
    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
    $action     = $body['action']     ?? '';
    $cliente_id = (int)($body['cliente_id'] ?? 0);

    if ($action === 'save' && $cliente_id) {
        $dias = $body['dias'] ?? [];

        db()->prepare("DELETE FROM cliente_rutina_semana WHERE cliente_id = :id")
             ->execute([':id' => $cliente_id]);

        $stmt = db()->prepare("INSERT INTO cliente_rutina_semana (cliente_id, dia_semana, rutina_id) VALUES (:cid, :dia, :rid)");
        foreach ($dias as $d) {
            if (!empty($d['rutina_id']) && !empty($d['dia_semana'])) {
                $stmt->execute([
                    ':cid' => $cliente_id,
                    ':dia' => $d['dia_semana'],
                    ':rid' => (int)$d['rutina_id'],
                ]);
            }
        }

        require_once __DIR__ . '/../inc/log_action.php';
        log_action(
            'Asignar rutina semanal',
            json_encode(['cliente_id' => $cliente_id, 'dias' => $dias], JSON_UNESCAPED_UNICODE),
            'Rutinas'
        );

        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['error' => 'invalid request']);
