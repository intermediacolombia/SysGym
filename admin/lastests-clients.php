<?php
/** Últimos 5 clientes inscritos */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';

try {

    $stmt = db()->prepare("
        SELECT c.id,
               c.nombres,
               c.apellidos,
               c.telefono,
               c.vencimiento_plan,
               c.imagen_perfil,
               p.nombre AS plan
        FROM clientes c
        LEFT JOIN planes p ON c.plan = p.id
        WHERE c.borrado = 0
        ORDER BY c.id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $clientes5 = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<style>
.latest-wrapper {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e5e5e5;
}

.latest-card {
    display: flex;
    align-items: center;
    padding: 12px 10px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s ease, transform .1s ease;
    cursor: pointer;
}

.latest-card:last-child {
    border-bottom: none;
}

.latest-card:hover {
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

.latest-info {
    flex: 1;
}

.latest-name {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.latest-sub {
    font-size: 13px;
    color: #666;
}

.badge-plan {
    background: #6c63ff;
    padding: 3px 9px;
    border-radius: 12px;
    color: #fff;
    font-size: 11px;
    margin-left: 5px;
}

.badge-venc {
    background: #ff9800;
    padding: 3px 9px;
    border-radius: 12px;
    color: #fff;
    font-size: 11px;
}

.no-data-5 {
    text-align: center;
    padding: 25px;
    color: #777;
    font-size: 15px;
}
</style>

<div class="section-title mb-3">Últimos 5 clientes inscritos</div>

<div class="latest-wrapper">

<?php if (empty($clientes5)): ?>

    <div class="no-data-5">
        No hay clientes registrados aún.
    </div>

<?php else: ?>

    <?php foreach ($clientes5 as $c): ?>

        <?php
            // Foto real o avatar
            $foto = (!empty($c['imagen_perfil']))
                ? '../uploads/clientes/'.$c['imagen_perfil']
                : 'https://ui-avatars.com/api/?name='.urlencode($c['nombres'].' '.$c['apellidos']).'&background=6c63ff&color=fff';
        ?>

        <div class="latest-card" onclick="window.location.href='clients/detail.php?id=<?= $c['id'] ?>'">

            <div class="avatar">
                <img src="<?= $foto ?>" class="avatar-img" alt="foto">
            </div>

            <div class="latest-info">
                <div class="latest-name">
                    <?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?>
                </div>
                <div class="latest-sub">
                    Tel: <?= htmlspecialchars($c['telefono'] ?: '—') ?>
                    <br>
                    <span class="badge-plan"><?= htmlspecialchars($c['plan'] ?: 'Sin plan') ?></span>
                </div>
            </div>

            <div class="latest-venc">
                <span class="badge-venc">
                    <?= htmlspecialchars($c['vencimiento_plan'] ?: '—') ?>
                </span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>




