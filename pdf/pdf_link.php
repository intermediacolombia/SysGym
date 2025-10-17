<?php
$id   = (int)($_GET['id'] ?? 0);
$type = preg_replace('/[^a-z_]/', '', $_GET['type'] ?? '');
if (!$id || !$type) exit('Parámetros inválidos');
header("Location: /pdf/?type=$type&id=$id");
exit;
