<?php
/** Últimos 5 clientes inscritos
 *  (fragmento para include; NO lleva <script>)
 */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';

try {
    

    $stmt = db()->prepare("
        SELECT c.id,
               c.nombres,
               c.apellidos,
               c.telefono,
               c.vencimiento_plan,
               p.nombre AS plan
        FROM   clientes c
        LEFT JOIN planes p ON c.plan = p.id
        WHERE  c.borrado = 0
        ORDER BY c.id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $clientes5 = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<div class="section-title">Últimos 5 clientes inscritos</div>

<table id="latest-clients"
       class="table table-striped table-bordered custom-table w-100">
  <thead class="table-dark">
    <tr>
      <th>Nombre</th>
      <th>Teléfono</th>
      <th>Plan</th>
      <th>Próximo vencimiento</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($clientes5 as $c): ?>
      <tr data-id="<?= $c['id'] ?>">
        <td><?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?></td>
        <td><?= htmlspecialchars($c['telefono']) ?></td>
        <td><?= htmlspecialchars($c['plan'] ?: '—') ?></td>
        <td><?= htmlspecialchars($c['vencimiento_plan'] ?: '—') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>



