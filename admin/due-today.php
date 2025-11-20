<?php
/* ——— Usuarios cuyo plan vence HOY ——— */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {
   

    /*  Si quieres mostrar también congelados, elimina  AND congelado = 0   */
    $stmt = db()->prepare("
        SELECT c.id,
               c.nombres,
               c.apellidos,
               c.vencimiento_plan,
               c.congelado,
               p.nombre AS plan
        FROM   clientes c
        LEFT JOIN planes p ON p.id = c.plan
        WHERE  c.borrado = 0
          AND   c.estado  = 'activo'
          AND   DATE(c.vencimiento_plan) = :hoy
          AND   c.congelado = 0
    ");
    $stmt->execute([':hoy'=>$hoy]);          //  $hoy viene de config.php (YYYY-MM-DD)
    $vencenHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<div class="section-title">Usuarios cuyo plan vence hoy</div>

<table id="planes-hoy"
       class="table table-striped table-bordered custom-table w-100">
  <thead class="table-dark">
    <tr>
      <th>Nombre</th>
      <th>Plan</th>
      <!--th>Fecha de vencimiento</th-->
    </tr>
  </thead>
  <tbody>
    <?php foreach ($vencenHoy as $u): ?>
      <tr data-id="<?= $u['id'] ?>">
        <td><?= htmlspecialchars($u['nombres'].' '.$u['apellidos']) ?></td>
        <td>
          <?= htmlspecialchars($u['plan'] ?: '—') ?>
          <?php if ($u['congelado'] == 1): ?>
            <i class="fa fa-snowflake-o text-info"></i>
          <?php endif; ?>
        </td>
        <!--td><?= htmlspecialchars($u['vencimiento_plan']) ?></td-->
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

