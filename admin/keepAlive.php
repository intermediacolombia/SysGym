<?php
// keepAlive.php

// Incluimos el archivo de sesión para iniciar o refrescar la sesión
require_once __DIR__ . '/login/session.php';  // Ajusta la ruta según la ubicación de tu session.php

// Si es necesario, puedes agregar más lógica aquí. 
// Por ejemplo, podrías actualizar alguna variable de sesión o registrar la actividad.

// Respondemos con un código 200 OK y un mensaje simple
header('Content-Type: text/plain');
http_response_code(200);
//echo "Sesión actualizada 👌";
?>

