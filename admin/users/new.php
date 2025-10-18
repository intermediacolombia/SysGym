<?php
require_once __DIR__ . '/../login/session.php';  // Inicia la sesión y carga la información del usuario
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');
session_start();

// Manejo del envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Credenciales de la base de datos
    require_once __DIR__ . '/../../inc/config.php';
// Conexión a la base de datos mediante PDO
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error de conexión: " . $e->getMessage();
        header("Location: $url/admin/users");
        exit();
    }

    // Recuperar y sanitizar los datos del formulario
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol      = trim($_POST['rol'] ?? '');
    $estado   = trim($_POST['estado'] ?? '');

    // Verificar si existe un registro con ese correo o nombre de usuario
    $sqlCheck = "SELECT * FROM usuarios WHERE correo = :correo OR username = :username LIMIT 1";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':correo'   => $correo,
        ':username' => $username
    ]);

    if ($stmtCheck->rowCount() > 0) {
        $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        // Si el registro existe y está activo (borrado = 0), se rechaza el registro.
        if ($existingUser['borrado'] == 0) {
            $_SESSION['error'] = "El correo o el nombre de usuario ya están registrados.";
            header("Location: $url/admin/users");
            exit();
        } else {
            // Si el registro existe pero estaba borrado, se actualizan TODOS los datos con los nuevos.
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sqlUpdate = "UPDATE usuarios 
                          SET nombre = :nombre, 
                              apellido = :apellido, 
                              correo = :correo, 
                              username = :username, 
                              password = :password, 
                              rol_id = :rol_id, 
                              estado = :estado, 
                              borrado = 0 
                          WHERE id = :id";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            try {
                $stmtUpdate->execute([
                    ':nombre'   => $nombre,
                    ':apellido' => $apellido,
                    ':correo'   => $correo,
                    ':username' => $username,
                    ':password' => $passwordHash,
                    ':rol_id'      => $rol,
                    ':estado'   => $estado,
                    ':id'       => $existingUser['id']
                ]);
				
				// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
					'nombre'   => $nombre,
                    'apellido' => $apellido,
                    'correo'   => $correo,
                    'username' => $username,
                    'rol_id'   => $rol,
                    'estado'   => $estado,
                    'id'       => $existingUser['id']
		], JSON_UNESCAPED_UNICODE);
		log_action('Crear Usuario', $desc, 'Usuarios');
		// END LOGS	
				
				
				
                $_SESSION['success'] = "Usuario registrado correctamente.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al actualizar el usuario: " . $e->getMessage();
            }
            header("Location: $url/admin/users");
            exit();
        }
    } else {
        // Si no se encontró ningún registro, se inserta un nuevo usuario.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sqlInsert = "INSERT INTO usuarios (nombre, apellido, correo, username, password, rol_id, estado) 
                      VALUES (:nombre, :apellido, :correo, :username, :password, :rol_id, :estado)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        try {
            $stmtInsert->execute([
                ':nombre'   => $nombre,
                ':apellido' => $apellido,
                ':correo'   => $correo,
                ':username' => $username,
                ':password' => $passwordHash,
                ':rol_id'      => $rol,
                ':estado'   => $estado
            ]);
			
			// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
					'nombre'   => $nombre,
                    'apellido' => $apellido,
                    'correo'   => $correo,
                    'username' => $username,
                    'rol_id'   => $rol,
                    'estado'   => $estado
		], JSON_UNESCAPED_UNICODE);
		log_action('Crear Usuario', $desc, 'Usuarios');
		// END LOGS	
			
            $_SESSION['success'] = "Usuario registrado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al registrar el usuario: " . $e->getMessage();
        }
        header("Location: $url/admin/users");
        exit();
    }
}
?>








