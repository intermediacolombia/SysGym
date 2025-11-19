<?php
require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';

// Asignamos la fecha actual (día de descongelado) en formato Y-m-d
$hoy = date("Y-m-d");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Falta el id del cliente.']);
        exit;
    }
    
    $id = trim($_POST['id']);
    
    try {
        // Se obtiene la fecha original de vencimiento y la fecha en que se congeló el plan
        $stmt = db()->prepare("SELECT vencimiento_plan, fecha_congelado FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente) {
            echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
            exit;
        }
        
        // Verificar que se tenga la información necesaria
        if (empty($cliente['fecha_congelado']) || empty($cliente['vencimiento_plan'])) {
            echo json_encode(['status' => 'error', 'message' => 'No se dispone de información de congelado para este plan.']);
            exit;
        }
        
        $fechaCongelado = new DateTime($cliente['fecha_congelado']);
        $vencimientoOriginal = new DateTime($cliente['vencimiento_plan']);
        
        // Calcula la diferencia en días entre la fecha de congelado y el vencimiento original.
        // Se resta 1 día para no contar el mismo día en que se congeló.
        $diasDiff = $fechaCongelado->diff($vencimientoOriginal)->days;
        $diasRestantes = $diasDiff - 1;
        if ($diasRestantes < 0) { 
            $diasRestantes = 0; 
        }
        
        // La nueva fecha de vencimiento se calcula a partir de hoy (el día de descongelar)
        // más los días restantes.
        $nuevaFechaVencimiento = date("Y-m-d", strtotime($hoy . " + {$diasRestantes} days"));
        
        // Actualiza el registro: descongela el plan (congelado = 0), borra la fecha de congelado y actualiza el vencimiento.
        $stmt = db()->prepare("UPDATE clientes 
                               SET congelado = 0, 
                                   fecha_congelado = NULL, 
                                   vencimiento_plan = :nuevaFechaVencimiento,
                                   updated_at = NOW()
                               WHERE id = :id");
        $stmt->execute([
            ':id' => $id,
            ':nuevaFechaVencimiento' => $nuevaFechaVencimiento
        ]);
		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
			'cliente_id' => $id,
			'nuevaFechaVencimiento' => $nuevaFechaVencimiento,
			
			
], JSON_UNESCAPED_UNICODE);

log_action('Descongelar Plan', $desc, 'Clientes');
// END LOGS
        
        echo json_encode(['status' => 'success', 'message' => 'Plan descongelado correctamente.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al descongelar el plan: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}
?>



