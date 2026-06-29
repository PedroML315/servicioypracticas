<?php
// controller/ajax/generarConvenio.php
declare(strict_types=1);

require_once __DIR__ . "/../../vendor/autoload.php";
use Dompdf\Dompdf;

$CONFIG_PATH = __DIR__ . '/../../config/convenio_config.json';

function loadConvenioConfig(string $path): array
{
    if (!file_exists($path))
        return [];
    $json = file_get_contents($path);
    if (str_starts_with($json, "\xEF\xBB\xBF"))
        $json = substr($json, 3);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function convVal(array $arr, string $path, $default = '')
{
    $tmp = $arr;
    foreach (explode('.', $path) as $k) {
        if (!is_array($tmp) || !array_key_exists($k, $tmp))
            return $default;
        $tmp = $tmp[$k];
    }
    return $tmp;
}

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha = date('j') . ' de ' . $meses[(int) date('n') - 1] . ' de ' . date('Y');
$vars = ['{{fecha}}' => $fecha];

$c = loadConvenioConfig($CONFIG_PATH);

$header_bar_color = convVal($c, 'header.bar_color', '#006837');
$header_logo_url = convVal($c, 'header.logo_url', '');
$header_city_line = strtr(convVal($c, 'header.city_line', ''), $vars);
$header_title = convVal($c, 'header.title', 'CONVENIO DE PRÁCTICAS PROFESIONALES');
$content_html = strtr(convVal($c, 'body.content_html', ''), $vars);

$signer_name = convVal($c, 'signature.signer_name', 'Noé Alonso González Herrera');
$signer_role = convVal($c, 'signature.signer_role', 'Representante Legal – Instituto Montrer, S.C.');

$footer_logo_url = convVal($c, 'footer.logo_url', '');
$footer_contact = convVal($c, 'footer.contact_line', '');
$bottom_bar_color = convVal($c, 'footer.bottom_bar_color', '#006837');
$bottom_text = convVal($c, 'footer.bottom_text', '');

$font_family = convVal($c, 'layout.font_family', 'Arial, sans-serif');
$font_size_pt = (int) convVal($c, 'layout.font_size_pt', 11);
$content_padding = (int) convVal($c, 'layout.content_padding_px', 40);
$page_margin = (int) convVal($c, 'layout.page_margin_px', 0);
$header_logo_width = (int) convVal($c, 'layout.header_logo_width', 150);
$footer_logo_width = (int) convVal($c, 'layout.footer_logo_width', 150);

function imgToDataUriConv(string $url): string
{
    if ($url === '')
        return '';
    $filename = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
    $localPath = __DIR__ . '/../../view/assets/images/' . $filename;
    $data = file_exists($localPath) ? file_get_contents($localPath) : @file_get_contents($url);
    if ($data === false || $data === '')
        return $url;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/png',
    };
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

$header_logo = imgToDataUriConv($header_logo_url);
$footer_logo = imgToDataUriConv($footer_logo_url);

$header_logo_img = $header_logo ? '<img src="' . $header_logo . '" alt="Logo Universidad Montrer">' : '';
$footer_logo_img = $footer_logo ? '<img src="' . $footer_logo . '" alt="Logo">' : '';

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { 
      margin-top: 150px; 
      margin-bottom: 95px; 
      margin-left: {$page_margin}px;
      margin-right: {$page_margin}px; 
    }
    html, body {
      margin: 0; padding: 0; height: 100%;
      font-family: {$font_family};
      font-size: {$font_size_pt}pt;
      color: #111;
    }

    header {
      position: fixed;
      top: -150px;
      left: 0;
      right: 0;
      height: 150px;
    }
    .header-bar {
      background-color: {$header_bar_color};
      height: 40px; 
      width: 100%;
    }
    .header-content {
      padding: 0 {$content_padding}px;
    }
    table.header-tbl { width: 100%; border-bottom: 1px solid #ccc; margin-top: 18px; padding-bottom: 18px; border-collapse: collapse; }
    .header-left { width: 140px; text-align: center; vertical-align: middle; }
    .header-left img { width: {$header_logo_width}px; }
    .header-right { text-align: right; vertical-align: middle; }
    .fecha { font-size: 10pt; color: #444; }

    footer {
      position: fixed;
      bottom: -95px;
      left: 0;
      right: 0;
      height: 95px;
    }
    .footer-content {
      padding: 0 {$content_padding}px;
    }
    table.footer-tbl {
      width: 100%;
      border-top: 1px solid #ccc;
      margin-top: 10px;
      padding-top: 10px;
      border-collapse: collapse;
    }
    .footer-tbl td { vertical-align: middle; font-size: 9pt; color: #444; }
    .footer-logo { width: {$footer_logo_width}px; padding-right: 15px; }
    .footer-logo img { width: {$footer_logo_width}px; }
    .footer-contact { text-align: left; }
    .footer-page { text-align: right; font-weight: bold; white-space: nowrap; }
    .footer-page:after { content: " " counter(page) " / " counter(pages); }

    .barra-inferior { 
      background-color: {$bottom_bar_color}; 
      color: white; 
      text-align: center; 
      padding: 6px 0; 
      font-size: 8pt; 
      position: absolute; 
      bottom: 0; 
      left: 0; 
      width: 100%; 
    }

    .contenido {
      padding: 0 {$content_padding}px;
      box-sizing: border-box;
    }

    .convenio-title {
      text-align: center;
      font-size: 14pt;
      font-weight: bold;
      text-transform: uppercase;
      margin: 0px 0 20px;
      letter-spacing: 1px;
    }

    .cuerpo { text-align: justify; line-height: 1.75; }
    .cuerpo p { margin-bottom: 10px; margin-top: 0; }
    .cuerpo ol, .cuerpo ul { margin-bottom: 10px; padding-left: 20px; }
    .cuerpo strong { font-weight: bold; }
    .cuerpo em { font-style: italic; }

    /* ── Sección de firmas ── */
    .firmas-section {
      margin-top: 70px;
      padding-top: 20px;
      page-break-inside: avoid;
    }
    .firmas-title {
      text-align: center;
      font-size: 10pt;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: .8px;
      margin-bottom: 50px;
      color: #333;
    }
    table.firmas-tbl {
      width: 100%;
      border-collapse: collapse;
    }
    table.firmas-tbl td {
      width: 50%;
      text-align: center;
      vertical-align: bottom;
      padding: 0 30px 0;
    }
    .firma-linea {
      border-top: 1px solid #333;
      width: 85%;
      margin: 0 auto 8px;
    }
    .firma-nombre {
      font-weight: bold;
      font-size: 10pt;
      line-height: 1.5;
    }
    .firma-cargo {
      font-size: 9pt;
      color: #444;
      line-height: 1.5;
      margin-top: 3px;
    }
    .firma-espacio {
      height: 90px;
    }

    /* ── Separador entre bloques de firmas ── */
    .firmas-sep {
      margin: 52px 0 40px;
      border: none;
      border-top: 1px dashed #ccc;
    }
  </style>
</head>
<body>

  <header>
    <div class="header-bar"></div>
    <div class="header-content">
      <table class="header-tbl">
        <tr>
          <td class="header-left">{$header_logo_img}</td>
          <td class="header-right"><div class="fecha">{$header_city_line}</div></td>
        </tr>
      </table>
    </div>
  </header>

  <footer>
    <div class="footer-content">
      <table class="footer-tbl">
        <tr>
          <td class="footer-logo">{$footer_logo_img}</td>
          <td class="footer-contact">{$footer_contact}</td>
          <td class="footer-page">Pág.</td>
        </tr>
      </table>
    </div>
    <div class="barra-inferior">{$bottom_text}</div>
  </footer>

  <main class="contenido">
    <div class="convenio-title">{$header_title}</div>

    <div class="cuerpo">
      {$content_html}
    </div>

    <!-- ══════════ SECCIÓN DE FIRMAS AUTÓGRAFAS ══════════ -->
    <div class="firmas-section">
      <div class="firmas-title">Firmas de conformidad</div>

      <!-- Fila 1: Representante Legal de la Empresa  |  Representante de la Universidad -->
      <table class="firmas-tbl">
        <tr>
          <td>
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">Representante Legal</div>
            <div class="firma-cargo">"La Empresa"<br>(Nombre y cargo)</div>
          </td>
          <td>
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{$signer_name}</div>
            <div class="firma-cargo">{$signer_role}<br>"La Universidad"</div>
          </td>
        </tr>
      </table>

      <hr class="firmas-sep">

      <!-- Fila 2: Testigo 1  |  Testigo 2 -->
      <table class="firmas-tbl">
        <tr>
          <td>
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">Testigo 1</div>
            <div class="firma-cargo">(Nombre y cargo)</div>
          </td>
          <td>
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">Testigo 2</div>
            <div class="firma-cargo">(Nombre y cargo)</div>
          </td>
        </tr>
      </table>
    </div>
  </main>

</body>
</html>
HTML;

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->setIsRemoteEnabled(true);
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Convenio_Practicas_Profesionales.pdf", ["Attachment" => true]);
exit;
