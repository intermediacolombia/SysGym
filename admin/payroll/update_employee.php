<?php
session_start();
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y limpiar los datos
    $id = trim($_POST['id']);
    $documento = trim($_POST['documento']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $dialCode = trim($_POST['dialCode']);
    $cargo = trim($_POST['cargo']);
    $salario = trim($_POST['salario']);
    $estado = trim($_POST['estado']);
    $fecha_ingreso = trim($_POST['fecha_ingreso']); // Nuevo campo

    // Validaciones básicas de campos requeridos
    if (empty($id) || empty($documento) || empty($nombre) || empty($apellido) || 
        empty($correo) || empty($telefono) || empty($dialCode) || empty($cargo) || 
        $salario === '' || $estado === '' || empty($fecha_ingreso)) { // Validación del nuevo campo
        $_SESSION['error'] = "Todos los campos son obligatorios.";
        header("Location: employees.php");
        exit;
    }

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "El correo ingresado no es válido.";
        header("Location: employees.php");
        exit;
    }

    // Validar que el salario sea numérico y no negativo
    if (!is_numeric($salario) || $salario < 0) {
        $_SESSION['error'] = "El salario debe ser un número válido y no negativo.";
        header("Location: employees.php");
        exit;
    }

    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_ingreso)) {
        $_SESSION['error'] = "Formato de fecha inválido. Use YYYY-MM-DD.";
        header("Location: employees.php");
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Actualizar datos del empleado, incluyendo el dialCode y fecha_ingreso
        $sql = "UPDATE empleados 
                SET documento = :documento,
                    nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo,
                    telefono = :telefono,
                    dialCode = :dialCode,
                    cargo = :cargo,
                    salario = :salario,
                    estado = :estado,
                    fecha_ingreso = :fecha_ingreso
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':documento' => $documento,
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':correo' => $correo,
            ':telefono' => $telefono,
            ':dialCode' => $dialCode,
            ':cargo' => $cargo,
            ':salario' => $salario,
            ':estado' => $estado,
            ':fecha_ingreso' => $fecha_ingreso, // Nuevo campo
            ':id' => $id
        ]);
		
		// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
			'documento' => $documento,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'telefono' => $telefono,
            'dialCode' => $dialCode,
            'cargo' => $cargo,
            'salario' => $salario,
            'estado' => $estado,
            'fecha_ingreso' => $fecha_ingreso, // Nuevo campo
            'id' => $id
		], JSON_UNESCAPED_UNICODE);
		log_action('Editar Empleado', $desc, 'Nomina');
		// END LOGS		

        $_SESSION['success'] = "Empleado actualizado correctamente.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar empleado: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Método de solicitud inválido.";
}

// Redirigir de vuelta al index
header("Location: employees.php");
exit;
?>
