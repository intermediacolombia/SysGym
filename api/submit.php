<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
        exit;
    }

    // Normalizar algunos campos para evitar discrepancias
    $data['identificacion'] = trim($data['identificacion']);
    $data['telefono'] = trim($data['telefono']);
    $data['dialCode'] = trim($data['dialCode']);
	$data['dialCodeEmergencia'] = trim($data['dialCodeEmergencia']);
    $data['email'] = trim($data['email']);

    // Token estático esperado
    $expectedToken = 'xEveK6O0mWc9vFCjQGl9IJb1rHposralGDq8M5CFCbM2AxO7UVsCQidQsw5A5IqJv9UXdChlo8mZr8gTQ9cyWTA6pC11oG6a3JR0CRPX78VbLZO7eV85ZAf0Gq7b38kZ12P2OVgl5BDtMyagzi7GSYqr2mTzh1gEL7YZgG77RaEPHOg7OaQXp6FSBqlB1OEOL63PpC0w757RsbywczQqRO5muyZFKRz7btTHr1qxmcfFDLYMD7fgpup6gV1yiVpDI2j89p3GLld9DB3TDuWQOl7a7KVgHCSrED39kQuIe6tYhJbpwdh9KQ3g9WWR9QxmCk2JazeiOruQR7MPa9uzLr1zxMxH0XhRVfICGcupCs7uBkXb8A4zm70IPflPCJxvxGkxjGHWmwg08aXSP95kESBfA1LJXC9Iia7JOb8bcviCSVO0HywtCsmMxRBycygo149kiYigzkPjLaqA7hYRwEkYHe1Lv2Er41sbpLimvF52aLHsrr8Pt09KtaESwtoJ';

    // Validar el token
    if (!isset($data['token']) || $data['token'] !== $expectedToken) {
        echo json_encode(['status' => 'error', 'message' => 'Token inválido o no proporcionado']);
        exit;
    }

    // Configuración de la base de datos
    require_once __DIR__ . '/../inc/config.php';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		
		
		
		// Verificar si el cliente está preinscrito y eliminarlo si existe
$sqlPreinscrito = "SELECT id FROM clientes_preinscritos WHERE identificacion = :identificacion";
$stmtPreinscrito = $pdo->prepare($sqlPreinscrito);
$stmtPreinscrito->execute([':identificacion' => $data['identificacion']]);
$preinscrito = $stmtPreinscrito->fetch(PDO::FETCH_ASSOC);

if ($preinscrito) {
    $sqlDeletePre = "DELETE FROM clientes_preinscritos WHERE id = :id";
    $stmtDeletePre = $pdo->prepare($sqlDeletePre);
    $stmtDeletePre->execute([':id' => $preinscrito['id']]);
}

		

        // Verificar duplicados en teléfono y correo (excluyendo al cliente actual)
        $errorMessages = [];

// Validar duplicidad del teléfono (siempre se valida)
$sqlCheckPhone = "SELECT id FROM clientes WHERE telefono = :telefono AND dialCode = :dialCode AND identificacion != :identificacion";
$stmtCheckPhone = $pdo->prepare($sqlCheckPhone);
$stmtCheckPhone->execute([
    ':telefono' => $data['telefono'],
    ':dialCode' => $data['dialCode'],
    ':identificacion' => $data['identificacion']
]);
if ($stmtCheckPhone->fetch(PDO::FETCH_ASSOC)) {
    $errorMessages[] = 'El teléfono ya está asociado a otro cliente';
}

// Validar duplicidad del correo solo si se proporcionó un email
if (!empty($data['email'])) {
    $sqlCheckEmail = "SELECT id FROM clientes WHERE email = :email AND identificacion != :identificacion";
    $stmtCheckEmail = $pdo->prepare($sqlCheckEmail);
    $stmtCheckEmail->execute([
        ':email' => $data['email'],
        ':identificacion' => $data['identificacion']
    ]);
    if ($stmtCheckEmail->fetch(PDO::FETCH_ASSOC)) {
        $errorMessages[] = 'El correo electrónico ya está asociado a otro cliente';
    }
}

if (!empty($errorMessages)) {
    echo json_encode(['status' => 'error', 'message' => implode(' y ', $errorMessages)]);
    exit;
}


        // Verificar si el cliente ya existe por identificación
        $sqlCheck = "SELECT id FROM clientes WHERE identificacion = :identificacion";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':identificacion' => $data['identificacion']]);
        $cliente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
    // Actualizar datos del cliente (excepto la identificación)
    $sqlUpdate = "UPDATE clientes SET 
        nombres = :nombres,
        apellidos = :apellidos,
        direccion = :direccion,
        dialCode = :dialCode,
        telefono = :telefono,
        genero = :genero,
        email = :email,
        fecha_nacimiento = :fecha_nacimiento,
        rh = :rh,
        eps = :eps,
        fracturas = :fracturas,
        alergias = :alergias,
        enfermedades_actuales = :enfermedades_actuales,
        observaciones = :observaciones,
        contacto_emergencia = :contacto_emergencia,
		dialCodeEmergencia = :dialCodeEmergencia,
        numero_emergencia = :numero_emergencia,
        notificaciones = :notificaciones,
		estado = 'activo',
        borrado = 0,
        updated_at = NOW()
    WHERE identificacion = :identificacion";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':nombres'                => $data['nombres'],
        ':apellidos'              => $data['apellidos'],
        ':direccion'              => $data['direccion'],
        ':dialCode'               => $data['dialCode'],
        ':telefono'               => $data['telefono'],
        ':genero'                 => $data['genero'],
        ':email'                  => $data['email'],
        ':fecha_nacimiento'       => $data['fecha_nacimiento'],
        ':rh'                     => $data['rh'],
        ':eps'                    => $data['eps'],
        ':fracturas'              => $data['fracturas'],
        ':alergias'               => $data['alergias'],
        ':enfermedades_actuales'  => $data['enfermedades_actuales'],
        ':observaciones'          => $data['observaciones'],
		':dialCodeEmergencia'    => $data['dialCodeEmergencia'],
        ':contacto_emergencia'    => $data['contacto_emergencia'],
        ':numero_emergencia'      => $data['numero_emergencia'],
        ':notificaciones'         => $data['notificaciones'],
        ':identificacion'         => $data['identificacion']
    ]);
    $clienteId = $cliente['id'];
} else {
    // Insertar un nuevo cliente
    $sqlInsert = "INSERT INTO clientes (
        identificacion, nombres, apellidos, direccion, dialCode, telefono, genero, email, fecha_nacimiento, rh, 
        eps, fracturas, alergias, enfermedades_actuales, observaciones, contacto_emergencia, dialCodeEmergencia, numero_emergencia, notificaciones
    ) VALUES (
        :identificacion, :nombres, :apellidos, :direccion, :dialCode, :telefono, :genero, :email, :fecha_nacimiento, :rh, 
        :eps, :fracturas, :alergias, :enfermedades_actuales, :observaciones, :contacto_emergencia,  :dialCodeEmergencia, :numero_emergencia, :notificaciones
    )";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        ':identificacion'         => $data['identificacion'],
        ':nombres'                => $data['nombres'],
        ':apellidos'              => $data['apellidos'],
        ':direccion'              => $data['direccion'],
        ':dialCode'               => $data['dialCode'],
        ':telefono'               => $data['telefono'],
        ':genero'                 => $data['genero'],
        ':email'                  => $data['email'],
        ':fecha_nacimiento'       => $data['fecha_nacimiento'],
        ':rh'                     => $data['rh'],
        ':eps'                    => $data['eps'],
        ':fracturas'              => $data['fracturas'],
        ':alergias'               => $data['alergias'],
        ':enfermedades_actuales'  => $data['enfermedades_actuales'],
        ':observaciones'          => $data['observaciones'],
        ':contacto_emergencia'    => $data['contacto_emergencia'],
		':dialCodeEmergencia'     => $data['dialCodeEmergencia'],
        ':numero_emergencia'      => $data['numero_emergencia'],
        ':notificaciones'         => $data['notificaciones']
    ]);
    $clienteId = $pdo->lastInsertId();
}


        // Verificar si ya existe un consentimiento informado para este cliente.
        $sqlCheckConsent = "SELECT id FROM formularios WHERE cliente_id = :cliente_id";
        $stmtCheckConsent = $pdo->prepare($sqlCheckConsent);
        $stmtCheckConsent->execute([':cliente_id' => $clienteId]);
        $consentRecord = $stmtCheckConsent->fetch(PDO::FETCH_ASSOC);
        
        // Variable para forzar el envío de notificaciones
        $envioForzado = false;
        $formulario_id = null;

        if ($consentRecord) {
            // Si ya existe, se verifica si se desea reemplazar.
            if (!isset($data['replaceConsent']) || $data['replaceConsent'] !== true) {
                echo json_encode([
                    'status' => 'confirm',
                    'message' => 'Ya existe un consentimiento informado para este cliente. ¿Desea reemplazarlo?'
                ]);
                exit;
            } else {
                // Borrar todos los formularios existentes para este cliente
                $sqlDeleteConsent = "DELETE FROM formularios WHERE cliente_id = :cliente_id";
                $stmtDeleteConsent = $pdo->prepare($sqlDeleteConsent);
                $stmtDeleteConsent->execute([':cliente_id' => $clienteId]);
                
                // Insertar el nuevo consentimiento informado y capturar su id
                $sqlInsertConsent = "INSERT INTO formularios (cliente_id, fecha, firma_digital_cliente) VALUES (:cliente_id, :fecha, :firma_digital_cliente)";
                $stmtInsertConsent = $pdo->prepare($sqlInsertConsent);
                $stmtInsertConsent->execute([
                    ':cliente_id' => $clienteId,
                    ':fecha' => date('Y-m-d'),
                    ':firma_digital_cliente' => $data['firma_digital_cliente']
                ]);
                $formulario_id = $pdo->lastInsertId();
                $envioForzado = true;
            }
        } else {
            // Insertar el nuevo consentimiento si no existe ninguno previo y capturar su id
            $sqlInsertConsent = "INSERT INTO formularios (cliente_id, fecha, firma_digital_cliente) VALUES (:cliente_id, :fecha, :firma_digital_cliente)";
            $stmtInsertConsent = $pdo->prepare($sqlInsertConsent);
            $stmtInsertConsent->execute([
                ':cliente_id' => $clienteId,
                ':fecha' => date('Y-m-d'),
                ':firma_digital_cliente' => $data['firma_digital_cliente']
            ]);
            $formulario_id = $pdo->lastInsertId();
            $envioForzado = true;
        }

        // Enviar notificaciones (correo y WhatsApp) usando $formulario_id, si se forzó el envío
        if ($envioForzado) {
            // Asegurarse de que $formulario_id esté disponible para los archivos incluidos
             // Solo incluir el envío de correo si se proporcionó un email
			if (!empty($data['email'])) {
				include('../mailer/consent.php');
			}
            ob_start();
            include('../whatsapp/consent.php');       
            ob_end_clean();
        }

        echo json_encode(['status' => 'success', 'message' => 'Datos guardados correctamente']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar los datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>






