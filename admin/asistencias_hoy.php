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

    <div class="card-list-empty">No hay asistencias registradas hoy.</div>

<?php else: ?>

    <?php foreach ($asistencias as $row): ?>

        <?php
            // Foto o avatar
            $foto = (!empty($row['imagen_perfil']))
                ? '../uploads/clientes/'.$row['imagen_perfil']
                : 'https://ui-avatars.com/api/?name=' . urlencode($row['nombres'].' '.$row['apellidos']) . '&background=2196f3&color=fff';

            // Formato hora
            $horaObj = DateTime::createFromFormat('H:i:s', $row['hora']);
            $horaBonita = $horaObj ? $horaObj->format('g:i a') : $row['hora'];
        ?>

        <div class="card-item" onclick="window.location.href='clients/detail.php?id=<?= $row['id'] ?>'">

            <div class="card-avatar">
                <img src="<?= $foto ?>" class="card-avatar-img">
            </div>

            <div class="card-info">
                <div class="card-title">
                    <?= htmlspecialchars($row['nombres'].' '.$row['apellidos']) ?>
                </div>
                <div class="card-sub">
                    Última asistencia registrada
                </div>
            </div>

            <div class="text-end">
                <span class="badge-pill badge-blue"><?= $horaBonita ?></span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>




