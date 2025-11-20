<?php
require_once __DIR__ . '/../../inc/config.php'; // Asegúrate de que este archivo define $host, $dbname, $dbuser y $dbpass

try {
    

    // Consulta para obtener cada plan (no borrado) y la cantidad de usuarios que lo usan (clientes no borrados)
    $stmt = db()->prepare("
        SELECT p.*, COUNT(c.id) as total
        FROM planes p
        LEFT JOIN clientes c ON p.id = c.plan AND c.borrado = 0
        WHERE p.borrado = 0 AND p.estado = 'activo'
        GROUP BY p.id, p.nombre
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preparamos los arrays para Chart.js
    $planLabels = [];
    $planData = [];

    foreach ($result as $row) {
        $planLabels[] = $row['nombre'];
        $planData[] = $row['total'];
    }
    
    // Convertimos los arrays a JSON para usarlos en JavaScript
    $planLabelsJson = json_encode($planLabels);
    $planDataJson = json_encode($planData);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

<div class="chart-container">
  <canvas id="plansChart"></canvas>
</div>

<script>
    const ctxPlans = document.getElementById('plansChart').getContext('2d');
    const plansChart = new Chart(ctxPlans, {
      type: 'bar', // Gráfico de barras
      data: {
        labels: <?php echo $planLabelsJson; ?>,
        datasets: [{
          label: 'Usuarios por Plan',
          data: <?php echo $planDataJson; ?>,
          backgroundColor: 'rgba(75, 192, 192, 0.5)',
          borderColor: 'rgba(75, 192, 192, 1)',
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
            text: 'Cantidad de Usuarios por Plan'
          }
        }
      }
    });
</script>

