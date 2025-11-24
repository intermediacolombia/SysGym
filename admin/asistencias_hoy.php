<div class="section-title mb-3">Asistencias únicas registradas hoy</div>

<div class="card-list-wrapper" id="asis-list">

    <div class="card-list-empty" id="asis-empty" style="display:none;">
        No hay asistencias registradas hoy.
    </div>

</div>

<script>
fetch('gets_home/get_asistencias_hoy.php')
    .then(response => response.json())
    .then(res => {

        let data = res.data || [];

        if (data.length === 0) {
            document.getElementById('asis-empty').style.display = 'block';
            return;
        }

        document.getElementById('asis-empty').style.display = 'none';

        data.forEach(u => {

            let foto = u.imagen_perfil
                ? '../uploads/clientes/' + u.imagen_perfil
                : 'https://ui-avatars.com/api/?name='
                    + encodeURIComponent(u.nombres + ' ' + u.apellidos)
                    + '&background=2196f3&color=fff';

            let html = `
            <div class="card-item" onclick="window.location.href='clients/detail.php?id=${u.id}'">

                <div class="card-avatar">
                    <img src="${foto}" class="card-avatar-img">
                </div>

                <div class="card-info">
                    <div class="card-title">${u.nombres} ${u.apellidos}</div>
                    <div class="card-sub">Última asistencia registrada</div>
                </div>

                <div class="text-end">
                    <span class="badge-pill badge-blue">${u.hora_bonita}</span>
                </div>

            </div>`;

            document.getElementById('asis-list').insertAdjacentHTML('beforeend', html);
        });

    })
    .catch(error => {
        document.getElementById('asis-empty').style.display = 'block';
        document.getElementById('asis-empty').textContent = 'Error al cargar asistencias';
    });
</script>





