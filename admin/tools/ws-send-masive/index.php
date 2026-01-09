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

// Procesar filtros si se envió el formulario
$clientes = [];
$filtrosAplicados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtrar'])) {
    
    $where = ["c.borrado = 0"];
    $params = [];
    
    // Filtro: Estado del cliente
    if (!empty($_POST['estado'])) {
        $where[] = "c.estado = :estado";
        $params[':estado'] = $_POST['estado'];
        $filtrosAplicados[] = "Estado: " . ucfirst($_POST['estado']);
    }
    
    // Filtro: Plan
    if (!empty($_POST['plan'])) {
        $where[] = "c.plan = :plan";
        $params[':plan'] = $_POST['plan'];
        // Obtener nombre del plan
        foreach ($planes as $p) {
            if ($p['id'] == $_POST['plan']) {
                $filtrosAplicados[] = "Plan: " . $p['nombre'];
                break;
            }
        }
    }
    
    // Filtro: Género
    if (!empty($_POST['genero'])) {
        $where[] = "c.genero = :genero";
        $params[':genero'] = $_POST['genero'];
        $filtrosAplicados[] = "Género: " . ucfirst($_POST['genero']);
    }
    
    // Filtro: Congelado
    if (isset($_POST['congelado']) && $_POST['congelado'] !== '') {
        $where[] = "c.congelado = :congelado";
        $params[':congelado'] = $_POST['congelado'];
        $filtrosAplicados[] = "Congelado: " . ($_POST['congelado'] == 1 ? 'Sí' : 'No');
    }
    
    // Filtro: Notificaciones activas
    if (isset($_POST['notificaciones']) && $_POST['notificaciones'] !== '') {
        $where[] = "c.notificaciones = :notificaciones";
        $params[':notificaciones'] = $_POST['notificaciones'];
        $filtrosAplicados[] = "Notificaciones: " . ($_POST['notificaciones'] == 1 ? 'Activas' : 'Inactivas');
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
        $filtrosAplicados[] = "Plan vencido: Sí";
    }
    
    // Filtro: Plan por vencer (próximos X días)
    if (!empty($_POST['dias_por_vencer'])) {
        $dias = intval($_POST['dias_por_vencer']);
        $where[] = "c.vencimiento_plan BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)";
        $params[':dias'] = $dias;
        $filtrosAplicados[] = "Plan por vencer en: $dias días";
    }
    
    // Filtro: Tiene teléfono
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
    <title>Envío Masivo de WhatsApp</title>
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
		
		/* Botón flotante */
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
    </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
    <div class="portada">
        <h1><i class="fab fa-whatsapp"></i> Envío Masivo de WhatsApp</h1>
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
                    
                    <!-- Estado del cliente -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-toggle-on me-1"></i> Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="activo" <?= (isset($_POST['estado']) && $_POST['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= (isset($_POST['estado']) && $_POST['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    
                    <!-- Plan -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-id-card me-1"></i> Plan</label>
                        <select name="plan" class="form-select">
                            <option value="">Todos los planes</option>
                            <?php foreach ($planes as $plan): ?>
                                <option value="<?= $plan['id'] ?>" <?= (isset($_POST['plan']) && $_POST['plan'] == $plan['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plan['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Género -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-venus-mars me-1"></i> Género</label>
                        <select name="genero" class="form-select">
                            <option value="">Todos</option>
                            <option value="masculino" <?= (isset($_POST['genero']) && $_POST['genero'] == 'masculino') ? 'selected' : '' ?>>Masculino</option>
                            <option value="femenino" <?= (isset($_POST['genero']) && $_POST['genero'] == 'femenino') ? 'selected' : '' ?>>Femenino</option>
                            <option value="otro" <?= (isset($_POST['genero']) && $_POST['genero'] == 'otro') ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    
                    <!-- Congelado -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-snowflake me-1"></i> Plan Congelado</label>
                        <select name="congelado" class="form-select">
                            <option value="">Todos</option>
                            <option value="0" <?= (isset($_POST['congelado']) && $_POST['congelado'] === '0') ? 'selected' : '' ?>>No congelado</option>
                            <option value="1" <?= (isset($_POST['congelado']) && $_POST['congelado'] === '1') ? 'selected' : '' ?>>Congelado</option>
                        </select>
                    </div>
                    
                    <!-- Notificaciones -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-bell me-1"></i> Notificaciones</label>
                        <select name="notificaciones" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?= (isset($_POST['notificaciones']) && $_POST['notificaciones'] === '1') ? 'selected' : '' ?>>Activas</option>
                            <option value="0" <?= (isset($_POST['notificaciones']) && $_POST['notificaciones'] === '0') ? 'selected' : '' ?>>Inactivas</option>
                        </select>
                    </div>
                    
                    <!-- Plan vencido -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar-times me-1"></i> Plan Vencido</label>
                        <select name="plan_vencido" class="form-select">
                            <option value="">No filtrar</option>
                            <option value="1" <?= (isset($_POST['plan_vencido']) && $_POST['plan_vencido'] == '1') ? 'selected' : '' ?>>Solo vencidos</option>
                        </select>
                    </div>
                    
                    <!-- Días por vencer -->
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-hourglass-half me-1"></i> Por vencer en (días)</label>
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
                            <th>Teléfono</th>
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
            
            <!-- Botón siguiente paso -->
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

	
	
<!-- Botón flotante para ver cola -->
<button id="btnVerCola" class="btn-flotante" title="Ver mensajes en cola">
    <i class="fas fa-clock"></i>
    <span class="badge-cola" id="badgeCola">0</span>
</button>

<!-- Modal de Cola -->
<div class="modal fade" id="modalCola" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-list-ul me-2"></i> Mensajes en Cola de Envío
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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

<script>
$(document).ready(function(){
    
    // Inicializar DataTable
    if ($('#tablaClientes').length) {
        $('#tablaClientes').DataTable({
    paging: false,          // ← desactiva paginación
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
    
    // Botón siguiente
    $('#btnSiguiente').on('click', function(){
        var seleccionados = [];
        $('.cliente-check:checked').each(function(){
            seleccionados.push({
                id: $(this).data('id'),
                telefono: $(this).data('telefono'),
                nombre: $(this).data('nombre')
            });
        });
        
        // Guardar en sessionStorage y redirigir al paso 2
        sessionStorage.setItem('clientesSeleccionados', JSON.stringify(seleccionados));
        window.location.href = 'send-massive-ws-step2.php';
    });
	
	
	// ══════════════════════════════════════════════════
// SISTEMA DE COLA DE MENSAJES
// ══════════════════════════════════════════════════

// Cargar contador de cola al cargar la página
cargarContadorCola();

// Actualizar cada 10 segundos
setInterval(cargarContadorCola, 10000);

// Abrir modal al hacer clic en el botón flotante
$('#btnVerCola').on('click', function() {
    cargarCola();
    $('#modalCola').modal('show');
});

// Función para cargar el contador
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

// Función para cargar la cola completa
function cargarCola() {
    $('#contenidoCola').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `);
    
    $.ajax({
        url: 'get_cola_messages.php',
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && res.mensajes.length > 0) {
                let html = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>${res.mensajes.length}</strong> mensajes pendientes de envío.
                        Se envían <strong>10 cada 5 minutos</strong> automáticamente.
                    </div>
                `;
                
                res.mensajes.forEach(function(msg, index) {
                    html += `
                        <div class="mensaje-cola-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${index + 1}. ${msg.nombre}</strong><br>
                                    <small class="text-muted">
                                        <i class="fab fa-whatsapp me-1"></i> ${msg.telefono}
                                    </small>
                                </div>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Mensaje:</small>
                                <p class="mb-0 small" style="white-space: pre-wrap; max-height: 60px; overflow: hidden;">
                                    ${msg.mensaje.substring(0, 100)}${msg.mensaje.length > 100 ? '...' : ''}
                                </p>
                            </div>
                            ${msg.adjunto ? '<small class="text-success"><i class="fas fa-paperclip me-1"></i> Con adjunto</small>' : ''}
                        </div>
                    `;
                });
                
                $('#contenidoCola').html(html);
            } else {
                $('#contenidoCola').html(`
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5>No hay mensajes en cola</h5>
                        <p class="mb-0">Todos los mensajes han sido enviados.</p>
                    </div>
                `);
                $('#btnCancelarCola').hide();
            }
        },
        error: function() {
            $('#contenidoCola').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los mensajes en cola.
                </div>
            `);
        }
    });
}

// Cancelar toda la cola
$('#btnCancelarCola').on('click', function() {
    Swal.fire({
        title: '¿Cancelar toda la cola?',
        html: '<p class="text-danger"><strong>Advertencia:</strong> Deberás volver a crear de nuevo el mensaje masivo.</p><p>¿Seguro que quieres cancelar?</p>',
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
                        Swal.fire({
                            title: '¡Cola cancelada!',
                            text: `Se eliminaron ${res.eliminados} mensajes pendientes.`,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            $('#modalCola').modal('hide');
                            cargarContadorCola();
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
    
});

function limpiarFiltros(){
    // 1. Resetear el formulario (vuelve a valores iniciales de PHP)
    $('#formFiltros')[0].reset();
    
    // 2. Forzar el vaciado de todos los inputs, selects y fechas manualmente
    $('#formFiltros').find('input, select').each(function() {
        $(this).val(''); // Vacía el valor
    });

    // 3. Opcional: Si quieres que la página se refresque para limpiar los resultados de la tabla
    // window.location.href = 'send-massive-ws.php'; 
}
</script>

</body>
</html>