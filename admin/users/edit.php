<?php require_once __DIR__ . '/../login/session.php';?>
<?php 
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');?>
<?php
session_start();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID de usuario no proporcionado.";
    header("Location: $url/admin/users");
    exit();
}

$id = intval($_GET['id']);

// Datos de conexión a la base de datos
require_once __DIR__ . '/../../inc/config.php';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener los datos del usuario por ID
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $_SESSION['error'] = "Usuario no encontrado.";
        header("Location: $url/admin/users");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error de conexión: " . $e->getMessage();
    header("Location: $url/admin/users");
    exit();
}
?>





