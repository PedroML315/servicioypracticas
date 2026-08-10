<?php
declare(strict_types=1);

require_once __DIR__ . "/../../model/forms.models.php";
require_once __DIR__ . "/../forms.controller.php";
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../servicio/cartaServicioGenerator.php";

session_start();

// ── Autenticación ──────────────────────────────────────────
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit('No autorizado');
}

$role      = $_SESSION['user']['role'] ?? '';
$idStudent = (int) ($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);

$solicitudId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$solicitudId) {
    http_response_code(400);
    exit('ID de solicitud requerido');
}

// ── Obtener datos del registro ─────────────────────────────
$pdo  = Conexion::conectar();
$stmt = $pdo->prepare(
    "SELECT s.*, st.firstname, st.lastname, st.lastnameMom, st.matricula,
            st.gender, st.idDegree, st.grado, st.type_lic
     FROM servicio_social_ijumich s
     JOIN student st ON st.idStudent = s.student_id
     WHERE s.id = :id AND s.tipo = 'carta_aceptacion_servicio'"
);
$stmt->execute([':id' => $solicitudId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Solicitud no encontrada');
}

// Solo el alumno dueño o admin puede descargar
if ($role !== 'admin' && (int)$row['student_id'] !== $idStudent) {
    http_response_code(403);
    exit('No autorizado');
}

// Solo si está aprobada (alumnos); admins pueden pre-visualizar siempre
if ($role !== 'admin' && $row['status'] !== 'aprobado') {
    http_response_code(403);
    exit('La carta aún no ha sido aprobada');
}

// ── Cargar config JSON ──────────────────────────────────────
$c = ssCargarConfig(__DIR__ . '/../../config/carta_aceptacion_servicio.json');

// ── Datos del alumno ───────────────────────────────────────
$fecha = ppFechaLarga();

$studentName = mb_strtoupper(trim(
    ($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '') . ' ' . ($row['lastnameMom'] ?? '')
));
$matricula  = $row['matricula'] ?? '';
$degree     = FormsModel::mdlSearchDegrees($row['idDegree'] ?? null);
$degreeName = 'LICENCIATURA EN ' . mb_strtoupper($degree['nameDegree'] ?? 'DESCONOCIDA');

// Generar folio CASS
$folio = ServicioModel::generateFolioAceptacion((int)$row['student_id']);

// Calcular fechas inicio/término
$horas  = (int)($degree['minPoints'] ?? 480) > 0 ? (int)($degree['minPoints'] ?? 480) : 480;
$mesesS = ($horas >= 480) ? 6 : 12;

$fechaInicioObj  = new DateTime();
$fechaTerminoObj = clone $fechaInicioObj;
$fechaTerminoObj->modify("+{$mesesS} months");

$nombresMeses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaInicioStr  = $fechaInicioObj->format('j') . ' de ' . $nombresMeses[(int)$fechaInicioObj->format('n') - 1] . ' de ' . $fechaInicioObj->format('Y');
$fechaTerminoStr = $fechaTerminoObj->format('j') . ' de ' . $nombresMeses[(int)$fechaTerminoObj->format('n') - 1] . ' de ' . $fechaTerminoObj->format('Y');

// Grado en texto
if (function_exists('numeroATexto')) {
    $gradoNum = (int)($row['grado'] ?? 1);
    $gradoTxt = numeroATexto($gradoNum);
} else {
    $numerosTexto = ['','PRIMERO','SEGUNDO','TERCERO','CUARTO','QUINTO','SEXTO','SÉPTIMO','OCTAVO','NOVENO','DÉCIMO','UNDÉCIMO','DUODÉCIMO'];
    $gradoNum = (int)($row['grado'] ?? 1);
    $gradoTxt = $numerosTexto[$gradoNum] ?? $gradoNum;
}
$tipoCuatri = ($row['type_lic'] ?? 'cuatrimestral') === 'cuatrimestral' ? 'CUATRIMESTRE' : 'SEMESTRE';
$gradoTexto = mb_strtoupper($gradoTxt) . ' ' . $tipoCuatri;

// ── PDF sobre la plantilla institucional ────────────────────
$html = construirCartaAceptacionServicioHtml([
    "studentName"  => $studentName,
    "matricula"    => $matricula,
    "degreeName"   => $degreeName,
    "fecha"        => $fecha,
    "folio"        => $folio,
    "gradoTexto"   => $gradoTexto,
    "horas"        => $horas,
    "meses"        => $mesesS,
    "fechaInicio"  => mb_strtoupper($fechaInicioStr),
    "fechaTermino" => mb_strtoupper($fechaTerminoStr),
], $c);

$filename = "CartaAceptacionSS_" . preg_replace("/\s+/", "_", $studentName) . "_" . date("Ymd") . ".pdf";
ppRenderPdf($html)->stream($filename, ["Attachment" => true]);