<?php 
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');


if (!isset($_POST['id'])) {
    $_SESSION['error'] = "ID de usuario no proporcionado.";
    header("Location: index.php");
    exit();
}

$id       = intval($_POST['id']);
$nombre   = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$correo   = trim($_POST['correo'] ?? '');
$rol      = intval($_POST['rol'] ?? 0);
$estado   = trim($_POST['estado'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$dialcode = trim($_POST['dialcode'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$recibe_alertas_stock = isset($_POST['recibe_alertas_stock']) ? 1 : 0;

// Validar rol
if ($rol <= 0) {
    $_SESSION['error'] = "Rol inválido.";
    header("Location: $url/admin/users");
    exit();
}

// Datos de conexión
require_once __DIR__ . '/../../inc/config.php';

try {

    // Obtener datos actuales del usuario (incluye foto actual)
    $stmtUser = db()->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id LIMIT 1");
    $stmtUser->execute([':id' => $id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        $_SESSION['error'] = "El usuario no existe.";
        header("Location: $url/admin/users");
        exit();
    }

    $fotoActual = $userData['foto_perfil'];

    // ==============================================================  
    // SUBIR NUEVA FOTO SI VIENE UNA
    // ==============================================================  
    $fotoNueva = $fotoActual; // Por defecto mantener la actual

    if (!empty($_FILES['foto_perfil']['name'])) {

        // Eliminar foto anterior si existe
        if ($fotoActual && file_exists(__DIR__ . '/../../' . $fotoActual)) {
            unlink(__DIR__ . '/../../' . $fotoActual);
        }

        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . time() . '.' . $ext;

        $uploadDir = __DIR__ . '/../../uploads/users/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destino = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino)) {
            $fotoNueva = 'uploads/users/' . $fileName;
        }
    }

    // Verificar que el rol exista
    $stmtCheckRole = db()->prepare("SELECT id FROM roles WHERE id = :id AND borrado = 0");
    $stmtCheckRole->execute([':id' => $rol]);
    if (!$stmtCheckRole->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['error'] = "El rol seleccionado no existe o está inactivo.";
        header("Location: $url/admin/users");
        exit();
    }

    // ======================================================
    // Construir consulta SQL dinámica (con o sin contraseña)
    // ======================================================
    if (!empty($password)) {
        if ($password !== $confirm) {
            $_SESSION['error'] = "Las contraseñas no coinciden.";
            header("Location: $url/admin/users");
            exit();
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE usuarios SET 
                    nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo,
                    rol_id = :rol_id,
                    estado = :estado,
                    dialcode = :dialcode,
                    telefono = :telefono,
                    recibe_alertas_stock = :recibe_alertas_stock,
                    foto_perfil = :foto_perfil,
                    password = :password
                WHERE id = :id";

        $params = [
            ':nombre'   => $nombre,
            ':apellido' => $apellido,
            ':correo'   => $correo,
            ':rol_id'   => $rol,
            ':estado'   => $estado,
            ':dialcode' => $dialcode,
            ':telefono' => $telefono,
            ':recibe_alertas_stock' => $recibe_alertas_stock,
            ':foto_perfil' => $fotoNueva,
            ':password' => $passwordHash,
            ':id'       => $id
        ];
    } else {

        $sql = "UPDATE usuarios SET 
                    nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo,
                    rol_id = :rol_id,
                    estado = :estado,
                    dialcode = :dialcode,
                    telefono = :telefono,
                    recibe_alertas_stock = :recibe_alertas_stock,
                    foto_perfil = :foto_perfil
                WHERE id = :id";

        $params = [
            ':nombre'   => $nombre,
            ':apellido' => $apellido,
            ':correo'   => $correo,
            ':rol_id'   => $rol,
            ':estado'   => $estado,
            ':dialcode' => $dialcode,
            ':telefono' => $telefono,
            ':recibe_alertas_stock' => $recibe_alertas_stock,
            ':foto_perfil' => $fotoNueva,
            ':id'       => $id
        ];
    }

    // Ejecutar actualización
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    // ======================================================
    // Registrar LOG
    // ======================================================
    require_once __DIR__ . '/../inc/log_action.php';
    $desc = json_encode([
        'id'       => $id,
        'nombre'   => $nombre,
        'apellido' => $apellido,
        'correo'   => $correo,
        'rol_id'   => $rol,
        'estado'   => $estado,
        'dialcode' => $dialcode,
        'telefono' => $telefono,
        'recibe_alertas_stock' => $recibe_alertas_stock,
        'foto_perfil' => $fotoNueva
    ], JSON_UNESCAPED_UNICODE);
    log_action('Edición de Usuario', $desc, 'Usuarios');

    $_SESSION['success'] = "Usuario actualizado correctamente.";
    header("Location: $url/admin/users");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Error al actualizar: " . $e->getMessage();
    header("Location: $url/admin/users");
    exit();
}
?>



