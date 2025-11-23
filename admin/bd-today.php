<?php
/* ——— QUERY ——— */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {

    $stmt = db()->prepare("
        SELECT id, nombres, apellidos, telefono, fecha_nacimiento, imagen_perfil
        FROM clientes
        WHERE borrado = 0
          AND estado = 'activo'
          AND MONTH(fecha_nacimiento) = MONTH(:hoy)
          AND DAY(fecha_nacimiento)   = DAY(:hoy)
    ");
    $stmt->execute([':hoy' => $hoy]);
    $cumples = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<style>
.cumple-wrapper {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e5e5e5;
}

.cumple-card {
    display: flex;
    align-items: center;
    padding: 12px 10px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s ease, transform .1s ease;
    cursor: pointer;
}

.cumple-card:last-child {
    border-bottom: none;
}

.cumple-card:hover {
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

.cumple-info {
    flex: 1;
}

.cumple-name {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.cumple-sub {
    font-size: 13px;
    color: #666;
}

.badge-age {
    background: #ff5722;
    padding: 4px 10px;
    border-radius: 12px;
    color: #fff;
    font-size: 12px;
    font-weight: bold;
}

.no-cumples {
    text-align: center;
    padding: 25px;
    color: #777;
    font-size: 15px;
}
</style>

<div class="section-title mb-3">Clientes que cumplen años hoy</div>

<div class="cumple-wrapper">

<?php if (empty($cumples)): ?>

    <div class="no-cumples">No hay cumpleaños hoy.</div>

<?php else: ?>

    <?php foreach ($cumples as $c): ?>

        <?php
            // FOTO
            $foto = (!empty($c['imagen_perfil']))
                ? '../uploads/clientes/'.$c['imagen_perfil']
                : 'https://ui-avatars.com/api/?name='.urlencode($c['nombres'].' '.$c['apellidos']).'&background=ff5722&color=fff';

            // CÁLCULO DE EDAD
            $hoyY = (int)date('Y');
            $nacY = (int)date('Y', strtotime($c['fecha_nacimiento']));
            $edad = $hoyY - $nacY;
        ?>

        <div class="cumple-card" onclick="window.location.href='clients/detail.php?id=<?= $c['id'] ?>'">

            <div class="avatar">
                <img src="<?= $foto ?>" class="avatar-img" alt="foto">
            </div>

            <div class="cumple-info">
                <div class="cumple-name">
                    <?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?>
                </div>
                <div class="cumple-sub">
                    Tel: <?= htmlspecialchars($c['telefono'] ?: '—') ?>
                </div>
            </div>

            <div>
                <span class="badge-age"><?= $edad ?> años</span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>


