<?php
/* —————  QUERY  ————— */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {
   

    $stmt = db()->prepare("
        SELECT id,nombres,apellidos,telefono,fecha_nacimiento
        FROM   clientes
        WHERE  borrado = 0
          AND  estado  = 'activo'
          AND  MONTH(fecha_nacimiento) = MONTH(:hoy)
          AND  DAY(fecha_nacimiento)   = DAY(:hoy)
    ");
    $stmt->execute([':hoy'=>$hoy]);             //  $hoy viene de config.php
    $cumples = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<!-- —————  TABLA  ————— -->
<div class="section-title">Clientes que cumplen años hoy</div>

<table id="cumpleanos-hoy"
       class="table table-striped table-bordered custom-table w-100">
  <thead class="table-dark">
    <tr>
      <th>Nombre</th>
      <th>Teléfono</th>
      <th>Fecha de nacimiento</th>
      <th>Edad que cumple</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($cumples as $c): ?>
      <?php
        $edad = (new DateTime())->format('Y') -
                (new DateTime($c['fecha_nacimiento']))->format('Y');
      ?>
      <tr data-id="<?= $c['id'] ?>">
        <td><?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?></td>
        <td><?= htmlspecialchars($c['telefono']) ?></td>
        <td><?= htmlspecialchars($c['fecha_nacimiento']) ?></td>
        <td><?= $edad ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>


