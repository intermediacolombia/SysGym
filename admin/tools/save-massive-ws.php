<?php
// ==========================================
// NO mostrar errores que rompan el JSON
// ==========================================
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sesión manualmente (sin usar session.php que puede redirigir)
session_start();

// Verificar sesión de forma manual
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida']);
    exit;
}

// Cargar solo la configuración de BD
require_once __DIR__ . '/../../inc/config.php';

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientes = isset($_POST['clientes']) ? json_decode($_POST['clientes'], true) : [];
    $mensajeBase = $_POST['mensaje'] ?? '';
    $tieneAdjunto = isset($_POST['tieneAdjunto']) && $_POST['tieneAdjunto'] === 'true';
    
    if (empty($clientes) || empty($mensajeBase)) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit;
    }

    $urlAdjunto = null;

    // ==========================================
    //  Subir archivo adjunto (si existe)
    // ==========================================
    if ($tieneAdjunto && !empty($_FILES['adjunto']['name'])) {
        try {
            $ext = strtolower(pathinfo($_FILES['adjunto']['name'], PATHINFO_EXTENSION));
            
            // Validar extensiones permitidas
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
            if (!in_array($ext, $extensionesPermitidas)) {
                echo json_encode(['status' => 'error', 'message' => 'Tipo de archivo no permitido']);
                exit;
            }

            $fileName = 'masivo_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/../../uploads/send_masive/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $destino = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['adjunto']['tmp_name'], $destino)) {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo subir el archivo adjunto']);
                exit;
            }

            // Ruta relativa para guardar en BD
            $urlAdjunto = 'uploads/send_masive/' . $fileName;

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al procesar adjunto: ' . $e->getMessage()]);
            exit;
        }
    }

    // ==========================================
    //  Insertar en la Base de Datos
    // ==========================================
    try {
        $db = db(); // Usar tu función PDO
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO envios_masivos_ws (nombre, telefono, mensaje, adjunto) VALUES (:nombre, :telefono, :mensaje, :adjunto)");
        
        $totalInsertados = 0;
        
        foreach ($clientes as $cliente) {
            if (!isset($cliente['nombre']) || !isset($cliente['telefono'])) {
                continue; // Saltar clientes con datos incompletos
            }
            
            $nombre = trim($cliente['nombre']);
            
            // Limpiar teléfono: quitar el '+', espacios, guiones, paréntesis
            $telefono = preg_replace('/[^0-9]/', '', $cliente['telefono']);
            
            if (empty($telefono)) {
                continue; // Saltar si no hay teléfono válido
            }
            
            // Personalización del mensaje
            $nombrePila = explode(' ', $nombre)[0];
            $fechaHoy = date('d \d\e F \d\e Y');
            
            $mensajePersonalizado = str_replace(
                ['{nombre}', '{gimnasio}', '{fecha_hoy}'],
                [$nombrePila, defined('NAME_GYM') ? NAME_GYM : 'Gimnasio', $fechaHoy],
                $mensajeBase
            );

            $stmt->execute([
                ':nombre' => $nombre,
                ':telefono' => $telefono,
                ':mensaje' => $mensajePersonalizado,
                ':adjunto' => $urlAdjunto
            ]);
            
            $totalInsertados++;
        }

        $db->commit();

        // ==========================================
        //  LOGS (opcional)
        // ==========================================
        if (file_exists(__DIR__ . '/../inc/log_action.php')) {
            try {
                require_once __DIR__ . '/../inc/log_action.php';
                $desc = json_encode([
                    'total' => $totalInsertados, 
                    'adjunto' => $urlAdjunto ? 'Sí' : 'No'
                ], JSON_UNESCAPED_UNICODE);
                log_action('Envío Masivo WhatsApp', $desc, 'WhatsApp');
            } catch (Exception $e) {
                // Ignorar errores de log
            }
        }

        echo json_encode([
            'status' => 'success', 
            'message' => 'Guardado correctamente', 
            'total' => $totalInsertados
        ]);

    } catch (PDOException $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
exit;