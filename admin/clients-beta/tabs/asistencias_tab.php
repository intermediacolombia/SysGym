<?php if (isset($_SESSION["user_permissions"])
          && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>
	
<div class="mt-4">
  <h3><i class="fa fa-calendar-check-o"></i> Historial de Asistencias</h3>
  <hr>

  <table id="asistencias-table" class="table table-striped table-bordered">
    <thead>
      <tr>
		 <th>Día</th>
        <th>Fecha</th>
        <th>Hora</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
<?php endif; ?>