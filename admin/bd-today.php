<div class="section-title mb-3">Clientes que cumplen años hoy</div>
<div class="card-list-wrapper" id="cumple-list">
    <div class="card-list-empty" id="cumple-empty" style="display:none;">
        No hay cumpleaños hoy.
    </div>
</div>

<script>
console.log("=== INICIO DIAGNÓSTICO ===");
console.log("1. jQuery existe?", typeof jQuery !== 'undefined');
console.log("2. $ existe?", typeof $ !== 'undefined');
console.log("3. Elemento #cumple-list existe?", document.getElementById('cumple-list') !== null);
console.log("4. Elemento #cumple-empty existe?", document.getElementById('cumple-empty') !== null);

// Intentar con JavaScript puro primero
fetch('gets_home/get_cumpleanos_hoy.php')
    .then(response => {
        console.log("5. Respuesta recibida, status:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("6. JSON parseado:", data);
        console.log("7. Cantidad de registros:", data.data ? data.data.length : 0);
        
        if (!data.data || data.data.length === 0) {
            document.getElementById('cumple-empty').style.display = 'block';
            console.log("8. No hay cumpleaños");
        } else {
            document.getElementById('cumple-empty').style.display = 'none';
            console.log("9. Procesando", data.data.length, "cumpleaños");
            
            data.data.forEach((c, index) => {
                console.log(`10.${index} Procesando:`, c.nombres, c.apellidos);
                
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
                console.log(`11.${index} Tarjeta insertada`);
            });
            console.log("12. PROCESO COMPLETADO");
        }
    })
    .catch(error => {
        console.error("ERROR:", error);
        document.getElementById('cumple-empty').style.display = 'block';
        document.getElementById('cumple-empty').textContent = 'Error: ' + error.message;
    });

console.log("=== FIN DIAGNÓSTICO ===");
</script>





