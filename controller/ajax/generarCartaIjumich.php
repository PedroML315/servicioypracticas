<?php
declare(strict_types=1);

require_once __DIR__ . "/../../model/forms.models.php";
require_once __DIR__ . "/../forms.controller.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use Dompdf\Dompdf;

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
$CONFIG_PATH = __DIR__ . '/../../config/carta_presentacion.json';
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

// ── Datos del alumno ───────────────────────────────────────────
$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fecha = date('j') . ' de ' . $meses[(int)date('n') - 1] . ' de ' . date('Y');

$studentName = mb_strtoupper(trim(
    ($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '') . ' ' . ($row['lastnameMom'] ?? '')
));
$matricula  = $row['matricula'] ?? '';
$genero     = (($row['gender'] ?? 1) == 1 ? 'el' : 'la');
$degree     = FormsModel::mdlSearchDegrees($row['idDegree'] ?? null);
$degreeName = 'LICENCIATURA EN ' . mb_strtoupper($degree['nameDegree'] ?? 'DESCONOCIDA');
$folio      = ServicioModel::generateFolio((int)$row['student_id']);

// ── Datos del organismo (del registro) ────────────────────────
$nameUR      = mb_strtoupper($row['nombre_organismo'] ?? '');
$responsable = mb_strtoupper($row['responsable'] ?? '');
$cargo       = mb_strtoupper($row['puesto_responsable'] ?? '');
$domicilio   = mb_strtoupper(
    trim(implode(', ', array_filter([
        $row['calle_numero']  ?? '',
        $row['colonia']       ?? '',
        ($row['codigo_postal'] ? 'C.P. ' . $row['codigo_postal'] : ''),
        $row['municipio']     ?? '',
        $row['estado']        ?? '',
        $row['pais']          ?? '',
    ])))
);

// ── Placeholders ──────────────────────────────────────────────
$vars = [
    '{{studentName}}' => $studentName,
    '{{matricula}}'   => $matricula,
    '{{degreeName}}'  => $degreeName,
    '{{genero}}'      => $genero,
    '{{fecha}}'       => $fecha,
    '{{folio}}'       => $folio,
    '{{nameUR}}'      => $nameUR,
    '{{responsable}}' => $responsable,
    '{{domicilio}}'   => $domicilio,
];

// ── Config valores ─────────────────────────────────────────────
$headerBarColor  = val($c, 'header.bar_color',       '#006837');
$headerLogoUrl   = val($c, 'header.logo_url',         '');
$headerLogoW     = (int) val($c, 'layout.header_logo_width', 150);
$headerCityLine  = strtr(val($c, 'header.city_line',  'Morelia, Michoacán, México, a {{fecha}}.'), $vars);
$headerSubject   = val($c, 'header.subject',          'Carta de Presentación de Servicio Social');
$showFolio       = (bool) val($c, 'header.show_folio', true);

$fontFamily      = val($c, 'layout.font_family',      'Arial, sans-serif');
$fontSize        = (int) val($c, 'layout.font_size_pt', 12);
$padding         = (int) val($c, 'layout.content_padding_px', 40);

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

// ── Imágenes a base64 ──────────────────────────────────────────
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

// ── Cuerpo ────────────────────────────────────────────────────
$paragraphs = val($c, 'body.paragraphs', []);
if (empty($paragraphs)) {
    $paragraphs = [
        'Por este medio, se hace constar que <strong>{{studentName}}</strong>, con matrícula: <strong>{{matricula}}</strong>, de la <strong>{{degreeName}}</strong> en esta Universidad, ha cumplido los requisitos para desarrollar el <strong>Servicio Social</strong> y es de su interés realizarlo en la institución que usted dignamente representa, considerando que debe cumplir <strong>480 horas</strong> en un periodo de <strong>6 meses</strong>.',
        'El Servicio Social debe ser desarrollado, conforme a lo establecido por el Organismo Público del Gobierno del Estado de Michoacán encargado de regularlo, actualmente denominado <strong>Instituto de la Juventud Michoacana</strong>, por tal motivo, se solicita por favor, sea emitida la <strong>Carta de Aceptación</strong> dirigida al <strong>Lic. Alejandro Cruz Ferreyra, Subdirector de Servicio Social y Pasantes</strong>, indicando el número de horas a cubrir mencionado en el párrafo anterior, así como la fecha de inicio y término.',
        'Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo.',
    ];
}

$bodyHtml = '';
foreach ($paragraphs as $p) {
    $bodyHtml .= '<p>' . strtr($p, $vars) . '</p>';
}

$folioHtml = $showFolio ? "<div class=\"asunto\"><strong>{$folio}</strong></div>" : '';

// ── HTML ──────────────────────────────────────────────────────
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
    .receptor { margin-top: 15px; font-weight: bold; line-height: 1.5; }
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
    .firma-nombre { font-size: 10pt; font-weight: bold;  margin-top: 80px;}
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
          {$folioHtml}
        </td>
      </tr>
    </table>
    <div class="receptor">
      {$cargo}<br>
      {$responsable}<br>
      {$nameUR}<br>
      {$domicilio}
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

// ── Generar PDF ───────────────────────────────────────────────
$dompdf = new Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'Arial']);
$dompdf->loadHtml($html);
$dompdf->setPaper('Letter', 'portrait');
$dompdf->render();

$filename = 'CartaPresentacion_' . preg_replace('/\s+/', '_', $studentName) . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
