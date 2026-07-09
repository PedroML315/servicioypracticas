<?php
/**
 * export_companies_excel.php
 * Descarga un archivo Excel con:
 *   Hoja 1 – Listado completo de organismos receptores
 *   Hoja N – Una hoja por organismo con sus alumnos, horas y estado
 */

/* ── Bootstrap ───────────────────────────────────────────────── */
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/conection.php';
require_once __DIR__ . '/../../model/PracticasModel.php';
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

/** Celda por índice numérico (PhpSpreadsheet 2+/5+) */
function cell(int $col, int $row): string
{
    return Coordinate::stringFromColumnIndex($col) . $row;
}

/**
 * Sanitiza el nombre de una hoja de Excel:
 * - elimina caracteres no permitidos
 * - trunca a 31 caracteres
 */
function sanitizeSheetName(string $name): string
{
    $name = preg_replace('/[:\\\\\/?*\[\]]/', '', $name);
    $name = trim($name);
    return mb_substr($name ?: 'Organismo', 0, 31);
}

/**
 * Aplica estilo de encabezado (fondo verde oscuro, texto blanco, negrita).
 */
function applyHeaderStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
{
    $sheet->getStyle($range)->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '01643D']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);
}

/**
 * Ajusta el ancho de columnas de forma automática.
 */
function autoWidthSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $colCount): void
{
    for ($c = 1; $c <= $colCount; $c++) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
    }
}

/* ════════════════════════════════════════════════════════════════
   CONSULTAS OPTIMIZADAS (sin N+1)
   ════════════════════════════════════════════════════════════════ */

/* 1. Todos los organismos con datos completos + conteo de alumnos */
$organismos = PracticasModel::mdlExportOrganismos();

/* 2. Todos los alumnos de todos los organismos (una sola query) */
$allStudents = PracticasModel::mdlExportStudentsAllOrganismos();

/* Indexar alumnos por organismo_id para acceso O(1) */
$studentsByOrg = [];
foreach ($allStudents as $s) {
    $studentsByOrg[(int)$s['organismo_id']][] = $s;
}

/* ════════════════════════════════════════════════════════════════
   CONSTRUCCIÓN DEL EXCEL
   ════════════════════════════════════════════════════════════════ */
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('UNIMO Sistema')
    ->setTitle('Organismos Receptores y Practicantes')
    ->setSubject('Exportación de prácticas profesionales')
    ->setDescription('Generado automáticamente por el sistema de prácticas UNIMO.');

/* ════════════
   HOJA 1 — Listado de Organismos
   ════════════ */
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Organismos');

$headers1 = [
    'ID', 'Empresa', 'Tipo Persona', 'Giro', 'Fecha Constitución',
    'Sitio Web', 'Calle', 'C.P.', 'Colonia', 'Ciudad',
    'Teléfonos', 'Email', 'Nombre Contacto', 'Celular',
    'Rep. Legal', 'Cargo Legal', 'Email Legal', 'Tel. Oficina',
    'Aceptado', 'Activo', 'Fecha Registro',
    'Alumnos Totales', 'Pendientes', 'Aceptados', 'Rechazados',
];

$col1 = count($headers1);
$lastCol1 = Coordinate::stringFromColumnIndex($col1);

/* Encabezados */
foreach ($headers1 as $ci => $h) {
    $sheet1->setCellValue(cell($ci + 1, 1), $h);
}
applyHeaderStyle($sheet1, 'A1:' . $lastCol1 . '1');
$sheet1->getRowDimension(1)->setRowHeight(22);

/* Datos */
$row = 2;
foreach ($organismos as $org) {
    $vals = [
        $org['id'],
        $org['empresa'],
        $org['tipo_persona'],
        $org['giro'],
        $org['fecha_constitucion'],
        $org['web'],
        $org['calle'],
        $org['cp'],
        $org['colonia'],
        $org['ciudad'],
        $org['telefonos'],
        $org['email'],
        $org['nombre_contacto'],
        $org['celular'],
        $org['rep_legal'],
        $org['cargo_legal'],
        $org['email_legal'],
        $org['tel_oficina'],
        $org['isAcepted'] ? 'Sí' : 'No',
        $org['isActive']  ? 'Sí' : 'No',
        $org['created_at'],
        (int)($org['num_students_total'] ?? 0),
        (int)($org['num_pendientes']     ?? 0),
        (int)($org['num_aceptados']      ?? 0),
        (int)($org['num_rechazados']     ?? 0),
    ];
    foreach ($vals as $ci => $v) {
        $sheet1->setCellValue(cell($ci + 1, $row), $v);
    }
    /* Zebra: fila par con fondo muy suave */
    if ($row % 2 === 0) {
        $sheet1->getStyle('A' . $row . ':' . $lastCol1 . $row)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F9F4');
    }
    $row++;
}

$sheet1->setAutoFilter('A1:' . $lastCol1 . '1');
$sheet1->freezePane('A2');
autoWidthSheet($sheet1, $col1);

/* ════════════
   HOJAS POR ORGANISMO
   ════════════ */
$sheetNames = ['Organismos'];

$statusMap = ['0' => 'Pendiente', '1' => 'Aceptado', '2' => 'Rechazado'];

$headers2 = [
    'Matrícula', 'Nombre Completo', 'Email', 'Teléfono',
    'Programa Académico', 'Perfil solicitado (habilidades/carrera)', 'Periodo',
    'CURP', 'Género', 'Fecha Nacimiento',
    'Tipo Práctica', 'Modalidad',
    'Actividades en Organismo',
    'Estado en Práctica', 'Fecha Inicio',
    'Horas Acumuladas (aprob.)',
    'Prácticas Finalizadas', 'Fecha Finalización',
    'Alumno Activo',
];
$col2 = count($headers2);

foreach ($organismos as $org) {
    $orgId   = (int)$org['id'];
    $orgName = sanitizeSheetName($org['empresa']);

    /* Asegurar nombre único */
    $base   = $orgName;
    $suffix = 2;
    while (in_array($orgName, $sheetNames, true)) {
        $orgName = mb_substr($base, 0, 28) . '_' . $suffix++;
    }
    $sheetNames[] = $orgName;

    $sheet        = $spreadsheet->createSheet();
    $lastColLetter = Coordinate::stringFromColumnIndex($col2);
    $sheet->setTitle($orgName);

    /* ── Título de organismo en fila 1 ── */
    $sheet->mergeCells('A1:' . $lastColLetter . '1');
    $sheet->setCellValue('A1', $org['empresa'] . ' — ' . $org['ciudad']);
    $sheet->getStyle('A1')->applyFromArray([
        'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00204A']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(24);

    /* ── Encabezados en fila 2 ── */
    foreach ($headers2 as $ci => $h) {
        $sheet->setCellValue(cell($ci + 1, 2), $h);
    }
    applyHeaderStyle($sheet, 'A2:' . $lastColLetter . '2');
    $sheet->getRowDimension(2)->setRowHeight(22);
    $sheet->freezePane('A3');
    $sheet->setAutoFilter('A2:' . $lastColLetter . '2');

    /* ── Datos de alumnos ── */
    $students = $studentsByOrg[$orgId] ?? [];
    $sRow = 3;

    if (empty($students)) {
        $sheet->mergeCells('A3:' . $lastColLetter . '3');
        $sheet->setCellValue('A3', 'Sin alumnos registrados en este organismo.');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('888888');
    } else {
        foreach ($students as $s) {
            $sVals = [
                $s['matricula'],
                $s['nombre_completo'],
                $s['email'],
                $s['telefono'],
                $s['programa_academico'],
                // Perfil de la vacante: habilidades (modelo nuevo) o licenciatura (legado)
                !empty($s['habilidades']) ? str_replace('|', ', ', $s['habilidades']) : $s['licenciatura'],
                (int)$s['periodo'],
                $s['curp'],
                $s['genero'],
                $s['fecha_nacimiento'],
                $s['tipo_practica'],
                $s['modalidad'],
                $s['actividades'],
                $statusMap[(string)$s['sip_status']] ?? (string)$s['sip_status'],
                $s['start_date'],
                round((float)($s['horas_acumuladas'] ?? 0), 2),
                $s['practicas_finalizadas'] ? 'Sí' : 'No',
                $s['fecha_finalizacion'],
                $s['isActive'] ? 'Sí' : 'No',
            ];
            foreach ($sVals as $ci => $v) {
                $sheet->setCellValue(cell($ci + 1, $sRow), $v);
            }
            if ($sRow % 2 === 1) {
                $sheet->getStyle('A' . $sRow . ':' . $lastColLetter . $sRow)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F9F4');
            }
            $sRow++;
        }
    }

    autoWidthSheet($sheet, $col2);
}

/* ── Volver a seleccionar la primera hoja ── */
$spreadsheet->setActiveSheetIndex(0);

/* ════════════════════════════════════════════════════════════════
   ENVIAR AL NAVEGADOR
   ════════════════════════════════════════════════════════════════ */
$filename = 'organismos_receptores_' . date('Ymd_His') . '.xlsx';

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
