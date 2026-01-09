<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

// Verificar sesión
if (!isset($_SESSION['user'])) {
    header("Location: $url/admin/login");
    exit();
}

$userId = $_SESSION['user']['id'];

// Validar archivo
if (empty($_FILES['foto_perfil']['name'])) {
    $_SESSION['error'] = "Debe seleccionar una imagen.";
    header("Location: $url/admin/profile");
    exit();
}

try {

    // Obtener foto actual
    $stmt = db()->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $fotoActual = $userRow['foto_perfil'] ?? null;

    // ==========================================
    //  Eliminar foto anterior
    // ==========================================
    if ($fotoActual && file_exists(__DIR__ . '/../../' . $fotoActual)) {
        unlink(__DIR__ . '/../../' . $fotoActual);
    }

    // ==========================================
    //  Subir nueva foto
    // ==========================================
    $ext = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
    $fileName = 'user_' . time() . '.' . $ext;

    $uploadDir = __DIR__ . '/../../uploads/users/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $destino = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino)) {
        $_SESSION['error'] = "No se pudo subir la imagen.";
        header("Location: $url/admin/profile");
        exit();
    }

    // Ruta para guardar en BD
    $nuevaRuta = 'uploads/users/' . $fileName;

    // ==========================================
    //  Guardar en BD
    // ==========================================
    $stmtUpdate = db()->prepare("
        UPDATE usuarios 
        SET foto_perfil = :foto 
        WHERE id = :id
    ");

    $stmtUpdate->execute([
        ':foto' => $nuevaRuta,
        ':id'   => $userId
    ]);


    // ==========================================
    //  Actualizar sesión para reflejar cambio
    // ==========================================
    $_SESSION['user']['foto_perfil'] = $nuevaRuta;

    // ==========================================
    //  LOGS
    // ==========================================
    require_once __DIR__ . '/../inc/log_action.php';
    $desc = json_encode(['id' => $userId], JSON_UNESCAPED_UNICODE);
    log_action('Editar Foto de Perfil', $desc, 'Perfil');

    $_SESSION['success'] = "Foto de perfil actualizada correctamente.";
    header("Location: $url/admin/profile");
    exit();

} catch (PDOException $e) {

    $_SESSION['error'] = "Error al actualizar la foto: " . $e->getMessage();
    header("Location: $url/admin/profile");
    exit();

}
?>