<?php
/**
 * FASE 6 · Generador reutilizable de la Carta de Presentación (SIN vigencia).
 * Genera el PDF, lo persiste en disco y actualiza cartas_practicas_profesionales
 * (pdf_path + idPractica, sin fecha_vencimiento).
 *
 * Lo usan:
 *   - controller/practices/generarCartaPresentacion.php (descarga del alumno, stream=true)
 *   - PracticasController::ctrProgramarEntrevista (generación al aceptar la entrevista)
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/conection.php';
require_once __DIR__ . '/../../model/PracticasModel.php';
require_once __DIR__ . '/membreteCarta.php';

use Dompdf\Dompdf;

// ppImgToDataUri(), ppFechaLarga() y ppRenderPdf() viven en membreteCarta.php:
// las comparten las cartas de Prácticas y las de Servicio Social.

if (!function_exists('ppCargarConfigCarta')) {
    /** Configuración editable de la carta de presentación (JSON del editor). */
    function ppCargarConfigCarta(): array
    {
        $path = __DIR__ . '/../../config/carta_practicas_config.json';
        if (!file_exists($path)) return [];
        $decoded = json_decode(file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }
}

/**
 * Arma el HTML de la carta de presentación. No toca la base de datos ni el
 * disco, así que sirve igual para la generación real y para la vista previa
 * con datos de prueba (controller/practices/previewCartaPP.php).
 *
 * @param array $d   studentName, matricula, degreeName, genero, fecha, folio,
 *                   empresa, cargoResponsable, responsable.
 * @param array $cfg Configuración del editor.
 */
function construirCartaPresentacionHtml(array $d, array $cfg): string
{
    $studentName      = (string) ($d['studentName'] ?? '');
    $matricula        = (string) ($d['matricula'] ?? '');
    $degreeName       = (string) ($d['degreeName'] ?? '');
    $genero           = (string) ($d['genero'] ?? 'la');
    $fecha            = (string) ($d['fecha'] ?? '');
    $folio            = (string) ($d['folio'] ?? '');
    $empresa          = (string) ($d['empresa'] ?? '');
    $cargoResponsable = (string) ($d['cargoResponsable'] ?? '');
    $responsable      = (string) ($d['responsable'] ?? '');

    $g = function (array $obj, string $path, $default = '') {
        $keys = explode('.', $path);
        $val = $obj;
        foreach ($keys as $k) {
            if (!is_array($val) || !array_key_exists($k, $val)) {
                return $default;
            }
            $val = $val[$k];
        }
        return $val !== null ? $val : $default;
    };

    $headerCityLine = $g($cfg, 'header.city_line', 'Morelia, Michoacán, México, a {{fecha}}.');
    $headerSubject = $g($cfg, 'header.subject', 'Carta de Presentación de Prácticas Profesionales');
    $showFolio = (bool) $g($cfg, 'header.show_folio', true);

    $fontFamily = $g($cfg, 'layout.font_family', 'Arial, sans-serif');
    $fontSize = (float) $g($cfg, 'layout.font_size_pt', 11);

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

    $sigImgUrl = ppImgToDataUri($sigImgUrl);
    $sealImgUrl = ppImgToDataUri($sealImgUrl);

    // Membrete institucional (barra lateral + barra verde), repetido en cada hoja.
    $membreteImg = ppMembreteImgTag('ppImgToDataUri');
    $membreteCss = ppMembreteCss();

    $bodyRaw = $g($cfg, 'body.paragraphs_html', '');
    if (empty($bodyRaw)) {
        $bodyRaw = '<p>Por este medio, se hace constar que <strong>{{studentName}}</strong>, con matrícula número <strong>{{matricula}}</strong>, de la <strong>{{degreeName}}</strong> en esta Universidad, ha cumplido los requisitos para desarrollar sus prácticas profesionales y es de su interés realizarlo en la institución que usted dignamente representa, considerando que debe cumplir <strong>360 horas</strong> propias de su perfil profesional.</p><p>Las Prácticas Profesionales deben ser desarrolladas, conforme a lo establecido en el Programa de Prácticas Profesionales de Alumnos de Universidad Montrer, por tal motivo, se solicita por favor, sean emitidos o firmados los siguientes documentos:</p><ul><li>Carta de aceptación, contando con siete días naturales a partir de esta fecha, donde se indique el periodo y área destinada a realizar la práctica.</li><li>Firma del reporte parcial de 180 horas de actividades elaborado por el alumno.</li><li>Firma del reporte final de 360 horas de actividades elaborado por el alumno.</li><li>Firma del reporte de puntualidad y asistencia elaborado por el alumno.</li><li>Evaluación del desempeño del practicante al concluir 180 horas y al final de las mismas, mediante el formato digital que el suscrito hará llegar a través del correo electrónico al responsable en el Organismo Receptor.</li><li>Carta de conclusión de Prácticas Profesionales al término de las mismas, en escrito libre, indicando el periodo y número de horas cubiertas.</li></ul><p>Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo.</p>';
    }

    $bodyHtml = str_replace(
        ['{{studentName}}', '{{matricula}}', '{{degreeName}}', '{{genero}}', '{{fecha}}', '{{folio}}', '{{empresa}}', '{{cargoResponsable}}', '{{responsable}}'],
        [$studentName, $matricula, $degreeName, $genero, $fecha, $folio, $empresa, $cargoResponsable, $responsable],
        $bodyRaw
    );

    $headerCityLine = str_replace('{{fecha}}', $fecha, $headerCityLine);
    $folioHtml = $showFolio ? "<div class=\"folio\"><strong>Folio:</strong> {$folio}</div>" : '';

    // Separación entre bloques: en la plantilla las líneas van a 12.66 pt y los
    // bloques a 26.6 pt, es decir un renglón en blanco (≈14 pt) entre uno y otro.
    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
{$membreteCss}
    /* Ojo: no se puede resetear el margen de html/body. Dompdf implementa los
       márgenes de @page sobre esas cajas y un `body { margin: 0 }` los anula,
       dejando el texto encima de la barra lateral del membrete. */
    body {
      font-family: {$fontFamily};
      font-size: {$fontSize}pt;
      line-height: 1.15;
      color: #000;
    }

    /* ── Encabezado: fecha, asunto y folio alineados a la derecha ── */
    .meta { text-align: right; }
    .meta .asunto { margin-top: 14pt; }

    .receptor { margin-top: 14pt; font-weight: bold; }
    .presente { margin-top: 14pt; font-weight: bold; }

    /* ── Cuerpo ──
       La barra lateral del membrete deja una columna de 399.5 pt, un 27% más
       angosta que el diseño anterior. Con el texto actual (6 viñetas) la carta
       se iría a una segunda hoja, así que la lista va a 9.5pt —como en el
       diseño previo— y la separación entre bloques se ajusta a 12pt. */
    .cuerpo { margin-top: 14pt; text-align: justify; }
    .cuerpo p { margin: 0 0 12pt; }
    .cuerpo ul, .cuerpo ol { margin: 0 0 12pt; padding-left: 30pt; list-style-type: disc; font-size: 9.5pt; }
    .cuerpo li { margin-bottom: 3pt; text-align: justify; }
    .cuerpo strong { font-weight: bold; }

    /* ── Firma ── */
    .firma { position: relative; text-align: center; margin-top: 14pt; page-break-inside: avoid; }
    /* La firma va en su propio renglón: si comparte línea con la leyenda,
       Dompdf centra el conjunto y la imagen queda desplazada a la derecha. */
    .firma-media { margin: 6pt 0; }
    .firma img.firma-img { width: {$sigWidth}px; position: relative; z-index: 1; }
    .firma img.sello { position: absolute; top: {$sealTop}px; left: {$sealLeft}%; width: {$sealWidth}px; z-index: 2; opacity: {$sealOpacity}; }
    .firmante { font-weight: bold; position: relative; z-index: 1; }
    .puesto { font-weight: bold; }
  </style>
</head>
<body>
  {$membreteImg}

  <div class="meta">
    <div class="fecha">{$headerCityLine}</div>
    <div class="asunto"><strong>Asunto:</strong> {$headerSubject}</div>
    {$folioHtml}
  </div>

  <div class="receptor">
    {$responsable}<br>
    {$cargoResponsable}<br>
    {$empresa}
  </div>
  <div class="presente">Presente.</div>

  <div class="cuerpo">{$bodyHtml}</div>

  <div class="firma">
    <div class="firma-legend">{$sigLegend}</div>
    <div class="firma-media"><img src="{$sigImgUrl}" alt="Firma" class="firma-img"></div>
    <img src="{$sealImgUrl}" alt="Sello de Prácticas Profesionales" class="sello">
    <div class="firmante">{$signerName}</div>
    <div class="puesto">{$signerRole}</div>
  </div>
</body>
</html>
HTML;

    return $html;
}

/**
 * Genera y persiste la carta de presentación (sin vigencia).
 * $o admite: idStudent, idPractica, empresa, cargoResponsable, nombreResponsable, stream(bool).
 * Devuelve la ruta absoluta del PDF guardado, o null si falla. Si stream=true, hace stream y termina.
 */
function generarCartaPresentacionPP(array $o): ?string
{
    $studentId = (int) ($o['idStudent'] ?? 0);
    $idPractica = (int) ($o['idPractica'] ?? 0);
    $stream = (bool) ($o['stream'] ?? false);

    $stud = PracticasModel::mdlGetStudentPracticesById($studentId);
    if (!$stud) {
        return null;
    }

    $matricula = $stud['matricula'] ?? '';

    // Folio (crea/reutiliza la fila de la carta). SIN vigencia en el nuevo flujo.
    $folio = PracticasModel::generateFolioPracticas($studentId);

    $html = construirCartaPresentacionHtml([
        'studentName'      => strtoupper(trim((string) ($stud['nombre_completo'] ?? ''))),
        'matricula'        => $matricula,
        'degreeName'       => 'LICENCIATURA EN ' . strtoupper($stud['programa_academico'] ?? 'DESCONOCIDA'),
        'genero'           => (($stud['genero'] ?? '') === 'Masculino') ? 'el' : 'la',
        'fecha'            => ppFechaLarga(),
        'folio'            => $folio,
        'empresa'          => mb_strtoupper($o['empresa'] ?? ''),
        'cargoResponsable' => mb_strtoupper($o['cargoResponsable'] ?? ''),
        'responsable'      => mb_strtoupper($o['nombreResponsable'] ?? ''),
    ], ppCargarConfigCarta());

    $dompdf = ppRenderPdf($html);

    // Persistir el PDF a disco para poder adjuntarlo en correos
    $dir = __DIR__ . '/../../uploads/cartas_presentacion';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $safeFolio = preg_replace('/[^A-Za-z0-9_-]/', '_', $folio);
    $path = $dir . '/' . $studentId . '_' . $safeFolio . '.pdf';
    file_put_contents($path, $dompdf->output());

    // Guardar ruta + vacante (sin vigencia)
    PracticasModel::mdlGuardarCartaPdf($folio, $path, $idPractica);

    if ($stream) {
        $dompdf->stream("Carta_Practicas_Profesionales_{$matricula}.pdf", ["Attachment" => false]);
        exit;
    }
    return $path;
}
