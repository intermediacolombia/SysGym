<?php
require_once __DIR__ . '/../../inc/config.php'; // Asegúrate de que este archivo define $host, $dbname, $dbuser y $dbpass

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para agrupar los cumpleaños por mes
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(fecha_nacimiento, '%m') AS mes, COUNT(*) AS total
        FROM clientes
        WHERE borrado = 0 AND fecha_nacimiento IS NOT NULL
        GROUP BY mes
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapeo de números de mes a nombres en español
    $months = [
        '01' => 'Enero',
        '02' => 'Febrero',
        '03' => 'Marzo',
        '04' => 'Abril',
        '05' => 'Mayo',
        '06' => 'Junio',
        '07' => 'Julio',
        '08' => 'Agosto',
        '09' => 'Septiembre',
        '10' => 'Octubre',
        '11' => 'Noviembre',
        '12' => 'Diciembre'
    ];
    
    // Inicializamos el array de conteo con 0 para cada mes
    $birthdayCounts = array_fill_keys(array_keys($months), 0);
    
    foreach ($result as $row) {
        $mes = $row['mes'];
        $birthdayCounts[$mes] = (int)$row['total'];
    }
    
    // Preparamos los arrays para Chart.js
    $labels = array_values($months);
    $data = array_values($birthdayCounts);
    
    // Convertimos los arrays a JSON para usarlos en JavaScript
    $labelsJson = json_encode($labels);
    $dataJson = json_encode($data);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

<div class="chart-container">
  <canvas id="cumpleanosChart"></canvas>
</div>

<script>
    const ctxCumple = document.getElementById('cumpleanosChart').getContext('2d');
    const cumpleanosChart = new Chart(ctxCumple, {
      type: 'bar', // Gráfico de barras
      data: {
        labels: <?php echo $labelsJson; ?>,
        datasets: [{
          label: 'Número de Cumpleaños',
          data: <?php echo $dataJson; ?>,
          backgroundColor: 'rgba(255, 205, 86, 0.5)',
          borderColor: 'rgba(255, 205, 86, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0 }
          }
        },
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'Cumpleaños por Mes' }
        }
      }
    });
</script>
