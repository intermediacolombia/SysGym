<?php require_once __DIR__ . '/login/session.php'; ?>
<?php
require_once __DIR__ . '/../inc/config.php';          // ← $host, $dbname, $dbuser, $dbpass, $url, $hoy
date_default_timezone_set('America/Bogota');

/* ——— Asistencias de HOY (una por cliente) ——— */
try {
    
    /*  Agrupamos por cliente y nos quedamos con la HORA más alta (la última)  */
    $stmt = db()->prepare("
        SELECT  c.id,
                c.nombres,
                c.apellidos,
                MAX(a.hora) AS hora
        FROM    asistencias a
        JOIN    clientes    c ON c.id = a.idCliente
        WHERE   a.fecha   = :hoy
          AND   c.borrado = 0
          AND   c.estado  = 'activo'
        GROUP BY c.id, c.nombres, c.apellidos          
        ORDER BY hora DESC                            
    ");
    $stmt->execute([':hoy' => $hoy]);
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>

<div class="section-title">Asistencias Unicas registradas hoy</div>

<table id="asistencias-hoy"
       class="table table-striped table-bordered custom-table w-100">
  <thead class="table-dark">
    <tr>
      <th>Nombre</th>
      <th>Hora de Ingreso</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($asistencias as $row): ?>
      <tr data-id="<?= $row['id'] ?>">
        <td><?= htmlspecialchars($row['nombres'].' '.$row['apellidos']) ?></td>
        <td
  data-order="<?= $row['hora']; /* ej. 14:05:12 */ ?>"
>
  <?php
    $horaObj = DateTime::createFromFormat('H:i:s', $row['hora']);
    echo htmlspecialchars($horaObj->format('g:i a'));   // 2:05 pm
  ?>
</td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>


