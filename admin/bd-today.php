<div class="section-title mb-3">Clientes que cumplen años hoy</div>
<div class="card-list-wrapper" id="cumple-list">
    <div class="card-list-empty" id="cumple-empty" style="display:none;">
		<i class="fas fa-check-circle btn-success" style="font-size:22px;display:block;margin-bottom:6px;"></i>
        No hay cumpleaños hoy.
    </div>
</div>

<script>
fetch('gets_home/get_cumpleanos_hoy.php')
    .then(response => response.json())
    .then(data => {
        if (!data.data || data.data.length === 0) {
            document.getElementById('cumple-empty').style.display = 'block';
        } else {
            document.getElementById('cumple-empty').style.display = 'none';
            
            data.data.forEach(c => {
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
                
                document.getElementById('cumple-list').insertAdjacentHTML('beforeend', html);
            });
        }
    })
    .catch(error => {
        document.getElementById('cumple-empty').style.display = 'block';
        document.getElementById('cumple-empty').textContent = 'Error al cargar cumpleaños';
    });
</script>





