<?php
require_once __DIR__ . '/../inc/config.php'; // Incluir el archivo de configuración

try {
    // Crear la conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

// Verificar si se proporcionó un ID de nómina
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Falta el ID de la nómina.");
}

$idNomina = $_GET['id'];

// Consultar los datos de la tabla `nomina` usando el ID proporcionado
$stmt = $pdo->prepare("SELECT * FROM nomina WHERE id_nomina = :id");
$stmt->execute([':id' => $idNomina]);
$nomina = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si se encontró la nómina
if (!$nomina) {
    die("Error: Nómina no encontrada.");
}

// Configurar el locale para fechas en español
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');

// Función para formatear las fechas como "día de mes del año"
function formatearFecha($fecha) {
    $timestamp = strtotime($fecha); // Convertir la fecha a timestamp
    $dia = date('d', $timestamp);   // Día del mes (con ceros iniciales)
    $mes = ucfirst(strftime('%B', $timestamp)); // Nombre del mes en español
    $anio = date('Y', $timestamp);  // Año
    return "$dia de $mes del $anio"; // Formato final: "01 de septiembre del 2023"
}

// Formatear las fechas
$fechaGeneracion = formatearFecha($nomina['fecha_generacion']);
$fechaInicioPago = formatearFecha($nomina['fecha_inicio_pago']);
$fechaFinPago = formatearFecha($nomina['fecha_fin_pago']);

// Formatear el valor pagado con separadores de miles y dos decimales
$valorPagadoFormateado = number_format($nomina['valor_pagado'], 2, ',', '.');

// Cargar el logo (verifica que la ruta sea correcta)
$logoPath = $_SERVER['DOCUMENT_ROOT'] . '/pdf/images/logo-activ.png';
if (!file_exists($logoPath)) {
    die("Error: No se encontró el logo en la ruta: " . $logoPath);
}
$logoData = base64_encode(file_get_contents($logoPath));
$logoDataURI = 'data:image/png;base64,' . $logoData;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Nómina</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            /*background-color: #f9f9f9;*/
        }
        .recibo {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
		
		.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.header img {
    max-width: 150px;
    height: auto;
    margin-right: 10px;
}

.header .empresa-info {
    text-align: left;
}

.header h1 {
    font-size: 18px;
    margin: 0;
}

.header p {
    font-size: 12px;
    margin: 2px 0;
}
        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .tabla th, .tabla td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .tabla th {
            background-color: #E21F0C;
            color: white;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="recibo">
        <!-- Encabezado -->
        <div class="header">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- Columna izquierda: Logo -->
            <td style="text-align: left; padding-right: 20px;">
                <img src="<?php echo $logoDataURI; ?>" alt="Activ Gym" style="max-width: 150px; height: auto;">
            </td>

            <!-- Columna derecha: Información de la empresa -->
            <td style="text-align: right;">
                <h1 style="margin: 0; font-size: 18px;">Recibo de Pago Nómina Nro. <?php echo $idNomina; ?></h1>
                <p style="margin: 2px 0; font-size: 12px;"><strong>Fecha de generación:</strong> <?php echo $fechaGeneracion; ?></p>
            </td>
        </tr>
    </table>
</div>

        <!-- Datos del empleado -->
        <table class="tabla">
            <tr>
                <th colspan="2">Datos del Empleado</th>
            </tr>
            <tr>
                <td><strong>Nombre</strong></td>
                <td><?php echo htmlspecialchars($nomina['nombre_empleado']); ?></td>
            </tr>
        </table>

        <!-- Datos del pago -->
        <table class="tabla">
            <tr>
                <th colspan="4">Detalles del Pago</th>
            </tr>
            <tr>
                <td><strong>Inicio Periodo</strong></td>
                <td><?php echo $fechaInicioPago; ?></td>
                <td><strong>Fin Periodo</strong></td>
                <td><?php echo $fechaFinPago; ?></td>
            </tr>
            <tr>
                <td><strong>Días laborados</strong></td>
                <td><?php echo htmlspecialchars($nomina['total_dias_pagados']); ?> días</td>
                <td><strong>Valor pagado</strong></td>
                <td>$<?php echo $valorPagadoFormateado; ?></td>
            </tr>
        </table>

        <!-- Resumen total -->
        <table class="tabla" style="margin-top: 20px;">
            <tr>
                <th colspan="4">Resumen de Pagos</th>
            </tr>
            <tr>
                <td><strong>Total a pagar</strong></td>
                <td colspan="3">$<?php echo $valorPagadoFormateado; ?></td>
            </tr>
        </table>

        <!-- Pie de página -->
        <div class="footer">
            <p>Este recibo es una constancia de pago válida para fines laborales.</p>
            <p>Gracias por su dedicación y trabajo.</p>
        </div>
    </div>
</body>
</html>