<?php 
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');

if (!isset($_POST['id'])) {
    $_SESSION['error'] = "ID de usuario no proporcionado.";
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../../inc/config.php';

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

// ===============================
// OBTENER DATOS ACTUALES DEL USUARIO
// ===============================
$stmtUser = db()->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "El usuario no existe.";
    header("Location: $url/admin/users");
    exit();
}

$fotoActual = $user['foto_perfil'];
$username   = $user['username']; // username NO se puede cambiar nunca

// ===============================
// PROCESAR SUBIDA DE FOTO
// ===============================
$fotoNueva = $fotoActual;

if (!empty($_FILES['foto_perfil']['name'])) {

    // borrar foto anterior si existe
    if ($fotoActual && file_exists(__DIR__ . '/../../' . $fotoActual)) {
        unlink(__DIR__ . '/../../' . $fotoActual);
    }

    $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
    $fileName = 'user_' . time() . '.' . $ext;

    $uploadDir = __DIR__ . '/../../uploads/users/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $destino = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino)) {
        $fotoNueva = 'uploads/users/' . $fileName;
    }
}

// ===============================
// VALIDAR ROL EXISTENTE
// ===============================
$stmtCheckRole = db()->prepare("SELECT id FROM roles WHERE id = :id AND borrado = 0");
$stmtCheckRole->execute([':id' => $rol]);

if (!$stmtCheckRole->fetch(PDO::FETCH_ASSOC)) {
    $_SESSION['error'] = "El rol seleccionado no existe o está inactivo.";
    header("Location: $url/admin/users");
    exit();
}

// ===============================
// VERIFICAR CORREO DUPLICADO EN OTRO USUARIO
// ===============================
$sqlCheckCorreo = "SELECT * FROM usuarios 
                   WHERE correo = :correo AND id != :id";
$stmtCheckCorreo = db()->prepare($sqlCheckCorreo);
$stmtCheckCorreo->execute([
    ':correo' => $correo,
    ':id'     => $id
]);

$duplicados = $stmtCheckCorreo->fetchAll(PDO::FETCH_ASSOC);

foreach ($duplicados as $dup) {

    // Si está activo → error
    if ($dup['borrado'] == 0) {
        $_SESSION['error'] = "El correo ya está registrado por otro usuario.";
        header("Location: $url/admin/users");
        exit();
    }

    // Si está borrado=1 → borrar físicamente
    if ($dup['borrado'] == 1) {

        // borrar foto
        if (!empty($dup['foto_perfil']) && file_exists(__DIR__ . '/../../' . $dup['foto_perfil'])) {
            unlink(__DIR__ . '/../../' . $dup['foto_perfil']);
        }

        // borrar usuario
        $deleteStmt = db()->prepare("DELETE FROM usuarios WHERE id = :id");
        $deleteStmt->execute([':id' => $dup['id']]);
    }
}

// ===============================
// VALIDAR CONTRASEÑA PARA CAMBIO
// ===============================
if (!empty($password)) {
    if ($password !== $confirm) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header("Location: $url/admin/users");
        exit();
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
}

// ===============================
// CONSTRUIR UPDATE DINÁMICO
// ===============================
$sql = "UPDATE usuarios SET 
            nombre = :nombre,
            apellido = :apellido,
            correo = :correo,
            rol_id = :rol_id,
            estado = :estado,
            dialcode = :dialcode,
            telefono = :telefono,
            recibe_alertas_stock = :ras,
            foto_perfil = :foto_perfil";

$params = [
    ':nombre'   => $nombre,
    ':apellido' => $apellido,
    ':correo'   => $correo,
    ':rol_id'   => $rol,
    ':estado'   => $estado,
    ':dialcode' => $dialcode,
    ':telefono' => $telefono,
    ':ras'      => $recibe_alertas_stock,
    ':foto_perfil' => $fotoNueva,
    ':id'       => $id
];

if (!empty($password)) {
    $sql .= ", password = :password";
    $params[':password'] = $passwordHash;
}

$sql .= " WHERE id = :id";

// ===============================
// EJECUTAR UPDATE
// ===============================
$stmtUpdate = db()->prepare($sql);
$stmtUpdate->execute($params);

// ===============================
// LOGS
// ===============================
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

log_action('Editar Usuario', $desc, 'Usuarios');

$_SESSION['success'] = "Usuario actualizado correctamente.";
header("Location: $url/admin/users");
exit();

?>




