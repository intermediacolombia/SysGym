<?php 
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');
session_start();

if (!isset($_POST['id'])) {
    $_SESSION['error'] = "ID de usuario no proporcionado.";
    header("Location: index.php");
    exit();
}

$id       = intval($_POST['id']);
$nombre   = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$correo   = trim($_POST['correo'] ?? '');
$rol      = intval($_POST['rol'] ?? 0); // Convertir a entero
$estado   = trim($_POST['estado'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

// Validar rol
if ($rol <= 0) {
    $_SESSION['error'] = "Rol inválido.";
    header("Location: $url/admin/users");
    exit();
}

// Datos de conexión
require_once __DIR__ . '/../../inc/config.php';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar que el rol exista
    $stmtCheckRole = $pdo->prepare("SELECT id FROM roles WHERE id = :id AND borrado = 0");
    $stmtCheckRole->execute([':id' => $rol]);
    if (!$stmtCheckRole->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['error'] = "El rol seleccionado no existe o está inactivo.";
        header("Location: $url/admin/users");
        exit();
    }

    // Si se ingresa nueva contraseña, verificar confirmación
    if (!empty($password)) {
        if ($password !== $confirm) {
            $_SESSION['error'] = "Las contraseñas no coinciden.";
            header("Location: $url/admin/users");
            exit();
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo, rol_id = :rol_id, estado = :estado, password = :password WHERE id = :id";
        $params = [
            ':nombre'   => $nombre,
            ':apellido' => $apellido,
            ':correo'   => $correo,
            ':rol_id'   => $rol,
            ':estado'   => $estado,
            ':password' => $passwordHash,
            ':id'       => $id
        ];
    } else {
        $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo, rol_id = :rol_id, estado = :estado WHERE id = :id";
        $params = [
            ':nombre'   => $nombre,
            ':apellido' => $apellido,
            ':correo'   => $correo,
            ':rol_id'   => $rol,
            ':estado'   => $estado,
            ':id'       => $id
        ];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

	// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
					'id'       => $id,
					'nombre'   => $nombre,
                    'apellido' => $apellido,
                    'correo'   => $correo,
                    'rol_id'   => $rol,
                    'estado'   => $estado
		], JSON_UNESCAPED_UNICODE);
		log_action('Edición de Usuarios', $desc, 'Usuarios');
		// END LOGS	
	
    $_SESSION['success'] = "Usuario actualizado correctamente.";
    header("Location: $url/admin/users");
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al actualizar: " . $e->getMessage();
    header("Location: $url/admin/users");
    exit();
}
?>

