<?php
declare(strict_types=1);

require_once __DIR__ . "/../../model/forms.models.php";
require_once __DIR__ . "/../forms.controller.php";
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../servicio/cartaServicioGenerator.php";

session_start();

// ── Autenticación ──────────────────────────────────────────────
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit('No autorizado');
}

$role      = $_SESSION['user']['role'] ?? '';
$idStudent = (int) ($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);

// Solo el alumno dueño (aprobado) o un admin puede generar
$solicitudId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$solicitudId) {
    http_response_code(400);
    exit('ID de solicitud requerido');
}

// ── Obtener datos del registro ─────────────────────────────────
$pdo  = Conexion::conectar();
$stmt = $pdo->prepare("SELECT s.*, st.firstname, st.lastname, st.lastnameMom, st.matricula, st.gender, st.idDegree
                        FROM servicio_social_ijumich s
                        JOIN student st ON st.idStudent = s.student_id
                        WHERE s.id = :id AND s.tipo = 'carta_presentacion'");
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

// ── Cargar config JSON ─────────────────────────────────────────
$c = ssCargarConfig(__DIR__ . '/../../config/carta_presentacion.json');

// ── Datos del alumno ───────────────────────────────────────────
$fecha = ppFechaLarga();

$studentName = ppCapitalizar(trim(
    ($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '') . ' ' . ($row['lastnameMom'] ?? '')
));
$matricula  = $row['matricula'] ?? '';
$genero     = (($row['gender'] ?? 1) == 1 ? 'el' : 'la');
$degree     = FormsModel::mdlSearchDegrees($row['idDegree'] ?? null);
$degreeName = 'Licenciatura en ' . ppCapitalizar($degree['nameDegree'] ?? 'Desconocida');
$folio      = ServicioModel::generateFolio((int)$row['student_id']);

// ── Datos del organismo (del registro) ────────────────────────
$nameUR      = ppCapitalizar($row['nombre_organismo'] ?? '');
$responsable = ppCapitalizar($row['responsable'] ?? '');
$cargo       = ppCapitalizar($row['puesto_responsable'] ?? '');
$domicilio   = ppCapitalizar(
    trim(implode(', ', array_filter([
        $row['calle_numero']  ?? '',
        $row['colonia']       ?? '',
        ($row['codigo_postal'] ? 'C.P. ' . $row['codigo_postal'] : ''),
        $row['municipio']     ?? '',
        $row['estado']        ?? '',
        $row['pais']          ?? '',
    ])))
);

// ── PDF sobre la plantilla institucional ──────────────────────
$html = construirCartaPresentacionServicioHtml([
    "studentName" => $studentName,
    "matricula"   => $matricula,
    "degreeName"  => $degreeName,
    "genero"      => $genero,
    "fecha"       => $fecha,
    "folio"       => $folio,
    "nameUR"      => $nameUR,
    "responsable" => $responsable,
    "cargo"       => $cargo,
    "domicilio"   => $domicilio,
], $c);

$filename = "CartaPresentacion_" . preg_replace("/\s+/", "_", $studentName) . "_" . date("Ymd") . ".pdf";
ppRenderPdf($html)->stream($filename, ["Attachment" => true]);