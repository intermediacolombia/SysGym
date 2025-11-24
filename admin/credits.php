<div class="section-title mb-3">Créditos activos</div>

<div class="card-list-wrapper" id="creditos-list">
    <div class="card-list-empty" id="creditos-empty" style="display:none;">
		<i class="fas fa-check-circle text-success" style="font-size:22px;display:block;margin-bottom:6px;"></i>
        No hay créditos activos.
    </div>
</div>

<div class="card-list-wrapper" style="margin-top:10px;">
    <div class="card-title" id="creditos-total" style="font-size:15px; display:none;">
        Total global en créditos: <strong></strong>
    </div>
</div>

<script>
fetch('gets_home/get_creditos_activos.php')
    .then(r => r.json())
    .then(result => {

        const data  = result.data || [];
        const total = result.total || 0;

        const list  = document.getElementById('creditos-list');
        const empty = document.getElementById('creditos-empty');
        const totalBox = document.getElementById('creditos-total');

        if (data.length === 0) {
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';

        data.forEach(cr => {

            let foto = cr.imagen_perfil
                ? '../uploads/clientes/' + cr.imagen_perfil
                : 'https://ui-avatars.com/api/?name=' + 
                    encodeURIComponent(cr.nombres + " " + cr.apellidos) +
                    '&background=f44336&color=fff';

            let badgeClass, diasTxt;

            if (cr.dias_restantes < 0) {
                diasTxt = "Vencido hace " + Math.abs(cr.dias_restantes) + " días";
                badgeClass = "badge-red";
            } else if (cr.dias_restantes == 0) {
                diasTxt = "Vence hoy";
                badgeClass = "badge-yellow";
            } else if (cr.dias_restantes <= 3) {
                diasTxt = cr.dias_restantes + " días (urgente)";
                badgeClass = "badge-yellow";
            } else {
                diasTxt = cr.dias_restantes + " días";
                badgeClass = "badge-green";
            }

            let html = `
            <div class="card-item" onclick="window.location.href='clients/detail.php?id=${cr.idCliente}'">

                <div class="card-avatar">
                    <img src="${foto}" class="card-avatar-img" alt="foto">
                </div>

                <div class="card-info">
                    <div class="card-title">${cr.nombres} ${cr.apellidos}</div>
                    <div class="card-sub">
                        ${cr.descripcion} · ${cr.fecha}
                    </div>
                </div>

                <div style="text-align:right;">
                    <div class="card-title" style="font-size:14px; font-weight:700;">
                        $${Number(cr.valor).toLocaleString('es-CO')}
                    </div>

                    <span class="badge-pill ${badgeClass}">
                        ${diasTxt}
                    </span>
                </div>

            </div>`;

            list.insertAdjacentHTML('beforeend', html);
        });

        // Total global
        totalBox.style.display = 'block';
        totalBox.querySelector('strong').textContent = 
            "$" + total.toLocaleString('es-CO');

    })
    .catch(err => {
        console.error(err);
        const empty = document.getElementById('creditos-empty');
        empty.style.display = 'block';
        empty.textContent = "Error al cargar créditos.";
    });
</script>





