<?php
require __DIR__.'/../../inc/config.php';
require __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    exit('ID inválido');
}

$id = (int)$_GET['id'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT * FROM valoraciones WHERE id = :id AND borrado = 0");
    $stmt->execute([':id' => $id]);
    $val = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$val) exit('Valoración no encontrada');

    $stmt = $pdo->prepare("SELECT nombres, apellidos FROM clientes WHERE id = :cid");
    $stmt->execute([':cid' => $val['cliente_id']]);
    $cli = $stmt->fetch(PDO::FETCH_ASSOC);

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

    // =======================================
    // Generar Excel
    // =======================================
    $spread = new Spreadsheet();
    $sheet  = $spread->getActiveSheet();

	
	$colorPrimario = defined('SYSTEM_COLOR_PRIMARY')
    ? ltrim(SYSTEM_COLOR_PRIMARY, '#')  // elimina el '#' si existe
    : '000000'; // color por defecto
	
	
    $styleHeader = [
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $colorPrimario]],
      'alignment' => ['horizontal' => 'center']
    ];
    $styleTable = [
      'borders' => [
        'allBorders' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
          'color' => ['rgb' => 'CCCCCC']
        ]
      ]
    ];

    $logoPath = $_SERVER['DOCUMENT_ROOT'] . SITE_LOGO;
    if (file_exists($logoPath)) {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath($logoPath)->setHeight(70)->setCoordinates('A1')->setOffsetY(5);
        $drawing->setWorksheet($sheet);
    }

    $row = 6;
    $sheet->setCellValue("A{$row}", "Valoración de {$cli['nombres']} {$cli['apellidos']}")
          ->mergeCells("A{$row}:B{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(15);
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal('center');
    $row += 2;

    $sheet->setCellValue("A{$row}", 'Dato')->setCellValue("B{$row}", 'Medida');
    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($styleHeader);
    $row++;

    $first = $row;
    foreach ($campos as $campo => $etiqueta) {
        $sheet->setCellValue("A{$row}", $etiqueta);
        if ($campo === 'fecha' && !empty($val[$campo])) {
    $excelDate = Date::PHPToExcel(new DateTime($val[$campo]));
    $sheet->setCellValue("B{$row}", $excelDate);

    //  Compatibilidad con distintas versiones de PhpSpreadsheet
    $fmt = defined('PhpOffice\\PhpSpreadsheet\\Style\\NumberFormat::FORMAT_DATE_YYYYMMDD2')
        ? NumberFormat::FORMAT_DATE_YYYYMMDD2
        : 'yyyy-mm-dd';

    $sheet->getStyle("B{$row}")
          ->getNumberFormat()
          ->setFormatCode($fmt);
} else {
    $sheet->setCellValue("B{$row}", $val[$campo]);
}

        $row++;
    }
    $last = $row - 1;

    $sheet->getStyle("A{$first}:B{$last}")->applyFromArray($styleTable);
    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getStyle("B{$first}:B{$last}")
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // =======================================
    // LOGS
    // =======================================
    require_once __DIR__ . '/../inc/log_action.php';
    $desc = json_encode([
        'valoracion_id' => $id,
        'cliente'       => "{$cli['nombres']} {$cli['apellidos']}",
        'accion'        => 'Exportación de valoración a Excel'
    ], JSON_UNESCAPED_UNICODE);
    log_action('Exportar valoración', $desc, 'Valoraciones');

    // =======================================
    // Salida limpia
    // =======================================
    while (ob_get_level()) { ob_end_clean(); } // Limpia todos los buffers activos

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="valoracion_'.$id.'.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spread);
    $writer->save('php://output');
    exit;

} catch (Throwable $e) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error al generar Excel:\n".$e->getMessage();
    exit;
}

