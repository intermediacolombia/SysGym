<?php
require_once __DIR__ . '/../inc/config.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Verificar Mensualidad - SysGym</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root {
  --primary: <?= SYSTEM_COLOR_PRIMARY; ?>;
}

body {
  font-family: 'Poppins', sans-serif;
  /*background: #f0f3f9;*/
background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url(<?= URLBASE;?>/admin/images/background.jpg) no-repeat center center fixed;
    background-size: cover;

  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}

.app-box {
  width: 380px;
  background: #fff;
  border-radius: 25px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  padding: 40px 30px;
  text-align: center;
}

.app-box img.logo {
  width: 250px;
  margin-bottom: 15px;
}

h4 {
  color: var(--primary);
  font-weight: 600;
  margin-bottom: 25px;
  letter-spacing: 0.5px;
}

.form-control {
  height: 45px;
  border-radius: 8px;
  border: 1px solid #ddd;
  margin-bottom: 15px;
  font-size: 15px;
  padding-left: 40px;
}

.form-control:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(95, 202, 0, 0.25);
}

.input-icon {
  position: relative;
}

.input-icon i {
  position: absolute;
  left: 12px;
  top: 12px;
  color: var(--primary);
  font-size: 18px;
}

.btn-login {
  width: 100%;
  height: 45px;
  border-radius: 8px;
  border: none;
  background: var(--primary);
  color: #fff;
  font-weight: 600;
  letter-spacing: 0.5px;
  transition: 0.3s;
}

.btn-login:hover {
  background: #4fb800;
  transform: translateY(-2px);
}

.link-row {
  margin-top: 15px;
  font-size: 14px;
  color: #555;
}

.link-row a {
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
}

.link-row a:hover {
  text-decoration: underline;
}

#resultado {
  display: none;
  background: #f8f9fa;
  border-radius: 8px;
  margin-top: 20px;
  padding: 15px;
  text-align: left;
}
</style>
</head>

<body>
  <div class="app-box">
    <img src="<?= $url . '/' . SITE_LOGO; ?>" alt="Logo" class="logo">
    <h4>Verificar Mensualidad</h4>

    <form id="buscarForm">
      <div class="input-icon">
        <i class="bi bi-person"></i>
        <input type="text" id="identificacion" name="identificacion" class="form-control" placeholder="Número de documento" required>
      </div>
      <button type="submit" class="btn btn-login">Buscar</button>
    </form>

    <div id="resultado">
      <h6 class="text-primary">Resultado</h6>
      <div id="infoCliente"></div>
    </div>

    <div class="link-row">
      <p>¿No recuerdas tu número? <a href="#">Contáctanos</a></p>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.js"></script>
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





