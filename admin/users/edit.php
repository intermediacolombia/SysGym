<?php
require_once __DIR__ . '/../login/session.php';  // Inicia la sesión y carga la información del usuario
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');


// ==========================================================
// MANEJO DEL ENVÍO DEL FORMULARIO
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Credenciales de la base de datos
    require_once __DIR__ . '/../../inc/config.php';

    try {
        

    // ==========================================================
    // Recuperar y sanitizar los datos del formulario
    // ==========================================================
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol      = trim($_POST['rol'] ?? '');
    $estado   = trim($_POST['estado'] ?? '');
    $dialcode = trim($_POST['dialcode'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $recibe_alertas_stock = isset($_POST['recibe_alertas_stock']) ? 1 : 0;

    // ==========================================================
    // Verificar si ya existe correo o username
    // ==========================================================
    $sqlCheck = "SELECT * FROM usuarios WHERE correo = :correo OR username = :username LIMIT 1";
    $stmtCheck = db()->prepare($sqlCheck);
    $stmtCheck->execute([
        ':correo'   => $correo,
        ':username' => $username
    ]);

    if ($stmtCheck->rowCount() > 0) {
        $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // Si el usuario existe y no está marcado como borrado
        if ($existingUser['borrado'] == 0) {
            $_SESSION['error'] = "El correo o el nombre de usuario ya están registrados.";
            header("Location: $url/admin/users");
            exit();
        } 
        else {
            // ==========================================================
            // Reactivar un usuario previamente borrado
            // ==========================================================
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sqlUpdate = "UPDATE usuarios 
                          SET nombre = :nombre,
                              apellido = :apellido,
                              correo = :correo,
                              username = :username,
                              password = :password,
                              rol_id = :rol_id,
                              estado = :estado,
                              dialcode = :dialcode,
                              telefono = :telefono,
                              recibe_alertas_stock = :recibe_alertas_stock,
                              borrado = 0 
                          WHERE id = :id";

            $stmtUpdate = db()->prepare($sqlUpdate);
            try {
                $stmtUpdate->execute([
                    ':nombre'   => $nombre,
                    ':apellido' => $apellido,
                    ':correo'   => $correo,
                    ':username' => $username,
                    ':password' => $passwordHash,
                    ':rol_id'   => $rol,
                    ':estado'   => $estado,
                    ':dialcode' => $dialcode,
                    ':telefono' => $telefono,
                    ':recibe_alertas_stock' => $recibe_alertas_stock,
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
                    'dialcode' => $dialcode,
                    'telefono' => $telefono,
                    'recibe_alertas_stock' => $recibe_alertas_stock,
                    'id'       => $existingUser['id']
                ], JSON_UNESCAPED_UNICODE);
                log_action('Reactivar Usuario', $desc, 'Usuarios');
                // END LOGS	

                $_SESSION['success'] = "Usuario reactivado correctamente.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al actualizar el usuario: " . $e->getMessage();
            }
            header("Location: $url/admin/users");
            exit();
        }
    } 
    else {
        // ==========================================================
        // Registrar un nuevo usuario
        // ==========================================================
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sqlInsert = "INSERT INTO usuarios 
                      (nombre, apellido, correo, dialcode, telefono, username, password, rol_id, estado, recibe_alertas_stock) 
                      VALUES 
                      (:nombre, :apellido, :correo, :dialcode, :telefono, :username, :password, :rol_id, :estado, :recibe_alertas_stock)";

        $stmtInsert = db()->prepare($sqlInsert);

        try {
            $stmtInsert->execute([
                ':nombre'   => $nombre,
                ':apellido' => $apellido,
                ':correo'   => $correo,
                ':dialcode' => $dialcode,
                ':telefono' => $telefono,
                ':username' => $username,
                ':password' => $passwordHash,
                ':rol_id'   => $rol,
                ':estado'   => $estado,
                ':recibe_alertas_stock' => $recibe_alertas_stock
            ]);

            // LOGS
            require_once __DIR__ . '/../inc/log_action.php';
            $desc = json_encode([
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'correo'   => $correo,
                'dialcode' => $dialcode,
                'telefono' => $telefono,
                'username' => $username,
                'rol_id'   => $rol,
                'estado'   => $estado,
                'recibe_alertas_stock' => $recibe_alertas_stock
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






