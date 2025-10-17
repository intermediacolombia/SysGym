<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // ← por si accedes desde apps externas

require_once __DIR__ . '/../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT identificacion, huella FROM clientes WHERE huella IS NOT NULL AND borrado = 0 AND congelado = 0");
    $stmt->execute();
    $huellas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asegurarse de que los datos estén limpios
    foreach ($huellas as &$h) {
        $h['huella'] = trim($h['huella']);
    }

    echo json_encode([
        "status" => "success",
        "data" => $huellas
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error en la base de datos: " . $e->getMessage()
    ]);
    http_response_code(500);
}
