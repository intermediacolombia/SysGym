<?php
require_once __DIR__ . '/../../inc/config.php'; // Asegúrate de que este archivo define $host, $dbname, $dbuser y $dbpass

try {
    

    // Consulta para obtener la cantidad de clientes por estado (activo vs inactivo)
    $stmt = db()->prepare("
        SELECT estado, COUNT(*) AS total
        FROM clientes
        WHERE borrado = 0
        GROUP BY estado
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicializamos las variables para cada estado
    $activos = 0;
    $inactivos = 0;

    foreach ($result as $row) {
        if (strtolower($row['estado']) === 'activo') {
            $activos = $row['total'];
        } elseif (strtolower($row['estado']) === 'inactivo') {
            $inactivos = $row['total'];
        }
    }

    // Definimos los labels y datos para Chart.js
    $labels = ['Activos', 'Inactivos'];
    $data = [$activos, $inactivos];

    // Convertimos los arrays a JSON para usarlos en JavaScript
    $labelsJson = json_encode($labels);
    $dataJson = json_encode($data);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

<div class="chart-container">
  <canvas id="activosChart"></canvas>
</div>

<script>
    const ctxActivos = document.getElementById('activosChart').getContext('2d');
    const activosChart = new Chart(ctxActivos, {
      type: 'doughnut', // Gráfico de dona (puedes cambiar a 'pie' o 'bar' si lo prefieres)
      data: {
        labels: <?php echo $labelsJson; ?>,
        datasets: [{
          label: 'Clientes',
          data: <?php echo $dataJson; ?>,
          backgroundColor: [
            'rgba(54, 162, 235, 0.5)',  // Color para Activos
            'rgba(255, 99, 132, 0.5)'   // Color para Inactivos
          ],
          borderColor: [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top'
          },
          title: {
            display: true,
            text: 'Clientes Activos vs Inactivos'
          }
        }
      }
    });
</script>
