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

<div class="section-title mb-3">Créditos activos</div>

<div class="card-list-wrapper">

<?php 
$total = 0;

if (empty($creditos)): ?>

    <div class="card-list-empty">No hay créditos activos.</div>

<?php else: ?>

    <?php foreach ($creditos as $cr): ?>

        <?php 
            $total += $cr['valor'];

            // Foto
            $foto = (!empty($cr['imagen_perfil']))
                ? '../uploads/clientes/'.$cr['imagen_perfil']
                : 'https://ui-avatars.com/api/?name='.urlencode($cr['nombres'].' '.$cr['apellidos']).'&background=f44336&color=fff';

            // Badge unificada por días restantes
            if ($cr['dias_restantes'] < 0) {
                $diasTxt = 'Vencido hace '.abs($cr['dias_restantes']).' días';
                $badgeClass = 'badge-red';
            } elseif ($cr['dias_restantes'] == 0) {
                $diasTxt = 'Vence hoy';
                $badgeClass = 'badge-yellow';
            } elseif ($cr['dias_restantes'] <= 3) {
                $diasTxt = $cr['dias_restantes'].' días (urgente)';
                $badgeClass = 'badge-yellow';
            } else {
                $diasTxt = $cr['dias_restantes'].' días';
                $badgeClass = 'badge-green';
            }
        ?>

        <div class="card-item" onclick="window.location.href='clients/detail.php?id=<?= $cr['idCliente'] ?>'">

            <div class="card-avatar">
                <img src="<?= $foto ?>" class="card-avatar-img" alt="foto">
            </div>

            <div class="card-info">
                <div class="card-title">
                    <?= htmlspecialchars($cr['nombres'].' '.$cr['apellidos']) ?>
                </div>

                <div class="card-sub">
                    <?= htmlspecialchars($cr['descripcion']) ?>
                    · <?= htmlspecialchars($cr['fecha']) ?>
                </div>
            </div>

            <div style="text-align:right;">
                <div class="card-title" style="font-size:14px; font-weight:700;">
                    $<?= number_format($cr['valor'], 0, ',', '.') ?>
                </div>

                <span class="badge-pill <?= $badgeClass ?>">
                    <?= $diasTxt ?>
                </span>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>

<!-- TOTAL GLOBAL -->
<div class="card-list-wrapper" style="margin-top:10px;">
    <div class="card-title" style="font-size:15px;">
        Total global en créditos:  
        <strong>$<?= number_format($total,0,',','.') ?></strong>
    </div>
</div>




