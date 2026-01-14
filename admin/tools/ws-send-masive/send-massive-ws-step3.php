<?php 
require_once __DIR__ . '/../../login/session.php';
require_once __DIR__ . '/../../../inc/config.php';

// Verificar permisos
$permisopage = 'Enviar WhatsApp Masivo';
include('../../login/restriction.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Paso 3: Confirmar Envío</title>
    <?php include('../../inc/header.php'); ?>
</head>
<body>
<?php include('../../inc/menu.php'); ?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Paso 3: Confirmar y Guardar</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-users"></i> Resumen de Destinatarios</h6>
                    <p>Total: <span id="countDest" class="badge bg-info">0</span></p>
                    <div id="listaDestinatarios" style="max-height: 200px; overflow-y: auto;" class="border p-2 mb-3"></div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-comment-alt"></i> Mensaje a enviar</h6>
                    <div id="msgPreview" class="p-3 bg-light border mb-3" style="white-space: pre-wrap; border-radius: 10px;"></div>
                    <div id="adjuntoPreview" class="d-none">
                        <h6><i class="fas fa-paperclip"></i> Adjunto detectado</h6>
                        <div id="filePreview"></div>
                    </div>
                </div>
            </div>
			
			<!-- Nota de Seguridad Anti-Baneo -->
<div class="alert alert-warning border-warning mt-4 mb-4">
    <div class="d-flex">
        <div class="me-3">
            <i class="fas fa-shield-alt fa-2x text-warning"></i>
        </div>
        <div class="w-100">
            <h6 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Protección Anti-Baneo Activa</h6>
            <p class="mb-2 small">
                Para proteger tu cuenta de WhatsApp y evitar bloqueos, el sistema procesará los envíos de forma gradual según la configuración establecida:
            </p>
            
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <div class="bg-white p-2 rounded border">
                        <small class="text-muted d-block"><i class="fas fa-calendar-alt me-1"></i> Días permitidos:</small>
                        <strong class="small" id="diasPermitidos">-</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-2 rounded border">
                        <small class="text-muted d-block"><i class="fas fa-clock me-1"></i> Horario:</small>
                        <strong class="small" id="horarioEnvio">-</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-2 rounded border">
                        <small class="text-muted d-block"><i class="fas fa-envelope me-1"></i> Límite diario:</small>
                        <strong class="small" id="limiteDiario">-</strong>
                    </div>
                </div>
            </div>
            
            <div class="bg-light p-2 rounded border">
                <div class="row g-2">
                    <div class="col-md-6">
                        <small class="text-muted"><i class="fas fa-hourglass-half me-1"></i> Tiempo estimado:</small>
                        <strong class="d-block" id="tiempoEstimado">-</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted"><i class="fas fa-calendar-check me-1"></i> Finalización aproximada:</small>
                        <strong class="d-block" id="fechaFinalizacion">-</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Botones de acción (aquí van los que ya tienes) -->
<div class="text-center mt-4">
    <div class="d-flex justify-content-center gap-3 flex-wrap">
        <!-- ... tus botones ... -->
            
            <div class="text-center mt-4">
    <div class="d-flex justify-content-center gap-3 flex-wrap">

        <!-- Cancelar -->
        <button id="btnCancelar" class="btn btn-outline-danger btn-lg">
            <i class="fas fa-times me-2"></i> Cancelar
        </button>

        <!-- Editar -->
        <button id="btnEditar" class="btn btn-outline-primary btn-lg">
            <i class="fas fa-edit me-2"></i> Editar mensaje
        </button>

        <!-- Confirmar -->
        <button id="btnFinalizar" class="btn btn-success btn-lg">
            <i class="fas fa-save me-2"></i> Confirmar
        </button>

    </div>
</div>
        </div>
    </div>
</div>
<?php include('../../inc/menu-footer.php'); ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    const clientes = JSON.parse(sessionStorage.getItem('clientesSeleccionados') || '[]');
    const mensaje = sessionStorage.getItem('mensajeMasivo');
    const adjunto = sessionStorage.getItem('adjuntoMasivo');
    const adjuntoNombre = sessionStorage.getItem('adjuntoNombre');

    if (!clientes.length || !mensaje) {
        window.location.href = 'index.php';
        return;
    }

    // Mostrar resumen
    $('#countDest').text(clientes.length);
    $('#msgPreview').text(mensaje);
    clientes.forEach(c => {
        $('#listaDestinatarios').append(`<div><small>• ${c.nombre} (${c.telefono})</small></div>`);
    });

    if (adjunto) {
        $('#adjuntoPreview').removeClass('d-none');
        $('#filePreview').html(`<small class="text-success">${adjuntoNombre}</small>`);
    }

    // ========================================================================
    // CALCULAR Y MOSTRAR INFORMACIÓN DE ENVÍO MASIVO
    // ========================================================================
    $.ajax({
        url: 'get-massive-config.php',
        method: 'GET',
        dataType: 'json',
        success: function(config) {
            const totalMensajes = clientes.length;
            
            // Días permitidos
            const diasMap = {1: 'Lun', 2: 'Mar', 3: 'Mié', 4: 'Jue', 5: 'Vie', 6: 'Sáb', 7: 'Dom'};
            const diasArray = config.dias.split(',').map(d => diasMap[d.trim()]);
            $('#diasPermitidos').text(diasArray.join(', '));
            
            // Horario
            const horaInicio = parseInt(config.hora_inicio);
            const horaFin = parseInt(config.hora_fin);
            const formatHora = (h) => {
                const hora12 = h === 0 ? 12 : (h > 12 ? h - 12 : h);
                const ampm = h < 12 ? 'AM' : 'PM';
                return `${hora12}:00 ${ampm}`;
            };
            $('#horarioEnvio').text(`${formatHora(horaInicio)} - ${formatHora(horaFin)}`);
            
            // Límite diario
            const limiteDiario = parseInt(config.limite);
            $('#limiteDiario').text(`${limiteDiario} mensajes/día`);
            
            // Calcular días necesarios
            const diasNecesarios = Math.ceil(totalMensajes / limiteDiario);
            
            // Calcular fecha de finalización (solo contando días permitidos)
            const diasPermitidos = config.dias.split(',').map(d => parseInt(d.trim()));
            let fecha = new Date();
            let diasContados = 0;
            
            while (diasContados < diasNecesarios) {
                fecha.setDate(fecha.getDate() + 1);
                const diaSemana = fecha.getDay() === 0 ? 7 : fecha.getDay(); // Convertir domingo de 0 a 7
                if (diasPermitidos.includes(diaSemana)) {
                    diasContados++;
                }
            }
            
            // Formatear fecha
            const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
            const fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);
            
            // Mostrar resultados
            if (diasNecesarios === 1) {
                $('#tiempoEstimado').html('<span class="text-success">Hoy mismo</span>');
                $('#fechaFinalizacion').html('<span class="text-success">Hoy</span>');
            } else {
                $('#tiempoEstimado').text(`${diasNecesarios} día${diasNecesarios > 1 ? 's' : ''} hábil${diasNecesarios > 1 ? 'es' : ''}`);
                $('#fechaFinalizacion').text(fechaFormateada);
            }
        },
        error: function() {
            // Valores por defecto en caso de error
            $('#diasPermitidos').text('Lun - Vie');
            $('#horarioEnvio').text('7:00 AM - 9:00 PM');
            $('#limiteDiario').text('50 mensajes/día');
            $('#tiempoEstimado').text('Calculando...');
            $('#fechaFinalizacion').text('Calculando...');
        }
    });

    // ----------------------------------
    // Cancelar envío
    // ----------------------------------
    $('#btnCancelar').on('click', function () {
        Swal.fire({
            title: '¿Cancelar envío?',
            text: 'Se perderá el mensaje y los adjuntos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                sessionStorage.clear();
                window.location.href = 'index.php';
            }
        });
    });

    // ----------------------------------
    // Editar mensaje (volver al step 2)
    // ----------------------------------
    $('#btnEditar').on('click', function () {
        window.location.href = 'send-massive-ws-step2.php';
    });

    // ----------------------------------
    // Finalizar y guardar envío
    // ----------------------------------
    $('#btnFinalizar').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        // Crear FormData para enviar archivos
        const formData = new FormData();
        formData.append('clientes', JSON.stringify(clientes));
        formData.append('mensaje', mensaje);

        // Si hay adjunto, recuperarlo del sessionStorage y convertirlo a Blob
        if (adjunto && adjuntoNombre) {
            // Convertir Base64 a Blob
            const arr = adjunto.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while(n--){
                u8arr[n] = bstr.charCodeAt(n);
            }
            const file = new Blob([u8arr], {type: mime});
            formData.append('adjunto', file, adjuntoNombre);
            formData.append('tieneAdjunto', 'true');
        } else {
            formData.append('tieneAdjunto', 'false');
        }

        $.ajax({
            url: 'save-massive-ws.php',
            method: 'POST',
            data: formData,
            processData: false,  // No procesar los datos
            contentType: false,  // No establecer ningún tipo de contenido
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: `Se guardaron ${res.total} mensajes correctamente.`,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        sessionStorage.clear();
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error completo:', xhr.responseText);
                Swal.fire('Error', 'No se pudo procesar. Revisa la consola (F12).', 'error');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
</body>
</html>