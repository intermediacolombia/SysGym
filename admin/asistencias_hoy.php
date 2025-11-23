<?php require_once __DIR__ . '/login/session.php'; ?>
<?php
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

/* ---- ASISTENCIAS HOY ---- */
try {

    $stmt = db()->prepare("
        SELECT 
            c.id,
            c.nombres,
            c.apellidos,
            c.imagen_perfil,
            MAX(a.hora) AS hora
        FROM asistencias a
        JOIN clientes c ON c.id = a.idCliente
        WHERE a.fecha = :hoy
          AND c.borrado = 0
          AND c.estado = 'activo'
        GROUP BY c.id
        ORDER BY hora DESC
    ");
    $stmt->execute([':hoy' => $hoy]);
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<div class="section-title mb-3">Asistencias únicas registradas hoy</div>

<div class="card-list-wrapper">


<?php if (empty($asistencias)): ?>

    <div class="no-asist">No hay asistencias registradas hoy.</div>

<?php else: ?>

    <?php foreach ($asistencias as $row): ?>

        <?php
            // Foto o avatar
            $foto = (!empty($row['imagen_perfil']))
                ? '../uploads/clientes/'.$row['imagen_perfil']
                : 'https://ui-avatars.com/api/?name='.urlencode($row['nombres'].' '.$row['apellidos']).'&background=2196f3&color=fff';

            // Formato hora
            $horaObj = DateTime::createFromFormat('H:i:s', $row['hora']);
            $horaBonita = $horaObj ? $horaObj->format('g:i a') : $row['hora'];
        ?>

        <div class="asis-card" onclick="window.location.href='clients/detail.php?id=<?= $row['id'] ?>'">

            <div class="avatar">
                <img src="<?= $foto ?>" class="avatar-img">
            </div>

            <div class="asis-info">
                <div class="asis-name">
                    <?= htmlspecialchars($row['nombres'].' '.$row['apellidos']) ?>
                </div>
                <div class="asis-sub">
                    Última asistencia registrada
                </div>
            </div>

            <div>
                <span class="asis-hour"><?= $horaBonita ?></span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>



