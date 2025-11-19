<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php 
$permisopage = 'Borrar Clientes';
include('../login/restriction.php');
?>

<?php
// NO llames session_start() si session.php ya lo hace
// session_start();

require_once __DIR__ . '/../../inc/config.php';

// Validar ID
if (empty($_GET['id'])) {
    $_SESSION['error'] = "No se proporcionó el id del cliente.";
    header("Location: index.php");
    exit;
}

$id = trim($_GET['id']);

try {

    // Borrar cliente
    $stmt = db()->prepare("
        UPDATE clientes 
        SET borrado = 1, updated_at = NOW() 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $id]);

    // === LOGS ===
    require_once __DIR__ . '/../inc/log_action.php';

    $desc = json_encode([
        'cliente_id' => $id,
        'accion'     => 'Cliente marcado como borrado'
    ], JSON_UNESCAPED_UNICODE);

    log_action('Borrar cliente', $desc, 'Clientes');
    // === END LOGS ===

    $_SESSION['success'] = "Cliente borrado correctamente.";
    header("Location: index.php");
    exit;

} catch (PDOException $e) {

    $_SESSION['error'] = "Error al borrar el cliente: " . $e->getMessage();
    header("Location: detail.php?id=" . urlencode($id));
    exit;
}
?>

