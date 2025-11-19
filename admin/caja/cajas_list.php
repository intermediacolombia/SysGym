<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';

$permisopage = 'Usar Cajas';
include('../login/restriction.php');

/* ── conexión ─────────────────────────────────────────────── */
/* ── fechas en las que ESTE usuario cerró caja ────────────── */
/* ── fechas en las que ESTE usuario cerró caja ───────────── */
$fechasCaja = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT DATE(fecha_apertura) AS f
        FROM   cajas
        WHERE  estado = 0          
          AND  usuario_id = :u     
    ");
    /* ⇩ ANTES: ':u' => $_SESSION['user_id']  */
    $stmt->execute([':u' => $id_user]);      // ⇦ usa la misma variable del login
    $fechasCaja = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $fechasCaja = [];
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Cajas Cerradas</title>
  <?php include('../inc/header.php'); ?>
  
</head>
<body>

<div class="container" style="padding:0;background:rgba(0,0,0,0)">
  <div class="portada"><h1 class="mb-4">Mis Cajas Cerradas</h1></div>
</div>
<?php include('../inc/menu.php'); ?>

<!-- filtro -->
<div class="d-flex align-items-center mb-3 ps-3" style="max-width:260px;">
  <i class="fa fa-calendar me-2"></i> Fecha:&nbsp;
  <input  type="text"
          id="filtroFecha"
          class="form-control form-control-sm me-2"
          placeholder="aaaa-mm-dd"
          readonly>
  <button id="btnClear" class="btn btn-outline-secondary btn-sm">Limpiar</button>
</div>

<div class="container mt-4">
  <table id="cajas-table" class="table table-striped table-bordered w-100">
    <thead>
      <tr>
        <th>ID</th>
        <th><i class="far fa-calendar-alt"></i> Apertura</th>
        <th><i class="fa fa-clock-o"></i> Apertura</th>
        <th><i class="far fa-calendar-alt"></i> Cierre</th>
        <th><i class="fa fa-clock-o"></i> Cierre</th>
        <th>Base</th><th>Total Vendido</th><th>Total en Caja</th>
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
$(function(){

  /* ---------- lista que viene de PHP ---------- */
  const habilitadasArr = <?= json_encode($fechasCaja) ?>;        // ex: ["2025-07-10","2025-07-18"]

  /*  La convertimos en Set para búsquedas O(1)  */
  const habilitadas = new Set(habilitadasArr);

  /* ---------- configuración Flatpickr ---------- */
  const fp = flatpickr('#filtroFecha',{
    dateFormat : 'Y-m-d',
    locale     : 'es',
    allowInput : false,
    maxDate    : 'today',

    /*   enable con FUNCIÓN
         – recibe cada Date
         – devuelve true sólo si está en el Set          */
    enable : [
      date => habilitadas.has( flatpickr.formatDate(date,'Y-m-d') )
    ],

    onChange : recargar
  });

  /* ---------- DataTable (igual) ---------- */
  const tabla = $('#cajas-table').DataTable({
    ajax : {
      url  : 'fetch_cajas.php',
      type : 'GET',
      data : d => {
        d.estado = 0;                          // sólo cerradas
        d.fecha  = $('#filtroFecha').val();    // '' → todas
      }
    },
    columns : [
      {data:'id'},
      {data:'fecha_apertura'},
      {data:'hora_apertura_formateada'},
      {data:'fecha_cierre'},
      {data:'hora_cierre_formateada'},
      {data:'monto_inicial', render:d=>'$'+(+d).toLocaleString('es-CO')},
      {data:'total_vendido', render:d=>'$'+(+d).toLocaleString('es-CO')},
      {data:'total_cierre' , render:d=>'$'+(+d).toLocaleString('es-CO')}
    ],
    order:[[0,'desc']], pageLength:50,
    language:{url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'}
  });

  function recargar(){ tabla.ajax.reload(); }

  /* botón Limpiar: borra fecha y recarga */
  $('#btnClear').on('click', () => { fp.clear(); recargar(); });

  /* clic en fila → detalle */
  $('#cajas-table tbody').on('click','tr',function(){
    const d = tabla.row(this).data();
    if(d) window.location.href = 'caja_detail.php?id='+d.id;
  });

});
</script>


</body>
</html>

