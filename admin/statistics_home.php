<?php
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/../inc/config.php';
date_default_timezone_set('America/Bogota');

try {
    

    $stmt = db()->prepare("
        SELECT COUNT(*) 
        FROM clientes 
        WHERE borrado = 0 
          AND estado = 'activo' 
          AND DATE(vencimiento_plan) = :hoy 
          AND congelado = 0
    ");
    $stmt->execute([':hoy' => $hoy]);  // $hoy debe venir de config.php en formato YYYY-MM-DD
    $vencimientosHoyCount = (int)$stmt->fetchColumn();

} catch (PDOException $e) {
    die('Error: '.$e->getMessage());
}
?>

<?php
// Contar clientes con estado activo y no borrados
$stmtActivos = db()->prepare("
    SELECT COUNT(*) 
    FROM clientes 
    WHERE borrado = 0 AND estado = 'activo'
");
$stmtActivos->execute();
$clientesActivosCount = (int)$stmtActivos->fetchColumn();
?>
<?php
// Contar clientes con estado activo y no borrados
$stmtActivos = db()->prepare("
    SELECT COUNT(*) 
    FROM clientes 
    WHERE borrado = 0 AND estado = 'activo'
");
$stmtActivos->execute();
$clientesActivosCount = (int)$stmtActivos->fetchColumn();
?>

<?php
$primerDiaMes = date('Y-m-01');
$ultimoDiaMes = date('Y-m-t');

$stmtNuevos = db()->prepare("
    SELECT COUNT(*) 
    FROM clientes 
    WHERE borrado = 0 
      AND DATE(created_at) BETWEEN :inicio AND :fin
");
$stmtNuevos->execute([
    ':inicio' => $primerDiaMes,
    ':fin' => $ultimoDiaMes
]);
$nuevosClientesCount = (int)$stmtNuevos->fetchColumn();
?>
<?php
$primerDiaMes = date('Y-m-01');
$ultimoDiaMes = date('Y-m-t');

$stmtAsistenciasUnicas = db()->prepare("
    SELECT COUNT(DISTINCT idCliente) 
    FROM asistencias 
    WHERE DATE(fecha) BETWEEN :inicio AND :fin
");
$stmtAsistenciasUnicas->execute([
    ':inicio' => $primerDiaMes,
    ':fin' => $ultimoDiaMes
]);
$asistenciasUnicasCount = (int)$stmtAsistenciasUnicas->fetchColumn();
?>


<style>
/* ============================================================
      TARJETAS ESTADÍSTICAS ESTILO “MY DEVICES”
      (Mockup moderno / neón / redondeado)
============================================================ */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

/* Tarjeta base */
.stats-card {
    border-radius: 18px;
    padding: 20px 18px;
    color: #fff;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .20s ease;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
}

/* Hover */
.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.25);
}

/* Número */
.stats-card h3 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 6px;
}

/* Título */
.stats-card p {
    font-size: .95rem;
    font-weight: 500;
    margin: 0;
}

/* Icono grande de fondo */
.stats-card::after {
    content: "\f0c8"; /* default icon */
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    font-size: 70px;
    opacity: 0.10;
    position: absolute;
    right: -10px;
    bottom: -10px;
}

/* ============================================================
      COLORES PERSONALIZADOS (como “My Devices”)
============================================================ */

.stats-blue     { background: linear-gradient(135deg,#84b8ff,#4a8dff); }
.stats-green    { background: linear-gradient(135deg,#8ed8a1,#3bba63); }
.stats-yellow   { background: linear-gradient(135deg,#ffe9a6,#e2b34c); }
.stats-purple   { background: linear-gradient(135deg,#c4a8ff,#8f63ff); }
.stats-red      { background: linear-gradient(135deg,#ffb1b1,#ff6a6a); }

/* ============================================================
      MODO OSCURO
============================================================ */
body.dark-mode .stats-card {
    box-shadow: 0 0 0 rgba(0,0,0,0.0);
}

body.dark-mode .stats-card:hover {
    box-shadow: 0 8px 15px rgba(0,0,0,0.45);
}

</style>
<div class="stats-grid">

    <div class="stats-card stats-blue">
        <h3><?= $vencimientosHoyCount ?></h3>
        <p>Clientes con Vencimientos Hoy</p>
    </div>

    <div class="stats-card stats-green">
        <h3><?= $clientesActivosCount ?></h3>
        <p>Número de Clientes Activos</p>
    </div>

    <div class="stats-card stats-yellow">
        <h3><?= $nuevosClientesCount ?></h3>
        <p>Nuevos Clientes Este Mes</p>
    </div>

    <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
    <div class="stats-card stats-purple">
        <h3 id="asistencias-count">0</h3>
        <p>Asistencias Únicas de Clientes Hoy</p>
    </div>
    <?php endif; ?>

    <div class="stats-card stats-red">
        <h3><?= $asistenciasUnicasCount ?></h3>
        <p>Asistencias Únicas Este Mes</p>
    </div>

</div>

