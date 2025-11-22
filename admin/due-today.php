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

<style>
.vencen-hoy-wrapper {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e5e5e5;
}

.venc-card {
    display: flex;
    align-items: center;
    padding: 12px 10px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s ease, transform .1s ease;
    cursor: pointer;
}

.venc-card:last-child {
    border-bottom: none;
}

.venc-card:hover {
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

.venc-info {
    flex: 1;
}

.venc-name {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.venc-sub {
    font-size: 13px;
    color: #666;
}

.venc-days .dias {
    background: #ff5252;
    color: #fff;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: bold;
}

.no-data {
    text-align: center;
    padding: 25px;
    color: #777;
    font-size: 15px;
}

.no-data i {
    font-size: 28px;
    color: #28a745;
    margin-bottom: 8px;
    display: block;
}
</style>

<div class="section-title mb-3">Usuarios cuyo plan vence hoy</div>

<div class="vencen-hoy-wrapper">

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
        <div class="venc-card" onclick="window.location.href='clientes/ver.php?id=<?= $u['id'] ?>'">

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

