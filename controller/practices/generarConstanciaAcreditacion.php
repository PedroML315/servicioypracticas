<?php
// generate/constancia.php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../../model/forms.models.php';
require_once __DIR__ . '/../../controller/forms.controller.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/constanciaHtml.php';

session_start();

ini_set('display_errors', '0');
error_reporting(E_ALL);

// --- Validar sesión
if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
  http_response_code(401);
  exit('Sesión inválida.');
}

$user = $_SESSION['user'];

// --- Datos del alumno
$studentName = mb_strtoupper(trim((string) ($user['nombre_completo'] ?? '')), 'UTF-8');
$matricula = (string) ($user['matricula'] ?? '');
$generoArt = (($user['genero'] ?? '') === "Masculino") ? 'el alumno' : 'la alumna';
$degreeName = 'LICENCIATURA EN ' . mb_strtoupper((string) ($user['programa_academico'] ?? 'DESCONOCIDA'), 'UTF-8');
$idStudent = (int) ($user['id'] ?? 0);

if ($idStudent <= 0 || $studentName === '' || $matricula === '') {
  http_response_code(422);
  exit('Faltan datos del alumno para generar la constancia.');
}

// --- Práctica y organismo
$tipoPractica = (string)($user['tipo_practica'] ?? 'empresa');

if ($tipoPractica === 'universidad') {
  // Flujo interno: áreas de la universidad
  $postulacion = PracticasController::ctrGetPostulacionAreaByStudent($idStudent);
  if (empty($postulacion) || (int)($postulacion['status'] ?? 0) !== 1) {
    http_response_code(404);
    exit('No se encontró registro de prácticas universitarias aceptado para el alumno.');
  }
  $nameOrganismo = mb_strtoupper((string)($postulacion['area_nombre'] ?? 'ÁREA DE PRÁCTICAS'), 'UTF-8');
} else {
  // Flujo externo: organismo receptor
  $practica = PracticasController::ctrIsStudentRegisteredInPractices($idStudent);
  if (empty($practica) || empty($practica['organismo_externo_id'])) {
    http_response_code(404);
    exit('No se encontró registro de prácticas para el alumno.');
  }
  $organismoReceptor = PracticasModel::mdlGetExternals((int)$practica['organismo_externo_id']);
  $nameOrganismo = (string)($organismoReceptor['empresa'] ?? 'ORGANISMO RECEPTOR');
}

// --- Fecha en español (anclada a Morelia: php.ini puede traer otra zona horaria
//     y con el desfase la constancia se fecharía al día siguiente)
$hoy = new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City'));
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha = $hoy->format('j') . ' de ' . $meses[(int) $hoy->format('n') - 1] . ' de ' . $hoy->format('Y');

// --- Folio
$folio = PracticasModel::generateFolioConstancias($idStudent);
if (empty($folio)) {
  error_log("generateFolioConstancias devolvió vacío para idStudent={$idStudent}");
  $folio = 'FOLIO PENDIENTE';
}

// --- Ruta del JSON de configuración
$CONFIG_PATH = __DIR__ . '/../../config/constancia_config.json';

// --- Cargar configuración persistida
function loadConfig(string $path): array
{
  if (!file_exists($path))
    return [];
  $json = file_get_contents($path);
  $data = json_decode($json, true);
  return is_array($data) ? $data : [];
}

// --- Configuración del usuario
$userCfg = loadConfig($CONFIG_PATH);
$cfg = is_array($userCfg) ? $userCfg : [];

// --- HTML (el armado vive en constanciaHtml.php para poder reutilizarlo en la
//     vista previa con datos de prueba, que no debe tocar la base de datos)
$html = construirConstanciaHtml([
  'studentName'   => $studentName,
  'matricula'     => $matricula,
  'degreeName'    => $degreeName,
  'generoArt'     => $generoArt,
  'nameOrganismo' => $nameOrganismo,
  'fecha'         => $fecha,
  'folio'         => $folio,
], $cfg);

// --- Marcar al alumno como "prácticas finalizadas" al generar su constancia
PracticasModel::mdlFinalizarPracticas($idStudent);

// --- Render PDF
try {
  if (ob_get_length()) {
    ob_end_clean();
  }

  $options = new Options();
  $options->set('isRemoteEnabled', true);
  $options->set('isHtml5ParserEnabled', true);

  $dompdf = new Dompdf($options);
  $dompdf->loadHtml($html);
  // Carta (612×792 pt): tamaño de la plantilla institucional.
  $dompdf->setPaper('letter', 'portrait');
  $dompdf->render();
  $dompdf->stream("constancia.pdf", ["Attachment" => false]);
} catch (Throwable $e) {
  error_log('Error generando PDF (constancia): ' . $e->getMessage());
  http_response_code(500);
  echo 'Ocurrió un error al generar el PDF.';
}
exit;
