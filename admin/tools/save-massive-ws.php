<?php
// Desactivar visualización de errores para que no rompan el JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');

// Limpiar cualquier salida previa (espacios, warnings)
ob_clean();

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
    if ($adjuntoBase64 && $adjuntoNombre) {
        // Ruta absoluta para el servidor
        $dir = __DIR__ . '/../../uploads/send_masive/';
        
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $extension = pathinfo($adjuntoNombre, PATHINFO_EXTENSION);
        $nuevoNombre = time() . '_' . uniqid() . '.' . $extension;
        $rutaFisica = $dir . $nuevoNombre;
        
        $data = explode(',', $adjuntoBase64);
        $content = (count($data) > 1) ? base64_decode($data[1]) : base64_decode($data[0]);
        
        if (file_put_contents($rutaFisica, $content)) {
            // URL relativa para guardar en BD
            $urlAdjunto = 'uploads/send_masive/' . $nuevoNombre;
        }
    }

    // 2. Insertar en la Base de Datos
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO envios_masivos_ws (nombre, telefono, mensaje, adjunto) VALUES (?, ?, ?, ?)");
        
        foreach ($clientes as $cliente) {
            $nombre = $cliente['nombre'];
            // Limpiar teléfono: quitar el '+', espacios y guiones
            $telefono = preg_replace('/[^0-9]/', '', $cliente['telefono']);
            
            // Personalización básica
            $nombrePila = explode(' ', trim($nombre))[0];
            $mensajePersonalizado = str_replace('{nombre}', $nombrePila, $mensajeBase);
            $mensajePersonalizado = str_replace('{gimnasio}', defined('NAME_GYM') ? NAME_GYM : 'Gimnasio', $mensajePersonalizado);

            $stmt->bind_param("ssss", $nombre, $telefono, $mensajePersonalizado, $urlAdjunto);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Guardado correctamente']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error BD: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
exit;