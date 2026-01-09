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
        <div>
            <h6 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Protección Anti-Baneo Activa</h6>
            <p class="mb-0 small">
                Para proteger tu cuenta de WhatsApp y evitar bloqueos, el sistema procesará los envíos de forma gradual. 
                <strong>Se enviarán lotes de 10 mensajes cada 5 minutos</strong> automáticamente hasta completar el total de destinatarios.
            </p>
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
        window.location.href = 'send-massive-ws.php';
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
            window.location.href = 'send-massive-ws.php';
        }
    });
});

// ----------------------------------
// Editar mensaje (volver al step 2)
// ----------------------------------
$('#btnEditar').on('click', function () {
    window.location.href = 'send-massive-ws-step2.php';
});

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
                    window.location.href = 'send-massive-ws.php';
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