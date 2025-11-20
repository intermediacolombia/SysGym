<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once __DIR__ . '/../../inc/config.php';

    // ==========================================================
    // Recuperar campos
    // ==========================================================
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $dialcode = trim($_POST['dialcode'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol      = trim($_POST['rol'] ?? '');
    $estado   = trim($_POST['estado'] ?? '');
    $recibe_alertas_stock = isset($_POST['recibe_alertas_stock']) ? 1 : 0;

    // ==========================================================
    // SUBIDA DE FOTO DE PERFIL
    // ==========================================================
    $fotoPerfil = null;

    if (!empty($_FILES['foto_perfil']['name'])) {

        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . time() . '.' . $ext;

        $uploadDir = __DIR__ . '/../../uploads/users/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destino = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino)) {
            // ruta relativa para guardar en BD
            $fotoPerfil = 'uploads/users/' . $fileName;
        }
    }

    // ==========================================================
    // Verificar duplicados
    // ==========================================================
    $sqlCheck = "SELECT * FROM usuarios WHERE correo = :correo OR username = :username LIMIT 1";
    $stmtCheck = db()->prepare($sqlCheck);
    $stmtCheck->execute([':correo'=>$correo, ':username'=>$username]);

    if ($stmtCheck->rowCount() > 0) {
        $_SESSION['error'] = "El correo o nombre de usuario ya están registrados.";
        header("Location: $url/admin/users");
        exit();
    }

    // ==========================================================
    // Insertar nuevo usuario
    // ==========================================================
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sqlInsert = "INSERT INTO usuarios 
        (nombre, apellido, correo, dialcode, telefono, username, password, rol_id, estado, recibe_alertas_stock, foto_perfil) 
        VALUES 
        (:nombre, :apellido, :correo, :dialcode, :telefono, :username, :password, :rol_id, :estado, :recibe_alertas_stock, :foto_perfil)";

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
            ':recibe_alertas_stock' => $recibe_alertas_stock,
            ':foto_perfil' => $fotoPerfil
        ]);

        // ==========================================================
        // LOGS
        // ==========================================================
        require_once __DIR__ . '/../inc/log_action.php';
        $desc = json_encode([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'dialcode' => $dialcode,
            'telefono' => $telefono,
            'username' => $username,
            'rol_id' => $rol,
            'estado' => $estado,
            'recibe_alertas_stock' => $recibe_alertas_stock,
            'foto_perfil' => $fotoPerfil
        ], JSON_UNESCAPED_UNICODE);

        log_action('Crear Usuario', $desc, 'Usuarios');

        $_SESSION['success'] = "Usuario registrado correctamente.";

    } catch (PDOException $e) {

        $_SESSION['error'] = "Error al registrar el usuario: " . $e->getMessage();
    }

    header("Location: $url/admin/users");
    exit();
}
?>










