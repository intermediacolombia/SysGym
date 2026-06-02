<?php
require_once __DIR__ . '/../../inc/config.php';

try {
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(fecha, '%b %Y') AS label,
               SUM(CASE WHEN payment_method != 'Egreso' THEN valor ELSE 0 END)       AS ingresos,
               SUM(CASE WHEN payment_method  = 'Egreso' THEN ABS(valor) ELSE 0 END)  AS egresos
        FROM ventas
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(fecha, '%Y-%m')
        ORDER BY DATE_FORMAT(fecha, '%Y-%m') ASC
    ");
    $stmt->execute();
    $rows     = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $labels   = array_column($rows, 'label');
    $ingresos = array_map('floatval', array_column($rows, 'ingresos'));
    $egresos  = array_map('floatval', array_column($rows, 'egresos'));
} catch (PDOException $e) {
    $labels   = [];
    $ingresos = [];
    $egresos  = [];
}
?>
<div class="chart-container">
  <canvas id="ingEgrChart"></canvas>
</div>
<script>
new Chart(document.getElementById('ingEgrChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [
      {
        label: 'Ingresos',
        data: <?= json_encode($ingresos) ?>,
        backgroundColor: 'rgba(16,185,129,0.78)',
        borderRadius: 5,
        borderSkipped: false
      },
      {
        label: 'Egresos',
        data: <?= json_encode($egresos) ?>,
        backgroundColor: 'rgba(239,68,68,0.72)',
        borderRadius: 5,
        borderSkipped: false
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        display: true,
        position: 'top',
        labels: { boxWidth: 12, padding: 14 }
      }
    },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true }
    }
  }
});
</script>
