<?php
require_once __DIR__ . '/../../inc/config.php';
session_start();


// Verificar que el usuario esté logueado
if (!isset($_SESSION['user'])) {
    header("Location: $url/admin/login");
    exit();
}

$userId = $_SESSION['user']['id'];

// Recoger datos del formulario
$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';
$confirmNewPassword = $_POST['confirmNewPassword'] ?? '';

// Validar que los campos no estén vacíos
if (empty($currentPassword) || empty($newPassword) || empty($confirmNewPassword)) {
    $_SESSION['error'] = "Todos los campos son obligatorios.";
    header("Location: $url/admin/profile");
    exit();
}

// Verificar que la nueva contraseña y su confirmación coincidan
if ($newPassword !== $confirmNewPassword) {
    $_SESSION['error'] = "Las nuevas contraseñas no coinciden.";
    header("Location: $url/admin/profile");
    exit();
}

// Datos de conexión


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener la contraseña actual del usuario
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        $_SESSION['error'] = "La contraseña actual es incorrecta.";
        header("Location: $url/admin/profile");
        exit();
    }
    
    // Actualizar la contraseña
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET password = :password WHERE id = :id");
    $stmtUpdate->execute([':password' => $newPasswordHash, ':id' => $userId]);
    
    $_SESSION['success'] = "Contraseña actualizada correctamente.";
    header("Location: $url/admin/profile");
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al actualizar la contraseña: " . $e->getMessage();
    header("Location: $url/admin/profile");
    exit();
}
?>


