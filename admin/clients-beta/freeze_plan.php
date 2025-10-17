<?php
require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Falta el id del cliente.']);
        exit;
    }
    
    $id = trim($_POST['id']);
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Actualiza el estado del plan a congelado y asigna la fecha actual en fecha_congelado
        $stmt = $pdo->prepare("UPDATE clientes 
                               SET congelado = 1, fecha_congelado = '$hoy', updated_at = NOW()
                               WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Plan congelado correctamente.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al congelar el plan: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}
?>
