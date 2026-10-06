<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('ID invalido'); }

$st = db()->prepare("
    SELECT c.*, TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,''))) AS usuario_nombre
    FROM cajas c
    LEFT JOIN usuarios u ON u.id = c.usuario_id
    WHERE c.id = :id LIMIT 1
");
$st->execute([':id' => $id]);
$caja = $st->fetch(PDO::FETCH_ASSOC);
if (!$caja) { http_response_code(404); exit('Caja no encontrada'); }

$cid = (int)$caja['id'];
$base = (float)$caja['monto_inicial'];

$ef  = (float) db()->query("SELECT IFNULL(SUM(valor),0) FROM ventas WHERE caja_id=$cid AND payment_method='Efectivo'")->fetchColumn();
$eg  = abs((float) db()->query("SELECT IFNULL(SUM(valor),0) FROM ventas WHERE caja_id=$cid AND payment_method='Egreso'")->fetchColumn());

$bancos = function_exists('getBancosDisponibles') ? getBancosDisponibles() : [];
$transferByBank = array_fill_keys($bancos, 0.0);
$totalTransf = 0.0;
$stT = db()->prepare("SELECT bank, IFNULL(SUM(valor),0) t FROM ventas WHERE caja_id=:id AND payment_method='Transferencia' GROUP BY bank");
$stT->execute([':id'=>$cid]);
foreach ($stT->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $b = $r['bank'] ?: 'Otro';
    $v = (float)$r['t'];
    if (!isset($transferByBank[$b])) $transferByBank[$b] = 0;
    $transferByBank[$b] += $v;
    $totalTransf += $v;
}
$totalEfectivo = $ef - $eg;
$totalTurno    = $totalEfectivo + $totalTransf;

$totalVendido = (float) db()->query("SELECT IFNULL(SUM(valor),0) FROM ventas WHERE caja_id=$cid AND payment_method<>'Egreso'")->fetchColumn();
$totalCierre  = $base + $totalVendido;

$fmt = function($n){ return '$ ' . number_format((float)$n, 0, ',', '.'); };
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cierre Caja #<?= $cid ?></title>
<style>
  @page { size: 80mm auto; margin: 3mm 8mm; }
  * { box-sizing: border-box; }
  html, body { margin:0; padding:0; }
  body { font-family: 'Courier New', monospace; font-size: 12px; color:#000; width: 64mm; }
  .center { text-align:center; }
  .b { font-weight:700; }
  .lg { font-size:14px; }
  .xl { font-size:16px; }
  hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
  .row { display:flex; justify-content:space-between; gap:6px; }
  .row > span:last-child { text-align:right; }
  .total { font-size:14px; font-weight:700; }
  .mt { margin-top:6px; }
  .mb { margin-bottom:6px; }
  @media screen {
    body { background:#eee; padding:10px; }
    .ticket { background:#fff; padding:8px; margin:0 auto; width:80mm; box-shadow:0 2px 8px rgba(0,0,0,.15); }
    .noprint { text-align:center; margin:10px 0; }
  }
  @media print {
    .noprint { display:none; }
    .ticket { padding:0; }
  }
</style>
</head>
<body>
<div class="noprint">
  <button onclick="window.print()">Imprimir</button>
  <button onclick="window.close()">Cerrar</button>
</div>
<div class="ticket">
  <div class="center b xl"><?= htmlspecialchars(NAME_GYM ?: 'Gimnasio') ?></div>
  <?php if (NIT_GYM): ?><div class="center">NIT: <?= htmlspecialchars(NIT_GYM) ?></div><?php endif; ?>
  <?php if (DIRECCION_GYM): ?><div class="center"><?= htmlspecialchars(DIRECCION_GYM) ?></div><?php endif; ?>
  <?php if (TEL_GYM): ?><div class="center">Tel: <?= htmlspecialchars(TEL_GYM) ?></div><?php endif; ?>
  <hr>
  <div class="center b lg">CIERRE DE CAJA</div>
  <div class="center">Turno #<?= $cid ?></div>
  <hr>
  <div class="row"><span>Cajero:</span><span><?= htmlspecialchars($caja['usuario_nombre'] ?? '-') ?></span></div>
  <div class="row"><span>Apertura:</span><span><?= htmlspecialchars($caja['fecha_apertura'] . ' ' . substr($caja['hora_apertura'],0,5)) ?></span></div>
  <div class="row"><span>Cierre:</span><span><?= htmlspecialchars(($caja['fecha_cierre'] ?: date('Y-m-d')) . ' ' . substr(($caja['hora_cierre'] ?: date('H:i:s')),0,5)) ?></span></div>
  <hr>
  <div class="b mb">RESUMEN</div>
  <div class="row"><span>Base inicial:</span><span><?= $fmt($base) ?></span></div>
  <div class="row"><span>Ventas efectivo:</span><span><?= $fmt($ef) ?></span></div>
  <div class="row"><span>Egresos:</span><span>-<?= $fmt($eg) ?></span></div>
  <div class="row b"><span>Total efectivo:</span><span><?= $fmt($totalEfectivo) ?></span></div>
  <hr>
  <div class="b mb">TRANSFERENCIAS</div>
  <?php
    $hayTr = false;
    foreach ($transferByBank as $b => $v) {
        if ($v > 0) { $hayTr = true; echo '<div class="row"><span>'.htmlspecialchars($b).':</span><span>'.$fmt($v).'</span></div>'; }
    }
    if (!$hayTr) echo '<div class="center">Sin transferencias</div>';
  ?>
  <div class="row b"><span>Total transferencias:</span><span><?= $fmt($totalTransf) ?></span></div>
  <hr>
  <div class="row total"><span>TOTAL TURNO:</span><span><?= $fmt($totalTurno) ?></span></div>
  <div class="row"><span>Total vendido:</span><span><?= $fmt($totalVendido) ?></span></div>
  <div class="row b"><span>Total cierre (base+ventas):</span><span><?= $fmt($totalCierre) ?></span></div>
  <hr>
  <div style="margin-top:10px">Sobrante: <b>$</b> ________________________</div>
  <div style="margin-top:28px">Firma: ______________________________</div>
  <hr>
  <div class="center">Impreso: <?= date('Y-m-d H:i') ?></div>
  <div class="center" style="margin-top:14px">.</div>
</div>
<script>
  window.addEventListener('load', function(){
    if (/noauto/.test(location.search)) return;
    setTimeout(function(){
      try { (window.frameElement ? window : window).focus(); } catch(e){}
      window.print();
    }, 300);
  });
</script>
</body>
</html>
