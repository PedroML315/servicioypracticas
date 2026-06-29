<?php
require_once "../../model/forms.models.php";
require_once "../forms.controller.php";
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

session_start();

// 1) Recoger datos del POST y sesión
$empresa = mb_strtoupper($_POST['empresa'] ?? '');
$cargoResponsable = mb_strtoupper($_POST['cargoResponsable'] ?? '');
$responsable = mb_strtoupper($_POST['nombreResponsable'] ?? '');
$domicilio = $_POST['domicilio'] ?? '';
$user = $_SESSION['user'];

$studentName = strtoupper(trim("{$user['nombre_completo']}"));
$matricula = $user['matricula'];
$genero = $user['genero'] == "Masculino" ? 'el' : 'la';
$degreeName = 'LICENCIATURA EN ' . strtoupper($user['programa_academico'] ?? 'DESCONOCIDA');

// 2) Fecha en español
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha = date('j') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y');

// Generar folio único para la carta
$folio = PracticasModel::generateFolioPracticas($user['id']);

require_once "../../model/BusinessHoursHelper.php";
require_once "../emails.php";

$ahora = new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City'));
$vencimiento = BusinessHoursHelper::calcularVencimientoCarta($ahora);

PracticasModel::mdlUpdateVencimientoCarta($folio, $vencimiento->format('Y-m-d H:i:s'));

$mesesNombres = ['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril','May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto','September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre'];
$fechaVencimientoLegible = str_replace(array_keys($mesesNombres), array_values($mesesNombres), $vencimiento->format('j \d\e F \d\e Y \a \l\a\s H:i'));

sendPpCartaPresentacionGenerada($user['email'], $studentName, $fechaVencimientoLegible, 2);

// 3) Leer configuración JSON editable
$cfgPath = __DIR__ . '/../../config/carta_practicas_config.json';
$cfg = [];
if (file_exists($cfgPath)) {
  $decoded = json_decode(file_get_contents($cfgPath), true);
  if (is_array($decoded))
    $cfg = $decoded;
}

// Helpers para leer el JSON con dot-notation
$g = function (array $obj, string $path, $default = '') use (&$g) {
  $keys = explode('.', $path);
  $val = $obj;
  foreach ($keys as $k) {
    if (!is_array($val) || !array_key_exists($k, $val))
      return $default;
    $val = $val[$k];
  }
  return $val !== null ? $val : $default;
};

// Variables de configuración
$headerBarColor = $g($cfg, 'header.bar_color', '#006837');
$headerLogoUrl = $g($cfg, 'header.logo_url', 'https://encuesta.unimontrer.edu.mx/images/logomontrer.png');
$headerLogoWidth = (int) $g($cfg, 'layout.header_logo_width', 130);
$headerCityLine = $g($cfg, 'header.city_line', 'Morelia, Michoacán, México, a {{fecha}}.');
$headerSubject = $g($cfg, 'header.subject', 'Carta de Presentación de Prácticas Profesionales');
$showFolio = (bool) $g($cfg, 'header.show_folio', true);

$fontFamily = $g($cfg, 'layout.font_family', 'Arial, sans-serif');
$fontSize = (int) $g($cfg, 'layout.font_size_pt', 12);
$contentPadding = (int) $g($cfg, 'layout.content_padding_px', 30);

$sigLegend = $g($cfg, 'signature.legend', 'ATENTAMENTE');
$sigImgUrl = $g($cfg, 'signature.signature_img_url', 'https://servicioypracticas.unimontrer.edu.mx/view/assets/images/firmaOL.png');
$sigWidth = (int) $g($cfg, 'signature.signature_width', 200);
$sealImgUrl = $g($cfg, 'signature.seal_img_url', 'https://servicioypracticas.unimontrer.edu.mx/view/assets/images/sello%20practicas.png');
$sealWidth = (int) $g($cfg, 'signature.seal.width', 240);
$sealTop = (int) $g($cfg, 'signature.seal.top', -60);
$sealLeft = (int) $g($cfg, 'signature.seal.left_percent', 50);
$sealOpacity = (float) $g($cfg, 'signature.seal.opacity', 0.8);
$signerName = $g($cfg, 'signature.signer_name', 'MGH Karla Mariana Fonseca Munguia');
$signerRole = $g($cfg, 'signature.signer_role', 'Coordinadora de Prácticas Profesionales UNIMO');

$footerBarColor = $g($cfg, 'footer.bottom_bar_color', '#006837');
$footerLogoUrl = $g($cfg, 'footer.logo_url', 'https://servicios.unimontrer.edu.mx/view/assets/images/logo-color.png');
$footerLogoWidth = (int) $g($cfg, 'layout.footer_logo_width', 130);
$footerContact = $g($cfg, 'footer.contact_line', 'Tel. 52 (443) 324 0439 · contacto@unimontrer.edu.mx');
$footerBottomText = $g($cfg, 'footer.bottom_text', 'UNIVERSIDAD MONTRER · Universidad en movimiento · www.unimontrer.edu.mx');

// Convertir URLs de imágenes a data URI para Dompdf (sin red)
function imgToDataUri(string $url): string
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

$headerLogoUrl = imgToDataUri($headerLogoUrl);
$sigImgUrl = imgToDataUri($sigImgUrl);
$sealImgUrl = imgToDataUri($sealImgUrl);
$footerLogoUrl = imgToDataUri($footerLogoUrl);

// Cuerpo: usar paragraphs_html del JSON; reemplazar variables
$bodyRaw = $g($cfg, 'body.paragraphs_html', '');
if (empty($bodyRaw)) {
  // Fallback si aún no se ha configurado
  $bodyRaw = '<p>Por este medio, se hace constar que <strong>{{studentName}}</strong>, con matrícula número <strong>{{matricula}}</strong>, de la <strong>{{degreeName}}</strong> en esta Universidad, ha cumplido los requisitos para desarrollar sus prácticas profesionales y es de su interés realizarlo en la institución que usted dignamente representa, considerando que debe cumplir <strong>360 horas</strong> propias de su perfil profesional.</p><p>Las Prácticas Profesionales deben ser desarrolladas, conforme a lo establecido en el Programa de Prácticas Profesionales de Alumnos de Universidad Montrer, por tal motivo, se solicita por favor, sean emitidos o firmados los siguientes documentos:</p><ul><li>Carta de aceptación, contando con siete días naturales a partir de esta fecha, donde se indique el periodo y área destinada a realizar la práctica.</li><li>Firma del reporte parcial de 180 horas de actividades elaborado por el alumno.</li><li>Firma del reporte final de 360 horas de actividades elaborado por el alumno.</li><li>Firma del reporte de puntualidad y asistencia elaborado por el alumno.</li><li>Evaluación del desempeño del practicante al concluir 180 horas y al final de las mismas, mediante el formato digital que el suscrito hará llegar a través del correo electrónico al responsable en el Organismo Receptor.</li><li>Carta de conclusión de Prácticas Profesionales al término de las mismas, en escrito libre, indicando el periodo y número de horas cubiertas.</li></ul><p>Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo.</p>';
}

$bodyHtml = str_replace(
  ['{{studentName}}', '{{matricula}}', '{{degreeName}}', '{{genero}}', '{{fecha}}', '{{folio}}', '{{empresa}}', '{{cargoResponsable}}', '{{responsable}}'],
  [$studentName, $matricula, $degreeName, $genero, $fecha, $folio, $empresa, $cargoResponsable, $responsable],
  $bodyRaw
);

// Reemplazar variables en línea de ciudad/fecha
$headerCityLine = str_replace('{{fecha}}', $fecha, $headerCityLine);

// Folio en encabezado
$folioHtml = $showFolio ? "<div class=\"asunto\"><strong>{$folio}</strong></div>" : '';

// 4) Generar el HTML desde la configuración
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 0; }
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: {$fontFamily};
      font-size: {$fontSize}pt;
    }
    .header-bar {
      background-color: {$headerBarColor};
      height: 40px;
      width: 100%;
    }
    .contenido {
      padding: 10px {$contentPadding}px;
      padding-bottom: 100px;
      box-sizing: border-box;
    }
    table.header {
      width: 100%;
      border-bottom: 1px solid #ccc;
    }
    .header-left {
      width: {$headerLogoWidth}px;
      text-align: center;
    }
    .header-left img {
      width: {$headerLogoWidth}px;
    }
    .header-right {
      text-align: right;
      vertical-align: top;
    }
    .fecha {
      font-size: 11pt;
      color: #444;
    }
    .asunto {
      margin-top: 10px;
      font-weight: bold;
    }
    .receptor {
      margin-top: 15px;
      font-weight: bold;
      line-height: 1.5;
    }
    .cuerpo {
      margin-top: 20px;
      text-align: justify;
      line-height: 1.6;
      font-size: 11pt;
    }
    .cuerpo ul, .cuerpo ol {
      margin: 5px;
      padding: 0px 40px;
      list-style-type: disc;
      font-size: 9pt;
    }
    .cuerpo ul li, .cuerpo ol li {
      margin-bottom: 5px;
    }
    .cuerpo p { margin: 0 0 10px 0; }
    .firma {
      position: relative;
      text-align: center;
      margin-top: 30px;
    }
    .firma img.firma-img {
      width: {$sigWidth}px;
      margin-bottom: 10px;
      position: relative;
      z-index: 1;
    }
    .firma img.sello {
      position: absolute;
      top: {$sealTop}px;
      left: {$sealLeft}%;
      width: {$sealWidth}px;
      z-index: 2;
      opacity: {$sealOpacity};
    }
    .firmante {
      font-weight: bold;
      position: relative;
      z-index: 1;
    }
    .puesto {
      font-size: 10pt;
    }
    .footer {
      position: fixed;
      bottom: 30px;
      left: 0;
      right: 0;
      padding: 0 40px;
      box-sizing: border-box;
    }
    .footer-logo-linea {
      border-top: 1px solid #ccc;
      display: flex;
      align-items: center;
      font-size: 10pt;
      padding-top: 10px;
      color: #444;
    }
    .footer-logo-linea img {
      width: {$footerLogoWidth}px;
      margin-right: 10px;
    }
    .barra-inferior {
      background-color: {$footerBarColor};
      color: white;
      text-align: center;
      padding: 6px 0;
      font-size: 9pt;
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
    }
  </style>
</head>
<body>
  <div class="header-bar"></div>

  <div class="contenido">
    <table class="header">
      <tr>
        <td class="header-left">
          <img src="{$headerLogoUrl}" alt="Logo UNIMO">
        </td>
        <td class="header-right">
          <div class="fecha">{$headerCityLine}</div>
          <div class="asunto">Asunto: <strong>{$headerSubject}</strong></div>
          {$folioHtml}
        </td>
      </tr>
    </table>

    <div class="receptor">
      {$responsable}<br><br>
      {$empresa}<br>
      <strong>Presente</strong>
    </div>

    <div class="cuerpo">
      {$bodyHtml}
    </div>

    <div class="firma">
      {$sigLegend}<br>
      <img src="{$sigImgUrl}" alt="Firma" class="firma-img"><br>
      <img src="{$sealImgUrl}" alt="Sello de Prácticas Profesionales" class="sello">
      <div class="firmante">{$signerName}</div>
      <div class="puesto">{$signerRole}</div>
    </div>
  </div>

  <div class="footer">
    <div class="footer-logo-linea">
      <img src="{$footerLogoUrl}" alt="Logo UNIMO">
      <div>{$footerContact}</div>
    </div>
  </div>

  <div class="barra-inferior">
    {$footerBottomText}
  </div>
</body>
</html>
HTML;

// 4) Generar el PDF
$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->setIsRemoteEnabled(true);
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Carta_Practicas_Profesionales_{$matricula}.pdf", [
  "Attachment" => false
]);
exit;
