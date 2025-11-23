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

<style>
.asis-wrapper {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e5e5e5;
}

.asis-card {
    display: flex;
    align-items: center;
    padding: 12px 10px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s ease, transform .1s ease;
    cursor: pointer;
}

.asis-card:last-child {
    border-bottom: none;
}

.asis-card:hover {
    background: #f7faff;
    transform: translateX(3px);
}

.avatar {
    width: 45px;
    height: 45px;
    margin-right: 15px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid #e3e3e3;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.asis-info {
    flex: 1;
}

.asis-name {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.asis-sub {
    font-size: 13px;
    color: #666;
}

.asis-hour {
    background: #2196f3;
    padding: 4px 10px;
    border-radius: 12px;
    color: #fff;
    font-size: 12px;
    font-weight: bold;
}

.no-asist {
    text-align: center;
    padding: 25px;
    color: #777;
    font-size: 15px;
}
</style>

<div class="section-title mb-3">Asistencias únicas registradas hoy</div>

<div class="asis-wrapper">

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



