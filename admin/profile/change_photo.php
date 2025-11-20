<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$id = $_SESSION['user']['id'];

if (!empty($_FILES['foto_perfil']['name'])) {

    // obtener foto actual
    $stmt = db()->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $fotoActual = $row['foto_perfil'];

    // borrar foto anterior si existe
    if ($fotoActual && file_exists(__DIR__ . '/../../' . $fotoActual)) {
        unlink(__DIR__ . '/../../' . $fotoActual);
    }

    // subir nueva
    $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
    $fileName = 'user_' . time() . '.' . $ext;

    $dir = __DIR__ . '/../../uploads/users/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $destino = $dir . $fileName;
    move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino);

    // guardar ruta en BD
    $stmt = db()->prepare("UPDATE usuarios SET foto_perfil = :foto WHERE id = :id");
    $stmt->execute([
        ':foto' => 'uploads/users/' . $fileName,
        ':id'   => $id
    ]);

    $_SESSION['success'] = "Foto actualizada correctamente.";
}

header("Location: /admin/profile/");
exit();
