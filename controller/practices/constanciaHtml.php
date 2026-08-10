<?php
/**
 * Armado del HTML de la Constancia de Acreditación de Prácticas Profesionales.
 *
 * Vive aparte del generador porque no toca la base de datos ni el disco: así lo
 * usan igual la generación real (generarConstanciaAcreditacion.php, que además
 * consume folio y marca las prácticas como finalizadas) y la vista previa con
 * datos de prueba (previewCartaPP.php).
 */
declare(strict_types=1);

require_once __DIR__ . '/membreteCarta.php';

if (!function_exists('constanciaImgToDataUri')) {
    /** Convierte una URL de imagen a data URI para que Dompdf la renderice sin red. */
    function constanciaImgToDataUri(string $url): string
    {
        if ($url === '') return '';

        // Intentar resolver localmente: extrae el nombre de archivo y busca en assets/images
        $filename = basename((string) parse_url($url, PHP_URL_PATH));
        $localPath = __DIR__ . '/../../view/assets/images/' . $filename;

        $data = file_exists($localPath) ? file_get_contents($localPath) : @file_get_contents($url);
        if ($data === false || $data === '') return $url;

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}

if (!function_exists('construirConstanciaHtml')) {
    /**
     * @param array $d   studentName, matricula, degreeName, generoArt,
     *                   nameOrganismo, fecha, folio.
     * @param array $cfg Configuración del editor (config/constancia_config.json).
     */
    function construirConstanciaHtml(array $d, array $cfg): string
    {
        $studentName   = (string) ($d['studentName'] ?? '');
        $matricula     = (string) ($d['matricula'] ?? '');
        $degreeName    = (string) ($d['degreeName'] ?? '');
        $generoArt     = (string) ($d['generoArt'] ?? 'el alumno');
        $nameOrganismo = (string) ($d['nameOrganismo'] ?? '');
        $fecha         = (string) ($d['fecha'] ?? '');
        $folio         = (string) ($d['folio'] ?? '');

        $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        // Cuerpo: preferir paragraphs_html; si no, construir desde paragraphs[]
        $bodyHtml = (string) ($cfg['body']['paragraphs_html'] ?? '');
        if ($bodyHtml === '' && !empty($cfg['body']['paragraphs']) && is_array($cfg['body']['paragraphs'])) {
            $bodyHtml = implode('', array_map(static fn($p) => '<p>' . (string) $p . '</p>', $cfg['body']['paragraphs']));
        }
        $bodyHtml = strtr($bodyHtml, [
            '{{studentName}}'   => $e($studentName),
            '{{matricula}}'     => $e($matricula),
            '{{degreeName}}'    => $e($degreeName),
            '{{generoArt}}'     => $e($generoArt),
            '{{nameOrganismo}}' => $e($nameOrganismo),
            '{{fecha}}'         => $e($fecha),
            '{{folio}}'         => $e($folio),
            '{{summary_hours}}' => (string) ($cfg['summary']['hours'] ?? 360),
        ]);

        // Textos de UI. El logo, el contacto y la barra inferior ya forman parte
        // del membrete institucional, por eso esas claves ya no se leen.
        $subject   = $e($cfg['header']['subject'] ?? 'Constancia de Acreditación de Prácticas Profesionales');
        $recName   = $e($cfg['recipient']['name'] ?? '');
        $recRole   = $e($cfg['recipient']['role'] ?? '');
        $recOrg    = $e($cfg['recipient']['org'] ?? '');
        $cond      = $e($cfg['summary']['condition'] ?? 'ACREDITADO');
        $titleCard = $e($cfg['summary']['title'] ?? 'Resumen de acreditación');
        $firmante  = $e($cfg['signature']['signer_name'] ?? '');
        $puesto    = $e($cfg['signature']['signer_role'] ?? '');
        $legend    = $e($cfg['signature']['legend'] ?? 'ATENTAMENTE');

        $hours      = (int) ($cfg['summary']['hours'] ?? 360);
        $fontSizePt = (float) ($cfg['layout']['font_size_pt'] ?? 11);
        $firmaW     = (int) ($cfg['signature']['signature_width'] ?? 200);
        $sealW      = (int) ($cfg['signature']['seal']['width'] ?? 220);
        $sealTop    = (int) ($cfg['signature']['seal']['top'] ?? -52);
        $sealLeftP  = (float) ($cfg['signature']['seal']['left_percent'] ?? 60);
        $sealOp     = (float) ($cfg['signature']['seal']['opacity'] ?? 0.85);
        $fontFamily = (string) ($cfg['layout']['font_family'] ?? 'Arial, Helvetica, sans-serif');

        $firmaURL = constanciaImgToDataUri((string) ($cfg['signature']['signature_img_url'] ?? ''));
        $sealURL  = constanciaImgToDataUri((string) ($cfg['signature']['seal_img_url'] ?? ''));

        // Membrete institucional, el mismo de la carta de presentación.
        $membreteImg = ppMembreteImgTag('constanciaImgToDataUri');
        $membreteCss = ppMembreteCss();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  :root{
    --ink:#111827; --muted:#6b7280; --line:#e5e7eb; --bg-chip:#f3f7f5;
  }
{$membreteCss}
  /* Ojo: no se puede resetear el margen de html/body. Dompdf implementa los
     márgenes de @page sobre esas cajas y un `body { margin: 0 }` los anula,
     dejando el texto encima de la barra lateral del membrete. */
  body{ font-family: {$fontFamily}; color: var(--ink); font-size: {$fontSizePt}pt; line-height: 1.15; }

  /* Encabezado: fecha, asunto y folio alineados a la derecha, como la plantilla */
  .encabezado{ text-align: right; }
  .fecha{ color: var(--muted); }
  .asunto{ font-weight: bold; margin-top: 14pt; }
  .folio{ margin-top: 2pt; }

  .card{ border:1px solid var(--line); border-radius:12px; background:var(--bg-chip); padding:14px; margin: 18px 0 8px 0; }
  .card-title{ font-weight:bold; font-size:11pt; margin-bottom:10px; letter-spacing:.2px; }
  .grid{ width:100%; border-collapse: collapse; }
  .grid td{ width:50%; padding:6px 8px; vertical-align: top; }
  .label{ font-size:10pt; color:var(--muted); display:block; margin-bottom:2px; }
  .value{ font-weight: bold; font-size: 11pt; }

  .receptor{ margin-top:14pt; font-weight: bold; }
  .presente{ margin-top: 14pt; font-weight: bold; }

  .cuerpo{ margin-top:14pt; text-align: justify; }
  .cuerpo p{ margin: 0 0 12pt; }

  /* La firma fluye con el texto: antes iba anclada al pie, que ahora ocupa la
     barra verde del membrete. La imagen va en su propio renglón porque si
     comparte línea con la leyenda, Dompdf centra el conjunto y la descuadra. */
  .firma{ position: relative; text-align: center; margin-top: 14pt; page-break-inside: avoid; }
  .firma .cta{ letter-spacing:1.5px; color:var(--muted); font-size:9pt; }
  .firma-media{ margin: 6pt 0; }
  .firma img.firma-img{ width: {$firmaW}px; position: relative; z-index: 1; }
  .firma img.sello{
    position: absolute; top: {$sealTop}px; left: {$sealLeftP}%;
    width: {$sealW}px; z-index: 2; opacity: {$sealOp};
  }
  .firmante{ font-weight:700; }
  .puesto{ font-size:10pt; color: var(--muted); }
</style>
</head>
<body>

  {$membreteImg}

  <div class="encabezado">
    <div class="fecha">Emitido el {$fecha}</div>
    <div class="asunto">{$subject}</div>
HTML;

        if (!empty($cfg['header']['show_folio'])) {
            $html .= '<div class="folio"><strong>Folio:</strong> ' . $e($folio) . '</div>';
        }
        $html .= '</div>';

        // Tarjeta de resumen opcional
        if (!empty($cfg['summary']['show_card'])) {
            $alumno = $e($studentName);
            $matri  = $e($matricula);
            $prog   = $e($degreeName);
            $org    = $e($nameOrganismo);

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

        $html .= <<<HTML
    <div class="receptor">
      {$recName}<br>
      {$recRole}<br>
      {$recOrg}
      <div class="presente">Presente</div>
    </div>

    <div class="cuerpo">{$bodyHtml}</div>

    <div class="firma">
      <div class="cta">{$legend}</div>
HTML;

        if ($firmaURL !== '') {
            $html .= '<div class="firma-media"><img src="' . $e($firmaURL) . '" alt="Firma" class="firma-img"></div>';
        }
        if ($sealURL !== '') {
            $html .= '<img src="' . $e($sealURL) . '" alt="Sello" class="sello">';
        }

        $html .= <<<HTML
      <div class="firmante">{$firmante}</div>
      <div class="puesto">{$puesto}</div>
    </div>

</body>
</html>
HTML;

        return $html;
    }
}
