</div>

<!-- Modal Cumpleaños -->
<div class="modal fade" id="modalCumple" tabindex="-1" aria-labelledby="cumpleTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title w-100" id="cumpleTitle">🎉 ¡Cliente de Cumpleaños! 🎂</h5>
      </div>
      <div class="modal-body">
        <p class="fw-bold" id="cumpleLista"></p>
        <p class="mb-0">Acaba de ingresar un cliente que está cumpliendo años hoy.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">Cerrar</button>
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
	function verificarCumpleanios() {
  fetch('cumple_hoy.php')
    .then(r => r.json())
    .then(data => {
      if (data.success && data.cumpleaneros.length > 0) {
        const mostrados = JSON.parse(localStorage.getItem('cumpleMostrados') || '[]');
        const nuevos = data.cumpleaneros.filter(n => !mostrados.includes(n));
        if (nuevos.length > 0) {
          document.getElementById('cumpleLista').innerHTML = nuevos.join('<br>');
          new bootstrap.Modal(document.getElementById('modalCumple')).show();
          localStorage.setItem('cumpleMostrados', JSON.stringify([...mostrados, ...nuevos]));
        }
      }
    });
}

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


