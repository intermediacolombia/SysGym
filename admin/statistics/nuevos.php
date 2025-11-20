<?php
require_once __DIR__ . '/../../inc/config.php'; // Asegúrate de que este archivo define $host, $dbname, $dbuser y $dbpass

try {
    

    // Consulta para obtener la cantidad de nuevos clientes mensuales basándonos en la columna created_at
    $stmt = db()->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS mes,
            DATE_FORMAT(created_at, '%b %Y') AS label,
            COUNT(*) AS total
        FROM clientes
        WHERE borrado = 0
        GROUP BY mes
        ORDER BY mes ASC
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $data = [];

    foreach ($result as $row) {
        $labels[] = $row['label'];
        $data[] = $row['total'];
    }

    // Convertimos los arrays a JSON para usarlos en JavaScript
    $labelsJson = json_encode($labels);
    $dataJson = json_encode($data);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

<div class="chart-container">
  <canvas id="nuevosChart"></canvas>
</div>

<script>
    const ctxNuevos = document.getElementById('nuevosChart').getContext('2d');
    const nuevosChart = new Chart(ctxNuevos, {
      type: 'line', // Gráfico de línea para mostrar la tendencia mensual
      data: {
        labels: <?php echo $labelsJson; ?>,
        datasets: [{
          label: 'Nuevos Clientes',
          data: <?php echo $dataJson; ?>,
          backgroundColor: 'rgba(255, 159, 64, 0.5)',
          borderColor: 'rgba(255, 159, 64, 1)',
          borderWidth: 1,
          fill: false,
          tension: 0.1
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
            text: 'Nuevos Clientes Mensuales'
          }
        }
      }
    });
</script>

