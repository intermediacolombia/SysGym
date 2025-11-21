</div>

<!-- Modal CumpleaÃ±os -->
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
          ðŸŽ‰ Â¡Cliente de CumpleaÃ±os! ðŸŽ‚
        </h5>
      </div>
      
      <div class="modal-body">
        <p class="fw-bold fs-5" id="cumpleLista"></p>
        <p class="mb-0">Acaba de ingresar un cliente que estÃ¡ cumpliendo aÃ±os hoy.</p>
      </div>
      
      <div class="modal-footer justify-content-center">
        <a href="https://www.youtube.com/results?search_query=cumplea%C3%B1os+feliz" 
           target="_blank" 
           class="btn btn-danger px-4 d-flex align-items-center gap-2">
          <i class="fa fa-youtube-play"></i> Poner CanciÃ³n
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

    // Reinicia el registro si cambiÃ³ el dÃ­a
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
      .catch(err => console.error('Error al verificar cumpleaÃ±os:', err));
  }

  // Llamar al cargar la pÃ¡gina
  verificarCumpleanios();

  // (Opcional) Revisar cada cierto tiempo, por ejemplo cada 30 segundos:
  setInterval(verificarCumpleanios, 5000);
});
	
	

</script>


<!-- AsegÃºrate de tener jQuery incluido previamente -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
<script src="/admin/js/keepAlive.js?cache=<?php echo time();?>"></script>
<!--script src="/admin/js/theme.js?<?php echo time();?>"></script-->
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
// Mostrar mensaje a los 5 segundos SIEMPRE
setTimeout(() => {
    const msg = document.getElementById("slow-loader-message");
    if (msg) msg.style.display = "block";
}, 5000);

// Ocultar loader después del mensaje (a los 6 segundos)
window.addEventListener('load', () => {
    setTimeout(() => {
        const loader = document.getElementById('page-loader');
        if (loader) {
            loader.style.cssText = `
                opacity: 0 !important;
                visibility: hidden !important;
                transition: opacity 0.3s ease-out;
            `;
        }
    }, 6000); // ? se oculta DESPUÉS del mensaje
});
</script>








<script>
  document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const toggle = document.getElementById('themeToggle');
    const icon = document.getElementById('themeIcon');
    const labelIcon = document.getElementById('themeLabelIcon');

    // Leer estado guardado
    const savedTheme = localStorage.getItem('theme-mode') || 'light';

    // Aplicar tema guardado (ya debería estar, pero lo reafirmamos)
    if (savedTheme === 'dark') {
      body.classList.add('dark-mode');
      if (toggle) toggle.checked = true;
    }

    // Mostrar body con transición lista
    setTimeout(() => body.classList.add('theme-ready'), 50);

    // Función para actualizar íconos
    function updateIcons(isDark) {
      if (!icon || !labelIcon) return;
      if (isDark) {
        icon.className = 'fas fa-moon text-info';
        labelIcon.className = 'fas fa-sun me-2 text-warning';
      } else {
        icon.className = 'fas fa-sun text-warning';
        labelIcon.className = 'fas fa-moon me-2 text-info';
      }
    }

    updateIcons(savedTheme === 'dark');

    // Evento del switch
    if (toggle) {
      toggle.addEventListener('change', function () {
        const isDark = this.checked;
        body.classList.toggle('dark-mode', isDark);
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        localStorage.setItem('theme-mode', isDark ? 'dark' : 'light');
        updateIcons(isDark);
      });
    }
  });

  // Ocultar loader cuando TODO cargue (incluyendo imágenes)
  window.addEventListener('load', () => {
    const loader = document.getElementById('page-loader');
    if (loader) {
      // Usar !important para forzar los estilos
      loader.style.cssText = 'opacity: 0 !important; visibility: hidden !important; transition: opacity 0.3s ease-out;';
      console.log('Loader ocultado'); // Debug
    }
  });
</script>




