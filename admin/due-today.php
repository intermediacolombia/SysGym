<?php
/* ——— Usuarios cuyo plan vence HOY ——— */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {

    $stmt = db()->prepare("
        SELECT 
            c.id,
            c.nombres,
            c.apellidos,
            c.imagen_perfil,
            c.vencimiento_plan,
            c.congelado,
            p.nombre AS plan
        FROM clientes c
        LEFT JOIN planes p ON p.id = c.plan
        WHERE c.borrado = 0
          AND c.estado = 'activo'
          AND DATE(c.vencimiento_plan) = :hoy
          AND c.congelado = 0
    ");
    $stmt->execute([':hoy' => $hoy]);
    $vencenHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<div class="section-title mb-3">Usuarios cuyo plan vence hoy</div>

<div class="card-list-wrapper">


<?php if (empty($vencenHoy)): ?>

    <div class="no-data">
        <i class="fas fa-check-circle"></i>
        No hay planes por vencer hoy.
    </div>

<?php else: ?>

    <?php foreach ($vencenHoy as $u): ?>

        <?php
            // Foto real o avatar
            $foto = (!empty($u['imagen_perfil']))
                ? '../uploads/clientes/'.$u['imagen_perfil']
                : 'https://ui-avatars.com/api/?name='.urlencode($u['nombres'].' '.$u['apellidos']).'&background=6c63ff&color=fff';
        ?>

        <!-- CARD CLICKEABLE: redirige al perfil -->
        <div class="card-item" onclick="window.location.href='clients/detail.php?id=<?= $u['id'] ?>'">

            <div class="avatar">
                <img src="<?= $foto ?>" class="avatar-img" alt="foto">
            </div>

            <div class="venc-info">
                <div class="venc-name">
                    <?= htmlspecialchars($u['nombres'].' '.$u['apellidos']) ?>
                </div>
                <div class="venc-sub">
                    Plan: <?= htmlspecialchars($u['plan'] ?: '—') ?>
                    <?php if ($u['congelado'] == 1): ?>
                        <i class="fa fa-snowflake-o text-info"></i>
                    <?php endif; ?>
                </div>
            </div>

            <div class="venc-days">
                <span class="dias">HOY</span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>

