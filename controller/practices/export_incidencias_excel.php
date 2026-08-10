<?php
/**
 * export_incidencias_excel.php
 * Descarga un archivo Excel con el seguimiento de reportes de incidencias:
 *   Hoja 1 – Seguimiento (empresa, alumno, incidencia, fechas y solución)
 *   Hoja 2 – Juntas de seguimiento convocadas
 *   Hoja 3 – Bitácora de comunicación (mensajes enviados)
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

function applyHeaderStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
{
    $sheet->getStyle($range)->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '01643D']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);
}

function autoWidthSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $colCount): void
{
    for ($c = 1; $c <= $colCount; $c++) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
    }
}

/** Vuelca un arreglo de filas con encabezados, zebra y autofiltro. */
function writeTable(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $headers, array $rows, string $vacio): void
{
    $colCount = count($headers);
    $lastCol  = Coordinate::stringFromColumnIndex($colCount);

    foreach ($headers as $ci => $h) {
        $sheet->setCellValue(cell($ci + 1, 1), $h);
    }
    applyHeaderStyle($sheet, 'A1:' . $lastCol . '1');
    $sheet->getRowDimension(1)->setRowHeight(24);
    $sheet->setAutoFilter('A1:' . $lastCol . '1');
    $sheet->freezePane('A2');

    if (!$rows) {
        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->setCellValue('A2', $vacio);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('888888');
        autoWidthSheet($sheet, $colCount);
        return;
    }

    $r = 2;
    foreach ($rows as $vals) {
        foreach (array_values($vals) as $ci => $v) {
            $sheet->setCellValueExplicit(
                cell($ci + 1, $r),
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
    $sheet->getStyle('A2:' . $lastCol . ($r - 1))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    autoWidthSheet($sheet, $colCount);
}

/** dd/mm/aaaa hh:mm — cadena vacía si no hay fecha. */
function fmtFechaHora(?string $f): string
{
    return $f ? date('d/m/Y H:i', strtotime($f)) : '';
}

/* ── Catálogos legibles ──────────────────────────────────────── */
$tipos = [
    'inasistencias'  => 'Faltas o retardos',
    'conducta'       => 'Conducta o actitud inadecuada',
    'desempeno'      => 'Bajo desempeño en sus actividades',
    'incumplimiento' => 'Incumplimiento de reglas o políticas',
    'seguridad'      => 'Riesgo de seguridad o daño',
    'otro'           => 'Otro',
];
$gravedades = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];
$acciones   = [
    'orientacion' => 'Orientar al alumno',
    'reunion'     => 'Reunión de las 3 partes',
    'baja'        => 'Solicitud de baja del practicante',
];
$estados = [0 => 'Pendiente', 1 => 'En proceso', 2 => 'Atendida'];

/* ════════════════════════════════════════════════════════════════
   DATOS
   ════════════════════════════════════════════════════════════════ */
$incidencias = PracticasModel::mdlExportIncidencias();

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$juntas = $pdo->query("
    SELECT ij.*, ri.idIncidencia, s.nombre_completo, s.matricula, oe.empresa,
           CONCAT(u.firstname, ' ', u.lastname) AS admin_nombre
    FROM incidencia_juntas ij
    JOIN reportes_incidencias ri ON ri.idIncidencia = ij.idIncidencia
    LEFT JOIN students_practicas s ON s.id = ri.idStudent
    LEFT JOIN organismos_externos oe ON oe.id = ri.idOrganismo
    LEFT JOIN users u ON u.id = ij.created_by
    ORDER BY ij.fecha DESC, ij.hora DESC
")->fetchAll(PDO::FETCH_ASSOC);

$mensajes = $pdo->query("
    SELECT im.*, ri.idIncidencia, s.nombre_completo, oe.empresa,
           CONCAT(u.firstname, ' ', u.lastname) AS admin_nombre
    FROM incidencia_mensajes im
    JOIN reportes_incidencias ri ON ri.idIncidencia = im.idIncidencia
    LEFT JOIN students_practicas s ON s.id = ri.idStudent
    LEFT JOIN organismos_externos oe ON oe.id = ri.idOrganismo
    LEFT JOIN users u ON u.id = im.created_by
    ORDER BY im.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ════════════════════════════════════════════════════════════════
   CONSTRUCCIÓN DEL EXCEL
   ════════════════════════════════════════════════════════════════ */
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('UNIMO Sistema')
    ->setTitle('Seguimiento de incidencias de practicantes')
    ->setSubject('Prácticas profesionales')
    ->setDescription('Generado automáticamente por el sistema de prácticas UNIMO.');

/* ════════════ HOJA 1 — Seguimiento ════════════ */
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Seguimiento');

$headers1 = [
    'Folio', 'Estado', 'Empresa', 'Ciudad', 'Contacto empresa', 'Email empresa', 'Teléfono empresa',
    'Alumno', 'Matrícula', 'Email alumno', 'Teléfono alumno', 'Programa académico',
    'Tipo de incidencia', 'Gravedad', 'Acción solicitada', 'Fecha del incidente',
    'Qué ocurrió', 'Acciones de la empresa',
    'Reporte levantado', 'Fecha de atención', 'Fecha de finalización',
    'Solución', 'Atendido por', 'Juntas', 'Mensajes enviados', 'Detalle de juntas',
];

$rows1 = [];
foreach ($incidencias as $i) {
    $rows1[] = [
        '#' . $i['idIncidencia'],
        $estados[(int) $i['status']] ?? $i['status'],
        $i['empresa'],
        $i['org_ciudad'],
        $i['nombre_contacto'],
        $i['org_email'],
        trim(($i['org_telefono'] ?? '') . ' ' . ($i['org_celular'] ?? '')),
        $i['nombre_completo'],
        $i['student_matricula'],
        $i['student_email'],
        $i['student_telefono'],
        $i['programa_academico'],
        $tipos[$i['tipo']] ?? $i['tipo'],
        $gravedades[$i['gravedad']] ?? $i['gravedad'],
        $acciones[$i['accion_solicitada']] ?? $i['accion_solicitada'],
        $i['fecha_incidente'] ? date('d/m/Y', strtotime($i['fecha_incidente'])) : '',
        $i['descripcion'],
        $i['acciones_tomadas'],
        fmtFechaHora($i['dateCreated']),
        fmtFechaHora($i['fecha_atencion']),
        fmtFechaHora($i['fecha_cierre']),
        $i['solucion'],
        $i['atendido_por_nombre'],
        (int) $i['num_juntas'],
        (int) $i['num_mensajes'],
        $i['juntas_detalle'],
    ];
}
writeTable($sheet1, $headers1, $rows1, 'Aún no hay reportes de incidencias registrados.');
$sheet1->getColumnDimension('Q')->setAutoSize(false)->setWidth(60); // Qué ocurrió
$sheet1->getColumnDimension('R')->setAutoSize(false)->setWidth(45); // Acciones de la empresa
$sheet1->getColumnDimension('V')->setAutoSize(false)->setWidth(60); // Solución

/* ════════════ HOJA 2 — Juntas ════════════ */
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Juntas');

$rows2 = [];
foreach ($juntas as $j) {
    $rows2[] = [
        '#' . $j['idIncidencia'],
        $j['empresa'],
        $j['nombre_completo'],
        $j['matricula'],
        $j['modalidad'],
        date('d/m/Y', strtotime($j['fecha'])),
        substr((string) $j['hora'], 0, 5),
        $j['modalidad'] === 'Virtual' ? $j['url_sesion'] : $j['lugar'],
        $j['invita_alumno'] ? 'Sí' : 'No',
        $j['invita_empresa'] ? 'Sí' : 'No',
        $j['agenda'],
        $j['admin_nombre'],
        fmtFechaHora($j['created_at']),
    ];
}
writeTable(
    $sheet2,
    ['Folio', 'Empresa', 'Alumno', 'Matrícula', 'Modalidad', 'Fecha', 'Hora',
     'Enlace / Lugar', 'Convocó al alumno', 'Convocó a la empresa', 'Puntos a tratar', 'Agendó', 'Registrada'],
    $rows2,
    'Aún no se han agendado juntas de seguimiento.'
);

/* ════════════ HOJA 3 — Comunicación ════════════ */
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Comunicacion');

$destinatarios = ['alumno' => 'Alumno', 'empresa' => 'Empresa', 'ambos' => 'Alumno y empresa'];
$rows3 = [];
foreach ($mensajes as $m) {
    $rows3[] = [
        '#' . $m['idIncidencia'],
        $m['empresa'],
        $m['nombre_completo'],
        $destinatarios[$m['destinatario']] ?? $m['destinatario'],
        $m['enviado_a'],
        $m['asunto'],
        $m['mensaje'],
        $m['admin_nombre'],
        fmtFechaHora($m['created_at']),
    ];
}
writeTable(
    $sheet3,
    ['Folio', 'Empresa', 'Alumno', 'Destinatario', 'Correos', 'Asunto', 'Mensaje', 'Enviado por', 'Fecha de envío'],
    $rows3,
    'Aún no se han enviado mensajes de seguimiento.'
);
$sheet3->getColumnDimension('G')->setAutoSize(false)->setWidth(60); // Mensaje

$spreadsheet->setActiveSheetIndex(0);

/* ════════════════════════════════════════════════════════════════
   ENVIAR AL NAVEGADOR
   ════════════════════════════════════════════════════════════════ */
$filename = 'seguimiento_incidencias_' . date('Ymd_His') . '.xlsx';

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
