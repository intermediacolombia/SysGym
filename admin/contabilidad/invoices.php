<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php require_once __DIR__ . '/../../inc/config.php'; ?>
<?php 
$permisopage = 'Manejar Contabilidad';
include('../login/restriction.php'); ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Facturas</title>
  <?php include('../inc/header.php'); ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <style>
    /* Opcional: para ajustar el tamaño del modal o el iframe */
    .modal-lg {
      max-width: 90%;
    }
    #invoiceModalFrame {
      width: 100%;
      height: 80vh;
      border: none;
    }
  </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1 class="mb-4">Listado de Facturas</h1>
  </div>
</div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    
    <table id="tabla-facturas" class="table table-striped table-bordered" style="width:100%">
      <thead>
        <tr>
          <th># Factura</th>
          <th>Cliente</th>
          <th>Fecha</th>
          <th>Detalle</th>
          <th>Valor</th>
        </tr>
      </thead>
    </table>
  </div>
  
  <?php include('../inc/menu-footer.php'); ?>
  
  <!-- Modal para mostrar la factura -->
  <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
		  
        <div class="modal-header">
  <h5 class="modal-title" id="invoiceModalLabel">Detalle de Factura</h5>
  <?php if (isset($_SESSION["user_permissions"]) && in_array('Descargar Copia de Facturas', $_SESSION["user_permissions"])): ?>
			
    <a id="downloadInvoice" href="" class="btn btn-link ms-3"><i class='fas fa-file-download'></i> Descargar Copia</a>
	 <?php endif; ?>
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Enviar Copia de Facturas', $_SESSION["user_permissions"])): ?>		
    <button id="sendInvoice" class="btn btn-link ms-2"><i class='fas fa-paper-plane'></i> Enviar Copia Factura</button>
  <?php endif; ?>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>
		  
        <div class="modal-body">
          <!-- Se utiliza un iframe para cargar la factura -->
          <iframe id="invoiceModalFrame" src=""></iframe>
        </div>
      </div>
    </div>
  </div>
  
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  
  <script>
    $(document).ready(function() {
      var table = $('#tabla-facturas').DataTable({
        ajax: 'get_invoices_all.php',
        columns: [
          { data: 'id' },
          { data: 'nombre_cliente' },
          { data: 'fecha' },
          { data: 'detalle' },
          { 
            data: 'valor',
            render: $.fn.dataTable.render.number('.', ',', 0, '$')
          }
        ],
        pageLength: 100,
        order: [[0, 'desc']],
        language: {
          url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
      });

      // Al hacer clic en una fila, cargar la factura en el modal
      $('#tabla-facturas tbody').on('click', 'tr', function() {
        var data = table.row(this).data();
        if(data && data.id) {
          // Construir la URL a cargar (ajusta la ruta si es necesario)
          var invoiceUrl = "/pdf/invoice.php?id=" + encodeURIComponent(data.id);
			var invoiceUrlDw = "/pdf/?type=invoice&id=" + encodeURIComponent(data.id);
          $("#invoiceModalFrame").attr("src", invoiceUrl);
          $("#downloadInvoice").attr("href", invoiceUrlDw);
          // Abrir el modal
          var myModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
          myModal.show();
        }
      });
		
		
		$('#sendInvoice').on('click', function() {
    var invoiceId = $('#invoiceModalFrame').attr('src').split('id=')[1];
    
    Swal.fire({
        title: 'Confirmar Envío',
        text: '¿Deseas enviar esta factura al cliente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../clients/send_invoice.php',
                method: 'POST',
                data: { invoice_id: invoiceId },
                dataType: 'json',
                success: function(response){
                    if(response.status === 'success'){
                        Swal.fire('Éxito', response.message, 'success');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(){
                    Swal.fire('Error', 'No se pudo enviar la factura.', 'error');
                }
            });
        }
    });
});
		
		
    });
  </script>
</body>
</html>



