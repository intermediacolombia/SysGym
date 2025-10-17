<?php

// registrar_huella.php

header('Content-Type: application/json');
require_once __DIR__ . '/../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        "status"  => "error",
        "message" => "Error de conexión: " . $e->getMessage()
    ]);
    exit;
}

// Obtener los datos
$identificacion = $_POST['identificacion'] ?? null;
$huella = $_POST['huella'] ?? null;

if (!$identificacion || !$huella) {
    echo json_encode([
        "status"  => "error",
        "message" => "Faltan datos (identificación o huella)."
    ]);
    exit;
}

// Validar existencia del cliente
$stmtCheck = $pdo->prepare("SELECT * FROM clientes WHERE identificacion = ?");
$stmtCheck->execute([$identificacion]);

if (!$stmtCheck->fetch()) {
    echo json_encode([
        "status"  => "error",
        "message" => "Cliente con esa identificación $identificacion no existe."
    ]);
    exit;
}

// Actualizar huella
$stmtUpdate = $pdo->prepare("UPDATE clientes SET huella = :huella WHERE identificacion = :identificacion");
$success = $stmtUpdate->execute([
    ':huella'         => $huella,
    ':identificacion' => $identificacion
]);

echo json_encode([
    "status"  => $success ? "success" : "error",
    "message" => $success ? "Huella registrada correctamente." : "Error al guardar la huella."
]);

?>

