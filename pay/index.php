<?php
require_once __DIR__ . '/../inc/config.php';
header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('<h3>Error al conectar con la base de datos: ' . $e->getMessage() . '</h3>');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Verificar Mensualidad - SysGym</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
  background-color: #f5f5f5;
  font-family: 'Segoe UI', sans-serif;
}
.card {
  border: none;
  border-radius: 10px;
}
.btn-primary-custom {
  background-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
  border-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
  color: white !important;
}
.btn-primary-custom:hover {
  opacity: 0.9;
}
.text-primary-custom {
  color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
}
.swal2-confirm {
  background-color: <?= SYSTEM_COLOR_PRIMARY; ?> !important;
}
</style>
</head>
<body class="py-5">

<div class="container">
  <div class="card shadow mx-auto" style="max-width:500px;">
    <div class="card-body">
      <center><img src="<?php echo $url;?>/<?php echo SITE_LOGO;?>" alt="Logo" width="200"></center>
      <br>
      <h4 class="text-center mb-4 text-primary-custom">Verificar Mensualidad Pendiente</h4>

      <form id="buscarForm">
        <div class="mb-3">
          <label for="identificacion" class="form-label">Número de Documento</label>
          <input type="text" id="identificacion" name="identificacion" class="form-control" placeholder="Ejemplo: 1024589623" required>
        </div>
        <button type="submit" class="btn btn-primary-custom w-100">Buscar Cliente</button>
      </form>

      <div id="resultado" class="mt-4" style="display:none;">
        <hr>
        <h5 class="text-primary-custom mb-3">Resultado</h5>
        <div id="infoCliente"></div>
      </div>
    </div>
  </div>
</div>

<script>
$('#buscarForm').on('submit', function(e){
  e.preventDefault();

  const doc = $('#identificacion').val().trim();
  if (!doc) {
    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: 'Por favor ingresa un número de documento.',
      confirmButtonColor: '<?= SYSTEM_COLOR_PRIMARY; ?>'
    });
    return;
  }

  Swal.fire({
    title: 'Buscando cliente...',
    didOpen: () => { Swal.showLoading(); },
    allowOutsideClick: false
  });

  $.ajax({
    url: 'verificar_mensualidad.php',
    type: 'POST',
    dataType: 'json',
    data: { identificacion: doc },
    success: function(response) {
      Swal.close();

      if (response.status === 'success') {
        $('#resultado').show();
        $('#infoCliente').html(response.html);
      } else {
        $('#resultado').hide();
        Swal.fire({
          icon: 'error',
          title: 'Sin resultados',
          text: response.message || 'No se encontró información para este cliente.',
          confirmButtonColor: '<?= SYSTEM_COLOR_PRIMARY; ?>'
        });
      }
    },
    error: function() {
      Swal.close();
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Ocurrió un problema al consultar la información.',
        confirmButtonColor: '<?= SYSTEM_COLOR_PRIMARY; ?>'
      });
    }
  });
});
</script>
</body>
</html>

