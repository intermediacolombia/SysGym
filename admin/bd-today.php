<div class="section-title mb-3">Clientes que cumplen años hoy</div>
<div class="card-list-wrapper" id="cumple-list">
    <div class="card-list-empty" id="cumple-empty" style="display:none;">
        No hay cumpleaños hoy.
    </div>
</div>

<script>
$(document).ready(function(){
    $.ajax({
        url: 'gets_home/get_cumpleanos_hoy.php',
        method: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(res) {
            let data = res.data || [];
            
            if(data.length === 0){
                $("#cumple-empty").show();
                return;
            }
            
            $("#cumple-empty").hide();
            
            data.forEach(function(c) {
                let foto = c.imagen_perfil 
                    ? '../uploads/clientes/' + c.imagen_perfil
                    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(c.nombres + ' ' + c.apellidos) + '&background=ff5722&color=fff';
                
                let html = `
                <div class="card-item" onclick="window.location.href='clients/detail.php?id=${c.id}'">
                    <div class="card-avatar">
                        <img src="${foto}" class="card-avatar-img" alt="foto">
                    </div>
                    <div class="card-info">
                        <div class="card-title">${c.nombres} ${c.apellidos}</div>
                        <div class="card-sub">Tel: ${c.telefono || '—'}</div>
                    </div>
                    <div>
                        <span class="badge-pill badge-orange">${c.edad} años</span>
                    </div>
                </div>`;
                
                $("#cumple-list").append(html);
            });
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar cumpleaños:", status, error);
            $("#cumple-empty").show().text("Error al cargar cumpleaños");
        }
    });
});
</script>





