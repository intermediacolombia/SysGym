<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

// Verificar permisos
if (!isset($_SESSION["user_permissions"]) || !in_array('Eliminar Creditos', $_SESSION["user_permissions"])) {
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción.']);
    exit;
}

// Verificar ID del crédito
if (!isset($_POST['credit_id']) || empty($_POST['credit_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Falta el ID del crédito.']);
    exit;
}

$credit_id = intval(trim($_POST['credit_id']));

if ($credit_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de crédito inválido.']);
    exit;
}

try {
    // Obtener información del crédito antes de eliminar (para el log)
    $stmt = db()->prepare("SELECT c.*, cl.nombres, cl.apellidos 
                           FROM creditos c 
                           LEFT JOIN clientes cl ON c.idCliente = cl.id 
                           WHERE c.id = :credit_id AND c.deleted = 0");
    $stmt->execute([':credit_id' => $credit_id]);
    $credit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$credit) {
        echo json_encode(['status' => 'error', 'message' => 'Crédito no encontrado o ya fue eliminado.']);
        exit;
    }
    
    // Obtener el ID del usuario de la sesión
    $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
    
    // Borrado lógico
    $stmtUpdate = db()->prepare("UPDATE creditos 
                                  SET deleted = 1, 
                                      deleted_at = NOW(), 
                                      deleted_by = :deleted_by 
                                  WHERE id = :credit_id");
    $result = $stmtUpdate->execute([
        ':deleted_by' => $user_id,
        ':credit_id' => $credit_id
    ]);
    
    if ($result && $stmtUpdate->rowCount() > 0) {
        
        // LOGS
        require_once __DIR__ . '/../inc/log_action.php';
        
        $nombre_cliente = isset($credit['apellidos']) 
            ? $credit['nombres'] . ' ' . $credit['apellidos'] 
            : $credit['nombres'];
        
        $user = isset($_SESSION['user']) 
            ? $_SESSION['user']['nombre'] . ' ' . $_SESSION['user']['apellido'] 
            : '';
        
        $desc = json_encode([
            'credit_id' => $credit_id,
            'nombre_cliente' => $nombre_cliente,
            'valor' => $credit['valor'],
            'descripcion' => $credit['descripcion'],
            'fecha' => $credit['fecha'],
            'eliminado_por' => $user
        ], JSON_UNESCAPED_UNICODE);
        
        log_action('Eliminar Credito', $desc, 'Creditos');
        
        echo json_encode(['status' => 'success', 'message' => 'Crédito eliminado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el crédito.']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos.']);
}
exit;
?>