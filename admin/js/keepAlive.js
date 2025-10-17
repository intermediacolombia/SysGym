var keep_alive = false;

// Detectar actividad y loguear el evento
$(document).on("click keydown keyup mousemove", function() {
    //console.log("Actividad detectada");
    keep_alive = true;
});

// Intervalo reducido a 5 segundos para pruebas
// Envía un ping automáticamente cada 5 segundos para debug (cambiar a 1,200,000 ms para 20 minutos en producción)
setInterval(function() {
    pingServer();
}, 5000);

function pingServer() {
    $.ajax({
        url: '/admin/keepAlive.php',  // Asegúrate de que la ruta sea correcta
        method: 'GET'
    })
    .done(function(response) {
        // Se obtiene la hora actual en formato local
        //console.log('KeepAlive response:', response, 'a las', new Date().toLocaleTimeString());
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        //console.error('KeepAlive request failed:', textStatus, errorThrown, 'a las', new Date().toLocaleTimeString());
    });
}



