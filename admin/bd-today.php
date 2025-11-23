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

<div class="section-title mb-3">Clientes que cumplen años hoy</div>

<div class="card-list-wrapper">

<?php if (empty($cumples)): ?>

    <div class="card-list-empty">No hay cumpleaños hoy.</div>

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

        <div class="card-item" onclick="window.location.href='clients/detail.php?id=<?= $c['id'] ?>'">

            <div class="card-avatar">
                <img src="<?= $foto ?>" class="card-avatar-img" alt="foto">
            </div>

            <div class="card-info">
                <div class="card-title">
                    <?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?>
                </div>
                <div class="card-sub">
                    Tel: <?= htmlspecialchars($c['telefono'] ?: '—') ?>
                </div>
            </div>

            <div>
                <span class="badge-pill badge-orange"><?= $edad ?> años</span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>



