<?php 
require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';

// Verificar permisos
$permisopage = 'Enviar WhatsApp Masivo';
include('../../login/restriction.php');

// Obtener nombre del gimnasio para las variables
$nombreGimnasio = defined('NAME_GYM') ? NAME_GYM : 'Gimnasio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Envío Masivo - Redactar Mensaje</title>
    <?php include('../../inc/header.php'); ?>
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
        .message-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .message-card .card-header {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .variable-btn {
            margin: 3px;
            font-size: 0.85rem;
        }
        .preview-bubble {
            background: #DCF8C6;
            border-radius: 15px;
            padding: 15px;
            max-width: 80%;
            margin: 10px 0;
            position: relative;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .preview-bubble::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 10px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent #DCF8C6 transparent transparent;
        }
        .preview-container {
            background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAMAAAAp4XiDAAAAUVBMVEWFhYWDg4N3d3dtbW17e3t1dXWBgYGHh4d5. eXlzc3Oeli4telerik4ieln1enp6telerikli4leln1enp6enptelerikli4leln1enp6ennrRYvQAAAAG3RSTlNAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEAvEOwtAAABeklEQVRIx7WSW4KDIAxFISQCAi++tv9/0NLOdGorHfTP9HhJ4EYCzs7O/h9CBVjy7szs7s6szzP7PPO/slrW67XW63W9Xte167rW63VdLpdlGYbhcrk0TdO27aZpbrcb+L0ty3K9Xn8ul0vTNH3fX6/X6/U6DMP1eh2GYRiGcRzHcRynaZqm6fl8jjF+fn6+Xi6X2+12v98fj8ftdrvf79frdV3X9XpdluWyLOu6Lsvyet+v1+t6va7rupxOp+VwOBwOh+Px2LbtcrkMwzCO43K5DMNwOp1Op9PlcjkejwFgGIZxHMdxnKbJ+74+Ho/H4/F4Ot/vd2PMsizHcTwcDofD4XQ6HQ6Hw+HQNM35fL7dbqfT6Xw+H4/Hw+HQNE3btm3bdl3XdV3Xdd04jvM8z/M8juM0TdM0zdM0TdO8ruu6btu2bdumbduu67qu67qua5pmmqZpmqZpmud5nud5nudpmqZpmqZpWpZl27Zt27Zt27Zty7JsWZZl2bYty7Isy7Isy7Is27Zt27Zt27Zt27Zty7Is27Zty7Jty7IsAAAA//8DAG1kK1IAAAAASUVORK5CYII=');
            background-color: #ECE5DD;
            border-radius: 10px;
            padding: 20px;
            min-height: 200px;
        }
        .char-counter {
            font-size: 0.85rem;
        }
        .char-counter.warning {
            color: #ffc107;
        }
        .char-counter.danger {
            color: #dc3545;
        }
        .template-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .template-card:hover {
            border-color: #25D366;
            transform: translateY(-2px);
        }
        .template-card.selected {
            border-color: #25D366;
            background: #f0fff4;
        }
        .destinatarios-resumen {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
        }
		
		.preview-attachment {
    width: 100%;
    border-radius: 10px 10px 0 0;
    margin-bottom: 5px;
    max-height: 200px;
    object-fit: cover;
}
.preview-file-box {
    background: rgba(0,0,0,0.05);
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
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
        <div class="step completed">
            <span class="step-number"><i class="fas fa-check"></i></span>
            Filtrar Clientes
        </div>
        <div class="step active">
            <span class="step-number">2</span>
            Redactar Mensaje
        </div>
        <div class="step">
            <span class="step-number">3</span>
            Confirmar y Enviar
        </div>
    </div>
    
    <!-- Alerta si no hay clientes -->
    <div class="alert alert-danger d-none" id="alertNoClientes">
        <i class="fas fa-exclamation-triangle me-2"></i>
        No hay clientes seleccionados. <a href="index.php">Volver al paso 1</a>
    </div>
    
    <!-- Resumen de destinatarios -->
    <div class="destinatarios-resumen mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <i class="fas fa-users fa-2x"></i>
            </div>
            <div class="col">
                <h5 class="mb-0">Destinatarios seleccionados: <span id="totalDestinatarios">0</span></h5>
                <small>El mensaje se enviará a todos los clientes seleccionados</small>
            </div>
            <div class="col-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-edit me-1"></i> Modificar selección
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Columna izquierda: Editor de mensaje -->
        <div class="col-lg-7">
            <div class="card message-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Paso 2: Redactar Mensaje</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Plantillas predefinidas -->
                    <h6 class="mb-3"><i class="fas fa-file-alt me-1"></i> Plantillas Rápidas</h6>
                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <div class="card template-card h-100" data-template="recordatorio">
                                <div class="card-body p-2 text-center">
                                    <i class="fas fa-bell text-warning mb-1"></i>
                                    <small class="d-block">Recordatorio de pago</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card template-card h-100" data-template="promocion">
                                <div class="card-body p-2 text-center">
                                    <i class="fas fa-percentage text-success mb-1"></i>
                                    <small class="d-block">Promoción</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card template-card h-100" data-template="bienvenida">
                                <div class="card-body p-2 text-center">
                                    <i class="fas fa-hand-peace text-primary mb-1"></i>
                                    <small class="d-block">Bienvenida</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card template-card h-100" data-template="vencimiento">
                                <div class="card-body p-2 text-center">
                                    <i class="fas fa-calendar-times text-danger mb-1"></i>
                                    <small class="d-block">Plan por vencer</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card template-card h-100" data-template="reactivacion">
                                <div class="card-body p-2 text-center">
                                    <i class="fas fa-redo text-info mb-1"></i>
                                    <small class="d-block">Reactivación</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card template-card h-100" data-template="personalizado">
                                <div class="card-body p-2 text-center">
                                    <i class="fas fa-pencil-alt text-secondary mb-1"></i>
                                    <small class="d-block">Personalizado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Variables disponibles -->
                    <h6 class="mb-2"><i class="fas fa-code me-1"></i> Variables Disponibles</h6>
                    <p class="text-muted small">Haz clic para insertar en el mensaje:</p>
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm variable-btn" data-variable="{nombre}">
                            {nombre}
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm variable-btn" data-variable="{gimnasio}">
                            {gimnasio}
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm variable-btn" data-variable="{fecha_hoy}">
                            {fecha_hoy}
                        </button>
                    </div>
                    
                    <!-- Textarea del mensaje -->
                    <div class="mb-3">
                        <label for="mensaje" class="form-label">
                            <strong>Mensaje</strong>
                            <span class="char-counter float-end" id="charCounter">0 / 1000</span>
                        </label>
                        <textarea class="form-control" id="mensaje" rows="8" maxlength="1000" 
                                  placeholder="Escribe tu mensaje aquí..."></textarea>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Usa las variables para personalizar el mensaje para cada cliente.
                        </small>
                    </div>
					
					
					<!-- Archivo Adjunto -->
<div class="mb-3">
    <label for="adjunto" class="form-label"><strong><i class="fas fa-paperclip me-1"></i> Archivo Adjunto (Opcional)</strong></label>
    <input class="form-control" type="file" id="adjunto" accept="image/*,.pdf,.doc,.docx">
    <div id="previewAdjuntoContainer" class="mt-2 d-none">
        <div class="position-relative d-inline-block">
            <img id="imgPreview" src="#" alt="Vista previa" class="img-thumbnail" style="max-height: 150px;">
            <div id="fileInfoPreview" class="alert alert-secondary p-2 mb-0 d-none">
                <i class="fas fa-file-pdf me-2"></i> <span id="fileNameDisplay">archivo.pdf</span>
            </div>
            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" id="btnRemoveAdjunto">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <small class="text-muted d-block mt-1">Puedes adjuntar imágenes (JPG, PNG) o documentos (PDF, DOC).</small>
</div>
                    
                    <!-- Opciones adicionales -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="incluirEmojis" checked>
                            <label class="form-check-label" for="incluirEmojis">
                                Permitir emojis en el mensaje
                            </label>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Columna derecha: Vista previa -->
        <div class="col-lg-5">
            <div class="card message-card">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i> Vista Previa</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Así se verá el mensaje (ejemplo con el primer destinatario):
                    </p>
                    
                    <div class="preview-container">
                        <div class="preview-bubble" id="previewMensaje">
                            <span class="text-muted">El mensaje aparecerá aquí...</span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">
                                <i class="fas fa-check-double text-primary"></i>
                                <span id="previewHora">00:00</span>
                            </small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6><i class="fas fa-user me-1"></i> Destinatario de ejemplo:</h6>
                    <p class="mb-1" id="previewDestinatario">
                        <strong>Nombre:</strong> <span id="previewNombre">-</span>
                    </p>
                    <p class="mb-0">
                        <strong>Teléfono:</strong> <span id="previewTelefono">-</span>
                    </p>
                </div>
            </div>
            
            <!-- Botones de navegación -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg" id="btnSiguiente" disabled>
                            <i class="fas fa-arrow-right me-2"></i> Siguiente: Confirmar y Enviar
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Volver al Paso 1
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<?php include('../../inc/menu-footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Variables globales
var clientesSeleccionados = [];
var nombreGimnasio = '<?= addslashes($nombreGimnasio) ?>';

// Plantillas predefinidas
var plantillas = {
    recordatorio: `¡Hola {nombre}! 👋 

Te escribimos de *${nombreGimnasio}* para recordarte que tu membresía está próxima a vencer. 

No pierdas tu racha de entrenamiento 💪 Queremos que sigas alcanzando tus metas con nosotros. 

¿Te gustaría que te ayudemos con el proceso de renovación por aquí mismo?`,
    
    promocion: `¡Hola {nombre}! 🎉 

¡Tenemos una sorpresa para ti en *${nombreGimnasio}*! 

Hemos lanzado una promoción especial y pensamos que te encantaría aprovecharla para darle un impulso extra a tu entrenamiento. 🚀

¿Quieres que te envíe los detalles de la promo? Responde con un *SÍ* y te cuento todo.`,
    
    bienvenida: `¡Bienvenido/a a la familia, {nombre}! 🎊 

Nos alegra mucho que hayas elegido a *${nombreGimnasio}* para transformar tu estilo de vida. Estamos aquí para apoyarte en cada paso de tu entrenamiento. 🏋️‍♂️

¿Tienes alguna duda sobre nuestras clases o el uso de las máquinas? ¡Escríbenos, estamos para ayudarte!`,
    
    vencimiento: `¡Hola {nombre}! ⏰ 

Tu plan en *${nombreGimnasio}* vence muy pronto. 

Para evitar filas y no perder el acceso a las instalaciones, puedes renovar hoy mismo. ¡No dejes que nada detenga tu progreso! 💪

¿Deseas que te enviemos los medios de pago electrónicos para mayor comodidad?`,
    
    reactivacion: `¡Hola {nombre}! 👋 

Te extrañamos en *${nombreGimnasio}*. Notamos que hace unos días no nos visitas y queremos saber si todo está bien. 

Recuerda que cada día es una nueva oportunidad para cuidar tu salud. 🍎

¿Te gustaría recibir un pase de cortesía para retomar tu rutina mañana mismo? ¡Dinos que sí!`,
    
    personalizado: ''
};

$(document).ready(function(){
    
    // Cargar clientes desde sessionStorage
    var clientesJSON = sessionStorage.getItem('clientesSeleccionados');
    
    if (!clientesJSON || clientesJSON === '[]') {
        $('#alertNoClientes').removeClass('d-none');
        $('.card').not('#alertNoClientes').addClass('d-none');
        return;
    }
    
    clientesSeleccionados = JSON.parse(clientesJSON);
    $('#totalDestinatarios').text(clientesSeleccionados.length);
    
    // Mostrar primer destinatario como ejemplo
    if (clientesSeleccionados.length > 0) {
        $('#previewNombre').text(clientesSeleccionados[0].nombre);
        $('#previewTelefono').text(clientesSeleccionados[0].telefono);
    }
    
    // Actualizar hora en la vista previa
    actualizarHoraPreview();
    setInterval(actualizarHoraPreview, 60000);
    
    // Evento: Escribir en el textarea
    $('#mensaje').on('input', function(){
        actualizarPreview();
        actualizarContadorCaracteres();
        validarFormulario();
    });
    
    // Evento: Click en plantilla
    $('.template-card').on('click', function(){
        $('.template-card').removeClass('selected');
        $(this).addClass('selected');
        
        var template = $(this).data('template');
        $('#mensaje').val(plantillas[template]);
        actualizarPreview();
        actualizarContadorCaracteres();
        validarFormulario();
    });
    
    // Evento: Click en variable
    $('.variable-btn').on('click', function(){
        var variable = $(this).data('variable');
        var textarea = document.getElementById('mensaje');
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var text = textarea.value;
        
        textarea.value = text.substring(0, start) + variable + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        textarea.focus();
        
        actualizarPreview();
        actualizarContadorCaracteres();
        validarFormulario();
    });
	
	
	// Evento: Cambio en el archivo adjunto
$('#adjunto').on('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        $('#previewAdjuntoContainer').removeClass('d-none');
        
        if (file.type.startsWith('image/')) {
            reader.onload = function(e) {
                $('#imgPreview').attr('src', e.target.result).removeClass('d-none');
                $('#fileInfoPreview').addClass('d-none');
                // Actualizar burbuja de WhatsApp
                $('#attachmentInPreview').html(`<img src="${e.target.result}" class="preview-attachment">`).removeClass('d-none');
            }
        } else {
            $('#imgPreview').addClass('d-none');
            $('#fileInfoPreview').removeClass('d-none');
            $('#fileNameDisplay').text(file.name);
            // Actualizar burbuja de WhatsApp
            $('#attachmentInPreview').html(`<div class="preview-file-box"><i class="fas fa-file-alt me-2"></i> ${file.name}</div>`).removeClass('d-none');
        }
        reader.readAsDataURL(file);
    }
});

// Evento: Quitar adjunto
$('#btnRemoveAdjunto').on('click', function() {
    $('#adjunto').val('');
    $('#previewAdjuntoContainer').addClass('d-none');
    $('#attachmentInPreview').addClass('d-none').html('');
});

// Modificar el evento del Botón Siguiente para manejar el archivo
$('#btnSiguiente').on('click', function() {
    var mensaje = $('#mensaje').val().trim();
    var fileInput = document.getElementById('adjunto');
    
    sessionStorage.setItem('mensajeMasivo', mensaje);
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            // Guardamos el archivo en Base64 para pasarlo al siguiente paso
            sessionStorage.setItem('adjuntoMasivo', e.target.result);
            sessionStorage.setItem('adjuntoNombre', file.name);
            sessionStorage.setItem('adjuntoTipo', file.type);
            window.location.href = 'send-massive-ws-step3.php';
        };
        reader.readAsDataURL(file);
    } else {
        sessionStorage.removeItem('adjuntoMasivo');
        window.location.href = 'send-massive-ws-step3.php';
    }
});
    
    // Evento: Botón siguiente
    $('#btnSiguiente').on('click', function(){
        var mensaje = $('#mensaje').val().trim();
        
        if (!mensaje) {
            Swal.fire('Error', 'Debes escribir un mensaje', 'error');
            return;
        }
        
        // Guardar mensaje en sessionStorage
        sessionStorage.setItem('mensajeMasivo', mensaje);
        
        // Redirigir al paso 3
        window.location.href = 'send-massive-ws-step3.php';
    });
    
});

function actualizarPreview() {
    var mensaje = $('#mensaje').val();
    
    if (!mensaje) {
        $('#previewMensaje').html('<span class="text-muted">El mensaje aparecerá aquí...</span>');
        return;
    }
    
    // Reemplazar variables con datos de ejemplo
    var nombreEjemplo = clientesSeleccionados.length > 0 ? clientesSeleccionados[0].nombre.split(' ')[0] : 'Cliente';
    var fechaHoy = new Date().toLocaleDateString('es-CO', { day: '2-digit', month: 'long', year: 'numeric' });
    
    var mensajePreview = mensaje
        .replace(/{nombre}/g, nombreEjemplo)
        .replace(/{gimnasio}/g, nombreGimnasio)
        .replace(/{fecha_hoy}/g, fechaHoy);
    
    // Convertir saltos de línea y formato WhatsApp
    mensajePreview = formatearTextoWhatsApp(mensajePreview);
    
    $('#previewMensaje').html(mensajePreview);
}

function formatearTextoWhatsApp(texto) {
    // Escapar HTML
    texto = $('<div>').text(texto).html();
    
    // Negrita: *texto*
    texto = texto.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
    
    // Cursiva: _texto_
    texto = texto.replace(/_([^_]+)_/g, '<em>$1</em>');
    
    // Tachado: ~texto~
    texto = texto.replace(/~([^~]+)~/g, '<del>$1</del>');
    
    // Saltos de línea
    texto = texto.replace(/\n/g, '<br>');
    
    return texto;
}

function actualizarContadorCaracteres() {
    var length = $('#mensaje').val().length;
    var counter = $('#charCounter');
    
    counter.text(length + ' / 1000');
    
    counter.removeClass('warning danger');
    if (length > 800) {
        counter.addClass('danger');
    } else if (length > 600) {
        counter.addClass('warning');
    }
}

function actualizarHoraPreview() {
    var now = new Date();
    var hora = now.getHours().toString().padStart(2, '0');
    var minutos = now.getMinutes().toString().padStart(2, '0');
    $('#previewHora').text(hora + ':' + minutos);
}

function validarFormulario() {
    var mensaje = $('#mensaje').val().trim();
    $('#btnSiguiente').prop('disabled', mensaje.length === 0);
}
</script>

</body>
</html>
