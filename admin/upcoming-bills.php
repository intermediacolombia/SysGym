<?php
/* ——— Próximos pagos a vencer en 7 días ——— */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {
    

    $stmt = db()->prepare("
        SELECT c.id,
               c.nombres,
               c.apellidos,
               c.telefono,
               c.vencimiento_plan,
               c.congelado,
               p.nombre  AS plan,
               p.precio  AS valor_pago
        FROM   clientes c
        LEFT JOIN planes p ON p.id = c.plan
        WHERE  c.borrado = 0
          AND   c.estado  = 'activo'
          AND   c.congelado = 0
          AND   DATE(c.vencimiento_plan)
                BETWEEN :hoy AND DATE_ADD(:hoy, INTERVAL 7 DAY)
        ORDER BY c.vencimiento_plan ASC
    ");
    $stmt->execute([':hoy'=>$hoy]);       //  $hoy → YYYY-MM-DD
    $proxPagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<div class="section-title">Próximos pagos a vencer (7&nbsp;días)</div>

<table id="upcoming-payments"
       class="table table-striped table-bordered custom-table w-100">
  <thead class="table-dark">
    <tr>
      <th>Nombre</th>
      <th>Teléfono</th>
      <th>Plan</th>
      <th>Precio</th>
      <th>Vencimiento</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($proxPagos as $p): ?>
      <tr data-id="<?= $p['id'] ?>">
        <td><?= htmlspecialchars($p['nombres'].' '.$p['apellidos']) ?></td>
        <td><?= htmlspecialchars($p['telefono']) ?></td>
        <td>
          <?= htmlspecialchars($p['plan'] ?: '—') ?>
          <?php if ($p['congelado'] == 1): ?>
            <i class="fa fa-snowflake-o text-info"></i>
          <?php endif; ?>
        </td>
        <td>$<?= number_format($p['valor_pago'],0,',','.') ?></td>
        <td><?= htmlspecialchars($p['vencimiento_plan']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>






