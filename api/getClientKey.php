<?php
// consulta_cliente_por_huella_y_asistencia.php

header('Content-Type: application/json');

// Incluir configuración (ajusta la ruta según tu estructura)
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

// Verificar que se haya enviado la huella por GET
if (!isset($_GET['key']) || empty($_GET['key'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "No se proporcionó key."
    ]);
    exit;
}

$huella = trim($_GET['key']);

// Consulta: Se obtiene toda la información del cliente (solo los que no están borrados)
// y se une con la tabla "planes" para obtener el nombre del plan asignado.
$stmt = $pdo->prepare("
    SELECT c.*, p.nombre AS plan_nombre
    FROM clientes c
    LEFT JOIN planes p ON c.plan = p.id
    WHERE c.huella = :huella AND c.congelado=0 AND c.borrado = 0
");
$stmt->execute([':huella' => $huella]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
    // Asignar el id del cliente a $idCliente para usarlo en la inserción de asistencia
    $idCliente = $cliente['id'];
    
    // Consultar el penúltimo registro de asistencia (último ingreso anterior)
    $stmtAsist = $pdo->prepare("
        SELECT fecha, hora 
        FROM asistencias 
        WHERE `idCliente` = :idCliente
        ORDER BY id DESC 
        LIMIT 1 OFFSET 1
    ");
    $stmtAsist->execute([':idCliente' => $idCliente]);
    $asistencia = $stmtAsist->fetch(PDO::FETCH_ASSOC);
    
    // Asignar las claves para el JSON (si no hay asistencia anterior se asigna null)
    $cliente['fecha_ultimo_ingreso'] = $asistencia ? $asistencia['fecha'] : null;
    $cliente['hora_ultimo_ingreso']  = $asistencia ? $asistencia['hora']  : null;
    
    // Si el cliente está activo, insertar la asistencia (la nueva asistencia se guardará con la fecha y hora actual)
    if (strtolower($cliente['estado']) === 'activo') {
        // Definir variables que espera el archivo de inserción
       // Incluir el archivo que inserta la asistencia (este archivo debe usar $idCliente, $fecha y $hora)
        include('insert_asistencia.php');
    }
    
    // Construir el array de respuesta con todas las claves solicitadas
    $response = [
        "status" => "success",
        "data"   => [
            //"id"                   => $cliente['id'],
            "identificacion"       => $cliente['identificacion'],
            "nombres"              => $cliente['nombres'],
            "apellidos"            => $cliente['apellidos'],
            //"direccion"            => $cliente['direccion'],
            //"dialCode"             => $cliente['dialCode'],
            //"telefono"             => $cliente['telefono'],
            //"genero"               => $cliente['genero'],
            //"email"                => $cliente['email'],
            "fecha_nacimiento"     => $cliente['fecha_nacimiento'],
            //"rh"                   => $cliente['rh'],
            //"eps"                  => $cliente['eps'],
            //"fracturas"            => $cliente['fracturas'],
            //"alergias"             => $cliente['alergias'],
            //"enfermedades_actuales" => $cliente['enfermedades_actuales'],
            //"observaciones"        => $cliente['observaciones'],
            "estado"               => $cliente['estado'],
            //"created_at"           => $cliente['created_at'],
            //"updated_at"           => $cliente['updated_at'],
            //"plan"                 => $cliente['plan'],
            "pago_plan"            => $cliente['pago_plan'],
            "vencimiento_plan"     => $cliente['vencimiento_plan'],
            //"borrado"              => $cliente['borrado'],
            //"contacto_emergencia"  => $cliente['contacto_emergencia'],
            //"dialCodeEmergencia"   => $cliente['dialCodeEmergencia'],
            //"numero_emergencia"    => $cliente['numero_emergencia'],
            //"notificaciones"       => $cliente['notificaciones'],
            //"huella"               => $cliente['huella'],
            "plan_nombre"          => $cliente['plan_nombre'],
            "fecha_ultimo_ingreso" => $cliente['fecha_ultimo_ingreso'],
            "hora_ultimo_ingreso"  => $cliente['hora_ultimo_ingreso']
        ]
    ];
    
    echo json_encode($response);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Cliente no encontrado."
    ]);
}
?>




