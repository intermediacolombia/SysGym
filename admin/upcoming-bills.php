<div class="section-title mb-3">Próximos pagos a vencer (7 días)</div>

<div class="card-list-wrapper" id="prox-list">

    <div class="card-list-empty" id="prox-empty" style="display:none;">
		<i class="fas fa-check-circle text-success" style="font-size:22px;display:block;margin-bottom:6px;"></i>
        No hay pagos por vencer en los próximos 7 días.
    </div>

</div>

<script>
fetch('gets_home/get_proximos_pagos.php')
    .then(res => res.json())
    .then(result => {

        const data = result.data || [];

        if (data.length === 0) {
            document.getElementById('prox-empty').style.display = 'block';
            return;
        }

        document.getElementById('prox-empty').style.display = 'none';

        data.forEach(p => {

            let foto = p.imagen_perfil
                ? '../uploads/clientes/' + p.imagen_perfil
                : 'https://ui-avatars.com/api/?name=' +
                    encodeURIComponent(p.nombres + ' ' + p.apellidos) +
                    '&background=2196f3&color=fff';

            // Lógica colores
            let badgeClass =
                p.dias_restantes <= 1 ? 'badge-red' :
                p.dias_restantes <= 3 ? 'badge-yellow' :
                                        'badge-green';

            let html = `
            <div class="card-item" onclick="window.location.href='clients/detail.php?id=${p.id}'">

                <div class="card-avatar">
                    <img src="${foto}" class="card-avatar-img">
                </div>

                <div class="card-info">
                    <div class="card-title">${p.nombres} ${p.apellidos}</div>
                    <div class="card-sub">
                        Plan: ${p.plan || '—'} · Tel: ${p.telefono}
                    </div>
                </div>

                <div style="text-align:right;">
                    <div class="card-title" style="font-weight:700;font-size:14px;">
                        $${parseInt(p.valor_pago).toLocaleString('es-CO')}
                    </div>

                    <span class="badge-pill ${badgeClass}">
                        ${p.dias_restantes} días
                    </span>
                </div>

            </div>`;

            document.getElementById('prox-list').insertAdjacentHTML('beforeend', html);
        });

    })
    .catch(e => {
        document.getElementById('prox-empty').style.display = 'block';
        document.getElementById('prox-empty').textContent = 'Error al cargar próximos pagos.';
    });
</script>








