<?php
declare(strict_types=1);

require_once __DIR__ . "/../../model/forms.models.php";
require_once __DIR__ . "/../forms.controller.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use Dompdf\Dompdf;

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
$CONFIG_PATH = __DIR__ . '/../../config/carta_aceptacion_servicio.json';
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

// ── Datos del alumno ───────────────────────────────────────
$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fecha = date('j') . ' de ' . $meses[(int)date('n') - 1] . ' de ' . date('Y');

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

// ── Placeholders ────────────────────────────────────────────
$vars = [
    '{{studentName}}'  => $studentName,
    '{{matricula}}'    => $matricula,
    '{{degreeName}}'   => $degreeName,
    '{{fecha}}'        => $fecha,
    '{{folio}}'        => $folio,
    '{{gradoTexto}}'   => $gradoTexto,
    '{{horas}}'        => $horas,
    '{{meses}}'        => $mesesS,
    '{{fechaInicio}}'  => mb_strtoupper($fechaInicioStr),
    '{{fechaTermino}}' => mb_strtoupper($fechaTerminoStr),
];

// ── Config valores ──────────────────────────────────────────
$headerBarColor  = val($c, 'header.bar_color',        '#006837');
$headerLogoUrl   = val($c, 'header.logo_url',          '');
$headerLogoW     = (int) val($c, 'layout.header_logo_width', 150);
$headerCityLine  = strtr(val($c, 'header.city_line',   'Morelia, Mich., a {{fecha}}.'), $vars);
$headerSubject   = val($c, 'header.subject',           'Carta de aceptación de Servicio Social.');
$showFolio       = (bool) val($c, 'header.show_folio', true);

$fontFamily      = val($c, 'layout.font_family',       'Arial, sans-serif');
$fontSize        = (int) val($c, 'layout.font_size_pt', 12);
$padding         = (int) val($c, 'layout.content_padding_px', 40);

$recipientCargo    = val($c, 'recipient.cargo',   'Subdirector de Servicio Social y Pasantes');
$recipientNombre   = val($c, 'recipient.nombre',  'Lic. Alejandro Cruz Ferreyra');
$recipientOrganismo= val($c, 'recipient.organismo','Instituto de la Juventud Michoacana');

$sigLegend       = val($c, 'signature.legend',        'ATENTAMENTE');
$sigImgUrl       = val($c, 'signature.signature_img_url', '');
$sigWidth        = (int) val($c, 'signature.signature_width', 200);
$sealImgUrl      = val($c, 'signature.seal_img_url',  '');
$sealW           = (int) val($c, 'signature.seal.width',  240);
$sealTop         = (int) val($c, 'signature.seal.top',    -72);
$sealTopFirma    = (int) val($c, 'signature.seal.top_firma', -20);
$sealLeft        = (int) val($c, 'signature.seal.left_percent', 47);
$sealOpacity     = (float)val($c, 'signature.seal.opacity', 0.8);
$signerName      = val($c, 'signature.signer_name',   'MGH Karla Mariana Fonseca Munguia');
$signerRole      = val($c, 'signature.signer_role',   'Coordinadora de Servicio Social UNIMO');

$footerBarColor  = val($c, 'footer.bottom_bar_color', '#006837');
$footerLogoUrl   = val($c, 'footer.logo_url',         '');
$footerLogoW     = (int) val($c, 'layout.footer_logo_width', 150);
$footerContact   = val($c, 'footer.contact_line',     '');
$footerText      = val($c, 'footer.bottom_text',      '');

// ── Imágenes a base64 ───────────────────────────────────────
function imgToDataUri(string $url): string {
    if ($url === '') return '';
    $filename  = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
    $localPath = __DIR__ . '/../../view/assets/images/' . $filename;
    $data = file_exists($localPath) ? file_get_contents($localPath) : @file_get_contents($url);
    if (!$data) return $url;
    $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = match($ext) {
        'jpg','jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/png',
    };
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

$headerLogoUrl = imgToDataUri($headerLogoUrl);
$sigImgUrl     = imgToDataUri($sigImgUrl);
$sealImgUrl    = imgToDataUri($sealImgUrl);
$footerLogoUrl = imgToDataUri($footerLogoUrl);

// ── Cuerpo ──────────────────────────────────────────────────
$paragraphs = val($c, 'body.paragraphs', []);
if (empty($paragraphs)) {
    $paragraphs = [
        "En relación a la solicitud del <strong>{{studentName}}</strong>, quien curso el <strong>{{gradoTexto}}</strong> de la <strong>{{degreeName}}</strong> de <strong>UNIVERSIDAD MONTRER</strong>, con número de matrícula <strong>{{matricula}}</strong>, me permito informarle que ha sido aceptado en este organismo receptor denominado: <strong>UNIVERSIDAD MONTRER</strong>, para realizar el <strong>SERVICIO SOCIAL</strong> y cubrir un total de <strong>{{horas}} HRS</strong> en un periodo de <strong>{{meses}} MESES</strong> comprendido del <strong>{{fechaInicio}}</strong> al <strong>{{fechaTermino}}</strong>.",
        "Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo.",
    ];
}

$bodyHtml = '';
foreach ($paragraphs as $p) {
    $bodyHtml .= '<p>' . strtr($p, $vars) . '</p>';
}

$folioHtml = $showFolio ? "<div class=\"asunto\"><strong>{$folio}</strong></div>" : '';

// ── HTML ────────────────────────────────────────────────────
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 0; }
    html, body { margin: 0; padding: 0; font-family: {$fontFamily}; font-size: {$fontSize}pt; }
    .header-bar { background-color: {$headerBarColor}; height: 40px; width: 100%; }
    .contenido  { padding: 10px {$padding}px; padding-bottom: 140px; box-sizing: border-box; }
    table.header { width: 100%; border-bottom: 1px solid #ccc; }
    .header-left  { width: {$headerLogoW}px; text-align: center; }
    .header-left img { width: {$headerLogoW}px; }
    .header-right { text-align: right; vertical-align: top; }
    .fecha   { font-size: 11pt; color: #444; }
    .asunto  { margin-top: 10px; font-weight: bold; }
    .receptor { margin-top: 15px; font-weight: bold; line-height: 1.7; }
    .cuerpo  { margin-top: 20px; text-align: justify; line-height: 1.6; font-size: 11pt; }
    .cuerpo p { margin: 0 0 10px 0; }
    .firma   { position: relative; text-align: center; margin-top: 30px; }
    .firma img.firma-img {
      width: {$sigWidth}px;
      position: absolute;
      top: {$sealTopFirma}px;
      left: 50%;
      transform: translateX(-50%);
      opacity: {$sealOpacity};
      z-index: 1;
    }
    .firma img.sello {
      position: absolute;
      width: {$sealW}px;
      top: {$sealTop}px;
      left: {$sealLeft}%;
      opacity: {$sealOpacity};
      z-index: 0;
    }
    .firma-nombre { font-size: 10pt; font-weight: bold; margin-top: 80px; }
    .firma-cargo  { font-size: 9pt; color: #555; }
    .footer-wrap { position: fixed; bottom: 0; left: 0; right: 0; }
    .footer-bar  { background-color: {$footerBarColor}; height: 10px; width: 100%; }
    .footer-content { background-color: #f5f5f5; padding: 6px {$padding}px; }
    table.footer { width: 100%; }
    .footer-logo-cell { width: {$footerLogoW}px; }
    .footer-logo-cell img { width: {$footerLogoW}px; }
    .footer-text { font-size: 8pt; color: #555; text-align: right; vertical-align: middle; }
  </style>
</head>
<body>
  <div class="header-bar"></div>
  <div class="contenido">
    <table class="header">
      <tr>
        <td class="header-left"><img src="{$headerLogoUrl}" alt="Logo"></td>
        <td class="header-right">
          <div class="fecha">{$headerCityLine}</div>
          <div class="asunto">Asunto: <strong>{$headerSubject}</strong></div>
          {$folioHtml}
        </td>
      </tr>
    </table>
    <div class="receptor">
      {$recipientNombre}<br>
      {$recipientCargo}<br>
      {$recipientOrganismo}
    </div>
    <div class="cuerpo">
      <br>
      <p><strong>PRESENTE</strong></p>
      <br>
      {$bodyHtml}
    </div>
    <div class="firma">
      <p>{$sigLegend}</p>
      <img class="sello"     src="{$sealImgUrl}"  alt="">
      <img class="firma-img" src="{$sigImgUrl}"   alt="Firma">
      <div class="firma-nombre">{$signerName}</div>
      <div class="firma-cargo">{$signerRole}</div>
    </div>
  </div>
  <div class="footer-wrap">
    <div class="footer-bar"></div>
    <div class="footer-content">
      <table class="footer">
        <tr>
          <td class="footer-logo-cell"><img src="{$footerLogoUrl}" alt="Logo"></td>
          <td class="footer-text">
            {$footerContact}<br>
            {$footerText}
          </td>
        </tr>
      </table>
    </div>
  </div>
</body>
</html>
HTML;

// ── Generar PDF ─────────────────────────────────────────────
$dompdf = new Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'Arial']);
$dompdf->loadHtml($html);
$dompdf->setPaper('Letter', 'portrait');
$dompdf->render();

$filename = 'CartaAceptacionSS_' . preg_replace('/\s+/', '_', $studentName) . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
