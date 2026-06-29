<?php
// generate/constancia.php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../../model/forms.models.php';
require_once __DIR__ . '/../../controller/forms.controller.php';
require_once __DIR__ . '/../../vendor/autoload.php';

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

// --- Fecha en español
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha = date('j') . ' de ' . $meses[(int) date('n') - 1] . ' de ' . date('Y');

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

// --- HTML del cuerpo (preferir paragraphs_html; si no, construir desde paragraphs[])
$bodyHtml = (string) ($cfg['body']['paragraphs_html'] ?? '');
if ($bodyHtml === '' && !empty($cfg['body']['paragraphs']) && is_array($cfg['body']['paragraphs'])) {
  $parts = array_map(static fn($p) => '<p>' . (string) $p . '</p>', $cfg['body']['paragraphs']);
  $bodyHtml = implode('', $parts);
}

// --- Reemplazo de placeholders
$vars = [
  '{{studentName}}' => htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'),
  '{{matricula}}' => htmlspecialchars($matricula, ENT_QUOTES, 'UTF-8'),
  '{{degreeName}}' => htmlspecialchars($degreeName, ENT_QUOTES, 'UTF-8'),
  '{{generoArt}}' => htmlspecialchars($generoArt, ENT_QUOTES, 'UTF-8'),
  '{{nameOrganismo}}' => htmlspecialchars($nameOrganismo, ENT_QUOTES, 'UTF-8'),
  '{{fecha}}' => htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'),
  '{{folio}}' => htmlspecialchars($folio, ENT_QUOTES, 'UTF-8'),
  '{{summary_hours}}' => (string) ($cfg['summary']['hours'] ?? 360),
];
$bodyHtml = strtr($bodyHtml, $vars);

// --- Sanitización ligera de textos de UI
$metaLine = htmlspecialchars((string) $cfg['header']['meta_line'], ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars((string) $cfg['header']['subject'], ENT_QUOTES, 'UTF-8');
$recName = htmlspecialchars((string) $cfg['recipient']['name'], ENT_QUOTES, 'UTF-8');
$recRole = htmlspecialchars((string) $cfg['recipient']['role'], ENT_QUOTES, 'UTF-8');
$recOrg = htmlspecialchars((string) $cfg['recipient']['org'], ENT_QUOTES, 'UTF-8');
$cond = htmlspecialchars((string) $cfg['summary']['condition'], ENT_QUOTES, 'UTF-8');
$titleCard = htmlspecialchars((string) $cfg['summary']['title'], ENT_QUOTES, 'UTF-8');
$firmante = htmlspecialchars((string) $cfg['signature']['signer_name'], ENT_QUOTES, 'UTF-8');
$puesto = htmlspecialchars((string) $cfg['signature']['signer_role'], ENT_QUOTES, 'UTF-8');
$contacto = htmlspecialchars((string) $cfg['footer']['contact_line'], ENT_QUOTES, 'UTF-8');
$bottomTxt = htmlspecialchars((string) $cfg['footer']['bottom_text'], ENT_QUOTES, 'UTF-8');

// --- Numéricos seguros
$hours = (int) ($cfg['summary']['hours'] ?? 360);
$logoWTop = (int) ($cfg['layout']['header_logo_width'] ?? 130);
$logoWBot = (int) ($cfg['layout']['footer_logo_width'] ?? 140);
$fontSizePt = (float) ($cfg['layout']['font_size_pt'] ?? 12);
$padPx = (int) ($cfg['layout']['content_padding_px'] ?? 28);
$pageMargin = (int) ($cfg['layout']['page_margin_px'] ?? 0);
$firmaW = (int) ($cfg['signature']['signature_width'] ?? 200);
$sealW = (int) ($cfg['signature']['seal']['width'] ?? 220);
$sealTop = (int) ($cfg['signature']['seal']['top'] ?? -52);
$sealLeftP = (float) ($cfg['signature']['seal']['left_percent'] ?? 60);
$sealOp = (float) ($cfg['signature']['seal']['opacity'] ?? 0.85);

// --- Colores y URLs
$brandTop = (string) ($cfg['header']['bar_color'] ?? '#01643d');
$brandBot = (string) ($cfg['footer']['bottom_bar_color'] ?? '#01643d');
$logoTopURL = (string) ($cfg['header']['logo_url'] ?? '');
$logoBotURL = (string) ($cfg['footer']['logo_url'] ?? '');
$firmaURL = (string) ($cfg['signature']['signature_img_url'] ?? '');
$sealURL = (string) ($cfg['signature']['seal_img_url'] ?? '');
$fontFamily = (string) ($cfg['layout']['font_family'] ?? 'Arial, Helvetica, sans-serif');

// --- Convertir URLs de imágenes a data URI para que Dompdf las renderice sin red
function imgToDataUri(string $url): string
{
    if ($url === '') return '';

    // Intentar resolver localmente: extrae el nombre de archivo y busca en assets/images
    $filename = basename(parse_url($url, PHP_URL_PATH));
    $localPath = __DIR__ . '/../../view/assets/images/' . $filename;

    if (file_exists($localPath)) {
        $data = file_get_contents($localPath);
    } else {
        // Fallback: lectura remota (solo funciona si allow_url_fopen = On)
        $data = @file_get_contents($url);
    }

    if ($data === false || $data === '') return $url;

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/png',
    };

    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

$logoTopURL = imgToDataUri($logoTopURL);
$logoBotURL = imgToDataUri($logoBotURL);
$firmaURL   = imgToDataUri($firmaURL);
$sealURL    = imgToDataUri($sealURL);

// --- HTML (muy similar al preview)
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  :root{
    --brand: {$brandTop};
    --brand-bottom: {$brandBot};
    --ink:#111827; --muted:#6b7280; --line:#e5e7eb; --bg-chip:#f3f7f5;
    --bar-h: 22px; --footer-h: 80px; --gap: 16px;
  }
  @page { margin: {$pageMargin}px; }
  html, body { margin:0; padding:0; height:100%; }
  body{ font-family: {$fontFamily}; color: var(--ink); font-size: {$fontSizePt}pt; }

  .topbar{ height: 6px; width: 100%; background: var(--brand); }
  .contenido{
    padding: {$padPx}px 34px 0 34px; box-sizing: border-box;
    padding-bottom: calc(var(--bar-h) + var(--footer-h) + 190px);
  }
  .header{ width:100%; border-bottom:1px solid var(--line); padding-bottom:14px; margin-bottom:18px; }
  .header .left{ width: 50%; vertical-align: top; }
  .header .right{ width: 50%; vertical-align: top; text-align: right; }
  .brand img{ width: {$logoWTop}px; vertical-align: middle; }
  .meta{ display:inline-block; vertical-align: middle; margin-left:12px; color:var(--muted); font-size:10pt; line-height:1.3; }
  .pill{ display:inline-block; border:1px solid var(--line); background:#fff; padding:6px 10px; border-radius:999px; font-size:10pt; margin-left:8px; }
  .pill--brand{ background: var(--bg-chip); border-color: transparent; font-weight: bold; }
  .fecha{ color: var(--muted); font-size: 10pt; margin-top: 4px; }
  .asunto{ font-weight: bold; margin-top: 8px; }

  .card{ border:1px solid var(--line); border-radius:12px; background:var(--bg-chip); padding:14px; margin: 18px 0 8px 0; }
  .card-title{ font-weight:bold; font-size:11pt; margin-bottom:10px; letter-spacing:.2px; }
  .grid{ width:100%; border-collapse: collapse; }
  .grid td{ width:50%; padding:6px 8px; vertical-align: top; }
  .label{ font-size:10pt; color:var(--muted); display:block; margin-bottom:2px; }
  .value{ font-weight: bold; font-size: 11pt; }

  .receptor{ margin-top:16px; margin-bottom:20px; line-height:1.5; font-weight:600; font-family: {$fontFamily}; }
  .presente{ margin-top: 8px; }

  .cuerpo{ margin-top:14px; text-align: justify; line-height:1.65; font-size: 11pt; }

  .firma{
    position: fixed; left: 0; right: 0; text-align: center;
    bottom: calc(var(--bar-h) + var(--footer-h) + var(--gap));
  }
  .firma .cta{ letter-spacing:1.5px; color:var(--muted); font-size:9pt; margin-bottom:6px; }
  .firma img.firma-img{ width: {$firmaW}px; margin-bottom: 8px; position: relative; z-index: 1; }
  .firma img.sello{
    position: absolute; top: {$sealTop}px; left: {$sealLeftP}%;
    width: {$sealW}px; z-index: 2; opacity: {$sealOp};
  }
  .firmante{ font-weight:700; }
  .puesto{ font-size:10pt; color: var(--muted); }

  .footer{
    position: fixed; left: 0; right: 0; bottom: var(--bar-h);
    height: var(--footer-h); box-sizing: border-box; padding: 0 34px; border-top: 1px solid var(--line);
    display: table; width: 100%;
  }
  .footer .cell{ display: table-cell; vertical-align: middle; }
  .footer .cell.logo{ width: 160px; }
  .footer img.logo{ width: {$logoWBot}px; }
  .footer .contacto{ text-align: left; font-size:10pt; color:var(--muted); padding-left:8px; }

  .barra-inferior{
    position: fixed; left:0; bottom:0; width:100%; height: var(--bar-h);
    background: var(--brand-bottom); color: #fff; text-align:center; line-height: var(--bar-h);
    font-size: 9pt; letter-spacing:.3px;
  }
</style>
</head>
<body>

  <div class="topbar"></div>

  <div class="contenido">
    <!-- Header -->
    <table class="header">
      <tr>
        <td class="left">
          <span class="brand">
            <img src="{$logoTopURL}" alt="Logo">
          </span>
          <span class="meta">{$metaLine}</span>
        </td>
        <td class="right">
HTML;

// FOLIO, fecha y asunto
if (!empty($cfg['header']['show_folio'])) {
  $html .= '<span class="pill">FOLIO</span><span class="pill pill--brand">' . htmlspecialchars($folio, ENT_QUOTES, 'UTF-8') . '</span>';
}
$html .= '<div class="fecha">Emitido el ' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '</div>';
$html .= '<div class="asunto">' . $subject . '</div>';

$html .= <<<HTML
        </td>
      </tr>
    </table>
HTML;

// Tarjeta de resumen opcional
if (!empty($cfg['summary']['show_card'])) {
  $alumno = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
  $matri = htmlspecialchars($matricula, ENT_QUOTES, 'UTF-8');
  $prog = htmlspecialchars($degreeName, ENT_QUOTES, 'UTF-8');
  $org = htmlspecialchars($nameOrganismo, ENT_QUOTES, 'UTF-8');

  $html .= <<<HTML
    <div class="card">
      <div class="card-title">{$titleCard}</div>
      <table class="grid">
        <tr>
          <td><span class="label">Alumno</span><span class="value">{$alumno}</span></td>
          <td><span class="label">Matrícula</span><span class="value">{$matri}</span></td>
        </tr>
        <tr>
          <td><span class="label">Programa</span><span class="value">{$prog}</span></td>
          <td><span class="label">Organismo Receptor</span><span class="value">{$org}</span></td>
        </tr>
        <tr>
          <td><span class="label">Horas cubiertas</span><span class="value">{$hours}</span></td>
          <td><span class="label">Condición</span><span class="value">{$cond}</span></td>
        </tr>
      </table>
    </div>
HTML;
}

// Destinatario
$html .= <<<HTML
    <div class="receptor">
      {$recName}<br>
      {$recRole}<br>
      {$recOrg}
      <div class="presente">Presente</div>
    </div>

    <!-- Cuerpo -->
    <div class="cuerpo">{$bodyHtml}</div>

    <!-- Firma fija -->
    <div class="firma">
      <div class="cta">{$cfg['signature']['legend']}</div>
HTML;

if (!empty($firmaURL)) {
  $html .= '<img src="' . htmlspecialchars($firmaURL, ENT_QUOTES, 'UTF-8') . '" alt="Firma" class="firma-img"><br>';
}
if (!empty($sealURL)) {
  $html .= '<img src="' . htmlspecialchars($sealURL, ENT_QUOTES, 'UTF-8') . '" alt="Sello" class="sello">';
}

$html .= <<<HTML
      <div class="firmante">{$firmante}</div>
      <div class="puesto">{$puesto}</div>
    </div>
  </div>

  <!-- Footer fijo -->
  <div class="footer">
    <div class="cell logo">
HTML;

if (!empty($logoBotURL)) {
  $html .= '<img src="' . htmlspecialchars($logoBotURL, ENT_QUOTES, 'UTF-8') . '" class="logo" alt="Logo pie">';
}

$html .= <<<HTML
    </div>
    <div class="cell contacto">{$contacto}</div>
  </div>

  <div class="barra-inferior">
    {$bottomTxt}
  </div>

</body>
</html>
HTML;

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
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  $dompdf->stream("constancia.pdf", ["Attachment" => false]);
} catch (Throwable $e) {
  error_log('Error generando PDF (constancia): ' . $e->getMessage());
  http_response_code(500);
  echo 'Ocurrió un error al generar el PDF.';
}
exit;
