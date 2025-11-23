<?php
/* ——— Próximos pagos a vencer en 7 días ——— */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {

    $stmt = db()->prepare("
        SELECT 
            c.id,
            c.nombres,
            c.apellidos,
            c.telefono,
            c.imagen_perfil,
            c.vencimiento_plan,
            c.congelado,
            p.nombre AS plan,
            p.precio AS valor_pago,
            DATEDIFF(c.vencimiento_plan, :hoy) AS dias_restantes
        FROM clientes c
        LEFT JOIN planes p ON p.id = c.plan
        WHERE c.borrado = 0
          AND c.estado = 'activo'
          AND c.congelado = 0
          AND DATE(c.vencimiento_plan)
              BETWEEN :hoy AND DATE_ADD(:hoy, INTERVAL 7 DAY)
        ORDER BY c.vencimiento_plan ASC
    ");
    $stmt->execute([':hoy' => $hoy]);
    $proxPagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<div class="section-title mb-3">Próximos pagos a vencer (7&nbsp;días)</div>

<div class="card-list-wrapper">

<?php if (empty($proxPagos)): ?>

    <div class="card-list-empty">No hay pagos por vencer en los próximos 7 días.</div>

<?php else: ?>

    <?php foreach ($proxPagos as $p): ?>

        <?php
            // Foto
            $foto = (!empty($p['imagen_perfil']))
                ? '../uploads/clientes/'.$p['imagen_perfil']
                : 'https://ui-avatars.com/api/?name='.urlencode($p['nombres'].' '.$p['apellidos']).'&background=2196f3&color=fff';

            // Badge según días restantes
            if ($p['dias_restantes'] <= 1) {
                $badgeClass = 'badge-red';
            } elseif ($p['dias_restantes'] <= 3) {
                $badgeClass = 'badge-yellow';
            } else {
                $badgeClass = 'badge-green';
            }
        ?>

        <div class="card-item" onclick="window.location.href='clients/detail.php?id=<?= $p['id'] ?>'">

            <div class="card-avatar">
                <img src="<?= $foto ?>" class="card-avatar-img">
            </div>

            <div class="card-info">
                <div class="card-title">
                    <?= htmlspecialchars($p['nombres'].' '.$p['apellidos']) ?>
                </div>

                <div class="card-sub">
                    Plan: <?= htmlspecialchars($p['plan']) ?>
                    · Tel: <?= htmlspecialchars($p['telefono']) ?>
                </div>
            </div>

            <div style="text-align:right;">
                <div class="card-title" style="font-weight:700; font-size:14px;">
                    $<?= number_format($p['valor_pago'], 0, ',', '.') ?>
                </div>

                <span class="badge-pill <?= $badgeClass ?>">
                    <?= $p['dias_restantes'] ?> días
                </span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>







