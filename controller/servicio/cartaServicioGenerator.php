<?php
/**
 * Cartas de Servicio Social sobre la plantilla institucional: presentación,
 * aceptación y conclusión.
 *
 * Usan exactamente la misma hoja membretada que los documentos de Prácticas
 * Profesionales (controller/practices/membreteCarta.php): barra lateral con el
 * escudo, los domicilios de los campus y el QR, más la barra verde inferior.
 * Por eso aquí ya no se arma encabezado ni pie de página propios: los aportaba
 * el diseño anterior (franja de color + logo + pie gris) y ahora vienen de la
 * plantilla, igual que en Prácticas.
 *
 * Lo que sí sigue siendo configurable desde el panel es lo mismo de antes:
 * línea de ciudad/fecha, asunto, folio, destinatario, cuerpo, firma y sello.
 *
 * Estas funciones sólo arman HTML —no tocan base de datos ni disco—, así que las
 * comparten la generación real y la vista previa con datos de prueba
 * (controller/servicio/previewCartaSS.php).
 */
declare(strict_types=1);

require_once __DIR__ . '/../practices/membreteCarta.php';

if (!function_exists('ssCargarConfig')) {
    /** Configuración editable del documento (JSON que guarda el panel). */
    function ssCargarConfig(string $rutaAbsoluta): array
    {
        if (!file_exists($rutaAbsoluta)) {
            return [];
        }
        $json = (string) file_get_contents($rutaAbsoluta);
        if (str_starts_with($json, "\xEF\xBB\xBF")) {
            $json = substr($json, 3);
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('ssCfg')) {
    /** Lectura segura con ruta por puntos: ssCfg($cfg, 'header.subject', '…'). */
    function ssCfg(array $cfg, string $path, $default = '')
    {
        $val = $cfg;
        foreach (explode('.', $path) as $k) {
            if (!is_array($val) || !array_key_exists($k, $val)) {
                return $default;
            }
            $val = $val[$k];
        }
        return $val ?? $default;
    }
}

if (!function_exists('ssCuerpoHtml')) {
    /**
     * Cuerpo de la carta con las variables ya sustituidas.
     *
     * Admite los dos formatos que ha guardado el editor: `body.paragraphs_html`
     * (HTML enriquecido, permite listas) y el arreglo `body.paragraphs` de las
     * configuraciones anteriores. Si no hay ninguno, usa el texto por defecto.
     *
     * @param string[] $porDefecto Párrafos de respaldo, sin envolver en <p>.
     */
    function ssCuerpoHtml(array $cfg, array $vars, array $porDefecto): string
    {
        $html = (string) ssCfg($cfg, 'body.paragraphs_html', '');

        if (trim($html) === '') {
            $parrafos = ssCfg($cfg, 'body.paragraphs', []);
            if (!is_array($parrafos) || $parrafos === []) {
                $parrafos = $porDefecto;
            }
            $html = '';
            foreach ($parrafos as $p) {
                $html .= '<p>' . (string) $p . '</p>';
            }
        }

        return ppSustituirVars($html, $vars);
    }
}

if (!function_exists('ssCartaHtml')) {
    /**
     * Esqueleto común de las cartas de Servicio Social, calcado del de Prácticas
     * (controller/practices/cartaPresentacionGenerator.php) para que los cuatro
     * documentos salgan idénticos de formato.
     *
     * @param array $o  subject_default, receptor (string[] de líneas), cuerpo (HTML).
     */
    function ssCartaHtml(array $cfg, array $vars, array $o): string
    {
        $folio = (string) ($vars['{{folio}}'] ?? '');

        $headerCityLine = ppSustituirVars((string) ssCfg($cfg, 'header.city_line', 'Morelia, Michoacán, México, a {{fecha}}.'), $vars);
        $headerSubject  = ppSustituirVars((string) ssCfg($cfg, 'header.subject', (string) ($o['subject_default'] ?? '')), $vars);
        $showFolio      = (bool) ssCfg($cfg, 'header.show_folio', true);

        $fontFamily = (string) ssCfg($cfg, 'layout.font_family', 'Arial, sans-serif');
        $fontSize   = (float) ssCfg($cfg, 'layout.font_size_pt', 11);

        $sigLegend   = (string) ssCfg($cfg, 'signature.legend', 'ATENTAMENTE');
        $sigImgUrl   = ppImgToDataUri((string) ssCfg($cfg, 'signature.signature_img_url', ''));
        $sigWidth    = (int) ssCfg($cfg, 'signature.signature_width', 200);
        $sealImgUrl  = ppImgToDataUri((string) ssCfg($cfg, 'signature.seal_img_url', ''));
        $sealWidth   = (int) ssCfg($cfg, 'signature.seal.width', 240);
        $sealTop     = (int) ssCfg($cfg, 'signature.seal.top', -60);
        $sealLeft    = (int) ssCfg($cfg, 'signature.seal.left_percent', 50);
        $sealOpacity = (float) ssCfg($cfg, 'signature.seal.opacity', 0.8);
        $signerName  = (string) ssCfg($cfg, 'signature.signer_name', '');
        $signerRole  = (string) ssCfg($cfg, 'signature.signer_role', '');

        // Membrete institucional, repetido en cada hoja.
        $membreteImg = ppMembreteImgTag('ppImgToDataUri');
        $membreteCss = ppMembreteCss();

        $asuntoHtml = trim($headerSubject) !== ''
            ? "<div class=\"asunto\"><strong>Asunto:</strong> {$headerSubject}</div>"
            : '';
        $folioHtml = ($showFolio && $folio !== '')
            ? "<div class=\"folio\"><strong>Folio:</strong> {$folio}</div>"
            : '';

        $lineasReceptor = array_values(array_filter(
            array_map('trim', (array) ($o['receptor'] ?? [])),
            static fn(string $l): bool => $l !== ''
        ));
        $receptorHtml = implode('<br>', $lineasReceptor);

        $cuerpoHtml = (string) ($o['cuerpo'] ?? '');

        // La firma sólo se dibuja si hay imagen: si no, quedaría un hueco.
        $firmaImgHtml = $sigImgUrl !== ''
            ? "<div class=\"firma-media\"><img src=\"{$sigImgUrl}\" alt=\"Firma\" class=\"firma-img\"></div>"
            : '';
        $selloImgHtml = $sealImgUrl !== ''
            ? "<img src=\"{$sealImgUrl}\" alt=\"Sello\" class=\"sello\">"
            : '';

        return <<<HTML
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
       La barra lateral del membrete deja una columna de 399.5 pt, bastante más
       angosta que el diseño anterior, por eso las listas van a 9.5 pt. */
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
    {$asuntoHtml}
    {$folioHtml}
  </div>

  <div class="receptor">{$receptorHtml}</div>
  <div class="presente">Presente.</div>

  <div class="cuerpo">{$cuerpoHtml}</div>

  <div class="firma">
    <div class="firma-legend">{$sigLegend}</div>
    {$firmaImgHtml}
    {$selloImgHtml}
    <div class="firmante">{$signerName}</div>
    <div class="puesto">{$signerRole}</div>
  </div>
</body>
</html>
HTML;
    }
}

if (!function_exists('construirCartaPresentacionServicioHtml')) {
    /**
     * Carta de Presentación de Servicio Social.
     *
     * @param array $d studentName, matricula, degreeName, genero ('el'/'la', de
     *                 donde sale también {{alumnoGenero}}), fecha, folio,
     *                 nameUR, responsable, cargo, domicilio.
     */
    function construirCartaPresentacionServicioHtml(array $d, array $cfg): string
    {
        $genero = (string) ($d['genero'] ?? 'el');

        $vars = [
            '{{studentName}}'  => (string) ($d['studentName'] ?? ''),
            '{{matricula}}'    => (string) ($d['matricula'] ?? ''),
            '{{degreeName}}'   => (string) ($d['degreeName'] ?? ''),
            '{{genero}}'       => $genero,
            '{{alumnoGenero}}' => ppAlumnoGenero($genero),
            '{{fecha}}'        => (string) ($d['fecha'] ?? ''),
            '{{folio}}'        => (string) ($d['folio'] ?? ''),
            '{{nameUR}}'       => (string) ($d['nameUR'] ?? ''),
            '{{responsable}}'  => (string) ($d['responsable'] ?? ''),
            '{{domicilio}}'    => (string) ($d['domicilio'] ?? ''),
        ];

        $cuerpo = ssCuerpoHtml($cfg, $vars, [
            'Por este medio, se hace constar que <strong>{{studentName}}</strong>, con matrícula: <strong>{{matricula}}</strong>, de la <strong>{{degreeName}}</strong> en esta Universidad, ha cumplido los requisitos para desarrollar el <strong>Servicio Social</strong> y es de su interés realizarlo en la institución que usted dignamente representa, considerando que debe cumplir <strong>480 horas</strong> en un periodo de <strong>6 meses</strong>.',
            'El Servicio Social debe ser desarrollado, conforme a lo establecido por el Organismo Público del Gobierno del Estado de Michoacán encargado de regularlo, actualmente denominado <strong>Instituto de la Juventud Michoacana</strong>, por tal motivo, se solicita por favor, sea emitida la <strong>Carta de Aceptación</strong> dirigida al <strong>Lic. Alejandro Cruz Ferreyra, Subdirector de Servicio Social y Pasantes</strong>, indicando el número de horas a cubrir mencionado en el párrafo anterior, así como la fecha de inicio y término.',
            'Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo.',
        ]);

        // El destinatario sale del registro del alumno (organismo receptor), igual
        // que antes: cargo, responsable, organismo y domicilio.
        $receptor = [
            (string) ($d['cargo'] ?? ''),
            (string) ($d['responsable'] ?? ''),
            (string) ($d['nameUR'] ?? ''),
            (string) ($d['domicilio'] ?? ''),
        ];

        return ssCartaHtml($cfg, $vars, [
            'subject_default' => 'Carta de Presentación de Servicio Social',
            'receptor'        => $receptor,
            'cuerpo'          => $cuerpo,
        ]);
    }
}

if (!function_exists('construirCartaAceptacionServicioHtml')) {
    /**
     * Carta de Aceptación de Servicio Social.
     *
     * @param array $d studentName, matricula, degreeName, genero ('el'/'la', de
     *                 donde sale también {{alumnoGenero}}), fecha, folio,
     *                 gradoTexto, horas, meses, fechaInicio, fechaTermino.
     */
    function construirCartaAceptacionServicioHtml(array $d, array $cfg): string
    {
        $genero = (string) ($d['genero'] ?? 'el');

        $vars = [
            '{{studentName}}'  => (string) ($d['studentName'] ?? ''),
            '{{matricula}}'    => (string) ($d['matricula'] ?? ''),
            '{{degreeName}}'   => (string) ($d['degreeName'] ?? ''),
            '{{genero}}'       => $genero,
            '{{alumnoGenero}}' => ppAlumnoGenero($genero),
            '{{fecha}}'        => (string) ($d['fecha'] ?? ''),
            '{{folio}}'        => (string) ($d['folio'] ?? ''),
            '{{gradoTexto}}'   => (string) ($d['gradoTexto'] ?? ''),
            '{{horas}}'        => (string) ($d['horas'] ?? ''),
            '{{meses}}'        => (string) ($d['meses'] ?? ''),
            '{{fechaInicio}}'  => (string) ($d['fechaInicio'] ?? ''),
            '{{fechaTermino}}' => (string) ($d['fechaTermino'] ?? ''),
        ];

        $cuerpo = ssCuerpoHtml($cfg, $vars, [
            'En relación a la solicitud del <strong>{{studentName}}</strong>, quien curso el <strong>{{gradoTexto}}</strong> de la <strong>{{degreeName}}</strong> de <strong>UNIVERSIDAD MONTRER</strong>, con número de matrícula <strong>{{matricula}}</strong>, me permito informarle que ha sido aceptado en este organismo receptor denominado: <strong>UNIVERSIDAD MONTRER</strong>, para realizar el <strong>SERVICIO SOCIAL</strong> y cubrir un total de <strong>{{horas}} HRS</strong> en un periodo de <strong>{{meses}} MESES</strong> comprendido del <strong>{{fechaInicio}}</strong> al <strong>{{fechaTermino}}</strong>.',
            'Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo.',
        ]);

        // Aquí el destinatario es fijo (lo captura el admin en el panel), no viene
        // del registro del alumno.
        $receptor = [
            ppSustituirVars((string) ssCfg($cfg, 'recipient.nombre', 'Lic. Alejandro Cruz Ferreyra'), $vars),
            ppSustituirVars((string) ssCfg($cfg, 'recipient.cargo', 'Subdirector de Servicio Social y Pasantes'), $vars),
            ppSustituirVars((string) ssCfg($cfg, 'recipient.organismo', 'Instituto de la Juventud Michoacana'), $vars),
        ];

        return ssCartaHtml($cfg, $vars, [
            'subject_default' => 'Carta de aceptación de Servicio Social.',
            'receptor'        => $receptor,
            'cuerpo'          => $cuerpo,
        ]);
    }
}

if (!function_exists('construirCartaConclusionServicioHtml')) {
    /**
     * Carta de Conclusión de Servicio Social.
     *
     * Antes tenía diseño propio (barra verde, logo y pie gris configurables).
     * Ahora va sobre la misma hoja membretada que las otras dos cartas, así que
     * el encabezado y el pie ya no se editan en el panel: los pone la plantilla.
     *
     * @param array $d studentName, matricula, degreeName, genero ('el'/'la', de
     *                 donde sale también {{alumnoGenero}}), fecha, folio,
     *                 horas, meses, fechaInicio, fechaFin.
     */
    function construirCartaConclusionServicioHtml(array $d, array $cfg): string
    {
        $genero = (string) ($d['genero'] ?? 'el');

        $vars = [
            '{{studentName}}'  => (string) ($d['studentName'] ?? ''),
            '{{matricula}}'    => (string) ($d['matricula'] ?? ''),
            '{{degreeName}}'   => (string) ($d['degreeName'] ?? ''),
            '{{genero}}'       => $genero,
            '{{alumnoGenero}}' => ppAlumnoGenero($genero),
            '{{fecha}}'        => (string) ($d['fecha'] ?? ''),
            '{{folio}}'        => (string) ($d['folio'] ?? ''),
            '{{horas}}'        => (string) ($d['horas'] ?? ''),
            '{{meses}}'        => (string) ($d['meses'] ?? ''),
            '{{fechaInicio}}'  => (string) ($d['fechaInicio'] ?? ''),
            '{{fechaFin}}'     => (string) ($d['fechaFin'] ?? ''),
        ];

        $cuerpo = ssCuerpoHtml($cfg, $vars, [
            'En relación a la solicitud de <strong>{{studentName}}</strong>, de la <strong>{{degreeName}}</strong> de <strong>UNIVERSIDAD MONTRER</strong>, con número de matrícula <strong>{{matricula}}</strong>, me permito informarle que ha <strong>concluido</strong> el desarrollo del <strong>Servicio Social</strong> en este organismo receptor denominado <strong>Universidad Montrer</strong>, cubriendo un total <strong>{{horas}} hrs</strong> en un periodo de <strong>{{meses}} meses</strong> del <strong>{{fechaInicio}}</strong> al <strong>{{fechaFin}}</strong>.',
            'Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo.',
        ]);

        // Destinatario fijo, capturado por el admin en el panel (igual que en la
        // carta de aceptación).
        $receptor = [
            ppSustituirVars((string) ssCfg($cfg, 'recipient.nombre', 'Lic. Alejandro Cruz Ferreyra'), $vars),
            ppSustituirVars((string) ssCfg($cfg, 'recipient.cargo', 'Subdirector de Servicio Social y Pasantes'), $vars),
            ppSustituirVars((string) ssCfg($cfg, 'recipient.organismo', 'Instituto de la Juventud Michoacana'), $vars),
        ];

        return ssCartaHtml($cfg, $vars, [
            'subject_default' => 'Carta de conclusión de Servicio Social.',
            'receptor'        => $receptor,
            'cuerpo'          => $cuerpo,
        ]);
    }
}
