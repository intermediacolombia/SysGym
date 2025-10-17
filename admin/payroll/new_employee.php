<?php
session_start();
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y limpiar los datos
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
    if (empty($documento) || empty($nombre) || empty($apellido) || empty($correo) || 
        empty($telefono) || empty($dialCode) || empty($cargo) || $salario === '' || 
        $estado === '' || empty($fecha_ingreso)) { // Validación del nuevo campo
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

        // Verificar si ya existe un empleado con el mismo documento, correo o teléfono
        $sql = "SELECT * FROM empleados WHERE documento = :documento OR correo = :correo OR telefono = :telefono LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':documento' => $documento,
            ':correo' => $correo,
            ':telefono' => $telefono
        ]);
        $existingEmployee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingEmployee) {
            // Si el empleado ya existe y está activo (borrado = 0), no se permiten duplicados
            if ($existingEmployee['borrado'] == 0) {
                $_SESSION['error'] = "Ya existe un empleado con alguno de los datos ingresados.";
                header("Location: employees.php");
                exit;
            } else {
                /* 
                 * Si se encuentra un empleado pero está marcado como borrado (borrado = 1)
                 * Se asume que alguno de sus datos ha cambiado, por lo que se actualizan
                 * y se re-activa el registro (borrado = 0)
                 */
                $sql_update = "UPDATE empleados 
                               SET documento = :documento,
                                   nombre = :nombre,
                                   apellido = :apellido,
                                   correo = :correo,
                                   telefono = :telefono,
                                   dialCode = :dialCode,
                                   cargo = :cargo,
                                   salario = :salario,
                                   estado = :estado,
                                   fecha_ingreso = :fecha_ingreso,
                                   borrado = 0
                               WHERE id = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([
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
                    ':id' => $existingEmployee['id']
                ]);
                $_SESSION['success'] = "Empleado registrado correctamente.";
                header("Location: employees.php");
                exit;
            }
        } else {
            // No existe ningún empleado con esos datos, se inserta uno nuevo
            $sql_insert = "INSERT INTO empleados 
                          (documento, nombre, apellido, correo, telefono, dialCode, 
                           cargo, salario, estado, fecha_ingreso, borrado)
                           VALUES 
                          (:documento, :nombre, :apellido, :correo, :telefono, :dialCode, 
                           :cargo, :salario, :estado, :fecha_ingreso, 0)"; // Nuevo campo
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                ':documento' => $documento,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':correo' => $correo,
                ':telefono' => $telefono,
                ':dialCode' => $dialCode,
                ':cargo' => $cargo,
                ':salario' => $salario,
                ':estado' => $estado,
                ':fecha_ingreso' => $fecha_ingreso // Nuevo campo
            ]);
            $_SESSION['success'] = "Empleado registrado correctamente.";
            header("Location: employees.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al registrar o actualizar el empleado: " . $e->getMessage();
        header("Location: employees.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Método de solicitud inválido.";
    header("Location: employees.php");
    exit;
}
?>