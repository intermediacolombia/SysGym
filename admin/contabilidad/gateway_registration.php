<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Pagos Pasarela';
include('../login/restriction.php');
session_start();
require_once __DIR__ . '/../../inc/config.php';

/* -------- CONSULTA DE PAGOS -------- */
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $pdo->exec("SET lc_time_names = 'es_ES';");

    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.referencia,
            p.monto,
            COALESCE(p.estado, 'pending') AS estado,
            p.metodo_pago,
            DATE_FORMAT(p.fecha_pago, '%Y-%m-%d') AS fecha,
            DATE_FORMAT(p.fecha_pago, '%H:%i:%s') AS hora,
            DATE_FORMAT(p.fecha_pago, '%W') AS dia,
            c.id AS idCliente,
            c.identificacion,
            c.nombres,
            c.apellidos
        FROM pagos p
        LEFT JOIN clientes c ON c.id = p.cliente_id
        ORDER BY p.fecha_pago DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error DB: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Pagos Pasarela - SysGym</title>

  <?php include('../inc/header.php'); ?>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables Bootstrap 5 CSS -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

  <style>
    .table td, .table th {
      vertical-align: middle;
    }
  </style>
</head>
<body>

<div class="container" style="padding:0;background:rgba(0,0,0,.00)">
  <div class="portada">
    <h1>Pagos Realizados por Pasarela</h1>
  </div>
</div>

<?php include('../inc/menu.php'); ?>

<div class="container mt-4">

  <div class="d-flex align-items-center mb-3">
    <label for="filtroFecha" class="me-2 mb-0">
      <i class="fa fa-calendar"></i> Fecha:
    </label>
    <input type="text" id="filtroFecha" class="form-control" style="max-width:180px"
           value="<?= date('Y-m-d') ?>">
  </div>

  <!-- TABLA DE PAGOS -->
  <table id="tablaPagos" class="table table-striped table-bordered w-100">
    <thead class="table-dark">
      <tr>
        <th>Fecha</th>
        <th>Día</th>
        <th>Hora</th>
        <th>Referencia</th>
        <th>Cliente</th>
        <th>Identificación</th>
        <th>Monto</th>
        <th>Estado</th>
        <th>Método</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $estado = strtolower(trim($r['estado']));
          $estadoColor = match ($estado) {
            'approved' => 'success',
            'pending'  => 'warning',
            'rejected' => 'danger',
            default    => 'secondary'
          };
        ?>
        <tr data-id="<?= htmlspecialchars($r['idCliente']) ?>">
          <td><?= htmlspecialchars($r['fecha']) ?></td>
          <td><?= ucfirst($r['dia']) ?></td>
          <td data-order="<?= htmlspecialchars($r['hora']) ?>">
            <?php
              $h = DateTime::createFromFormat('H:i:s', $r['hora']);
              echo $h ? $h->format('g:i a') : '-';
            ?>
          </td>
          <td><?= htmlspecialchars($r['referencia']) ?></td>
          <td><?= htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) ?></td>
          <td><?= htmlspecialchars($r['identificacion']) ?></td>
          <td>$<?= number_format($r['monto'], 0, ',', '.') ?></td>
          <td>
            <span class="badge bg-<?= $estadoColor ?>">
              <?= ucfirst($estado) ?>
            </span>
          </td>
          <td><?= ucfirst($r['metodo_pago']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

<script>
$(function () {
  /* === DataTable === */
  const tabla = $('#tablaPagos').DataTable({
    order: [[0, 'desc'], [2, 'desc']],
    pageLength: 50,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
  });

  /* === Click fila → Perfil cliente === */
  $(document).on('click', '#tablaPagos tbody tr', function() {
    const id = $(this).data('id');
    if (id) window.location.href = "../clients/detail.php?id=" + encodeURIComponent(id);
  });

  /* === Filtro por fecha === */
  flatpickr('#filtroFecha', {
    dateFormat: 'Y-m-d',
    locale: 'es',
    defaultDate: '<?= date('Y-m-d') ?>',
    maxDate: 'today',
    onChange: function(sel) {
      const fecha = sel[0].toISOString().slice(0, 10);
      tabla.column(0).search('^' + fecha + '$', true, false).draw();
    }
  });
});
</script>

</body>
</html>


