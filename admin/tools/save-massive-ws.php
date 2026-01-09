<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientes = $_POST['clientes'] ?? [];
    $mensajeBase = $_POST['mensaje'] ?? '';
    $adjuntoBase64 = $_POST['adjunto'] ?? null;
    $adjuntoNombre = $_POST['adjuntoNombre'] ?? null;
    
    if (empty($clientes) || empty($mensajeBase)) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit;
    }

    $urlAdjunto = null;

    // 1. Gestionar Carpeta y Archivo Adjunto
    if ($adjuntoBase64) {
        $dir = __DIR__ . '/../../uploads/send_masive/';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        // Limpiar nombre de archivo y generar uno único
        $extension = pathinfo($adjuntoNombre, PATHINFO_EXTENSION);
        $nuevoNombre = time() . '_' . uniqid() . '.' . $extension;
        $rutaFisica = $dir . $nuevoNombre;
        
        // Decodificar Base64 y guardar
        $data = explode(',', $adjuntoBase64);
        if (count($data) > 1) {
            file_put_contents($rutaFisica, base64_decode($data[1]));
            // URL relativa para la base de datos
            $urlAdjunto = 'uploads/send_masive/' . $nuevoNombre;
        }
    }

    // 2. Insertar en la Base de Datos
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO envios_masivos_ws (nombre, telefono, mensaje, adjunto) VALUES (?, ?, ?, ?)");
        
        foreach ($clientes as $cliente) {
            $nombre = $cliente['nombre'];
            // Limpiar teléfono: quitar el '+' y espacios
            $telefono = str_replace(['+', ' '], '', $cliente['telefono']);
            
            // Personalizar mensaje para este cliente
            $nombrePila = explode(' ', trim($nombre))[0];
            $mensajePersonalizado = str_replace('{nombre}', $nombrePila, $mensajeBase);
            $mensajePersonalizado = str_replace('{gimnasio}', defined('NAME_GYM') ? NAME_GYM : 'Gimnasio', $mensajePersonalizado);

            $stmt->bind_param("ssss", $nombre, $telefono, $mensajePersonalizado, $urlAdjunto);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Mensajes guardados correctamente']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}