<?php
require_once __DIR__ . '/../login/session.php';
session_start();
require_once __DIR__ . '/../../inc/config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Soporte en Vivo</title>
  <?php include('../inc/header.php'); ?>
  <style type="text/css">
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
    }
    /* El iframe ocupará el 100% de la altura del viewport */
    iframe {
      display: block;
      border: none;
      width: 100%;
      height: 100vh;
    }
  </style>
</head>
<body>
  <?php include('../inc/menu.php'); ?>
  <iframe id="iframe"
          frameborder="0"
          scrolling="no"
          marginheight="0"
          marginwidth="0"
          allowtransparency="yes"
          src="https://tawk.to/253fda940885f2de1b113a266292fb3979fcb101">
  </iframe>
  <?php include('../inc/menu-footer.php'); ?>
</body>
</html>