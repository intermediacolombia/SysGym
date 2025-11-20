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
            $fotoPerfil = 'uploads/users/' . $fileName;
        }
    }

    // ==========================================================
    // CONSULTAR SI EXISTE UN USUARIO CON ESE CORREO O USERNAME
    // ==========================================================
    $sqlCheck = "SELECT * FROM usuarios 
                 WHERE correo = :correo OR username = :username 
                 LIMIT 1";

    $stmtCheck = db()->prepare($sqlCheck);
    $stmtCheck->execute([
        ':correo' => $correo,
        ':username' => $username
    ]);

    $usuarioExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    // ==============================================================  
    // CASO 1 → EXISTE Y ESTÁ BORRADO = 1 → ACTUALIZAR Y REACTIVAR  
    // ==============================================================  
    if ($usuarioExistente && $usuarioExistente['borrado'] == 1) {

        $idUser = $usuarioExistente['id'];
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sqlUpdate = "UPDATE usuarios SET
            nombre = :nombre,
            apellido = :apellido,
            correo = :correo,
            dialcode = :dialcode,
            telefono = :telefono,
            username = :username,
            password = :password,
            rol_id = :rol,
            estado = :estado,
            recibe_alertas_stock = :recibe_alertas_stock,
            foto_perfil = :foto_perfil,
            borrado = 0
        WHERE id = :id";

        $stmtUpdate = db()->prepare($sqlUpdate);

        $stmtUpdate->execute([
            ':nombre'   => $nombre,
            ':apellido' => $apellido,
            ':correo'   => $correo,
            ':dialcode' => $dialcode,
            ':telefono' => $telefono,
            ':username' => $username,
            ':password' => $passwordHash,
            ':rol'      => $rol,
            ':estado'   => $estado,
            ':recibe_alertas_stock' => $recibe_alertas_stock,
            ':foto_perfil' => $fotoPerfil ?: $usuarioExistente['foto_perfil'],
            ':id'       => $idUser
        ]);

        // LOGS
        require_once __DIR__ . '/../inc/log_action.php';

        $desc = json_encode([
            'id_reactivado' => $idUser,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'username' => $username
        ], JSON_UNESCAPED_UNICODE);

        log_action('Reactivar Usuario', $desc, 'Usuarios');

        $_SESSION['success'] = "El usuario existía en borrado. Fue reactivado y actualizado correctamente.";
        header("Location: $url/admin/users");
        exit();
    }

    // ==============================================================  
    // CASO 2 → EXISTE PERO NO ESTÁ BORRADO → ERROR  
    // ==============================================================  
    if ($usuarioExistente && $usuarioExistente['borrado'] == 0) {
        $_SESSION['error'] = "El correo o nombre de usuario ya están registrados.";
        header("Location: $url/admin/users");
        exit();
    }

    // ==============================================================  
    // CASO 3 → NO EXISTE → CREAR NUEVO  
    // ==============================================================  
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sqlInsert = "INSERT INTO usuarios 
        (nombre, apellido, correo, dialcode, telefono, username, password, rol_id, estado, recibe_alertas_stock, foto_perfil)
        VALUES
        (:nombre, :apellido, :correo, :dialcode, :telefono, :username, :password, :rol, :estado, :recibe_alertas_stock, :foto_perfil)";

    $stmtInsert = db()->prepare($sqlInsert);

    $stmtInsert->execute([
        ':nombre'   => $nombre,
        ':apellido' => $apellido,
        ':correo'   => $correo,
        ':dialcode' => $dialcode,
        ':telefono' => $telefono,
        ':username' => $username,
        ':password' => $passwordHash,
        ':rol'      => $rol,
        ':estado'   => $estado,
        ':recibe_alertas_stock' => $recibe_alertas_stock,
        ':foto_perfil' => $fotoPerfil
    ]);

    // LOGS
    require_once __DIR__ . '/../inc/log_action.php';

    $desc = json_encode([
        'nombre' => $nombre,
        'apellido' => $apellido,
        'correo' => $correo,
        'username' => $username
    ], JSON_UNESCAPED_UNICODE);

    log_action('Crear Usuario', $desc, 'Usuarios');

    $_SESSION['success'] = "Usuario registrado correctamente.";
    header("Location: $url/admin/users");
    exit();
}
?>










