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





<div class="container mt-4" style="border-radius: 25px;">
  <div class="row row-cols-1 row-cols-md-5 g-3 text-center">

    <div class="col">
      <div class="card card-soft bg-soft-blue vencimientos shadow-sm">
        <div class="card-body">
          <h3 id="vencimientos"><?= $vencimientosHoyCount ?></h3>
          <p class="mb-0">Clientes con Vencimientos Hoy</p>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card card-soft bg-soft-green clientes-activos shadow-sm">
        <div class="card-body">
          <h3 id="clientesActivos"><?= $clientesActivosCount ?></h3>
          <p class="mb-0">Numero de Clientes Activos</p>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card card-soft bg-soft-yellow nuevos-clientes shadow-sm">
        <div class="card-body">
          <h3 id="nuevosClientes"><?= $nuevosClientesCount ?></h3>
          <p class="mb-0">Nuevos Clientes Este Mes</p>
        </div>
      </div>
    </div>
	  
	  <?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
     <div class="col">
  <div class="card card-soft bg-soft-purple asistencias-hoy shadow-sm">
    <div class="card-body text-center p-3">
      <h3  id="asistencias-count">0</h3>
      <p class="mb-0">Asistencias Únicas de Clientes Hoy</p>
    </div>
  </div>
</div>

    <?php endif; ?>

    <div class="col">
      <div class="card card-soft bg-soft-red asistencias shadow-sm">
        <div class="card-body">
          <h3 id="asistenciasUnicas"><?= $asistenciasUnicasCount ?></h3>
          <p class="mb-0">Asistencias Únicas Este Mes</p>
        </div>
      </div>
    </div>
</div>
</div>


