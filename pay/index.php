<?php
require_once __DIR__ . '/../inc/config.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Paga tu Plan - SysGym</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root {
  --primary: <?= SYSTEM_COLOR_PRIMARY; ?>;
  --secondary: <?= SYSTEM_COLOR_SECONDARY; ?>;
}
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
              url(<?= URLBASE;?>/admin/images/background.jpg) no-repeat center center fixed;
  background-size: cover;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}
.app-box {
  width: 440px;
  background: #fff;
  border-radius: 25px;
  box-shadow: 0 15px 35px rgba(0,0,0,0.15);
  padding: 50px 45px;
  text-align: center;
}
.app-box img.logo {
  width: 260px;
  margin-bottom: 20px;
}
h4 {
  color: var(--primary);
  font-weight: 600;
  margin-bottom: 30px;
  letter-spacing: 0.5px;
  font-size: 1.4rem;
}
.form-control {
  height: 52px;
  border-radius: 10px;
  border: 1px solid #ddd;
  margin-bottom: 20px;
  font-size: 16px;
  padding-left: 45px;
}
.form-control:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.25rem rgba(95, 202, 0, 0.25);
}
.input-icon {
  position: relative;
}
.input-icon i {
  position: absolute;
  left: 14px;
  top: 15px;
  color: var(--primary);
  font-size: 20px;
}
.btn-login {
  width: 100%;
  height: 52px;
  border-radius: 10px;
  border: none;
  background: var(--primary);
  color: #fff;
  font-weight: 600;
  font-size: 16px;
  letter-spacing: 0.5px;
  transition: 0.3s;
}
.btn-login:hover {
  background: var(--secondary);
  transform: translateY(-2px);
  color: var(--primary);
}
#resultado {
  display: none;
  background: #f8f9fa;
  border-radius: 10px;
  margin-top: 25px;
  padding: 18px;
  text-align: left;
}
.plan-info {
  font-size: 15px;
  color: #444;
  margin-bottom: 5px;
}
strong { color: var(--primary); }

/* Modal */
.modal-content {
  border-radius: 20px;
  border: none;
  padding: 20px;
}
.modal-header, .modal-footer {
  border: none;
  text-align: center;
  justify-content: center;
}
.modal-body {
  text-align: center;
}
.btn-confirm {
  background: var(--primary);
  color: #fff;
  font-weight: 600;
  border-radius: 30px;
  padding: 10px 30px;
}
.btn-confirm:hover {
  background: var(--secondary);
  color: var(--primary);
}
</style>
</head>

<body>
  <div class="app-box">
    <img src="<?= $url . '/' . SITE_LOGO; ?>" alt="Logo" class="logo">
    <h4>Paga tu Plan</h4>

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

    <div class="link-row mt-3">
      <p>¿Inconvenientes? <a href="#">Contáctanos</a></p>
    </div>
  </div>

<!-- Modal -->
<div class="modal fade" id="modalConfirm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-primary fw-bold">Confirmar Pago</h5>
      </div>
      <div class="modal-body">
        <p>Estás a punto de pagar tu plan:</p>
        <h5 id="planNombre" class="text-dark fw-bold"></h5>
        <p>Valor base: <strong id="valorBase"></strong></p>
        <p>+ <?= ADDITIONAL_PERCENTAGE_PAYMENT; ?>% adicional</p>
        <h4 class="mt-2 text-success fw-bold">Total: <span id="valorFinal"></span></h4>
        <p class="mt-3 text-muted" style="font-size: 14px;">
          Serás redirigido a la pasarela de pago.<br>
          Pago 100 % seguro y encriptado.
        </p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-confirm" id="btnIrPagar">Continuar</button>
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

        // Si hay botón de pago, lo interceptamos
        $(document).on('click', '.btn-pagar', function(e){
          e.preventDefault();
          const id = $(this).data('id');
          const plan = $(this).data('plan');
          const valorBase = parseFloat($(this).data('valor'));
          const adicional = <?= ADDITIONAL_PERCENTAGE_PAYMENT; ?>;
          const valorFinal = (valorBase * (1 + (adicional / 100))).toFixed(2);

          $('#planNombre').text(plan);
          $('#valorBase').text(`$${valorBase.toLocaleString()}`);
          $('#valorFinal').text(`$${parseFloat(valorFinal).toLocaleString()}`);

          $('#btnIrPagar').off('click').on('click', function(){
            window.location.href = `crear_pago.php?id=${id}`;
          });

          const modal = new bootstrap.Modal(document.getElementById('modalConfirm'));
          modal.show();
        });
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>






