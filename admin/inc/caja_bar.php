<?php
// --- Barra Superior Global (modo seguro) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$actual = basename($_SERVER['SCRIPT_NAME']);
$noMostrar = [
    'caja_detail.php', 'close_id.php', 'open_id.php',
    'transferir_caja.php', 'get_total_simple.php', 'get_total_caja.php'
];

if (in_array($actual, $noMostrar)) {
    return;
}

require_once __DIR__ . '/../../inc/config.php';

$nombre = $_SESSION['user']['nombre'] ?? '';
$apellido = $_SESSION['user']['apellido'] ?? '';
$nombreCompleto = htmlspecialchars(trim("$nombre $apellido"));

try {

    if ($caja_id) {

        // --- INGRESOS ---
        $stmtIngresos = db()->prepare("
    SELECT 
      IFNULL(SUM(CASE WHEN payment_method='Efectivo' THEN valor ELSE 0 END), 0) AS efectivo,
      IFNULL(SUM(CASE WHEN payment_method='Transferencia' THEN valor ELSE 0 END), 0) AS transferencias,
      IFNULL(SUM(CASE WHEN payment_method='Egreso' THEN ABS(valor) ELSE 0 END), 0) AS egresos
    FROM ventas
    WHERE caja_id = :caja_id
");
$stmtIngresos->execute([':caja_id' => $caja_id]);
$datos = $stmtIngresos->fetch(PDO::FETCH_ASSOC);

$efectivo       = (float)$datos['efectivo'];
$transferencias = (float)$datos['transferencias'];
$egresos        = (float)$datos['egresos'];

$totalIngresos = $efectivo + $transferencias;

// RESTA S� O S�
$totalCaja = $totalIngresos - $egresos;


    } else {
        $efectivo = $transferencias = $egresos = $totalIngresos = $totalCaja = 0;
    }

} catch (PDOException $e) {
    error_log("Error en caja_bar: " . $e->getMessage());
    $efectivo = $transferencias = $egresos = $totalIngresos = $totalCaja = 0;
}
?>


<?php if (!empty($caja_id)): ?>
<style>
.topbar-wrapper {
  position: relative;
  z-index: 50;
  margin-left: 250px; /* ?? ancho del men� lateral */
}

.topbar {
  position: fixed;
  top: 0;
  left: 271px; /* ?? alinea con el contenido */
  right: 0;
  height: 50px;
  background: var(--system-color-primary);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  font-size: 14px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  z-index: 1050;
}

.topbar .left span { font-weight: 600; }
.topbar .right { display: flex; align-items: center; gap: 15px; }
.topbar .right a { color: #fff; font-weight: 600; text-decoration: none; }
.topbar .right a:hover { text-decoration: underline; }


/* Empuja el contenido hacia abajo para no quedar debajo de la barra */
.portada {
  margin-top: 50px; /* ?? espacio exacto igual o mayor que height */
}
</style>

<div class="topbar">
  <div class="left">
    <i class='fas fa-coins'></i> Efectivo:
    <span id="totalEfectivo">$<?php echo number_format($efectivo, 0, '', '.'); ?></span> |
    <i class="fa fa-bank"></i> Transferencias:
    <span id="totalTrans">$<?php echo number_format($transferencias, 0, '', '.'); ?></span> | <?php if ($egresos > 0): ?>
        <i class="fas fa-arrow-down"></i> Egresos:
        <span id="totalEgresos" style="color:#ffbbbb;">-$<?php echo number_format($egresos, 0, '', '.'); ?></span> |
    <?php endif; ?> 
    <i class="fa fa-money"></i> Total en Caja:
    <span id="totalCaja">$<?php echo number_format($totalCaja, 0, '', '.'); ?></span>
  </div>
  <div class="right">
    <span><i class="fa fa-user"></i> <?php echo $nombreCompleto; ?></span>
    <span id="topbarHora"></span>
    <a href="<?php echo $url; ?>/admin/caja/"><i class="fas fa-cash-register"></i> Ir a mi Caja</a>
  </div>
</div>

<script>
$(function() {
  const cajaId = '<?php echo (int)$caja_id; ?>';

  // ?? Reloj
  function actualizarHora() {
    const d = new Date();
    const h = d.getHours().toString().padStart(2,'0');
    const m = d.getMinutes().toString().padStart(2,'0');
    const s = d.getSeconds().toString().padStart(2,'0');
    $('#topbarHora').text(`?? ${h}:${m}:${s}`);
  }
  actualizarHora();
  setInterval(actualizarHora, 1000);

  // ?? Actualizar totales
  function actualizarTotales() {
    $.getJSON('<?php echo $url; ?>/admin/caja/get_total_simple.php', { caja_id: cajaId }, function(res) {
      if (res.status === 'success') {
        $('#totalEfectivo').text('$' + parseFloat(res.efectivo).toLocaleString('es-CO'));
        $('#totalTrans').text('$' + parseFloat(res.transferencias).toLocaleString('es-CO'));
        $('#totalCaja').text('$' + parseFloat(res.total).toLocaleString('es-CO'));
      }
    });
  }
  actualizarTotales();
  setInterval(actualizarTotales, 10000);
});
</script>
<?php endif; ?>








