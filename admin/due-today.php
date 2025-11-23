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

    <div class="card-list-empty">
        <i class="fas fa-check-circle" style="font-size:22px;display:block;margin-bottom:6px;"></i>
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

        <div class="card-item" onclick="window.location.href='clients/detail.php?id=<?= $u['id'] ?>'">

            <div class="card-avatar">
                <img src="<?= $foto ?>" class="card-avatar-img" alt="foto">
            </div>

            <div class="card-info">
                <div class="card-title">
                    <?= htmlspecialchars($u['nombres'].' '.$u['apellidos']) ?>
                </div>

                <div class="card-sub">
                    Plan: <?= htmlspecialchars($u['plan'] ?: '—') ?>
                </div>
            </div>

            <div class="text-end">
                <span class="badge-pill badge-red">HOY</span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>

