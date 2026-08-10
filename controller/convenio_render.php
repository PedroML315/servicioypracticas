<?php
/**
 * controller/convenio_render.php
 *
 * Renderizador compartido del Convenio de Prácticas Profesionales.
 * - convenioLoadConfig(): carga el JSON de configuración.
 * - convenioBuildHtml():  arma el HTML (membrete en cada hoja, paginación,
 *                         4 bloques de firma: 2 representantes + 2 testigos).
 * - convenioRenderPdf():  devuelve un Dompdf ya renderizado.
 *
 * MEMBRETE: el diseño institucional proviene de la plantilla
 * storage/templates/convenio_plantilla.pdf (tamaño Carta). Sus dos bandas se
 * extrajeron a view/assets/images/convenio_banda_{superior,inferior}.png y se
 * imprimen a sangre en cada hoja. No son configurables desde el editor: para
 * cambiar el diseño hay que reemplazar la plantilla y volver a extraer las
 * bandas con las medidas que documenta convenioBuildHtml().
 *
 * NO produce salida por sí mismo; lo usan generarConvenio.php (descarga)
 * y convenio-config.php (generación automática al guardar).
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

if (!function_exists('convenioLoadConfig')) {
    function convenioLoadConfig(string $path): array
    {
        if (!file_exists($path)) return [];
        $json = file_get_contents($path);
        if (str_starts_with($json, "\xEF\xBB\xBF")) $json = substr($json, 3);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('convenioVal')) {
    function convenioVal(array $arr, string $path, $default = '')
    {
        $tmp = $arr;
        foreach (explode('.', $path) as $k) {
            if (!is_array($tmp) || !array_key_exists($k, $tmp)) return $default;
            $tmp = $tmp[$k];
        }
        return $tmp;
    }
}

if (!function_exists('convenioImgToDataUri')) {
    function convenioImgToDataUri(string $url): string
    {
        if ($url === '') return '';
        $filename = rawurldecode(basename((string)parse_url($url, PHP_URL_PATH)));
        $localPath = __DIR__ . '/../view/assets/images/' . $filename;
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
}

if (!function_exists('convenioOrganismoVars')) {
    /**
     * Mapa de tokens {{...}} que se sustituyen con los datos del organismo
     * al generar un convenio personalizado. Extensible: agregar una entrada
     * aquí basta para exponer una nueva variable en la plantilla.
     *
     * @param array $org Fila de organismos_externos (puede venir vacía → tokens vacíos).
     */
    function convenioOrganismoVars(array $org): array
    {
        $g = fn(string $k) => trim((string) ($org[$k] ?? ''));

        // Domicilio compuesto a partir de las columnas de dirección.
        $partes = array_filter([
            $g('calle'),
            $g('colonia'),
            $g('ciudad'),
            $g('cp') !== '' ? 'C.P. ' . $g('cp') : '',
        ], fn($p) => $p !== '');
        $direccion = implode(', ', $partes);

        $telefono = $g('telefonos') !== '' ? $g('telefonos') : $g('tel_oficina');

        return [
            '{{nombre_empresa}}'      => $g('empresa'),
            '{{representante_legal}}' => $g('rep_legal'),
            '{{cargo_representante}}' => $g('cargo_legal'),
            '{{direccion_empresa}}'   => $direccion,
            '{{telefono}}'            => $telefono,
            '{{correo}}'              => $g('email'),
            '{{giro}}'                => $g('giro'),
            '{{ciudad}}'              => $g('ciudad'),
        ];
    }
}

if (!function_exists('convenioNumeroLetras')) {
    /** Convierte un entero 0..99 a palabras en español (para el año: "dos mil {n}"). */
    function convenioNumeroLetras(int $n): string
    {
        $unidades = [
            'cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez',
            'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte',
            'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve',
        ];
        if ($n < 0) return '';
        if ($n <= 29) return $unidades[$n];
        $decenas = [3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa'];
        $d = intdiv($n, 10);
        $u = $n % 10;
        if (!isset($decenas[$d])) return (string) $n;
        return $u === 0 ? $decenas[$d] : $decenas[$d] . ' y ' . $unidades[$u];
    }
}

if (!function_exists('convenioBuildHtml')) {
    /**
     * @param array    $c           Configuración del convenio.
     * @param int|null $totalPages  Total de páginas (segundo pase). Si es null,
     *                              el footer muestra solo el número de página
     *                              actual (primer pase para contar páginas).
     * @param array    $org         Datos del organismo para sustituir tokens
     *                              por-organismo ({{nombre_empresa}}, etc.). Vacío
     *                              → convenio genérico con espacios en blanco.
     */
    function convenioBuildHtml(array $c, ?int $totalPages = null, array $org = []): string
    {
        // La zona horaria de PHP no es necesariamente la local (php.ini puede traer
        // otra), y con un desfase de horas el convenio se fecharía al día siguiente.
        // Se fija explícitamente la de Morelia sin tocar la zona global del proceso.
        $hoy = new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City'));

        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $dia   = $hoy->format('j');
        $mes   = $meses[(int) $hoy->format('n') - 1];
        $fecha = $dia . ' de ' . $mes . ' de ' . $hoy->format('Y');

        // Fecha completa con estilo del convenio: "3 del mes de julio en el año de dos mil veintiséis".
        $anio       = (int) $hoy->format('Y');
        $anioLetras = $anio >= 2000 && $anio <= 2099
            ? 'dos mil ' . convenioNumeroLetras($anio - 2000)
            : (string) $anio;
        $fecha_larga = $dia . ' del mes de ' . $mes . ' en el año de ' . $anioLetras;

        $signer_name = (string) convenioVal($c, 'signature.signer_name', 'Noé Alonso González Herrera');
        $signer_role = (string) convenioVal($c, 'signature.signer_role', 'Representante Legal – Instituto Montrer, S.C.');

        // Tokens reemplazables en el cuerpo. Los tokens por-organismo
        // (si $org viene con datos) se fusionan sobre los de sistema.
        $vars = array_merge([
            '{{fecha}}'          => $fecha,
            '{{fecha_larga}}'    => $fecha_larga,
            '{{repUniversidad}}' => $signer_name,
        ], convenioOrganismoVars($org));

        $header_city_line = strtr((string) convenioVal($c, 'header.city_line', ''), $vars);
        $header_title     = (string) convenioVal($c, 'header.title', 'CONVENIO DE PRÁCTICAS PROFESIONALES');
        $content_html     = strtr((string) convenioVal($c, 'body.content_html', ''), $vars);

        $empresa_label  = strtr((string) convenioVal($c, 'signature.empresa_label', 'Representante Legal de “La Empresa”'), $vars);
        $testigo1_label = strtr((string) convenioVal($c, 'signature.testigo1_label', 'Testigo'), $vars);
        // Testigo de la Universidad: nombre configurable; fallback a la etiqueta genérica.
        $testigo_univ   = strtr((string) convenioVal($c, 'signature.testigo_universidad', ''), $vars);
        $testigo2_label = strtr((string) convenioVal($c, 'signature.testigo2_label', 'Testigo'), $vars);
        if (trim($testigo_univ) !== '') {
            $testigo2_label = $testigo_univ;
        }

        $footer_contact   = (string) convenioVal($c, 'footer.contact_line', '');

        $font_family     = (string) convenioVal($c, 'layout.font_family', 'Arial, Helvetica, sans-serif');
        $font_size_pt    = (int) convenioVal($c, 'layout.font_size_pt', 11);
        $content_padding = (int) convenioVal($c, 'layout.content_padding_px', 96);

        // Geometría del membrete, medida sobre la plantilla (hoja Carta, 816×1056 px):
        //   banda superior  → 72.3 pt = 96 px de alto, a sangre
        //   banda inferior  → 51 pt   = 68 px de alto, a sangre
        // La franja reservada añade aire para la línea de ciudad/fecha (arriba) y
        // para el contacto + paginación (abajo), que van SOBRE la banda inferior.
        $banda_sup_h = 96;
        $banda_inf_h = 68;
        $header_band = (int) convenioVal($c, 'layout.header_band_px', 130);
        $footer_band = (int) convenioVal($c, 'layout.footer_band_px', 105);
        // Las bandas son a sangre: el margen de página debe ser 0 o el membrete
        // dejaría de tocar los bordes de la hoja.
        $page_margin = 0;

        // counter(pages) no es fiable en esta versión de Dompdf: el total se
        // inyecta como literal en el segundo pase (ver convenioRenderPdf()).
        $pageInfoAfter = $totalPages !== null
            ? 'content: "Página " counter(page) " de ' . (int) $totalPages . '";'
            : 'content: "Página " counter(page);';

        // Bandas del membrete institucional. Si el PNG faltara, se omite la banda
        // en lugar de romper la generación del convenio.
        $banda = function (string $file, string $class, string $alt): string {
            if (!is_file(__DIR__ . '/../view/assets/images/' . $file)) return '';
            return '<img class="' . $class . '" src="' . convenioImgToDataUri($file) . '" alt="' . $alt . '">';
        };
        $banda_sup_img = $banda('convenio_banda_superior.png', 'banda-sup', 'Universidad Montrer');
        $banda_inf_img = $banda('convenio_banda_inferior.png', 'banda-inf', '');

        // Imágenes de firma (institucionales): representante y testigo de la Universidad.
        $signer_sig_url       = (string) convenioVal($c, 'signature.signer_sig_url', '');
        $testigo_univ_sig_url = (string) convenioVal($c, 'signature.testigo_universidad_sig_url', '');
        $signer_sig       = $signer_sig_url !== '' ? convenioImgToDataUri($signer_sig_url) : '';
        $testigo_univ_sig = $testigo_univ_sig_url !== '' ? convenioImgToDataUri($testigo_univ_sig_url) : '';
        $signer_sig_img       = $signer_sig ? '<img class="firma-img" src="' . $signer_sig . '" alt="Firma">' : '';
        $testigo_univ_sig_img = $testigo_univ_sig ? '<img class="firma-img" src="' . $testigo_univ_sig . '" alt="Firma testigo">' : '';

        // Escapes seguros para nombre/cargo del firmante
        $signer_name_e = htmlspecialchars($signer_name, ENT_QUOTES, 'UTF-8');
        $signer_role_e = htmlspecialchars($signer_role, ENT_QUOTES, 'UTF-8');
        $empresa_e     = htmlspecialchars($empresa_label, ENT_QUOTES, 'UTF-8');
        $testigo1_e    = htmlspecialchars($testigo1_label, ENT_QUOTES, 'UTF-8');
        $testigo2_e    = htmlspecialchars($testigo2_label, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page {
      margin-top: {$header_band}px;
      margin-bottom: {$footer_band}px;
      margin-left: {$page_margin}px;
      margin-right: {$page_margin}px;
    }
    body {
      margin: 0; padding: 0;
      font-family: {$font_family};
      font-size: {$font_size_pt}pt;
      color: #111;
    }

    /* ── HEADER (banda institucional a sangre, repetida en cada hoja) ── */
    header {
      position: fixed;
      top: -{$header_band}px;
      left: 0; right: 0;
      height: {$header_band}px;
    }
    .banda-sup { display: block; width: 100%; height: {$banda_sup_h}px; }
    .header-meta { padding: 9px {$content_padding}px 0; text-align: right; }
    .fecha { font-size: 9.5pt; color: #444; }

    /* ── FOOTER (contacto + paginación sobre la banda inferior) ── */
    footer {
      position: fixed;
      bottom: -{$footer_band}px;
      left: 0; right: 0;
      height: {$footer_band}px;
    }
    .footer-content { padding: 6px {$content_padding}px 0; }
    table.footer-tbl { width: 100%; border-collapse: collapse; }
    .footer-tbl td { vertical-align: middle; font-size: 8.5pt; color: #555; }
    /* 3 columnas de igual ancho: así la paginación queda centrada en la hoja
       aunque la línea de contacto de la izquierda esté presente o vacía. */
    .footer-contact { text-align: left; width: 33%; }
    .footer-page { text-align: center; font-weight: bold; white-space: nowrap; width: 34%; }
    .footer-page:after { {$pageInfoAfter} }
    .footer-spacer { width: 33%; }
    .banda-inf {
      display: block; width: 100%; height: {$banda_inf_h}px;
      position: absolute; bottom: 0; left: 0;
    }

    /* ── CONTENIDO ── */
    .contenido { padding: 0 {$content_padding}px; box-sizing: border-box; }

    .convenio-title {
      text-align: center; font-size: 14pt; font-weight: bold;
      text-transform: uppercase; margin: 0 0 20px; letter-spacing: 1px;
    }

    .cuerpo { line-height: 1.7; }
    .cuerpo p { margin: 0 0 10px; text-align: justify; }
    .cuerpo ol, .cuerpo ul { margin-bottom: 10px; padding-left: 20px; }
    .cuerpo strong { font-weight: bold; }
    .cuerpo em { font-style: italic; }
    /* Alineaciones que produce Quill */
    .cuerpo .ql-align-center, .cuerpo p[style*="text-align:center"], .cuerpo p[style*="text-align: center"] { text-align: center; }
    .cuerpo .ql-align-right,  .cuerpo p[style*="text-align:right"],  .cuerpo p[style*="text-align: right"]  { text-align: right; }
    .cuerpo .ql-align-justify { text-align: justify; }

    /* ── SECCIÓN DE FIRMAS AUTÓGRAFAS (4 bloques) ── */
    .firmas-section { margin-top: 55px; padding-top: 10px; page-break-inside: avoid; }
    .firmas-title {
      text-align: center; font-size: 10pt; font-weight: bold;
      text-transform: uppercase; letter-spacing: .8px; margin-bottom: 45px; color: #333;
    }
    table.firmas-tbl { width: 100%; border-collapse: collapse; }
    table.firmas-tbl td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 25px; }
    .firma-espacio { height: 75px; text-align: center; }
    .firma-img { max-height: 72px; max-width: 85%; display: block; margin: 3px auto 0; }
    .firma-linea { border-top: 1px solid #333; width: 88%; margin: 0 auto 8px; }
    .firma-nombre { font-weight: bold; font-size: 10pt; line-height: 1.4; }
    .firma-cargo { font-size: 9pt; color: #444; line-height: 1.4; margin-top: 3px; }
    .firmas-sep { margin: 46px 0 36px; border: none; border-top: 1px dashed #ccc; }
  </style>
</head>
<body>

  <header>
    {$banda_sup_img}
    <div class="header-meta"><span class="fecha">{$header_city_line}</span></div>
  </header>

  <footer>
    <div class="footer-content">
      <table class="footer-tbl">
        <tr>
          <td class="footer-contact">{$footer_contact}</td>
          <td class="footer-page"></td>
          <td class="footer-spacer"></td>
        </tr>
      </table>
    </div>
    {$banda_inf_img}
  </footer>

  <main class="contenido">
    <div class="convenio-title">{$header_title}</div>

    <div class="cuerpo">
      {$content_html}
    </div>

    <!-- ══════════ FIRMAS AUTÓGRAFAS ══════════ -->
    <div class="firmas-section">
      <div class="firmas-title">Firmas de conformidad</div>

      <!-- Fila 1: Representantes -->
      <table class="firmas-tbl">
        <tr>
          <td>
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{$empresa_e}</div>
            <div class="firma-cargo">“La Empresa”<br>(Nombre y firma autógrafa)</div>
          </td>
          <td>
            <div class="firma-espacio">{$signer_sig_img}</div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{$signer_name_e}</div>
            <div class="firma-cargo">{$signer_role_e}<br>“La Universidad”</div>
          </td>
        </tr>
      </table>

      <hr class="firmas-sep">

      <!-- Fila 2: Testigos -->
      <table class="firmas-tbl">
        <tr>
          <td>
            <div class="firma-espacio"></div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{$testigo1_e}</div>
            <div class="firma-cargo">Testigo — “La Empresa”<br>(Nombre y firma autógrafa)</div>
          </td>
          <td>
            <div class="firma-espacio">{$testigo_univ_sig_img}</div>
            <div class="firma-linea"></div>
            <div class="firma-nombre">{$testigo2_e}</div>
            <div class="firma-cargo">Testigo — “La Universidad”<br>(Nombre y firma autógrafa)</div>
          </td>
        </tr>
      </table>
    </div>
  </main>

</body>
</html>
HTML;
    }
}

if (!function_exists('convenioRenderOnce')) {
    function convenioRenderOnce(string $html): Dompdf
    {
        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->setIsRemoteEnabled(true);
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html, 'UTF-8');
        // Carta (612×792 pt): tamaño de la plantilla institucional.
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        return $dompdf;
    }
}

if (!function_exists('convenioRenderPdf')) {
    function convenioRenderPdf(array $config, array $org = []): Dompdf
    {
        // Primer pase: contar el total real de páginas.
        $first = convenioRenderOnce(convenioBuildHtml($config, null, $org));
        $total = (int) $first->getCanvas()->get_page_count();
        if ($total < 1) {
            return $first;
        }
        // Segundo pase: inyectar el total para el footer "Página X de Y".
        return convenioRenderOnce(convenioBuildHtml($config, $total, $org));
    }
}

if (!function_exists('convenioRenderPdfForOrganismo')) {
    /** Convenio personalizado con los datos de un organismo. */
    function convenioRenderPdfForOrganismo(array $config, array $org): Dompdf
    {
        return convenioRenderPdf($config, $org);
    }
}

if (!function_exists('convenioGeneratedPath')) {
    /** Ruta canónica del PDF generado automáticamente al guardar. */
    function convenioGeneratedPath(): string
    {
        return __DIR__ . '/../storage/generated/convenio_practicas_profesionales.pdf';
    }
}
