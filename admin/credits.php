<?php
/* ——— Créditos activos ——— */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {
    

    $stmt = db()->prepare("
        SELECT  c.id,
                c.idCliente, 
                CONCAT(cli.nombres,' ',cli.apellidos) AS cliente,
                c.fecha,
                c.descripcion,
                c.valor,
                c.fecha_limite,
                DATEDIFF(c.fecha_limite,:hoy) AS dias_restantes
        FROM    creditos c
        JOIN    clientes cli ON cli.id = c.idCliente
        WHERE   c.estado = 'activo' AND cli.estado = 'activo'
    ");
    $stmt->execute([':hoy'=>$hoy]);        //  $hoy llega de config.php (YYYY-MM-DD)
    $creditos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<style>
.alerta-icon{font-size:1.3em;vertical-align:middle}
</style>

<div class="section-title">Créditos activos</div>

<table id="creditos-activos"
       class="table table-striped table-bordered custom-table w-100">
  <thead class="table-dark">
    <tr>
      <th>Cliente</th>
      <th>Fecha</th>
      <th>Descripción</th>
      <th>Valor</th>
      <th>Fecha límite</th>
      <th>Días&nbsp;para&nbsp;pago</th>
    </tr>
  </thead>

  <tbody>
    <?php $total = 0; ?>
    <?php foreach ($creditos as $cr): ?>
      <?php
        $total += $cr['valor'];

        // fila en rojo si está vencido
        $rowClass = $cr['dias_restantes'] < 0 ? 'table-danger' : '';

        // texto días restantes
        if ($cr['dias_restantes'] == 0)         $dias = 'Hoy';
        elseif ($cr['dias_restantes'] < 0)      $dias = 'Hace '.abs($cr['dias_restantes']).' días <span class="text-warning alerta-icon">&#9888;</span>';
        else                                    $dias = $cr['dias_restantes'].' días';
      ?>
      <tr class="<?= $rowClass ?>" data-id="<?= $cr['idCliente'] ?>">
        <td><?= htmlspecialchars($cr['cliente']) ?></td>
        <td><?= htmlspecialchars($cr['fecha']) ?></td>
        <td><?= htmlspecialchars($cr['descripcion']) ?></td>
        <td><?= number_format($cr['valor'],0,',','.') ?></td>
        <td><?= htmlspecialchars($cr['fecha_limite']) ?></td>
        <td><?= $dias ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>

  <!-- El pie sí puede llevar colspan -->
  <tfoot>
    <tr class="table-secondary">
      <th colspan="3" class="text-end">Total global en créditos</th>
      <th><?= number_format($total,0,',','.') ?></th>
      <th colspan="2"></th>
    </tr>
  </tfoot>
</table>


