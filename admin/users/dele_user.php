<?php require_once __DIR__ . '/../login/session.php';

$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');

if(!isset($_GET['id'])) {
    $_SESSION['error'] = "ID no proporcionado.";
    header("Location: $url$url/admin/users");
    exit();
}

$id = intval($_GET['id']);

// Datos de conexión
require_once __DIR__ . '/../../inc/config.php';
try {
    
    
    // Actualizar el campo "borrado" a 1 para el usuario con el id indicado
    $stmt = db()->prepare("UPDATE usuarios SET borrado = 1 WHERE id = :id");
    $stmt->execute([':id' => $id]);
	
	// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'usuario_id' => $id,
    'accion'     => 'Usuario marcado como borrado'
], JSON_UNESCAPED_UNICODE);

log_action('Eliminar usuario', $desc, 'Usuarios');
// END LOGS

    
    if($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Usuario borrado exitosamente.";
    } else {
        $_SESSION['error'] = "No se encontró el usuario o ya estaba borrado.";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al borrar: " . $e->getMessage();
}
header("Location: $url/admin/users");
exit();
?>
