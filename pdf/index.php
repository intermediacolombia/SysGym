<?php


// Habilitar errores para desarrollo (opcional; quita en producción)
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

// Obtener y sanitizar el parámetro 'id'
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
if (!$id) {
    die("Error: Parámetro 'id' es requerido.");
}

// Obtener y sanitizar el parámetro 'id'
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
if (!$type) {
    die("Error: Parámetro 'type' es requerido.");
}

use Dompdf\Dompdf;
use Dompdf\Options;

require_once '../vendor/autoload.php';  // Ruta correcta al autoload de Composer

// Configurar las opciones de Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);      // Permitir la carga de contenido remoto (imágenes, CSS, etc.)
$options->set('isHtml5ParserEnabled', true);   // Habilitar soporte HTML5
$options->set('isPhpEnabled', true);           // Permitir ejecución de PHP en el HTML

$pdf = new Dompdf($options);

// Configurar la base path para que las rutas relativas se resuelvan correctamente.
// Aquí se indica la carpeta 'pdf' que contiene tu archivo pdf.php y la carpeta images.
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/activgym/pdf/';
$pdf->setBasePath($basePath);

// Capturar el contenido HTML de pdf.php usando output buffering
$_GET['id'] = $id; // Pasar el parámetro a pdf.php
ob_start();
include ''.$type.'.php';
$html = ob_get_clean();

if (empty($html)) {
    die("Error: No se obtuvo contenido HTML.");
}

$pdf->loadHtml($html);

// Establecer el tamaño de papel y orientación
$pdf->setPaper('A4', 'portrait');

// Forzar márgenes personalizados: superior, derecho, inferior, izquierdo
$pdf->set_option('defaultPaperMargins', array(20, 15, 20, 15));

// Renderizar el PDF
$pdf->render();

// Generar y enviar el archivo PDF al navegador
$filename = $type . "_" . $id . ".pdf";
$pdf->stream($filename, ['Attachment' => true]);


?>
