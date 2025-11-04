<?php
require_once __DIR__ . '/../inc/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Falta el id de la factura.");
}

$facturaId = $_GET['id'];

// Se consulta la tabla facturas utilizando el id
$stmt = $pdo->prepare("SELECT * FROM facturas WHERE id = :id");
$stmt->execute([':id' => $facturaId]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factura) {
    die("Factura no encontrada.");
}

// Formatear el valor sin decimales (por ejemplo, redondeando)
$valorFormateado = number_format($factura['valor'], 0, ',', '.');

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
  <title>Recibo de Pago - Activ Gym</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      /*background-color: #f9f9f9;*/
    }
    .invoice-container {
      max-width: 100%;
      margin: 0 auto;
      background-color: #fff;
      border: 1px solid #ddd;
      padding: 20px;
    }
    .header, .footer {
      text-align: center;
      margin-bottom: 20px;
    }
    .header h1 {
      margin: 5px 0;
      font-size: 24px;
    }
    .header p, .footer p {
      margin: 5px 0;
      font-size: 14px;
    }
    .section {
      margin-bottom: 20px;
    }
    .section h2 {
      margin-bottom: 10px;
      font-size: 20px;
    }
    .section p {
      margin: 5px 0;
      font-size: 14px;
    }
    .client-details {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 10px;
  display: table;
  width: 100%;
  table-layout: fixed;
  margin-bottom: 20px;
}

.client-details .client-column {
  display: table-cell;
  vertical-align: top;
  white-space: nowrap; /* Evita saltos de línea */
  padding: 5px;
}

.client-details p {
  margin: 0; /* Quita márgenes para evitar saltos de línea */
}

.client-details p span {
  font-weight: bold; /* Mantiene el texto en negrita para los títulos */
}

    .service-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .service-table th, .service-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
      font-size: 14px;
    }
    .service-table th {
      background-color: var(--system-color-primary);
      color: #fff;
    }
    .total {
      text-align: right;
      font-size: 16px;
      font-weight: bold;
    }
    hr {
      border: none;
      border-top: 1px solid #ddd;
      margin: 20px 0;
    }
  </style>
</head>
<body>
  <div class="invoice-container">
    <div class="header">
      <img src="<?php echo $logoDataURI; ?>" width="150" style="margin: 0; padding: 0;" alt="Logo">
      <p>NO RESPONSABLE DE IVA</p>
      <p>NIT. 10753739</p>
      <p>Tel.: 3224318775 | Correo: activgymar@gmail.com</p>
    </div>

    <hr>

    <div class="section invoice-details">
      <h2>RECIBO DE PAGO No <?php echo htmlspecialchars($factura['id']); ?></h2>
    </div>

    <div class="section client-details">
      <div class="client-column">
        <p><span>Fecha:</span> <?php echo htmlspecialchars($factura['fecha']); ?></p>
        <p><span>Cliente:</span> <?php echo htmlspecialchars($factura['nombre_cliente']); ?></p>
        <p><span>Identificación:</span> <?php echo htmlspecialchars($factura['identificacion']); ?></p>
      </div>
      <div class="client-column">
        <p><span>Teléfono:</span> <?php echo htmlspecialchars($factura['telefono']); ?></p>
        <p><span>Dirección:</span> <?php echo htmlspecialchars($factura['direccion']); ?></p>
      </div>
    </div>

    <div class="section service-details">
      <table class="service-table">
        <thead>
          <tr>
            <th>DETALLE</th>
            <th>FECHA INICIO</th>
            <th>FECHA VENCIMIENTO</th>
            <th>VALOR</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="text-transform: uppercase;"><?php echo htmlspecialchars($factura['detalle']); ?></td>
            <td><?php echo htmlspecialchars($factura['fecha_inicio']); ?></td>
            <td><?php echo htmlspecialchars($factura['fecha_vencimiento']); ?></td>
            <td>$<?php echo $valorFormateado; ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="total">
      <p>TOTAL PAGADO: $<?php echo $valorFormateado; ?></p>
    </div>
    <hr>
    <center><h3>¡Gracias por ser parte de Activ Gym!</h3></center>
  </div>
</body>
</html>




