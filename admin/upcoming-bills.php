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

<style>
.prox-wrapper {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e5e5e5;
}

.prox-card {
    display: flex;
    align-items: center;
    padding: 15px 10px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s ease, transform .1s ease;
    cursor: pointer;
}

.prox-card:last-child {
    border-bottom: none;
}

.prox-card:hover {
    background: #f7faff;
    transform: translateX(3px);
}

.avatar {
    width: 45px;
    height: 45px;
    margin-right: 15px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #e3e3e3;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prox-info {
    flex: 1;
}

.prox-name {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.prox-sub {
    font-size: 13px;
    color: #666;
}

.prox-price {
    font-size: 14px;
    font-weight: bold;
}

.badge-days {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    color: #fff;
}

.badge-green { background: #4caf50; }
.badge-yellow { background: #fbc02d; }
.badge-red { background: #f44336; }
</style>

<div class="section-title mb-3">Próximos pagos a vencer (7&nbsp;días)</div>

<div class="prox-wrapper">

<?php if (empty($proxPagos)): ?>

    <div class="no-asist">No hay pagos por vencer en los próximos 7 días.</div>

<?php else: ?>

    <?php foreach ($proxPagos as $p): ?>

        <?php
            // Foto
            $foto = (!empty($p['imagen_perfil']))
                ? '../uploads/clientes/'.$p['imagen_perfil']
                : 'https://ui-avatars.com/api/?name='.urlencode($p['nombres'].' '.$p['apellidos']).'&background=2196f3&color=fff';

            // Días restantes con color
            if ($p['dias_restantes'] <= 1) {
                $badgeClass = 'badge-red';
            } elseif ($p['dias_restantes'] <= 3) {
                $badgeClass = 'badge-yellow';
            } else {
                $badgeClass = 'badge-green';
            }
        ?>

        <div class="prox-card" onclick="window.location.href='clients/detail.php?id=<?= $p['id'] ?>'">

            <div class="avatar">
                <img src="<?= $foto ?>" class="avatar-img">
            </div>

            <div class="prox-info">
                <div class="prox-name">
                    <?= htmlspecialchars($p['nombres'].' '.$p['apellidos']) ?>
                </div>
                <div class="prox-sub">
                    Plan: <?= htmlspecialchars($p['plan']) ?>  
                    · Tel: <?= htmlspecialchars($p['telefono']) ?>
                </div>
            </div>

            <div style="text-align:right;">
                <div class="prox-price">
                    $<?= number_format($p['valor_pago'], 0, ',', '.') ?>
                </div>
                <span class="badge-days <?= $badgeClass ?>">
                    <?= $p['dias_restantes'] ?> días
                </span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>






