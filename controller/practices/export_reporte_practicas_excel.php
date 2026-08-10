<?php
/**
 * export_reporte_practicas_excel.php
 * Descarga el reporte de Prácticas Profesionales del panel del administrador
 * respetando los mismos filtros de la pantalla (rango de fechas, empresa,
 * estado y búsqueda).
 *
 *   Hoja 1 – Reporte  (una fila por alumno colocado, con datos de contacto)
 *   Hoja 2 – Resumen  (filtros aplicados y totales)
 */

/* ── Bootstrap ───────────────────────────────────────────────── */
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/conection.php';
require_once __DIR__ . '/../../model/PracticasModel.php';
require_once __DIR__ . '/../forms.controller.php';
require_once __DIR__ . '/../../config/Security.php';

Security::init();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ── Autorización ────────────────────────────────────────────── */
$role = $_SESSION['user']['role'] ?? '';
if (!isset($_SESSION['logged']) || !in_array($role, ['admin', 'admin_practicas'], true)) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

/* ── Imports ─────────────────────────────────────────────────── */
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/* ── Helpers ─────────────────────────────────────────────────── */

function cellRP(int $col, int $row): string
{
    return Coordinate::stringFromColumnIndex($col) . $row;
}

function applyHeaderStyleRP(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
{
    $sheet->getStyle($range)->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '01643D']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);
}

function writeTableRP(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $headers, array $rows, string $vacio, int $startRow = 1): void
{
    $colCount = count($headers);
    $lastCol  = Coordinate::stringFromColumnIndex($colCount);

    foreach ($headers as $ci => $h) {
        $sheet->setCellValue(cellRP($ci + 1, $startRow), $h);
    }
    applyHeaderStyleRP($sheet, 'A' . $startRow . ':' . $lastCol . $startRow);
    $sheet->getRowDimension($startRow)->setRowHeight(24);
    $sheet->setAutoFilter('A' . $startRow . ':' . $lastCol . $startRow);
    $sheet->freezePane('A' . ($startRow + 1));

    if (!$rows) {
        $sheet->mergeCells('A' . ($startRow + 1) . ':' . $lastCol . ($startRow + 1));
        $sheet->setCellValue('A' . ($startRow + 1), $vacio);
        $sheet->getStyle('A' . ($startRow + 1))->getFont()->setItalic(true)->getColor()->setRGB('888888');
    } else {
        $r = $startRow + 1;
        foreach ($rows as $vals) {
            foreach (array_values($vals) as $ci => $v) {
                $sheet->setCellValueExplicit(
                    cellRP($ci + 1, $r),
                    (string) $v,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
            if ($r % 2 === 0) {
                $sheet->getStyle('A' . $r . ':' . $lastCol . $r)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F9F4');
            }
            $r++;
        }
        $sheet->getStyle('A' . ($startRow + 1) . ':' . $lastCol . ($r - 1))
            ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }

    for ($c = 1; $c <= $colCount; $c++) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
    }
}

function fmtFechaRP(?string $f, bool $conHora = false): string
{
    if (!$f) {
        return '';
    }
    return date($conHora ? 'd/m/Y H:i' : 'd/m/Y', strtotime($f));
}

/* ── Filtros (los mismos de la pantalla) ─────────────────────── */
$filtros = PracticasController::ctrNormalizarFiltrosReporte([
    'desde'       => $_GET['desde'] ?? '',
    'hasta'       => $_GET['hasta'] ?? '',
    'campo_fecha' => $_GET['campo_fecha'] ?? 'inicio',
    'empresa'     => $_GET['empresa'] ?? '',
    'estado'      => $_GET['estado'] ?? '',
    'origen'      => $_GET['origen'] ?? '',
    'q'           => $_GET['q'] ?? '',
]);

$reporte = PracticasController::ctrGetReportePracticas($filtros);
$items   = $reporte['items'];
$resumen = PracticasController::ctrResumenReportePracticas($items);

$estadosLbl = [
    'en_proceso' => 'En proceso',
    'concluida'  => 'Concluida',
    'baja'       => 'Baja por strikes',
];

/* Nombre legible de la empresa/área filtrada */
$empresaLbl = 'Todas las empresas y áreas';
if ($filtros['empresa'] !== '') {
    $cat = PracticasController::ctrGetEmpresasReporte();
    foreach (array_merge($cat['externas'], $cat['internas']) as $e) {
        if ($e['empresa_key'] === $filtros['empresa']) {
            $empresaLbl = $e['label'] . ($e['origen'] === 'interna' ? ' (área interna)' : '');
            break;
        }
    }
}

/* ════════════════════════════════════════════════════════════════
   CONSTRUCCIÓN DEL EXCEL
   ════════════════════════════════════════════════════════════════ */
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('UNIMO Sistema')
    ->setTitle('Reporte de Prácticas Profesionales')
    ->setSubject('Prácticas profesionales')
    ->setDescription('Generado automáticamente por el sistema de prácticas UNIMO.');

/* ════════════ HOJA 1 — Reporte ════════════ */
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Reporte');

$headers1 = [
    'Estado', 'Alumno', 'Matrícula', 'Grupo', 'Programa académico', 'Periodo',
    'Correo del alumno', 'Teléfono del alumno',
    'Empresa / Área', 'Tipo', 'Ciudad', 'Contacto', 'Correo de la empresa', 'Teléfono de la empresa',
    'Vacante / Área asignada', 'Modalidad',
    'Fecha de inicio', 'Fecha de conclusión', 'Horas acreditadas', 'Días registrados',
    'Reportes parciales aprobados', 'Reporte final aprobado', 'Fecha de asignación',
];

$rows1 = [];
foreach ($items as $i) {
    $rows1[] = [
        $estadosLbl[$i['estado']] ?? $i['estado'],
        $i['nombre_completo'],
        $i['matricula'],
        $i['grupo'],
        $i['programa_academico'],
        $i['periodo'],
        $i['student_email'],
        $i['student_telefono'],
        $i['empresa'],
        $i['origen'] === 'interna' ? 'Área interna' : 'Organismo externo',
        $i['empresa_ciudad'],
        $i['empresa_contacto'],
        $i['empresa_email'],
        $i['empresa_telefono'],
        $i['vacante'],
        $i['modalidad'],
        fmtFechaRP($i['fecha_inicio']),
        fmtFechaRP($i['fecha_fin'], true),
        number_format((float) $i['horas'], 2, '.', ''),
        $i['dias'],
        $i['reportes_parciales'],
        $i['reportes_finales'] > 0 ? 'Sí' : 'No',
        fmtFechaRP($i['fecha_asignacion'], true),
    ];
}
writeTableRP($sheet1, $headers1, $rows1, 'No hay alumnos que cumplan con los filtros seleccionados.');

/* ════════════ HOJA 2 — Resumen ════════════ */
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Resumen');

$rangoTxt = ($filtros['desde'] || $filtros['hasta'])
    ? trim(($filtros['desde'] ? 'del ' . fmtFechaRP($filtros['desde']) : '')
        . ($filtros['hasta'] ? ' al ' . fmtFechaRP($filtros['hasta']) : ''))
    : 'Sin límite de fechas (histórico completo)';

$rows2 = [
    ['Reporte', 'Prácticas Profesionales'],
    ['Generado el', date('d/m/Y H:i')],
    ['Generado por', trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '')) ?: 'Administrador'],
    ['', ''],
    ['FILTROS APLICADOS', ''],
    ['Rango de fechas', $rangoTxt],
    ['El rango se aplica a', $filtros['campo_fecha'] === 'fin' ? 'Fecha de conclusión de la práctica' : 'Fecha de inicio de la práctica'],
    ['Empresa / Área', $empresaLbl],
    ['Estado de la práctica', $filtros['estado'] === '' ? 'Todos los estados' : ($estadosLbl[$filtros['estado']] ?? $filtros['estado'])],
    ['Búsqueda', $filtros['q'] !== '' ? $filtros['q'] : '—'],
    ['', ''],
    ['TOTALES DEL REPORTE', ''],
    ['Alumnos en el reporte', (string) $resumen['total']],
    ['Prácticas en proceso', (string) $resumen['en_proceso']],
    ['Prácticas concluidas', (string) $resumen['concluida']],
    ['Bajas por strikes', (string) $resumen['baja']],
    ['Alumnos distintos', (string) $resumen['alumnos']],
    ['Empresas / áreas distintas', (string) $resumen['empresas']],
    ['Horas acreditadas (suma)', number_format($resumen['horas'], 2, '.', '')],
];
writeTableRP($sheet2, ['Concepto', 'Valor'], $rows2, '');
$sheet2->getColumnDimension('B')->setAutoSize(false)->setWidth(55);

$spreadsheet->setActiveSheetIndex(0);

/* ════════════════════════════════════════════════════════════════
   ENVIAR AL NAVEGADOR
   ════════════════════════════════════════════════════════════════ */
$filename = 'reporte_practicas_profesionales_' . date('Ymd_His') . '.xlsx';

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
