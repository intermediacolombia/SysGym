<?php
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Configurar Sistema';
include('../login/restriction.php');
require_once __DIR__ . '/../../inc/config.php';

try {

    // Asegurar conexión inicial
    db()->exec("SET NAMES 'utf8mb4'");

    // Cargar configuraciones habilitadas
    $stmt = db()->prepare("
        SELECT setting_name, value 
        FROM system_settings 
        WHERE enabled = 1
    ");
    $stmt->execute();

    $settings = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['setting_name']] = $row['value'];
    }

} catch (PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
    $settings = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Configuraciones del Sistema</title>
  <?php include('../inc/header.php'); ?>
  <style>
      body {
          background-color: #f8f9fa;
      }
      .nav-tabs .nav-link.active {
          background-color: var(--system-color-primary);
          color: #fff !important;
          border: none;
      }
      .nav-tabs .nav-link {
          color: #495057;
          border: none;
          font-weight: 500;
      }
      .tab-content {
          background-color: #fff;
          border-radius: 0.5rem;
          box-shadow: 0 0 10px rgba(0,0,0,0.1);
          padding: 2rem;
          margin-top: 1rem;
      }
      textarea.form-control {
          height: 180px;
          resize: vertical;
      }
      label strong {
          color: #000;
      }
  </style>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
    <h1>Configuraciones del Sistema</h1>
  </div>
  </div>

  <?php include('../inc/menu.php'); ?>
<div class="container mt-4">
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form id="configForm" action="save_config.php" method="POST" enctype="multipart/form-data">

    <!-- ======== TABS ======== -->
    <ul class="nav nav-tabs" id="configTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema" type="button" role="tab"><i class="fas fa-cog"></i> Sistema </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#whatsapp" type="button" role="tab"><i class="fa fa-whatsapp"></i> WhatsApp</button>
      </li>
		
		<li class="nav-item">
  <button class="nav-link" id="pasarela-tab" data-bs-toggle="tab" data-bs-target="#pasarela" type="button" role="tab" aria-controls="pasarela" aria-selected="false">
    <i class="fas fa-credit-card"></i> Pagos
  </button>
</li>

		
		<li class="nav-item" role="presentation">
    <button class="nav-link" id="consent-tab" data-bs-toggle="tab" data-bs-target="#consent" type="button" role="tab">
      <i class="fas fa-file-signature"></i> Consentimiento Informado
    </button>
  </li>
		
		<li class="nav-item" role="presentation">
  <button class="nav-link" id="smtp-tab" data-bs-toggle="tab" data-bs-target="#smtp" type="button" role="tab">
    <i class="fas fa-envelope"></i> SMTP
  </button>
</li>
	
		<li class="nav-item" role="presentation">
  <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab">
    <i class="fas fa-tools"></i> Avanzado
  </button>
</li>


		
    </ul>

    <!-- ======== CONTENIDO DE TABS ======== -->
    <div class="tab-content" id="configTabsContent">

     <?php require_once __DIR__ . '/tabs/system.php';?>	
     <?php require_once __DIR__ . '/tabs/whatsapp.php';?>
     <?php require_once __DIR__ . '/tabs/getaway.php';?>
	<?php require_once __DIR__ . '/tabs/consent.php'; ?>
	<?php require_once __DIR__ . '/tabs/email.php'; ?>
	<?php require_once __DIR__ . '/tabs/advanced.php'; ?>

     
     
		
    </div>

    <!-- Botón de guardado -->
    <div class="text-end mt-4">
      <button type="submit" class="btn btn-primary btn-lg px-4">
        <i class="fa fa-save me-2"></i>Guardar Cambios
      </button>
    </div>
  </form>
</div>

<?php include('../inc/menu-footer.php'); ?>

<!-- Librerías -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){
  $("#configForm").submit(function(e){
    e.preventDefault();

    var formData = new FormData(this);
    $.ajax({
      url: $(this).attr("action"),
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? 'success' : 'error',
          title: res.success ? 'Éxito' : 'Error',
          text: res.message
        }).then(() => {
          if(res.success) location.reload();
        });
      },
      error: function(){
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo guardar la configuración.'
        });
      }
    });
  });
});
</script>
	
	
	<!-- Summernote CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {
  // Inicializar Summernote solo cuando se muestre la pestaña
  $('#wa_consent_html').summernote({
    placeholder: 'Escribe aquí el consentimiento informado...',
    tabsize: 2,
    height: 300,
    toolbar: [
      ['style', ['bold', 'italic', 'underline', 'clear']],
      ['font', ['fontsize', 'color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['insert', ['link', 'picture', 'video']],
      ['view', ['fullscreen', 'codeview']]
    ]
  });

  // Guardar por AJAX
  $("#configForm").off('submit').on('submit', function(e){
    e.preventDefault();

    // Asegurar que el contenido HTML de Summernote se incluya
    const htmlContent = $('#wa_consent_html').summernote('code');
    const formData = new FormData(this);
    formData.set('wa_consent_html', htmlContent);

    $.ajax({
      url: $(this).attr("action"),
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? 'success' : 'error',
          title: res.success ? 'Éxito' : 'Error',
          text: res.message
        }).then(() => {
          if(res.success) location.reload();
        });
      },
      error: function(){
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo guardar la configuración.'
        });
      }
    });
  });
});
</script>


</body>
</html>












