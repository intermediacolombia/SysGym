<?php
// Tu conexión a la base de datos
$conexion = new mysqli("localhost", "coomoqui_pqrs", "T(CT[cnhaaQJEODD", "coomoqui_pqrs");

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}

// Consulta SQL para obtener los correos electrónicos
$sql = "SELECT correo_electronico FROM usuarios WHERE enviar_emails = 1 AND estado = 1 AND borrado = 1";

// Ejecutar la consulta
$resultado = $conexion->query($sql);

// Verificar si se obtuvieron resultados
if ($resultado->num_rows > 0) {
    // Array para almacenar los correos electrónicos
    $destinatarios = array();

    // Iterar sobre los resultados y agregar los correos electrónicos al array
    while ($fila = $resultado->fetch_assoc()) {
        $destinatarios[] = $fila['correo_electronico'];
    }

    // Liberar el resultado
    $resultado->free();
} else {
    echo "No se encontraron usuarios con enviar_emails = 1";
}

// Cerrar la conexión
$conexion->close();

// Imprimir los correos electrónicos obtenidos (para verificar)

?>
