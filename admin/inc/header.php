<?php require_once __DIR__ . '/../../inc/config.php'; ?>
<link rel="icon" href="<?php echo URLBASE . SITE_ICON; ?>"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">-->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo URLBASE; ?>/admin/css/menu.css?cache=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo URLBASE; ?>/admin/css/buttons.css?cache=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo URLBASE; ?>/admin/css/forms.css?cache=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo URLBASE; ?>/admin/css/style.css?cache=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo URLBASE; ?>/admin/css/cards.css?cache=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo URLBASE; ?>/admin/css/siluet.css?cache=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo URLBASE; ?>/admin/css/theme.css?cache=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo URLBASE; ?>/admin/css/loader.css?cache=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
<!-- DataTables Bootstrap 5 CSS -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Font Awesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/material_red.css">
<style>
        :root {
            --system-color-primary: <?= SYSTEM_COLOR_PRIMARY ?>;
            --system-color-secondary: <?= SYSTEM_COLOR_SECONDARY ?>;
			--system-color-primary-dark: <?= SYSTEM_COLOR_PRIMARY_DARK ?>;
			--system-color-secondary-dark: <?= SYSTEM_COLOR_SECONDARY_DARK ?>;
            
        }
    </style>


<script>
  // Script bloqueante - se ejecuta ANTES de renderizar la página
  const theme = localStorage.getItem('theme-mode') || 'light';
  document.documentElement.setAttribute('data-theme', theme);
  if (theme === 'dark') {
    document.documentElement.classList.add('dark-mode');
  }
</script>

 
