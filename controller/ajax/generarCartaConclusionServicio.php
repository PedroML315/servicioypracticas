<?php
declare(strict_types=1);

require_once __DIR__ . '/../../model/forms.models.php';
require_once __DIR__ . '/../forms.controller.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../emails.php';
// Arma el HTML sobre la hoja membretada institucional. Vive aparte para que la
// vista previa del panel de Configuraciones use exactamente el mismo código que
// esta generación real.
require_once __DIR__ . '/../servicio/cartaServicioGenerator.php';

session_start();

// ══════════════════════════════════════════════════════════════════
//  SEGURIDAD – Solo el alumno dueño puede generar su propia carta
// ══════════════════════════════════════════════════════════════════
if (empty($_SESSION['user'])) {
    http_response_code(403);
    exit('No autorizado');
}

$role      = $_SESSION['user']['role'] ?? '';
$idStudent = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);

// Solo alumnos (no admins, no organismos)
if ($role !== 'student') {
    http_response_code(403);
    exit('Esta carta solo puede ser generada por el alumno titular.');
}

if ($idStudent <= 0) {
    http_response_code(400);
    exit('Sesión inválida');
}

// ── CSRF / one-time token de sesión ─────────────────────────────
// Se genera un token en el dashboard y se valida aquí para evitar
// que alguien acceda directamente a la URL sin pasar por la UI.
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN']   ?? '';
$csrfGet    = $_GET['csrf_token']              ?? '';
$csrfToken  = $csrfHeader ?: $csrfGet;
$sessionCsrf = $_SESSION['csrf_token'] ?? '';

if (!$sessionCsrf || !hash_equals($sessionCsrf, $csrfToken)) {
    http_response_code(403);
    exit('Token de seguridad inválido. Recarga la página e intenta de nuevo.');
}

// ── Parámetros de periodo ────────────────────────────────────────
$fechaInicioRaw = trim($_GET['fecha_inicio'] ?? '');
$fechaFinRaw    = trim($_GET['fecha_fin']    ?? '');

if (!$fechaInicioRaw || !$fechaFinRaw) {
    http_response_code(400);
    exit('Debes indicar fecha_inicio y fecha_fin en el formato YYYY-MM-DD.');
}

// Validar formato fecha
$disFechaInicio = \DateTime::createFromFormat('Y-m-d', $fechaInicioRaw);
$disFechaFin    = \DateTime::createFromFormat('Y-m-d', $fechaFinRaw);

if (!$disFechaInicio || !$disFechaFin) {
    http_response_code(400);
    exit('Formato de fecha inválido (debe ser YYYY-MM-DD).');
}

if ($disFechaFin <= $disFechaInicio) {
    http_response_code(400);
    exit('La fecha de fin debe ser posterior a la fecha de inicio.');
}

// ══════════════════════════════════════════════════════════════════
//  VERIFICAR QUE LOS 3 REPORTES ESTÉN APROBADOS
// ══════════════════════════════════════════════════════════════════
$pdo = Conexion::conectar();

$stmtRep = $pdo->prepare("
    SELECT tipo, status
    FROM servicio_social_ijumich
    WHERE student_id = :sid
      AND tipo IN ('reporte_parcial_1','reporte_parcial_2','reporte_parcial_3')
      AND status = 'aprobado'
");
$stmtRep->execute([':sid' => $idStudent]);
$reportesAprobados = $stmtRep->fetchAll(PDO::FETCH_COLUMN, 0);

$required = ['reporte_parcial_1', 'reporte_parcial_2', 'reporte_parcial_3'];
foreach ($required as $r) {
    if (!in_array($r, $reportesAprobados, true)) {
        http_response_code(403);
        exit('Los tres reportes parciales deben estar aprobados para generar esta carta.');
    }
}

// ── Verificar que NO haya ya una carta de conclusión generada ────
// Si ya existe, simplemente la regresa (evita duplicados en BD)
$stmtExist = $pdo->prepare("
    SELECT id FROM cartas_conclusion_servicio
    WHERE student_id = :sid
    ORDER BY id DESC LIMIT 1
");
$stmtExist->execute([':sid' => $idStudent]);
$existente = $stmtExist->fetch(PDO::FETCH_ASSOC);

// ══════════════════════════════════════════════════════════════════
//  DATOS DEL ALUMNO
// ══════════════════════════════════════════════════════════════════
$stmtSt = $pdo->prepare("
    SELECT s.firstname, s.lastname, s.lastnameMom, s.matricula, s.gender, s.idDegree
    FROM student s
    WHERE s.idStudent = :sid
");
$stmtSt->execute([':sid' => $idStudent]);
$student = $stmtSt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    http_response_code(404);
    exit('Alumno no encontrado.');
}

// ══════════════════════════════════════════════════════════════════
//  CARGAR CONFIG JSON
// ══════════════════════════════════════════════════════════════════
$CONFIG_PATH = __DIR__ . '/../../config/carta_conclusion_config.json';
$c = [];
if (file_exists($CONFIG_PATH)) {
    $decoded = json_decode(file_get_contents($CONFIG_PATH), true);
    if (is_array($decoded)) $c = $decoded;
}

function val(array $arr, string $path, $default = '') {
    $tmp = $arr;
    foreach (explode('.', $path) as $k) {
        if (!is_array($tmp) || !array_key_exists($k, $tmp)) return $default;
        $tmp = $tmp[$k];
    }
    return $tmp ?? $default;
}

// ══════════════════════════════════════════════════════════════════
//  CONSTRUIR DATOS
// ══════════════════════════════════════════════════════════════════
$mesesArr = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaHoy = date('j') . ' de ' . $mesesArr[(int)date('n') - 1] . ' de ' . date('Y');

$studentName = ppCapitalizar(trim(
    ($student['firstname'] ?? '') . ' ' . ($student['lastname'] ?? '') . ' ' . ($student['lastnameMom'] ?? '')
));
$matricula   = $student['matricula'] ?? '';
$degree      = FormsModel::mdlSearchDegrees($student['idDegree'] ?? null);
$degreeName  = 'Licenciatura en ' . ppCapitalizar($degree['nameDegree'] ?? 'Desconocida');

// Formato legible de fechas
$fmtInicio = $disFechaInicio->format('j') . ' de ' . $mesesArr[(int)$disFechaInicio->format('n') - 1] . ' ' . $disFechaInicio->format('Y');
$fmtFin    = $disFechaFin->format('j')    . ' de ' . $mesesArr[(int)$disFechaFin->format('n') - 1]    . ' ' . $disFechaFin->format('Y');

$horas  = (int) val($c, 'config.horas', 480);
$meses  = (int) val($c, 'config.meses', 6);

// ── Folio ────────────────────────────────────────────────────────
// Reutiliza o genera folio
if ($existente) {
    $stmtFol = $pdo->prepare("SELECT code FROM cartas_conclusion_servicio WHERE id = :id");
    $stmtFol->execute([':id' => $existente['id']]);
    $folio = $stmtFol->fetchColumn() ?: 'DSS-CCSS-0-' . date('Y');
} else {
    // Siguiente correlativo del año
    $year = date('Y');
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM cartas_conclusion_servicio WHERE YEAR(created_at) = :y");
    $stmtCount->execute([':y' => $year]);
    $num   = (int)$stmtCount->fetchColumn() + 1;
    $prefix = val($c, 'header.folio_prefix', 'DSS-CCSS');
    $folio = $prefix . '-' . str_pad((string)$num, 3, '0', STR_PAD_LEFT) . '-' . $year;

    // Registrar en BD
    $stmtIns = $pdo->prepare("
        INSERT INTO cartas_conclusion_servicio (code, student_id, fecha_inicio, fecha_fin, horas, meses)
        VALUES (:code, :sid, :fi, :ff, :h, :m)
    ");
    $stmtIns->execute([
        ':code' => $folio,
        ':sid'  => $idStudent,
        ':fi'   => $fechaInicioRaw,
        ':ff'   => $fechaFinRaw,
        ':h'    => $horas,
        ':m'    => $meses,
    ]);

    // ── Notificación por correo solo en primera generación ─────────
    $studentEmail = $_SESSION['user']['email'] ?? '';
    if ($studentEmail !== '') {
        sendCartaConclusionAlumno(
            $studentEmail,
            $studentName,
            $folio,
            $disFechaInicio->format('d/m/Y'),
            $disFechaFin->format('d/m/Y'),
            $horas,
            $meses
        );
    }
    // Notificación al admin SS
    $stEmailNotif = $_SESSION['user']['email'] ?? '';
    sendSsCartaConclusionAdmin(
        $studentName,
        $stEmailNotif,
        $folio,
        $disFechaInicio->format('d/m/Y'),
        $disFechaFin->format('d/m/Y'),
        $horas
    );
}

// ── Placeholders ─────────────────────────────────────────────────
$genero = (($student['gender'] ?? 1) == 1 ? 'el' : 'la');

// ══════════════════════════════════════════════════════════════════
//  GENERAR PDF
// ══════════════════════════════════════════════════════════════════
$html = construirCartaConclusionServicioHtml([
    'studentName' => $studentName,
    'matricula'   => $matricula,
    'degreeName'  => $degreeName,
    'genero'      => $genero,
    'fecha'       => $fechaHoy,
    'folio'       => $folio,
    'fechaInicio' => strtolower($fmtInicio),
    'fechaFin'    => strtolower($fmtFin),
    'horas'       => (string) $horas,
    'meses'       => (string) $meses,
], $c);

if (ob_get_length()) ob_end_clean();

$safeNombre = preg_replace('/\s+/', '_', $studentName);
$filename   = 'CartaConclusionServicio_' . $safeNombre . '_' . date('Ymd') . '.pdf';

ppRenderPdf($html)->stream($filename, ['Attachment' => true]);
