<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Falta el id del cliente.']);
        exit;
    }
    
    $id = trim($_POST['id']);
    
    try { 
        // Actualiza el cliente asignando NULL al plan y a las fechas de pago/vencimiento, y actualiza la fecha de modificación
        $stmt = db()->prepare("UPDATE clientes 
                               SET plan = NULL, pago_plan = NULL, vencimiento_plan = NULL, estado = 'inactivo', updated_at = NOW() 
                               WHERE id = :id");
        $stmt->execute([':id' => $id]);
		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';

$desc = json_encode([
			'cliente_id' => $id,
			'accion' => 'Plan Anulado',
			
], JSON_UNESCAPED_UNICODE);

log_action('Anular Plan', $desc, 'Clientes');
// END LOGS
        
        echo json_encode(['status' => 'success', 'message' => 'Plan eliminado correctamente.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el plan: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}
?>
