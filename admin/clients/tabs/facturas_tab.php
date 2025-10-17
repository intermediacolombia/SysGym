<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Historial  de Facturas del Cliente', $_SESSION["user_permissions"])): ?>
  <h3><i class='fas fa-file-invoice-dollar'></i> Historial de Facturas</h3>
	<hr>
  <div id="totalGlobal" style="font-size:16px; font-weight:bold; margin-bottom:10px;"></div>
<table id="facturas-table" class="table table-striped table-bordered">
  <thead>
    <tr>
      <th># Factura</th>
      <th>Fecha Factura</th>
      <th>Detalle</th>
      <th>Valor Pagado</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>
<?php endif;?>

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
