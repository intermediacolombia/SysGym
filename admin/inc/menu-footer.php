</div>

<!-- Modal Cumpleaños -->
<div class="modal fade" 
     id="modalCumple" 
     tabindex="-1" 
     aria-labelledby="cumpleTitle" 
     aria-hidden="true"
     data-bs-backdrop="static" 
     data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center border-0 shadow-lg">
      
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title w-100" id="cumpleTitle">
          🎉 ¡Cliente de Cumpleaños! 🎂
        </h5>
      </div>
      
      <div class="modal-body">
        <p class="fw-bold fs-5" id="cumpleLista"></p>
        <p class="mb-0">Acaba de ingresar un cliente que está cumpliendo años hoy.</p>
      </div>
      
      <div class="modal-footer justify-content-center">
        <a href="https://www.youtube.com/results?search_query=cumplea%C3%B1os+feliz" 
           target="_blank" 
           class="btn btn-danger px-4 d-flex align-items-center gap-2">
          <i class="fa fa-youtube-play"></i> Poner Canción
        </a>
        <button type="button" 
                class="btn btn-success px-4 d-flex align-items-center gap-2" 
                data-bs-dismiss="modal">
          <i class='far fa-check-circle'></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>



    
     <script>
        function toggleSubmenu(event) {
            event.preventDefault();
            const submenuToggle = event.currentTarget;
            submenuToggle.classList.toggle('active');
            const submenu = submenuToggle.nextElementSibling;
            if (submenu.style.maxHeight && submenu.style.maxHeight !== "0px") {
                submenu.style.maxHeight = "0px";
                submenu.style.padding = "0";
            } else {
                submenu.style.maxHeight = submenu.scrollHeight + "px";
                submenu.style.padding = "5px 0";
            }
        }
    </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  function verificarCumpleanios() {
    const hoy = new Date().toISOString().slice(0,10);
    const ultimaFecha = localStorage.getItem('cumpleFecha');

    // Reinicia el registro si cambió el día
    if (ultimaFecha !== hoy) {
      localStorage.removeItem('cumpleMostrados');
      localStorage.setItem('cumpleFecha', hoy);
    }

    fetch('<?php echo $url; ?>/admin/cumple_hoy.php')
      .then(r => r.json())
      .then(data => {
        if (data.success && Array.isArray(data.cumpleaneros) && data.cumpleaneros.length > 0) {
          const mostrados = JSON.parse(localStorage.getItem('cumpleMostrados') || '[]');
          const nuevos = data.cumpleaneros.filter(n => !mostrados.includes(n));
          if (nuevos.length > 0) {
            document.getElementById('cumpleLista').innerHTML = nuevos.join('<br>');
            const modal = new bootstrap.Modal(document.getElementById('modalCumple'));
            modal.show();
            localStorage.setItem('cumpleMostrados', JSON.stringify([...mostrados, ...nuevos]));
          }
        }
      })
      .catch(err => console.error('Error al verificar cumpleaños:', err));
  }

  // Llamar al cargar la página
  verificarCumpleanios();

  // (Opcional) Revisar cada cierto tiempo, por ejemplo cada 30 segundos:
  setInterval(verificarCumpleanios, 5000);
});
	
	

</script>


<!-- Asegúrate de tener jQuery incluido previamente -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
<script src="/admin/js/keepAlive.js?cache=<?php echo time();?>"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap 5 JS bundle (incluye Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <!-- DataTables Bootstrap 5 JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>


<!-- intlTelInput JS (antes del cierre del body) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const themeSwitch = document.getElementById('themeSwitch');
  const themeIcon = document.getElementById('themeIcon');
  const themeText = document.getElementById('themeText');
  const body = document.body;

  if (!themeSwitch || !themeIcon || !themeText) {
    console.warn('Switch de tema no encontrado en el DOM.');
    return;
  }

  // Leer preferencia guardada
  const savedTheme = localStorage.getItem('theme-mode');
  if (savedTheme === 'dark') {
    body.classList.add('dark-mode');
    themeSwitch.checked = true;
    themeIcon.className = 'fas fa-moon text-warning';
    themeText.textContent = 'Modo Oscuro';
  } else if (savedTheme === 'light') {
    body.classList.remove('dark-mode');
    themeSwitch.checked = false;
    themeIcon.className = 'fas fa-sun text-warning';
    themeText.textContent = 'Modo Claro';
  }

  // Detectar configuraci�n del sistema si no hay preferencia
  if (!savedTheme) {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
      body.classList.add('dark-mode');
      themeSwitch.checked = true;
      themeIcon.className = 'fas fa-moon text-warning';
      themeText.textContent = 'Modo Oscuro';
    }
  }

  // Evento de cambio
  themeSwitch.addEventListener('change', function () {
    if (this.checked) {
      body.classList.add('dark-mode');
      localStorage.setItem('theme-mode', 'dark');
      themeIcon.className = 'fas fa-moon text-warning';
      themeText.textContent = 'Modo Oscuro';
    } else {
      body.classList.remove('dark-mode');
      localStorage.setItem('theme-mode', 'light');
      themeIcon.className = 'fas fa-sun text-warning';
      themeText.textContent = 'Modo Claro';
    }
  });
});
</script>


