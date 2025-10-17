<?php
// insert_asistencia_simple.php

// Variables que se pueden definir manualmente o asignar de otra forma
            // Por ejemplo, el ID del cliente
       // Hora actual (o una hora específica, por ejemplo: '08:30:00')

// Incluir el archivo de configuración (ajusta la ruta según tu estructura)
require_once __DIR__ . '/../inc/config.php';


try {
    // Crear conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

// Preparar la sentencia SQL para insertar en la tabla asistencias
$stmt = $pdo->prepare("INSERT INTO asistencias (idCliente, fecha, hora) VALUES (:idCliente, :fecha, :hora)");

// Ejecutar la sentencia con los valores definidos
$stmt->execute([
    ':idCliente' => $idCliente,
    ':fecha'     => $hoy,
    ':hora'      => $hora
]);

// Mostrar un mensaje simple indicando éxito
//echo "Asistencia insertada correctamente. ID: " . $pdo->lastInsertId();
?>
