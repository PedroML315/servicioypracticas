<?php
// generate/carta-presentacion.php
declare(strict_types=1);

require_once __DIR__ . "/../../model/forms.models.php";
require_once __DIR__ . "/../forms.controller.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use Dompdf\Dompdf;

session_start();

// === Ruta a tu JSON ===
$CONFIG_PATH = __DIR__ . '/../../config/carta_presentacion.json';

// === Helper: cargar config ===
function cfg(): array
{
  global $CONFIG_PATH;
  if (!file_exists($CONFIG_PATH))
    return [];
  $json = file_get_contents($CONFIG_PATH);
  $data = json_decode($json, true);
  return is_array($data) ? $data : [];
}

// === Datos dinámicos originales ===
$idUR = $_POST['idUR'] ?? '';
$nameUR = $_POST['nameUR'] ?? '';
$responsable = $_POST['responsable'] ?? '';
$domicilio = $_POST['domicilio'] ?? '';
$user = $_SESSION['user'] ?? [];

$studentName = strtoupper(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '') . ' ' . ($user['lastnameMom'] ?? '')));
$matricula = $user['matricula'] ?? '';
$genero = (($user['gender'] ?? 1) == 1 ? 'el' : 'la');

$degree = FormsModel::mdlSearchDegrees($user['idDegree'] ?? null);
$degreeName = 'LICENCIATURA EN ' . strtoupper($degree['nameDegree'] ?? 'DESCONOCIDA');

// Fecha español
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha = date('j') . ' de ' . $meses[(int) date('n') - 1] . ' de ' . date('Y');

// Folio
$folio = ServicioModel::generateFolio($user['idStudent'] ?? null);

// === Placeholders disponibles ===
$vars = [
  '{{studentName}}' => $studentName,
  '{{matricula}}' => $matricula,
  '{{degreeName}}' => $degreeName,
  '{{genero}}' => $genero,
  '{{fecha}}' => $fecha,
  '{{folio}}' => $folio,
  '{{nameUR}}' => $nameUR,
  '{{responsable}}' => $responsable,
  '{{domicilio}}' => $domicilio,
];

// === Cargar configuración ===
$c = cfg();

// === Helpers de lectura segura con default ===
function val(array $arr, string $path, $default = '')
{
  $tmp = $arr;
  foreach (explode('.', $path) as $k) {
    if (!is_array($tmp) || !array_key_exists($k, $tmp))
      return $default;
    $tmp = $tmp[$k];
  }
  return $tmp;
}
function repl(string $s, array $vars): string
{
  return strtr($s, $vars);
}
function replArr(array $arr, array $vars): array
{
  return array_map(fn($p) => repl((string) $p, $vars), $arr);
}

// === Derivar textos/valores ya con placeholders resueltos ===
$header_bar_color = val($c, 'header.bar_color', '#006837');
$header_logo = val($c, 'header.logo_url', '');
$header_city_line = repl(val($c, 'header.city_line', ''), $vars);
$header_subject = val($c, 'header.subject', 'Carta de Presentación');
$show_folio = (bool) val($c, 'header.show_folio', true);

$recipient_name = repl(val($c, 'body.recipient_name', ''), $vars);
$recipient_role = repl(val($c, 'body.recipient_role', ''), $vars);
$recipient_address = repl(val($c, 'body.recipient_address', ''), $vars);
$show_address = (bool) val($c, 'body.show_address', false);
$paragraphs = replArr(val($c, 'body.paragraphs', []), $vars);

$sig_legend = val($c, 'signature.legend', 'ATENTAMENTE');
$sig_img = val($c, 'signature.signature_img_url', '');
$sig_width = (int) val($c, 'signature.signature_width', 200);
$seal_img = val($c, 'signature.seal_img_url', '');
$seal_width = (int) val($c, 'signature.seal.width', 240);
$seal_top = (int) val($c, 'signature.seal.top', -60);
$seal_left = (int) val($c, 'signature.seal.left_percent', 50);
$seal_opacity = (float) val($c, 'signature.seal.opacity', 0.8);
$signer_name = val($c, 'signature.signer_name', '');
$signer_role = val($c, 'signature.signer_role', '');

$footer_logo = val($c, 'footer.logo_url', '');
$footer_contact = val($c, 'footer.contact_line', '');
$bottom_bar_color = val($c, 'footer.bottom_bar_color', '#006837');
$bottom_text = val($c, 'footer.bottom_text', '');

$font_family = val($c, 'layout.font_family', 'Arial, sans-serif');
$font_size_pt = (int) val($c, 'layout.font_size_pt', 12);
$content_padding_px = (int) val($c, 'layout.content_padding_px', 40);
$page_margin_px = (int) val($c, 'layout.page_margin_px', 0);
$header_logo_width = (int) val($c, 'layout.header_logo_width', 150);
$footer_logo_width = (int) val($c, 'layout.footer_logo_width', 150);

// Convertir URLs a data URI para Dompdf (sin acceso a red)
function imgToDataUri(string $url): string
{
    if ($url === '') return '';
    $filename = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
    $localPath = __DIR__ . '/../../view/assets/images/' . $filename;
    $data = file_exists($localPath) ? file_get_contents($localPath) : @file_get_contents($url);
    if ($data === false || $data === '') return $url;
    $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/png',
    };
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

$header_logo = imgToDataUri($header_logo);
$sig_img     = imgToDataUri($sig_img);
$seal_img    = imgToDataUri($seal_img);
$footer_logo = imgToDataUri($footer_logo);

// === HTML (usa config) ===
$parrafosHtml = '';
foreach ($paragraphs as $p) {
  $parrafosHtml .= "<p>{$p}</p>\n";
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: {$page_margin_px}px; }
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: {$font_family};
      font-size: {$font_size_pt}pt;
    }
    .header-bar {
      background-color: {$header_bar_color};
      height: 40px;
      width: 100%;
    }
    .contenido {
      padding: {$content_padding_px}px;
      padding-bottom: 140px;
      box-sizing: border-box;
    }
    table.header { width: 100%; border-bottom: 1px solid #ccc; }
    .header-left { width: 120px; text-align: center; }
    .header-left img { width: {$header_logo_width}px; }
    .header-right { text-align: right; vertical-align: top; }
    .fecha { font-size: 11pt; color: #444; }
    .asunto { margin-top: 10px; font-weight: bold; }
    .receptor { margin-top: 30px; font-weight: bold; line-height: 1.5; }
    .cuerpo { margin-top: 20px; text-align: justify; line-height: 1.6; }
    .firma { position: relative; text-align: center; margin-top: 70px; }
    .firma img.firma-img { width: {$sig_width}px; margin-bottom: 10px; position: relative; z-index: 1; }

    .firma img.sello {
      position: absolute;
      top: {$seal_top}px;
      left: {$seal_left}%;
      /* transform: translateX(-%); */
      width: 240px;
      z-index: 2;
      opacity: 0.8;
    }
        .firmante { font-weight: bold; position: relative; z-index: 1; }
    .puesto { font-size: 11pt; }
    .footer { position: fixed; bottom: 30px; left: 0; right: 0; padding: 0 {$content_padding_px}px; box-sizing: border-box; }
    .footer-logo-linea { border-top: 1px solid #ccc; display: flex; align-items: center; font-size: 10pt; padding-top: 10px; color: #444; }
    .footer-logo-linea img { width: {$footer_logo_width}px; margin-right: 10px; }
    .barra-inferior { background-color: {$bottom_bar_color}; color: white; text-align: center; padding: 6px 0; font-size: 9pt; position: fixed; bottom: 0; left: 0; width: 100%; }
  </style>
</head>
<body>
  <div class="header-bar"></div>

  <div class="contenido">
    <table class="header">
      <tr>
        <td class="header-left">
          <img src="{$header_logo}" alt="Logo UNIMO">
        </td>
        <td class="header-right">
          <div class="fecha">{$header_city_line}</div>
          <div class="asunto">Asunto: <strong>{$header_subject}</strong></div>
HTML;

if ($show_folio) {
  $html .= "<div class=\"asunto\"><strong>{$folio}</strong></div>";
}

$html .= <<<HTML
        </td>
      </tr>
    </table>

    <div class="receptor">
      {$recipient_name}<br>
      {$recipient_role}<br>
HTML;

if ($show_address && $recipient_address !== '') {
  $html .= "<br><!-- Domicilio visible -->{$recipient_address}<br>";
}

$html .= <<<HTML
      <strong>Presente</strong>
    </div>

    <div class="cuerpo">
      {$parrafosHtml}
    </div>

    <div class="firma">
      {$sig_legend}<br>
      <img src="{$sig_img}" alt="Firma" class="firma-img"><br>
      <img src="{$seal_img}" alt="Sello" class="sello">
      <div class="firmante">{$signer_name}</div>
      <div class="puesto">{$signer_role}</div>
    </div>
  </div>

  <div class="footer">
    <div class="footer-logo-linea">
      <img src="{$footer_logo}" alt="Logo UNIMO">
      <div>{$footer_contact}</div>
    </div>
  </div>

  <div class="barra-inferior">
    {$bottom_text}
  </div>
</body>
</html>
HTML;

// === Generar PDF como antes ===
$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->setIsRemoteEnabled(true);
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("CartaPresentacion_{$matricula}.pdf", ["Attachment" => false]);
exit;
