<?php
/* ——— Créditos activos ——— */
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {

    $stmt = db()->prepare("
        SELECT  
            c.id,
            c.idCliente, 
            cli.nombres,
            cli.apellidos,
            cli.imagen_perfil,
            c.fecha,
            c.descripcion,
            c.valor,
            c.fecha_limite,
            DATEDIFF(c.fecha_limite, :hoy) AS dias_restantes
        FROM creditos c
        JOIN clientes cli ON cli.id = c.idCliente
        WHERE c.estado = 'activo' 
          AND cli.estado = 'activo'
        ORDER BY c.fecha_limite ASC
    ");
    $stmt->execute([':hoy'=>$hoy]);
    $creditos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<style>
.creditos-wrapper {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e5e5e5;
}

.credito-card {
    display: flex;
    align-items: center;
    padding: 15px 10px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s ease, transform .1s ease;
    cursor: pointer;
}

.credito-card:last-child {
    border-bottom: none;
}

.credito-card:hover {
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

.credito-info {
    flex: 1;
}

.credito-name {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.credito-sub {
    font-size: 13px;
    color: #666;
}

.credito-valor {
    font-size: 14px;
    font-weight: 700;
    color: #000;
}

.credito-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    color: #fff;
}

.badge-ok { background: #4caf50; }
.badge-warning { background: #fbc02d; }
.badge-danger { background: #f44336; }

.credito-footer {
    padding-top: 10px;
    font-weight: bold;
}
</style>

<div class="section-title mb-3">Créditos activos</div>

<div class="creditos-wrapper">

<?php 
$total = 0;
if (empty($creditos)): ?>

    <div class="no-asist">No hay créditos activos.</div>

<?php else: ?>

    <?php foreach ($creditos as $cr): ?>
        <?php 
            $total += $cr['valor'];

            // Foto
            $foto = (!empty($cr['imagen_perfil']))
                ? '../uploads/clientes/'.$cr['imagen_perfil']
                : 'https://ui-avatars.com/api/?name='.urlencode($cr['nombres'].' '.$cr['apellidos']).'&background=f44336&color=fff';

            // Etiqueta días restantes
            if ($cr['dias_restantes'] < 0) {
                $diasTxt = 'Vencido hace '.abs($cr['dias_restantes']).' días';
                $badgeClass = 'badge-danger';
            } elseif ($cr['dias_restantes'] == 0) {
                $diasTxt = 'Vence hoy';
                $badgeClass = 'badge-warning';
            } elseif ($cr['dias_restantes'] <= 3) {
                $diasTxt = $cr['dias_restantes'].' días (urgente)';
                $badgeClass = 'badge-warning';
            } else {
                $diasTxt = $cr['dias_restantes'].' días';
                $badgeClass = 'badge-ok';
            }
        ?>

        <div class="credito-card" onclick="window.location.href='clients/detail.php?id=<?= $cr['idCliente'] ?>'">

            <div class="avatar">
                <img src="<?= $foto ?>" class="avatar-img">
            </div>

            <div class="credito-info">
                <div class="credito-name">
                    <?= htmlspecialchars($cr['nombres'].' '.$cr['apellidos']) ?>
                </div>
                <div class="credito-sub">
                    <?= htmlspecialchars($cr['descripcion']) ?> · <?= htmlspecialchars($cr['fecha']) ?>
                </div>
            </div>

            <div style="text-align:right;">
                <div class="credito-valor">
                    $<?= number_format($cr['valor'], 0, ',', '.') ?>
                </div>
                <span class="credito-badge <?= $badgeClass ?>">
                    <?= $diasTxt ?>
                </span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>

<!-- TOTAL GLOBAL -->
<div class="creditos-wrapper" style="margin-top:10px;">
    <div class="credito-footer">
        Total global en créditos:  
        <strong>$<?= number_format($total,0,',','.') ?></strong>
    </div>
</div>



