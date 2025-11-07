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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root {
  --primary-color: <?= SYSTEM_COLOR_PRIMARY; ?>;
}
body {
  font-family: 'Inter', 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, var(--primary-color) 0%, #57a700 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #333;
}
.card {
  border: none;
  border-radius: 15px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  overflow: hidden;
}
.card-header {
  background: var(--primary-color);
  color: white;
  padding: 20px 25px;
  text-align: center;
}
.card-header img {
  width: 120px;
  margin-bottom: 10px;
}
.card-body {
  padding: 30px;
  background: #fff;
}
.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(95, 202, 0, 0.25);
}
.btn-primary-custom {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
  font-weight: 500;
  transition: all 0.3s ease;
}
.btn-primary-custom:hover {
  transform: scale(1.02);
  background-color: var(--primary-color);
  opacity: 0.9;
}
.result-box {
  background-color: #f8f9fa;
  border: 1px solid #eaeaea;
  border-radius: 8px;
  padding: 15px;
}
.text-primary-custom {
  color: var(--primary-color);
}
.swal2-confirm {
  background-color: var(--primary-color) !important;
}
footer {
  text-align: center;
  color: white;
  opacity: 0.8;
  font-size: 0.9rem;
  margin-top: 30px;
}
</style>
</head>

<body>
  <div class="card shadow-lg" style="width: 100%; max-width: 480px;">
    <div class="card-header">
      <img src="<?php echo $url . '/' . SITE_LOGO; ?>" alt="Logo">
      <h5 class="mb-0">Verificar Mensualidad Pendiente</h5>
      <small>Consulta rápida de estado del cliente</small>
    </div>
    <div class="card-body">
      <form id="buscarForm">
        <div class="mb-3">
          <label for="identificacion" class="form-label fw-semibold">Número de Documento</label>
          <input type="text" id="identificacion" name="identificacion" class="form-control" placeholder="Ejemplo: 1024589623" required>
        </div>
        <button type="submit" class="btn btn-primary-custom w-100 py-2">Buscar Cliente</button>
      </form>

      <div id="resultado" class="mt-4" style="display:none;">
        <hr>
        <h6 class="text-primary-custom fw-semibold mb-3">Resultado</h6>
        <div id="infoCliente" class="result-box"></div>
      </div>
    </div>
  </div>

  <footer>
    <p>© <?= date('Y'); ?> SysGym · Intermedia Colombia</p>
  </footer>

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


