<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver y Editar Usuarios';
include('../login/restriction.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once __DIR__ . '/../../inc/config.php';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error de conexión: " . $e->getMessage();
        header("Location: $url/admin/users");
        exit();
    }

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

    // Verificar duplicados
    $sqlCheck = "SELECT * FROM usuarios WHERE correo = :correo OR username = :username LIMIT 1";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':correo'=>$correo, ':username'=>$username]);

    if ($stmtCheck->rowCount() > 0) {
        $_SESSION['error'] = "El correo o nombre de usuario ya están registrados.";
        header("Location: $url/admin/users");
        exit();
    }

    // Insertar nuevo usuario
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $sqlInsert = "INSERT INTO usuarios 
        (nombre, apellido, correo, dialcode, telefono, username, password, rol_id, estado, recibe_alertas_stock) 
        VALUES (:nombre, :apellido, :correo, :dialcode, :telefono, :username, :password, :rol_id, :estado, :recibe_alertas_stock)";
    $stmtInsert = $pdo->prepare($sqlInsert);
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
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'dialcode' => $dialcode,
            'telefono' => $telefono,
            'username' => $username,
            'rol_id' => $rol,
            'estado' => $estado,
            'recibe_alertas_stock' => $recibe_alertas_stock
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









