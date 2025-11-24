<div class="section-title mb-3">Últimos 5 clientes inscritos</div>

<div class="card-list-wrapper" id="ultimos-list">

    <div class="card-list-empty" id="ultimos-empty" style="display:none;">
		<i class="fas fa-check-circle text-success" style="font-size:22px;display:block;margin-bottom:6px;"></i>
        No hay clientes registrados aún.
    </div>

</div>

<script>
fetch('gets_home/get_ultimos_clientes.php')
    .then(r => r.json())
    .then(result => {

        const data = result.data || [];

        if (data.length === 0) {
            document.getElementById('ultimos-empty').style.display = 'block';
            return;
        }

        document.getElementById('ultimos-empty').style.display = 'none';

        data.forEach(c => {

            let foto = c.imagen_perfil
                ? '../uploads/clientes/' + c.imagen_perfil
                : 'https://ui-avatars.com/api/?name=' +
                    encodeURIComponent(c.nombres + ' ' + c.apellidos) +
                    '&background=6c63ff&color=fff';

            let html = `
            <div class="card-item" onclick="window.location.href='clients/detail.php?id=${c.id}'">

                <div class="card-avatar">
                    <img src="${foto}" class="card-avatar-img" alt="foto">
                </div>

                <div class="card-info">
                    <div class="card-title">${c.nombres} ${c.apellidos}</div>

                    <div class="card-sub">
                        Tel: ${c.telefono || '—'}<br>
                        <span class="badge-pill badge-purple">
                            ${c.plan || 'Sin plan'}
                        </span>
                    </div>
                </div>

                <div>
                    <span class="badge-pill badge-orange">
                        ${c.vencimiento_plan || '—'}
                    </span>
                </div>

            </div>`;

            document.getElementById('ultimos-list')
                .insertAdjacentHTML('beforeend', html);
        });

    })
    .catch(error => {
        console.error("Error últimos clientes", error);
        document.getElementById('ultimos-empty').style.display = 'block';
        document.getElementById('ultimos-empty').textContent = 
            'Error al cargar información.';
    });
</script>






