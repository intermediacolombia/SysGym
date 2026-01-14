<?php 
require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';

// Verificar permisos
$permisopage = 'Enviar WhatsApp Masivo';
include('../../login/restriction.php');

// Obtener planes activos para el filtro
$stmtPlanes = db()->prepare("SELECT id, nombre FROM planes WHERE borrado = 0 AND estado = 'activo' ORDER BY nombre");
$stmtPlanes->execute();
$planes = $stmtPlanes->fetchAll(PDO::FETCH_ASSOC);

// Procesar filtros si se envio el formulario
$clientes = [];
$filtrosAplicados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtrar'])) {
    
    $where = ["c.borrado = 0"];
    $params = [];
    
    // Filtro: Estado del cliente (multiple)
    if (!empty($_POST['estado']) && is_array($_POST['estado'])) {
        $estadoPlaceholders = [];
        foreach ($_POST['estado'] as $i => $estado) {
            $key = ":estado_$i";
            $estadoPlaceholders[] = $key;
            $params[$key] = $estado;
        }
        $where[] = "c.estado IN (" . implode(',', $estadoPlaceholders) . ")";
        $filtrosAplicados[] = "Estado: " . implode(', ', array_map('ucfirst', $_POST['estado']));
    }
    
    // Filtro: Plan (multiple)
    if (!empty($_POST['plan']) && is_array($_POST['plan'])) {
        $planPlaceholders = [];
        foreach ($_POST['plan'] as $i => $planId) {
            $key = ":plan_$i";
            $planPlaceholders[] = $key;
            $params[$key] = $planId;
        }
        $where[] = "c.plan IN (" . implode(',', $planPlaceholders) . ")";
        
        // Obtener nombres de planes seleccionados
        $nombresPlanes = [];
        foreach ($planes as $p) {
            if (in_array($p['id'], $_POST['plan'])) {
                $nombresPlanes[] = $p['nombre'];
            }
        }
        $filtrosAplicados[] = "Plan: " . implode(', ', $nombresPlanes);
    }
    
    // Filtro: Genero (multiple)
    if (!empty($_POST['genero']) && is_array($_POST['genero'])) {
        $generoPlaceholders = [];
        foreach ($_POST['genero'] as $i => $genero) {
            $key = ":genero_$i";
            $generoPlaceholders[] = $key;
            $params[$key] = $genero;
        }
        $where[] = "c.genero IN (" . implode(',', $generoPlaceholders) . ")";
        $filtrosAplicados[] = "Genero: " . implode(', ', array_map('ucfirst', $_POST['genero']));
    }
    
    // Filtro: Congelado (multiple)
    if (isset($_POST['congelado']) && is_array($_POST['congelado']) && count($_POST['congelado']) > 0) {
        $congeladoPlaceholders = [];
        foreach ($_POST['congelado'] as $i => $val) {
            $key = ":congelado_$i";
            $congeladoPlaceholders[] = $key;
            $params[$key] = $val;
        }
        $where[] = "c.congelado IN (" . implode(',', $congeladoPlaceholders) . ")";
        $labels = array_map(function($v) { return $v == 1 ? 'Si' : 'No'; }, $_POST['congelado']);
        $filtrosAplicados[] = "Congelado: " . implode(', ', $labels);
    }
    
    // Filtro: Notificaciones (multiple)
    if (isset($_POST['notificaciones']) && is_array($_POST['notificaciones']) && count($_POST['notificaciones']) > 0) {
        $notifPlaceholders = [];
        foreach ($_POST['notificaciones'] as $i => $val) {
            $key = ":notif_$i";
            $notifPlaceholders[] = $key;
            $params[$key] = $val;
        }
        $where[] = "c.notificaciones IN (" . implode(',', $notifPlaceholders) . ")";
        $labels = array_map(function($v) { return $v == 1 ? 'Activas' : 'Inactivas'; }, $_POST['notificaciones']);
        $filtrosAplicados[] = "Notificaciones: " . implode(', ', $labels);
    }
    
    // Filtro: Vencimiento del plan
    if (!empty($_POST['vencimiento_desde'])) {
        $where[] = "c.vencimiento_plan >= :venc_desde";
        $params[':venc_desde'] = $_POST['vencimiento_desde'];
        $filtrosAplicados[] = "Vencimiento desde: " . $_POST['vencimiento_desde'];
    }
    
    if (!empty($_POST['vencimiento_hasta'])) {
        $where[] = "c.vencimiento_plan <= :venc_hasta";
        $params[':venc_hasta'] = $_POST['vencimiento_hasta'];
        $filtrosAplicados[] = "Vencimiento hasta: " . $_POST['vencimiento_hasta'];
    }
    
    // Filtro: Plan vencido (vencimiento < hoy)
    if (isset($_POST['plan_vencido']) && $_POST['plan_vencido'] == '1') {
        $where[] = "c.vencimiento_plan < CURDATE()";
        $filtrosAplicados[] = "Plan vencido: Si";
    }
    
    // Filtro: Plan por vencer (proximos X dias)
    if (!empty($_POST['dias_por_vencer'])) {
        $dias = intval($_POST['dias_por_vencer']);
        $where[] = "c.vencimiento_plan BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)";
        $params[':dias'] = $dias;
        $filtrosAplicados[] = "Plan por vencer en: $dias dias";
    }
    
    // Filtro: Tiene telefono
    $where[] = "c.telefono IS NOT NULL AND c.telefono != ''";
    $where[] = "c.dialCode IS NOT NULL AND c.dialCode != ''";
    
    // Construir query
    $sql = "SELECT 
                c.id,
                c.nombres,
                c.apellidos,
                c.dialCode,
                c.telefono,
                c.estado,
                c.genero,
                c.vencimiento_plan,
                c.congelado,
                c.notificaciones,
                p.nombre AS nombre_plan
            FROM clientes c
            LEFT JOIN planes p ON c.plan = p.id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY c.apellidos, c.nombres";
    
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Envio Masivo de WhatsApp</title>
    <?php include('../../inc/header.php'); ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            background: #e9ecef;
            border-radius: 25px;
            margin: 0 10px;
            color: #6c757d;
            font-weight: 600;
        }
        .step.active {
            background: var(--system-color-primary, #d81f1f);
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .filter-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .filter-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .result-count {
            font-size: 2rem;
            font-weight: bold;
            color: var(--system-color-primary, #d81f1f);
        }
        .filtros-aplicados {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .filtro-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 12px;
            border-radius: 20px;
            margin: 3px;
            font-size: 0.85rem;
        }
        
        /* Boton flotante */
        .btn-flotante {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            font-size: 24px;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-flotante:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }
        
        .badge-cola {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            border: 2px solid white;
        }
        
        .mensaje-cola-item {
            border-left: 4px solid #667eea;
            padding: 12px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .mensaje-cola-item:hover {
            background: #e9ecef;
        }
        
        /* Estilos para multi-select dropdown */
        .multi-select-dropdown {
            position: relative;
        }
        
        .multi-select-btn {
            width: 100%;
            text-align: left;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 38px;
        }
        
        .multi-select-btn:hover {
            border-color: #86b7fe;
        }
        
        .multi-select-btn .selected-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            color: #6c757d;
        }
        
        .multi-select-btn .selected-text.has-selection {
            color: #212529;
        }
        
        .multi-select-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1050;
            display: none;
            max-height: 250px;
            overflow-y: auto;
        }
        
        .multi-select-menu.show {
            display: block;
        }
        
        .multi-select-item {
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background 0.2s;
        }
        
        .multi-select-item:hover {
            background: #f0f0f0;
        }
        
        .multi-select-item input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .multi-select-item label {
            margin: 0;
            cursor: pointer;
            flex: 1;
        }
        
        .multi-select-actions {
            border-top: 1px solid #eee;
            padding: 8px;
            display: flex;
            gap: 5px;
        }
        
        .multi-select-actions button {
            flex: 1;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
    <div class="portada">
        <h1><i class="fab fa-whatsapp"></i> Envio Masivo de WhatsApp</h1>
    </div>
</div>

<?php include('../../inc/menu.php'); ?>

<div class="container mt-4">
    
    <!-- Indicador de pasos -->
    <div class="step-indicator">
        <div class="step active">
            <span class="step-number">1</span>
            Filtrar Clientes
        </div>
        <div class="step">
            <span class="step-number">2</span>
            Redactar Mensaje
        </div>
        <div class="step">
            <span class="step-number">3</span>
            Confirmar y Enviar
        </div>
    </div>
    
    <!-- Card de Filtros -->
    <div class="card filter-card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Paso 1: Filtrar Destinatarios</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="formFiltros">
                <div class="row g-3">
                    
                    <!-- Estado del cliente (Multi-select) -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-toggle-on me-1"></i> Estado</label>
                        <div class="multi-select-dropdown" data-name="estado">
                            <div class="multi-select-btn">
                                <span class="selected-text">Todos</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="multi-select-menu">
                                <div class="multi-select-item">
                                    <input type="checkbox" id="estado_activo" value="activo" 
                                           <?= (isset($_POST['estado']) && in_array('activo', $_POST['estado'])) ? 'checked' : '' ?>>
                                    <label for="estado_activo">Activo</label>
                                </div>
                                <div class="multi-select-item">
                                    <input type="checkbox" id="estado_inactivo" value="inactivo"
                                           <?= (isset($_POST['estado']) && in_array('inactivo', $_POST['estado'])) ? 'checked' : '' ?>>
                                    <label for="estado_inactivo">Inactivo</label>
                                </div>
                                <div class="multi-select-actions">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-clear">Limpiar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Plan (Multi-select) -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-id-card me-1"></i> Plan</label>
                        <div class="multi-select-dropdown" data-name="plan">
                            <div class="multi-select-btn">
                                <span class="selected-text">Todos los planes</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="multi-select-menu">
                                <?php foreach ($planes as $plan): ?>
                                <div class="multi-select-item">
                                    <input type="checkbox" id="plan_<?= $plan['id'] ?>" value="<?= $plan['id'] ?>"
                                           <?= (isset($_POST['plan']) && in_array($plan['id'], $_POST['plan'])) ? 'checked' : '' ?>>
                                    <label for="plan_<?= $plan['id'] ?>"><?= htmlspecialchars($plan['nombre']) ?></label>
                                </div>
                                <?php endforeach; ?>
                                <div class="multi-select-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-select-all">Todos</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-clear">Limpiar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Genero (Multi-select) -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-venus-mars me-1"></i> Genero</label>
                        <div class="multi-select-dropdown" data-name="genero">
                            <div class="multi-select-btn">
                                <span class="selected-text">Todos</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="multi-select-menu">
                                <div class="multi-select-item">
                                    <input type="checkbox" id="genero_masculino" value="masculino"
                                           <?= (isset($_POST['genero']) && in_array('masculino', $_POST['genero'])) ? 'checked' : '' ?>>
                                    <label for="genero_masculino">Masculino</label>
                                </div>
                                <div class="multi-select-item">
                                    <input type="checkbox" id="genero_femenino" value="femenino"
                                           <?= (isset($_POST['genero']) && in_array('femenino', $_POST['genero'])) ? 'checked' : '' ?>>
                                    <label for="genero_femenino">Femenino</label>
                                </div>
                                <div class="multi-select-item">
                                    <input type="checkbox" id="genero_otro" value="otro"
                                           <?= (isset($_POST['genero']) && in_array('otro', $_POST['genero'])) ? 'checked' : '' ?>>
                                    <label for="genero_otro">Otro</label>
                                </div>
                                <div class="multi-select-actions">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-clear">Limpiar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Congelado (Multi-select) -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-snowflake me-1"></i> Plan Congelado</label>
                        <div class="multi-select-dropdown" data-name="congelado">
                            <div class="multi-select-btn">
                                <span class="selected-text">Todos</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="multi-select-menu">
                                <div class="multi-select-item">
                                    <input type="checkbox" id="congelado_no" value="0"
                                           <?= (isset($_POST['congelado']) && in_array('0', $_POST['congelado'])) ? 'checked' : '' ?>>
                                    <label for="congelado_no">No congelado</label>
                                </div>
                                <div class="multi-select-item">
                                    <input type="checkbox" id="congelado_si" value="1"
                                           <?= (isset($_POST['congelado']) && in_array('1', $_POST['congelado'])) ? 'checked' : '' ?>>
                                    <label for="congelado_si">Congelado</label>
                                </div>
                                <div class="multi-select-actions">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-clear">Limpiar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notificaciones (Multi-select) -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-bell me-1"></i> Notificaciones</label>
                        <div class="multi-select-dropdown" data-name="notificaciones">
                            <div class="multi-select-btn">
                                <span class="selected-text">Todos</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="multi-select-menu">
                                <div class="multi-select-item">
                                    <input type="checkbox" id="notif_activas" value="1"
                                           <?= (isset($_POST['notificaciones']) && in_array('1', $_POST['notificaciones'])) ? 'checked' : '' ?>>
                                    <label for="notif_activas">Activas</label>
                                </div>
                                <div class="multi-select-item">
                                    <input type="checkbox" id="notif_inactivas" value="0"
                                           <?= (isset($_POST['notificaciones']) && in_array('0', $_POST['notificaciones'])) ? 'checked' : '' ?>>
                                    <label for="notif_inactivas">Inactivas</label>
                                </div>
                                <div class="multi-select-actions">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-clear">Limpiar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Plan vencido -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar-times me-1"></i> Plan Vencido</label>
                        <select name="plan_vencido" class="form-select">
                            <option value="">No filtrar</option>
                            <option value="1" <?= (isset($_POST['plan_vencido']) && $_POST['plan_vencido'] == '1') ? 'selected' : '' ?>>Solo vencidos</option>
                        </select>
                    </div>
                    
                    <!-- Dias por vencer -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-hourglass-half me-1"></i> Por vencer en (dias)</label>
                        <input type="number" name="dias_por_vencer" class="form-control" min="1" max="365" 
                               value="<?= isset($_POST['dias_por_vencer']) ? htmlspecialchars($_POST['dias_por_vencer']) : '' ?>"
                               placeholder="Ej: 7">
                    </div>
                    
                    <!-- Vencimiento desde -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar me-1"></i> Vencimiento Desde</label>
                        <input type="date" name="vencimiento_desde" class="form-control" 
                               value="<?= isset($_POST['vencimiento_desde']) ? htmlspecialchars($_POST['vencimiento_desde']) : '' ?>">
                    </div>
                    
                    <!-- Vencimiento hasta -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar me-1"></i> Vencimiento Hasta</label>
                        <input type="date" name="vencimiento_hasta" class="form-control" 
                               value="<?= isset($_POST['vencimiento_hasta']) ? htmlspecialchars($_POST['vencimiento_hasta']) : '' ?>">
                    </div>
                    
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-eraser me-1"></i> Limpiar Filtros
                    </button>
                    <button type="submit" name="filtrar" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-1"></i> Buscar Clientes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resultados -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtrar'])): ?>
    <div class="card filter-card">
        <div class="card-header bg-success">
            <h5 class="mb-0"><i class="fas fa-users me-2"></i> Resultados</h5>
        </div>
        <div class="card-body">
            
            <!-- Filtros aplicados -->
            <?php if (!empty($filtrosAplicados)): ?>
            <div class="filtros-aplicados">
                <strong><i class="fas fa-filter me-1"></i> Filtros aplicados:</strong>
                <?php foreach ($filtrosAplicados as $filtro): ?>
                    <span class="filtro-badge"><?= htmlspecialchars($filtro) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Contador -->
            <div class="text-center mb-4">
                <span class="result-count"><?= count($clientes) ?></span>
                <p class="text-muted mb-0">clientes encontrados</p>
            </div>
            
            <?php if (count($clientes) > 0): ?>
            <!-- Tabla de resultados -->
            <div class="table-responsive">
                <table id="tablaClientes" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Nombre</th>
                            <th>Telefono</th>
                            <th>Plan</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="cliente-check" 
                                       data-id="<?= $cliente['id'] ?>"
                                       data-telefono="+<?= htmlspecialchars($cliente['dialCode']) ?><?= htmlspecialchars(str_replace(' ', '', $cliente['telefono'])) ?>"
                                       data-nombre="<?= htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']) ?>">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']) ?></strong>
                            </td>
                            <td>
                                <i class="fab fa-whatsapp text-success me-1"></i>
                                +<?= htmlspecialchars($cliente['dialCode']) ?> <?= htmlspecialchars($cliente['telefono']) ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($cliente['nombre_plan'] ?? 'Sin plan') ?></span>
                            </td>
                            <td>
                                <?php 
                                $venc = $cliente['vencimiento_plan'];
                                $hoy = date('Y-m-d');
                                $badgeClass = $venc < $hoy ? 'bg-danger' : 'bg-success';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($venc) ?></span>
                            </td>
                            <td>
                                <?php if ($cliente['estado'] == 'activo'): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                                <?php if ($cliente['congelado'] == 1): ?>
                                    <span class="badge bg-info"><i class="fas fa-snowflake"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <hr class="my-4">
            
            <!-- Boton siguiente paso -->
            <div class="text-center">
                <button type="button" class="btn btn-success btn-lg" id="btnSiguiente" disabled>
                    <i class="fas fa-arrow-right me-2"></i> Siguiente: Redactar Mensaje
                </button>
                <p class="text-muted mt-2">
                    <small><span id="contadorSeleccionados">0</span> clientes seleccionados</small>
                </p>
            </div>
            
            <?php else: ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No se encontraron clientes con los filtros seleccionados.
            </div>
            <?php endif; ?>
            
        </div>
    </div>
    <?php endif; ?>
    
</div>

<!-- Boton flotante para ver cola -->
<button id="btnVerCola" class="btn-flotante" title="Ver mensajes en cola" style="display:none;">
    <i class="fas fa-clock"></i>
    <span class="badge-cola" id="badgeCola">0</span>
</button>

<!-- Modal de Cola -->
<div class="modal fade" id="modalCola" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-list-ul me-2"></i> Mensajes en Cola de Envio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
				
				<div id="barraProgresoEnvios" class="mb-3">
  <div class="d-flex justify-content-between align-items-center mb-1">
    <div>
      <strong><i class="fas fa-chart-line me-1"></i> Progreso de envíos (cupo diario)</strong>
      <div class="small text-muted" id="progressSubText">Calculando...</div>
    </div>
    <span class="badge bg-dark" id="progressBadge">0%</span>
  </div>

  <div class="progress" style="height: 18px; border-radius: 999px;">
    <div
      id="progressBar"
      class="progress-bar progress-bar-striped progress-bar-animated bg-success"
      role="progressbar"
      style="width: 0%; border-radius: 999px;"
      aria-valuenow="0"
      aria-valuemin="0"
      aria-valuemax="100"
    >
      <span class="small fw-bold" id="progressBarText">0%</span>
    </div>
  </div>

  <div class="d-flex justify-content-between mt-1">
    <small class="text-muted" id="progressLeftText">Faltan: -</small>
    <small class="text-muted" id="progressQueueText">En cola: -</small>
  </div>
</div>
				
                <div id="contenidoCola">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-danger" id="btnCancelarCola">
                    <i class="fas fa-trash-alt me-1"></i> Cancelar Toda la Cola
                </button>
            </div>
        </div>
    </div>
</div>
    
<?php include('../../inc/menu-footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
    
    // ══════════════════════════════════════════════════
    // MULTI-SELECT DROPDOWN
    // ══════════════════════════════════════════════════
    
    // Toggle dropdown
    $('.multi-select-btn').on('click', function(e) {
        e.stopPropagation();
        var menu = $(this).siblings('.multi-select-menu');
        
        // Cerrar otros dropdowns
        $('.multi-select-menu').not(menu).removeClass('show');
        
        menu.toggleClass('show');
    });
    
    // Cerrar al hacer clic fuera
    $(document).on('click', function() {
        $('.multi-select-menu').removeClass('show');
    });
    
    // Evitar que se cierre al hacer clic dentro del menu
    $('.multi-select-menu').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Actualizar texto al cambiar checkbox
    $('.multi-select-item input[type="checkbox"]').on('change', function() {
        var dropdown = $(this).closest('.multi-select-dropdown');
        actualizarTextoMultiSelect(dropdown);
    });
    
    // Boton "Limpiar"
    $('.btn-clear').on('click', function() {
        var dropdown = $(this).closest('.multi-select-dropdown');
        dropdown.find('input[type="checkbox"]').prop('checked', false);
        actualizarTextoMultiSelect(dropdown);
    });
    
    // Boton "Todos"
    $('.btn-select-all').on('click', function() {
        var dropdown = $(this).closest('.multi-select-dropdown');
        dropdown.find('input[type="checkbox"]').prop('checked', true);
        actualizarTextoMultiSelect(dropdown);
    });
    
    // Funcion para actualizar el texto del boton
    function actualizarTextoMultiSelect(dropdown) {
        var name = dropdown.data('name');
        var checked = dropdown.find('input[type="checkbox"]:checked');
        var textSpan = dropdown.find('.selected-text');
        
        if (checked.length === 0) {
            textSpan.text('Todos').removeClass('has-selection');
        } else {
            var labels = [];
            checked.each(function() {
                labels.push($(this).siblings('label').text());
            });
            textSpan.text(labels.join(', ')).addClass('has-selection');
        }
    }
    
    // Inicializar textos al cargar
    $('.multi-select-dropdown').each(function() {
        actualizarTextoMultiSelect($(this));
    });
    
    // Antes de enviar el formulario, crear inputs hidden con los valores
    $('#formFiltros').on('submit', function() {
        // Remover inputs hidden anteriores
        $(this).find('input[type="hidden"].multi-select-value').remove();
        
        // Crear inputs hidden para cada multi-select
        $('.multi-select-dropdown').each(function() {
            var name = $(this).data('name');
            $(this).find('input[type="checkbox"]:checked').each(function() {
                $('<input>').attr({
                    type: 'hidden',
                    name: name + '[]',
                    value: $(this).val(),
                    class: 'multi-select-value'
                }).appendTo('#formFiltros');
            });
        });
    });
    
    // ══════════════════════════════════════════════════
    // DATATABLE Y SELECCION
    // ══════════════════════════════════════════════════
    
    if ($('#tablaClientes').length) {
        $('#tablaClientes').DataTable({
            paging: false,
            info: true,
            searching: true,
            ordering: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            columnDefs: [
                { orderable: false, targets: 0 }
            ]
        });
    }
    
    // Seleccionar todos
    $('#selectAll').on('change', function(){
        $('.cliente-check').prop('checked', this.checked);
        actualizarContador();
    });
    
    // Actualizar contador al cambiar checkboxes
    $(document).on('change', '.cliente-check', function(){
        actualizarContador();
    });
    
    function actualizarContador(){
        var count = $('.cliente-check:checked').length;
        $('#contadorSeleccionados').text(count);
        $('#btnSiguiente').prop('disabled', count === 0);
    }
    
    // Boton siguiente
    $('#btnSiguiente').on('click', function(){
        var seleccionados = [];
        $('.cliente-check:checked').each(function(){
            seleccionados.push({
                id: $(this).data('id'),
                telefono: $(this).data('telefono'),
                nombre: $(this).data('nombre')
            });
        });
        
        sessionStorage.setItem('clientesSeleccionados', JSON.stringify(seleccionados));
        window.location.href = 'send-massive-ws-step2.php';
    });
    
    // ══════════════════════════════════════════════════
    // SISTEMA DE COLA DE MENSAJES Y PROGRESO
    // ══════════════════════════════════════════════════
    
    // Función para cargar progreso de envíos
    // Función para cargar progreso de envíos
function cargarProgresoEnvios() {
    $.ajax({
        url: 'get_cola_progress.php',
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status !== 'success') return;

            const enviados = parseInt(res.enviados_hoy || 0);
            const limite = parseInt(res.limite_diario || 0);
            const cola = parseInt(res.cola_pendiente || 0);

            let pct = 0;
            if (limite > 0) pct = Math.round((enviados / limite) * 100);
            pct = Math.max(0, Math.min(100, pct));

            const faltan = Math.max(0, limite - enviados);

            // Texto
            $('#progressBadge').text(pct + '%');
            $('#progressBarText').text(pct + '%');
            $('#progressSubText').text('Enviados hoy: ' + enviados + ' / ' + limite);
            $('#progressLeftText').text('Faltan hoy: ' + faltan + ' mensaje(s) para completar el cupo');
            $('#progressQueueText').text('En cola: ' + cola + ' mensaje(s)');

            // Barra
            $('#progressBar')
                .css('width', pct + '%')
                .attr('aria-valuenow', pct);

            // Colores/estado bonito
            $('#progressBar')
                .removeClass('bg-success bg-warning bg-danger');

            if (pct >= 100) {
                $('#progressBar').addClass('bg-success');
                $('#progressSubText').html('<span class="text-success fw-bold">Cupo diario completado. Los envíos continúan el próximo día permitido.</span>');
            } else if (pct >= 70) {
                $('#progressBar').addClass('bg-warning');
            } else {
                $('#progressBar').addClass('bg-success');
            }
        }
    });
}
    
    // Función para cargar contador de cola
    function cargarContadorCola() {
        $.ajax({
            url: 'get_cola_count.php',
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.count > 0) {
                    $('#badgeCola').text(res.count).show();
                    $('#btnVerCola').show();
                } else {
                    $('#btnVerCola').hide();
                }
            },
            error: function() {
                console.error('Error al cargar contador de cola');
            }
        });
    }
    
    // Función para cargar mensajes de cola
    function cargarCola() {
        $('#contenidoCola').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        
        $.ajax({
            url: 'get_cola_messages.php',
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.mensajes.length > 0) {
                    let html = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>' + res.mensajes.length + '</strong> mensajes pendientes de envio.</div>';
                    
                    res.mensajes.forEach(function(msg, index) {
                        html += '<div class="mensaje-cola-item"><div class="d-flex justify-content-between align-items-start"><div><strong>' + (index + 1) + '. ' + msg.nombre + '</strong><br><small class="text-muted"><i class="fab fa-whatsapp me-1"></i> ' + msg.telefono + '</small></div><span class="badge bg-warning text-dark">Pendiente</span></div><div class="mt-2"><small class="text-muted">Mensaje:</small><p class="mb-0 small" style="white-space: pre-wrap; max-height: 60px; overflow: hidden;">' + msg.mensaje.substring(0, 100) + (msg.mensaje.length > 100 ? '...' : '') + '</p></div>' + (msg.adjunto ? '<small class="text-success"><i class="fas fa-paperclip me-1"></i> Con adjunto</small>' : '') + '</div>';
                    });
                    
                    $('#contenidoCola').html(html);
                } else {
                    $('#contenidoCola').html('<div class="alert alert-success text-center"><i class="fas fa-check-circle fa-3x mb-3"></i><h5>No hay mensajes en cola</h5><p class="mb-0">Todos los mensajes han sido enviados.</p></div>');
                    $('#btnCancelarCola').hide();
                }
            },
            error: function() {
                $('#contenidoCola').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar los mensajes en cola.</div>');
            }
        });
    }
    
    // Inicializar carga de progreso y contador
cargarProgresoEnvios();
cargarContadorCola();

// Actualizar cada 10 segundos
setInterval(function() {
    cargarProgresoEnvios();
    cargarContadorCola();
    
    // Solo actualizamos la tabla si el modal está abierto para no sobrecargar el servidor
    if ($('#modalCola').hasClass('show')) {
        cargarCola();
    }
}, 10000); // Se actualiza todo cada 10 segundos
	
	
    // Al hacer clic en el botón flotante
    $('#btnVerCola').on('click', function() {
        cargarProgresoEnvios();
        cargarCola();
        $('#modalCola').modal('show');
    });
    
    // Cancelar toda la cola
    $('#btnCancelarCola').on('click', function() {
        Swal.fire({
            title: '¿Cancelar toda la cola?',
            html: '<p class="text-danger"><strong>Advertencia:</strong> Deberás volver a crear el mensaje masivo.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cancelar todo',
            cancelButtonText: 'No, mantener'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'cancel_cola.php',
                    method: 'POST',
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('¡Cola cancelada!', 'Se eliminaron ' + res.eliminados + ' mensajes.', 'success').then(() => {
                                $('#modalCola').modal('hide');
                                cargarContadorCola();
                                cargarProgresoEnvios();
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo cancelar la cola.', 'error');
                    }
                });
            }
        });
    });
    
}); // Fin de $(document).ready

function limpiarFiltros(){
    // Limpiar checkboxes de multi-select
    $('.multi-select-dropdown input[type="checkbox"]').prop('checked', false);
    $('.multi-select-dropdown .selected-text').text('Todos').removeClass('has-selection');
    
    // Limpiar otros campos
    $('select[name="plan_vencido"]').val('');
    $('input[name="dias_por_vencer"]').val('');
    $('input[name="vencimiento_desde"]').val('');
    $('input[name="vencimiento_hasta"]').val('');
}
</script>

</body>
</html>