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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root {
  --primary-color: <?= SYSTEM_COLOR_PRIMARY; ?>;
}

body {
  background-color: #b7d9f3;
  font-family: 'Inter', sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
}

.app-card {
  background-color: #fff;
  width: 360px;
  border-radius: 16px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  overflow: hidden;
}

.app-header {
  background-color: var(--primary-color);
  height: 90px;
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 18px;
}

.app-header .menu {
  font-size: 24px;
  color: #fff;
}

.app-header .dots {
  display: flex;
  gap: 6px;
}
.app-header .dots span {
  width: 8px;
  height: 8px;
  background-color: rgba(255,255,255,0.8);
  border-radius: 50%;
}

.app-header .avatar {
  position: absolute;
  left: 50%;
  bottom: -35px;
  transform: translateX(-50%);
  background-color: #fff;
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.app-header .avatar i {
  font-size: 32px;
  color: var(--primary-color);
}

.app-body {
  padding: 60px 25px 25px;
}

.app-body h5 {
  text-align: center;
  color: var(--primary-color);
  font-weight: 600;
  margin-bottom: 25px;
}

.form-control {
  border-radius: 8px;
  padding: 10px 12px;
  border: 1px solid #ccc;
}
.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(95, 202, 0, 0.25);
}

.btn-send {
  background-color: var(--primary-color);
  border: none;
  border-radius: 25px;
  width: 100%;
  padding: 10px;
  color: white;
  font-weight: 600;
  letter-spacing: 1px;
  transition: all 0.3s ease;
}
.btn-send:hover {
  opacity: 0.9;
  transform: scale(1.02);
}

#resultado {
  display: none;
  margin-top: 20px;
  border-radius: 10px;
  background-color: #f8f9fa;
  padding: 15px;
}
</style>
</head>

<body>
  <div class="app-card">
    <div class="app-header">
      <div class="dots">
        <span></span><span></span><span></span>
      </div>
      <div class="avatar">
        <i class="bi bi-person"></i>
      </div>
      <div class="menu">☰</div>
    </div>
    <div class="app-body">
      <h5>Verificar Mensualidad</h5>
      <form id="buscarForm">
        <div class="mb-3">
          <input type="text" id="identificacion" name="identificacion" class="form-control text-center" placeholder="Número de Documento" required>
        </div>
        <button type="submit" class="btn btn-send">BUSCAR</button>
      </form>

      <div id="resultado">
        <h6 class="text-primary mt-2">Resultado</h6>
        <div id="infoCliente"></div>
      </div>
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



