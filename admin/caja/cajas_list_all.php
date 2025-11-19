<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';
$permisopage = 'Ver Todas las Cajas';
include('../login/restriction.php');

/* ────────────────────────────────────────────────
 * 1)  Traer las fechas que SÍ tienen cajas
 * ────────────────────────────────────────────────*/
try {
    // Intentar usar la conexión global
    $stmt = db()->query("SELECT DISTINCT DATE(fecha_apertura) AS f FROM cajas");
    $fechasCaja = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Si falla la BD, no aplicar restricciones al calendario
    $fechasCaja = [];
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Cajas</title>
  <?php include('../inc/header.php'); ?>
  
  <style>
    .estado-cerrada { background:#FFCDD2!important;color:#C62828!important;font-weight:bold;text-align:center; }
    .estado-abierta { background:#C8E6C9!important;color:#2E7D32!important;font-weight:bold;text-align:center; }
  </style>
</head>
<body>

<div class="container" style="padding:0;background:rgba(0,0,0,0)">
  <div class="portada"><h1 class="mb-4">Listado de Cajas</h1></div>
</div>
<?php include('../inc/menu.php'); ?>

<!-- Filtro por fecha -->
<div class="d-flex align-items-center mb-3 ps-3" style="max-width:340px;">
  <i class="fa fa-calendar me-2"></i> Fecha:&nbsp;

  <input type="text" id="filtroFecha"
         class="form-control form-control-sm me-2"
         placeholder="<?php echo $hoy;?>" readonly>

  <button id="btnClear" class="btn btn-outline-secondary btn-sm">
    Limpiar
  </button>
</div>


<div class="container mt-4">
  <table id="cajas-table" class="table table-striped table-bordered w-100">
    <thead>
      <tr>
        <th>ID</th><th>Usuario</th>
        <th><i class="far fa-calendar-alt"></i> Apertura</th>
        <th><i class="fa fa-clock-o"></i> Apertura</th>
        <th><i class="far fa-calendar-alt"></i> Cierre</th>
        <th><i class="fa fa-clock-o"></i> Cierre</th>
        <th>Base</th><th>Total Vendido</th><th>Total en Caja</th><th>Estado</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<?php include('../inc/menu-footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script>
$(function () {

  /*── 1. calendario con fechas habilitadas ──────────────────────────*/
  const fechasHabilitadas = <?= json_encode($fechasCaja) ?>;   // desde PHP

  flatpickr('#filtroFecha', {
    dateFormat : 'Y-m-d',
    locale     : 'es',
    allowInput : false,
    maxDate    : 'today',
    enable     : fechasHabilitadas,    // solo esas fechas son clicables
    onChange       : recargar,
    onValueUpdate  : recargar          // también al borrar manualmente
  });

  /*── 2. DataTable ──────────────────────────────────────────────────*/
  const tabla = $('#cajas-table').DataTable({
    ajax : {
      url  : 'fetch_cajas_all_closed.php',
      type : 'GET',
      data : d => { d.fecha = $('#filtroFecha').val(); }   // "" = todas
    },
    columns : [
      {data:'id'},
      {data:'usuario'},
      {data:'fecha_apertura'},
      {data:'hora_apertura_formateada'},
      {data:'fecha_cierre'},
      {data:'hora_cierre_formateada'},
      {data:'monto_inicial', render:d=>'$'+parseFloat(d).toLocaleString('es-CO')},
      {data:'total_vendido', render:d=>'$'+parseFloat(d).toLocaleString('es-CO')},
      {data:'total_cierre' , render:d=>'$'+parseFloat(d).toLocaleString('es-CO')},
      {data:'estado', render:d=>Number(d)===0?'Cerrada':'Abierta'}
    ],
    order: [[0,'desc']],
    pageLength:50,
    language:{url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'},
    createdRow:function(row,data){
      const cls = Number(data.estado)===0?'estado-cerrada':'estado-abierta';
      $('td',row).eq(9).addClass(cls);
    }
  });

  /*── 3. recargar al cambiar fecha ─────────────────────────────────*/
  function recargar(){ tabla.ajax.reload(); }

  /*── 4. clic en fila → detalle ───────────────────────────────────*/
  $('#cajas-table tbody').on('click','tr',function(){
    const d = tabla.row(this).data();
    if(d) window.location.href = 'caja_detail.php?id='+d.id;
  });
	
	/* botón LIMPIAR → vacía el input y muestra todas las cajas */
$('#btnClear').on('click', function () {
  $('#filtroFecha').val('');   // borra el texto
  recargar();                  // recarga DataTable (sin fecha = todas)
});


});
</script>
</body>
</html>


