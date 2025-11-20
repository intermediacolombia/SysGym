<?php
session_start();
require_once __DIR__ . '/../../inc/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
       

        // Borrado lógico del empleado
        $sql = "UPDATE empleados SET borrado = 1 WHERE id = :id";
        $stmt = db()->prepare($sql);
        $stmt->execute([':id' => $id]);
		
		// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
			'id' => $id
		], JSON_UNESCAPED_UNICODE);
		log_action('Borrar Empleado', $desc, 'Nomina');
		// END LOGS	

        $_SESSION['success'] = "Empleado borrado correctamente.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al borrar empleado: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "No se recibió el ID del empleado.";
}

// Redirigir de vuelta al index
header("Location: employees.php");
exit;
?>
