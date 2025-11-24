<div class="section-title mb-3">Clientes que cumplen años hoy</div>

<div class="card-list-wrapper" id="cumple-list">

    <div class="card-list-empty" id="cumple-empty" style="display:none;">
        No hay cumpleaños hoy.
    </div>

</div>

<script>
$(async function(){

    try {
        let res = await $.getJSON("gets_home/get_cumpleanos_hoy.php");

        let data = res.data || [];

        if(data.length === 0){
            $("#cumple-empty").show();
            return;
        }

        data.forEach(c => {

            let foto = c.imagen_perfil 
                ? '../uploads/clientes/' + c.imagen_perfil
                : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(c.nombres + ' ' + c.apellidos) + '&background=ff5722&color=fff';

            let html = `
            <div class="card-item" onclick="window.location.href='clients/detail.php?id=${c.id}'">

                <div class="card-avatar">
                    <img src="${foto}" class="card-avatar-img">
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

    } catch(e){
        console.error("Error cargando cumpleaños", e);
        $("#cumple-empty").show().text("Error al cargar cumpleaños");
    }

});
</script>




