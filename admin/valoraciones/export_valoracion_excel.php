<?php
// export_valoracion_excel.php
require __DIR__.'/../../inc/config.php';
require __DIR__.'/../../vendor/autoload.php';      // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID inválido');
}
$id = (int)$_GET['id'];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $dbuser,$dbpass,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );

    /* 1) Valoración ---------------------------------------------------- */
    $stmt = $pdo->prepare("SELECT * FROM valoraciones WHERE id = :id AND borrado = 0");
    $stmt->execute([':id'=>$id]);
    $val = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$val) die('Valoración no encontrada');

    /* 2) Cliente (para mostrar el nombre) ----------------------------- */
    $stmt = $pdo->prepare("SELECT nombres, apellidos FROM clientes WHERE id = :cid");
    $stmt->execute([':cid'=>$val['cliente_id']]);
    $cli = $stmt->fetch(PDO::FETCH_ASSOC);

    /* 3) Define columnas que quieras incluir ------------------------- */
    $campos = [
      'fecha'                        => 'Fecha',
      'peso'                         => 'Peso (Kg)',
      'estatura'                     => 'Estatura (m)',
      'imc'                          => 'IMC',
      'porcentaje_grasa_corporal'    => '% Grasa',
      'porcentaje_musculo_esqueletico'=> '% Músculo',
      'metabolismo_basal'            => 'Metabolismo Basal',
      'edad_corporal'                => 'Edad Corporal',
      'nivel_grasa_visceral'         => 'Grasa Visceral',
      'hombros'                      => 'Hombros',
      'pecho'                        => 'Pecho',
      'abdomen'                      => 'Abdomen',
      'cintura'                      => 'Cintura',
      'cadera'                       => 'Cadera',
      'izq_brazo'                    => 'Brazo Izq.',
      'der_brazo'                    => 'Brazo Der.',
      'izq_antebrazo'                => 'Antebrazo Izq.',
      'der_antebrazo'                => 'Antebrazo Der.',
      'izq_muneca'                   => 'Muñeca Izq.',
      'der_muneca'                   => 'Muñeca Der.',
      'izq_muslo_medio'              => 'Muslo Medio Izq.',
      'der_muslo_medio'              => 'Muslo Medio Der.',
      'izq_pantorrilla'              => 'Pantorrilla Izq.',
      'der_pantorrilla'              => 'Pantorrilla Der.',
      'observaciones'                => 'Observaciones'
    ];

   /* ===============================================================
   4)   GENERAR Y ENVIAR EL EXCEL
   ===============================================================*/

/* –– 4.0  Iniciar/limpiar buffer –– */
if (ob_get_length()) { ob_end_clean(); }
ob_start();

/* –– 4.1  Crear libro y hoja –– */
$spread = new Spreadsheet();
$sheet  = $spread->getActiveSheet();

/* –– 4.2  Estilos reutilizables –– */
$styleHeader = [
  'font' => ['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
  'fill' => ['fillType'=>'solid','startColor'=>['rgb'=>'E21F0C']],
  'alignment'=>['horizontal'=>'center']
];
$styleTable = [
  'borders'=>[
    'allBorders'=>[
      'borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
      'color'=>['rgb'=>'CCCCCC']
    ]
  ]
];

/* –– 4.3  Logo (opcional) –– */
$logoPath = __DIR__.'/../../img/logo-black.png';     // ajusta si es otra ruta
if (!file_exists($logoPath)) {
    @copy('https://sysgym.intermediacolombia.com/admin/images/logo-black.png', $logoPath);
}
if (file_exists($logoPath)) {
    $d = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $d->setPath($logoPath)->setHeight(70)
      ->setCoordinates('A1')->setOffsetY(5)
      ->setWorksheet($sheet);
}

/* –– 4.4  Título –– */
$row = 6;    // espacio para el logo
$titulo = "Valoración de {$cli['nombres']} {$cli['apellidos']}";
$sheet->setCellValue("A{$row}", $titulo)
      ->mergeCells("A{$row}:B{$row}");
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(15);
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal('center');
$row += 2;

/* –– 4.5  Encabezado tabla –– */
$sheet->setCellValue("A{$row}", 'Dato')
      ->setCellValue("B{$row}", 'Medida');
$sheet->getStyle("A{$row}:B{$row}")->applyFromArray($styleHeader);
$row++;

/* –– 4.6  Datos –– */
$primeraFilaDatos = $row;
foreach ($campos as $campo=>$etiqueta) {

    $sheet->setCellValue("A{$row}", $etiqueta);

    /* dentro del foreach, cuando procesas la fecha */
if ($campo === 'fecha' && !empty($val[$campo])) {
    $excelDate = Date::PHPToExcel(new DateTime($val[$campo]));

    // si la constante existe, úsala; si no, usa el formato literal
    $fmt = defined('\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2')
           ? NumberFormat::FORMAT_DATE_YYYYMMDD2
           : 'yyyy-mm-dd';

    $sheet->setCellValue("B{$row}", $excelDate);
    $sheet->getStyle("B{$row}")
          ->getNumberFormat()
          ->setFormatCode($fmt);
} else {
    $sheet->setCellValue("B{$row}", $val[$campo]);
}

    $row++;
}
$ultimaFilaDatos = $row-1;

/* –– 4.7  Bordes + ancho + alineación derecha –– */
$sheet->getStyle("A{$primeraFilaDatos}:B{$ultimaFilaDatos}")
      ->applyFromArray($styleTable);

$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(20);

/*  fuerza alineación derecha para toda la columna “Medida”            */
$sheet->getStyle("B{$primeraFilaDatos}:B{$ultimaFilaDatos}")
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

/* –– 4.8  Enviar archivo –– */
if (ob_get_length()) { ob_end_clean(); }

	
// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'valoracion_id' => $id,
    'cliente'       => "{$cli['nombres']} {$cli['apellidos']}",
    'accion'        => 'Exportación de valoración a Excel'
], JSON_UNESCAPED_UNICODE);

log_action('Exportar valoración', $desc, 'Valoraciones');
// END LOGS

	
	
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="valoracion_'.$id.'.xlsx"');
header('Cache-Control: max-age=0');

(new Xlsx($spread))->save('php://output');
exit;






} catch (PDOException $e) {
    die("Error BD: ".$e->getMessage());
}
