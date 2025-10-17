<?php
require_once __DIR__ . '/../../inc/config.php'; // Asegúrate de que este archivo define $host, $dbname, $dbuser y $dbpass

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para contar los clientes por género (solo los no borrados)
    $stmt = $pdo->prepare("SELECT genero, COUNT(*) as total FROM clientes WHERE borrado = 0 GROUP BY genero");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicializamos los contadores para cada género
    $masculino = 0;
    $femenino = 0;
    $otro = 0;

    // Procesamos los resultados
    foreach ($result as $row) {
        if ($row['genero'] === 'masculino') {
            $masculino = $row['total'];
        } elseif ($row['genero'] === 'femenino') {
            $femenino = $row['total'];
        } else {
            // Para otros valores o 'otro'
            $otro += $row['total'];
        }
    }
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

<div class="chart-container">
  <canvas id="genderChart"></canvas>
</div>

<script>
    const ctxGender = document.getElementById('genderChart').getContext('2d');
    const genderChart = new Chart(ctxGender, {
      type: 'pie', // Gráfico de pastel (puedes cambiarlo a 'bar' u otro tipo)
      data: {
        labels: ['Masculino', 'Femenino', 'Otro'],
        datasets: [{
          label: 'Número de Clientes',
          data: [<?php echo $masculino; ?>, <?php echo $femenino; ?>, <?php echo $otro; ?>],
          backgroundColor: [
            'rgba(54, 162, 235, 0.5)',
            'rgba(255, 99, 132, 0.5)',
            'rgba(255, 206, 86, 0.5)'
          ],
          borderColor: [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(255, 206, 86, 1)'
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
            text: 'Distribución de Género de Clientes'
          }
        }
      }
    });
</script>
