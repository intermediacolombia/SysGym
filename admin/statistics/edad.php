<?php
require_once __DIR__ . '/../../inc/config.php'; // Asegúrate de que este archivo define $host, $dbname, $dbuser y $dbpass

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para agrupar clientes en rangos de edad basados en su fecha_nacimiento
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 46 AND 55 THEN '46-55'
                ELSE '56+'
            END as rango_edad,
            COUNT(*) as total
        FROM clientes
        WHERE borrado = 0 AND fecha_nacimiento IS NOT NULL
        GROUP BY rango_edad
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Definimos los rangos en orden
    $rangos = ['18-25', '26-35', '36-45', '46-55', '56+'];
    $edadLabels = [];
    $edadData = [];

    // Inicializamos el array con 0 para cada rango
    foreach ($rangos as $rango) {
        $edadLabels[] = $rango;
        $edadData[$rango] = 0;
    }
    
    // Asignamos los datos obtenidos
    foreach ($result as $row) {
        $edadData[$row['rango_edad']] = (int)$row['total'];
    }
    
    // Ordenamos los datos según el array $rangos
    $edadDataOrdered = [];
    foreach ($rangos as $rango) {
        $edadDataOrdered[] = $edadData[$rango];
    }

    // Convertimos los arrays a JSON para usarlos en JavaScript
    $edadLabelsJson = json_encode($rangos);
    $edadDataJson = json_encode($edadDataOrdered);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

<div class="chart-container">
  <canvas id="edadChart"></canvas>
</div>

<script>
    const ctxEdad = document.getElementById('edadChart').getContext('2d');
    const edadChart = new Chart(ctxEdad, {
      type: 'bar', // Gráfico de barras
      data: {
        labels: <?php echo $edadLabelsJson; ?>,
        datasets: [{
          label: 'Número de Clientes',
          data: <?php echo $edadDataJson; ?>,
          backgroundColor: 'rgba(153, 102, 255, 0.5)',
          borderColor: 'rgba(153, 102, 255, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        },
        plugins: {
          legend: {
            display: true,
            position: 'top'
          },
          title: {
            display: true,
            text: 'Distribución por Edad'
          }
        }
      }
    });
</script>
