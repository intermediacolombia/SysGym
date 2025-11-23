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

<div class="section-title mb-3">Últimos 5 clientes inscritos</div>

<div class="card-list-wrapper">

<?php if (empty($clientes5)): ?>

    <div class="card-list-empty">
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

        <div class="card-item" onclick="window.location.href='clients/detail.php?id=<?= $c['id'] ?>'">

            <div class="card-avatar">
                <img src="<?= $foto ?>" class="card-avatar-img" alt="foto">
            </div>

            <div class="card-info">
                <div class="card-title">
                    <?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?>
                </div>

                <div class="card-sub">
                    Tel: <?= htmlspecialchars($c['telefono'] ?: '—') ?><br>

                    <span class="badge-pill badge-purple">
                        <?= htmlspecialchars($c['plan'] ?: 'Sin plan') ?>
                    </span>
                </div>
            </div>

            <div>
                <span class="badge-pill badge-orange">
                    <?= htmlspecialchars($c['vencimiento_plan'] ?: '—') ?>
                </span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>





