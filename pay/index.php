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
  background: linear-gradient(180deg, #6f00ff 0%, #c300ff 100%);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0;
}
.app-container {
  width: 360px;
  background: rgba(255,255,255,0.12);
  border-radius: 25px;
  padding: 40px 30px;
  box-shadow: 0 8px 40px rgba(0,0,0,0.3);
  backdrop-filter: blur(15px);
  text-align: center;
  color: white;
}
.app-container .avatar {
  background: rgba(255,255,255,0.15);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  margin: 0 auto 15px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.app-container .avatar i {
  font-size: 36px;
  color: white;
}
h4 {
  font-weight: 600;
  margin-bottom: 25px;
  letter-spacing: 0.5px;
}
.form-control {
  background: transparent;
  border: 2px solid rgba(255,255,255,0.6);
  border-radius: 30px;
  color: white;
  padding: 10px 15px;
  margin-bottom: 20px;
  text-align: center;
  transition: 0.3s;
}
.form-control:focus {
  background: rgba(255,255,255,0.15);
  border-color: white;
  outline: none;
  box-shadow: none;
}
::placeholder {
  color: rgba(255,255,255,0.7);
}
.btn-send {
  width: 100%;
  border: none;
  border-radius: 25px;
  background-color: white;
  color: #6f00ff;
  font-weight: 600;
  padding: 10px;
  letter-spacing: 1px;
  transition: 0.3s;
}
.btn-send:hover {
  transform: scale(1.03);
  background: #f0f0f0;
}
#resultado {
  display: none;
  margin-top: 25px;
  background: rgba(255,255,255,0.1);
  border-radius: 15px;
  padding: 15px;
  color: white;
}
footer {
  color: rgba(255,255,255,0.7);
  text-align: center;
  font-size: 0.9rem;
  margin-top: 25px;
}
</style>
</head>

<body>
<div class="app-container">
  <div class="avatar">
    <i class="bi bi-person-fill"></i>
  </div>
  <h4>Verificar Mensualidad</h4>

  <form id="buscarForm">
    <input type="text" id="identificacion" name="identificacion" class="form-control" placeholder="Número de Documento" required>
    <button type="submit" class="btn btn-send">Buscar</button>
  </form>

  <div id="resultado">
    <h6>Resultado</h6>
    <div id="infoCliente"></div>
  </div>

  <footer>© <?= date('Y'); ?> SysGym · Intermedia Colombia</footer>
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




