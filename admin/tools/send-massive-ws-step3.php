<?php 
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

// Verificar permisos
$permisopage = 'Enviar WhatsApp Masivo';
include('../login/restriction.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Paso 3: Confirmar Envío</title>
    <?php include('../inc/header.php'); ?>
</head>
<body>
<?php include('../inc/menu.php'); ?>

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
            
            <div class="text-center mt-4">
                <button id="btnFinalizar" class="btn btn-success btn-lg">
                    <i class="fas fa-save me-2"></i> Confirmar y Guardar en Base de Datos
                </button>
            </div>
        </div>
    </div>
</div>

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

    $('#btnFinalizar').on('click', function() {
    const btn = $(this);
    const originalText = btn.html();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

    $.ajax({
        url: 'save-massive-ws.php',
        method: 'POST',
        data: {
            clientes: clientes,
            mensaje: mensaje,
            adjunto: adjunto,
            adjuntoNombre: adjuntoNombre
        },
        dataType: 'json', // Forzamos a que espere un JSON
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Los datos se han guardado correctamente.',
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
            // Si hay un error de PHP (500) o el JSON está mal formado, entrará aquí
            console.error(xhr.responseText); // Para que puedas ver el error real en la consola
            Swal.fire('Error Crítico', 'No se pudo procesar la solicitud. Revisa la consola (F12).', 'error');
            btn.prop('disabled', false).html(originalText);
        }
    });
});
});
</script>
</body>
</html>