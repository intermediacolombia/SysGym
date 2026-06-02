<?php
require_once __DIR__ . '/../../inc/config.php';

try {
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(fecha, '%b %Y') AS label,
               COUNT(DISTINCT idCliente) AS total
        FROM asistencias
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
        GROUP BY DATE_FORMAT(fecha, '%Y-%m')
        ORDER BY DATE_FORMAT(fecha, '%Y-%m') ASC
    ");
    $stmt->execute();
    $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $labels = array_column($rows, 'label');
    $data   = array_map('intval', array_column($rows, 'total'));
} catch (PDOException $e) {
    $labels = [];
    $data   = [];
}
?>
<div class="chart-container">
  <canvas id="asisChart"></canvas>
</div>
<script>
new Chart(document.getElementById('asisChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Asistencias únicas',
      data: <?= json_encode($data) ?>,
      borderColor: '#8b5cf6',
      backgroundColor: 'rgba(139,92,246,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 4,
      pointBackgroundColor: '#8b5cf6',
      pointBorderWidth: 0
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, ticks: { precision: 0 } }
    }
  }
});
</script>
