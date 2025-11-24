
	<div class="kpi-grid">

<?php
try {

    /* ===========================
       FECHAS
    ============================ */
    $hoy          = date('Y-m-d');
    $primerDiaMes = date('Y-m-01');
    $ultimoDiaMes = date('Y-m-t');

    /* ===========================
       1. VENCEN HOY
    ============================ */
    $stmt = db()->prepare("
        SELECT COUNT(*) 
        FROM clientes 
        WHERE borrado = 0 
          AND estado = 'activo' 
          AND DATE(vencimiento_plan) = :hoy 
          AND congelado = 0
    ");
    $stmt->execute([':hoy' => $hoy]);
    $vencimientosHoyCount = (int)$stmt->fetchColumn();


    /* ===========================
       2. CLIENTES ACTIVOS
    ============================ */
    $stmtActivos = db()->query("
        SELECT COUNT(*) 
        FROM clientes 
        WHERE borrado = 0 AND estado = 'activo'
    ");
    $clientesActivosCount = (int)$stmtActivos->fetchColumn();


    /* ===========================
       3. NUEVOS ESTE MES
    ============================ */
    $stmtNuevos = db()->prepare("
        SELECT COUNT(*) 
        FROM clientes 
        WHERE borrado = 0 
          AND DATE(created_at) BETWEEN :inicio AND :fin
    ");
    $stmtNuevos->execute([
        ':inicio' => $primerDiaMes,
        ':fin'    => $ultimoDiaMes
    ]);
    $nuevosClientesCount = (int)$stmtNuevos->fetchColumn();


    /* ===========================
       4. ASISTENCIAS ÚNICAS MES
    ============================ */
    $stmtAsistenciasUnicas = db()->prepare("
        SELECT COUNT(DISTINCT idCliente) 
        FROM asistencias 
        WHERE fecha BETWEEN :inicio AND :fin
    ");
    $stmtAsistenciasUnicas->execute([
        ':inicio' => $primerDiaMes,
        ':fin'    => $ultimoDiaMes
    ]);
    $asistenciasUnicasCount = (int)$stmtAsistenciasUnicas->fetchColumn();


    /* ===========================
       5. VENTAS DEL MES (todas las cajas, sin base)
       usamos total_vendido en tabla `cajas`
    ============================ */
    $stmtVentasMes = db()->prepare("
        SELECT COALESCE(SUM(total_vendido), 0)
        FROM cajas
        WHERE fecha_apertura BETWEEN :inicio AND :fin
          AND borrado = 0
    ");
    $stmtVentasMes->execute([
        ':inicio' => $primerDiaMes,
        ':fin'    => $ultimoDiaMes
    ]);
    $ventasMesTotal = (float)$stmtVentasMes->fetchColumn();


    /* ===========================
       6. VENTAS HOY (todas las cajas de hoy, sin base)
    ============================ */
    $stmtVentasHoy = db()->prepare("
        SELECT COALESCE(SUM(total_vendido), 0)
        FROM cajas
        WHERE fecha_apertura = :hoy
          AND borrado = 0
    ");
    $stmtVentasHoy->execute([':hoy' => $hoy]);
    $ventasHoyTotal = (float)$stmtVentasHoy->fetchColumn();


    /* ===========================
       7. CRÉDITOS ACTIVOS
    ============================ */
    $stmtCreditos = db()->query("
        SELECT COUNT(*) 
        FROM creditos c
        INNER JOIN clientes cl ON c.idCliente = cl.id
        WHERE c.estado = 'abierto'
          AND cl.borrado = 0 
          AND cl.estado = 'activo'
    ");
    $creditosActivosCount = (int)$stmtCreditos->fetchColumn();


} catch (PDOException $e) {
    $vencimientosHoyCount   = 0;
    $clientesActivosCount   = 0;
    $nuevosClientesCount    = 0;
    $asistenciasUnicasCount = 0;
    $ventasMesTotal         = 0;
    $ventasHoyTotal         = 0;
    $creditosActivosCount   = 0;
}
?>


<!-- ===================== TARJETAS KPI ===================== -->

<!-- Vencen hoy -->
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Vencen Hoy</div>
      <div class="kpi-value"><?= $vencimientosHoyCount ?></div>
      <div class="kpi-trend neutral">
        <i class="fas fa-exclamation-circle"></i> Requieren atención
      </div>
    </div>
    <div class="kpi-icon blue">
      <i class="fas fa-calendar-times"></i>
    </div>
  </div>
</div>

<!-- Clientes activos -->
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Clientes Activos</div>
      <div class="kpi-value"><?= $clientesActivosCount ?></div>
      <div class="kpi-trend positive">
        <i class="fas fa-check-circle"></i> Con plan vigente
      </div>
    </div>
    <div class="kpi-icon green">
      <i class="fas fa-users"></i>
    </div>
  </div>
</div>

<!-- Nuevos este mes -->
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Nuevos Este Mes</div>
      <div class="kpi-value"><?= $nuevosClientesCount ?></div>
      <div class="kpi-trend neutral">
        <i class="fas fa-calendar"></i> <?= date('F') ?>
      </div>
    </div>
    <div class="kpi-icon orange">
      <i class="fas fa-user-plus"></i>
    </div>
  </div>
</div>

<!-- Asistencias hoy (si tiene permiso) / Créditos activos (si no) -->
<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Asistencias Hoy</div>
      <div class="kpi-value" id="asistencias-count">0</div>
      <div class="kpi-trend neutral">
        <i class="fas fa-clock"></i> Tiempo real
      </div>
    </div>
    <div class="kpi-icon purple">
      <i class="fas fa-walking"></i>
    </div>
  </div>
</div>
<?php else: ?>
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Créditos Activos</div>
      <div class="kpi-value"><?= $creditosActivosCount ?></div>
      <div class="kpi-trend neutral">
        <i class="fas fa-wallet"></i> Cartera abierta
      </div>
    </div>
    <div class="kpi-icon orange">
      <i class="fas fa-file-invoice-dollar"></i>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Asistencias únicas mes -->
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Asist. Únicas Mes</div>
      <div class="kpi-value"><?= $asistenciasUnicasCount ?></div>
      <div class="kpi-trend neutral">
        <i class="fas fa-chart-bar"></i> Clientes únicos
      </div>
    </div>
    <div class="kpi-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
      <i class="fas fa-chart-line"></i>
    </div>
  </div>
</div>

<!-- Ventas HOY -->
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Ventas Hoy</div>
      <div class="kpi-value">
        $<?= number_format($ventasHoyTotal, 0, ',', '.') ?>
      </div>
      <div class="kpi-trend neutral">
        <i class="fas fa-cash-register"></i> Todas las cajas de hoy
      </div>
    </div>
    <div class="kpi-icon purple">
      <i class="fas fa-cash-register"></i>
    </div>
  </div>
</div>

<!-- Ingresos del mes -->
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Ingresos del Mes</div>
      <div class="kpi-value">
        $<?= number_format($ventasMesTotal, 0, ',', '.') ?>
      </div>
      <div class="kpi-trend positive">
        <i class="fas fa-chart-pie"></i> Acumulado <?= date('F') ?>
      </div>
    </div>
    <div class="kpi-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
      <i class="fas fa-dollar-sign"></i>
    </div>
  </div>
</div>

<!-- Créditos activos (si además quieres verlo siempre) -->
<div class="kpi-card">
  <div class="kpi-header">
    <div class="kpi-content">
      <div class="kpi-label">Créditos Activos</div>
      <div class="kpi-value"><?= $creditosActivosCount ?></div>
      <div class="kpi-trend neutral">
        <i class="fas fa-wallet"></i> Cartera abierta
      </div>
    </div>
    <div class="kpi-icon orange">
      <i class="fas fa-file-invoice-dollar"></i>
    </div>
  </div>
</div>

</div>

	