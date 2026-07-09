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
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $fecha = date('j') . ' de ' . $meses[(int) date('n') - 1] . ' de ' . date('Y');

        // Fecha completa con estilo del convenio: "3 del mes de julio en el año de dos mil veintiséis".
        $anio       = (int) date('Y');
        $anioLetras = $anio >= 2000 && $anio <= 2099
            ? 'dos mil ' . convenioNumeroLetras($anio - 2000)
            : (string) $anio;
        $fecha_larga = date('j') . ' del mes de ' . $meses[(int) date('n') - 1] . ' en el año de ' . $anioLetras;

        $signer_name = (string) convenioVal($c, 'signature.signer_name', 'Noé Alonso González Herrera');
        $signer_role = (string) convenioVal($c, 'signature.signer_role', 'Representante Legal – Instituto Montrer, S.C.');

        // Tokens reemplazables en el cuerpo. Los tokens por-organismo
        // (si $org viene con datos) se fusionan sobre los de sistema.
        $vars = array_merge([
            '{{fecha}}'          => $fecha,
            '{{fecha_larga}}'    => $fecha_larga,
            '{{repUniversidad}}' => $signer_name,
        ], convenioOrganismoVars($org));

        $header_bar_color = convenioVal($c, 'header.bar_color', '#006837');
        $header_logo_url  = (string) convenioVal($c, 'header.logo_url', '');
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

        $footer_logo_url  = (string) convenioVal($c, 'footer.logo_url', '');
        $footer_contact   = (string) convenioVal($c, 'footer.contact_line', '');
        $bottom_bar_color = convenioVal($c, 'footer.bottom_bar_color', '#006837');
        $bottom_text      = (string) convenioVal($c, 'footer.bottom_text', '');

        $font_family       = (string) convenioVal($c, 'layout.font_family', 'Arial, Helvetica, sans-serif');
        $font_size_pt      = (int) convenioVal($c, 'layout.font_size_pt', 11);
        // Altura reservada para el membrete superior. Debe ser mayor que el alto
        // del logo para que el contenido empiece por debajo de la línea divisoria.
        $header_band       = (int) convenioVal($c, 'layout.header_band_px', 225);
        $content_padding   = (int) convenioVal($c, 'layout.content_padding_px', 40);
        $page_margin       = (int) convenioVal($c, 'layout.page_margin_px', 0);
        $header_logo_width = (int) convenioVal($c, 'layout.header_logo_width', 150);
        $footer_logo_width = (int) convenioVal($c, 'layout.footer_logo_width', 130);

        // counter(pages) no es fiable en esta versión de Dompdf: el total se
        // inyecta como literal en el segundo pase (ver convenioRenderPdf()).
        $pageInfoAfter = $totalPages !== null
            ? 'content: "Página " counter(page) " de ' . (int) $totalPages . '";'
            : 'content: "Página " counter(page);';

        $header_logo = convenioImgToDataUri($header_logo_url);
        $footer_logo = convenioImgToDataUri($footer_logo_url);

        $header_logo_img = $header_logo ? '<img src="' . $header_logo . '" alt="Logo Universidad Montrer">' : '';
        $footer_logo_img = $footer_logo ? '<img src="' . $footer_logo . '" alt="Logo">' : '';

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
      margin-bottom: 110px;
      margin-left: {$page_margin}px;
      margin-right: {$page_margin}px;
    }
    body {
      margin: 0; padding: 0;
      font-family: {$font_family};
      font-size: {$font_size_pt}pt;
      color: #111;
    }

    /* ── HEADER (membrete superior, repetido en cada hoja) ── */
    header {
      position: fixed;
      top: -{$header_band}px;
      left: 0; right: 0;
      height: {$header_band}px;
    }
    .header-bar { background-color: {$header_bar_color}; height: 40px; width: 100%; }
    .header-content { padding: 0 {$content_padding}px; }
    table.header-tbl { width: 100%; border-bottom: 1px solid #ccc; margin-top: 18px; padding-bottom: 18px; border-collapse: collapse; }
    .header-left { width: 160px; text-align: center; vertical-align: middle; }
    .header-left img { width: {$header_logo_width}px; }
    .header-right { text-align: right; vertical-align: middle; }
    .fecha { font-size: 10pt; color: #444; }

    /* ── FOOTER (membrete inferior, repetido en cada hoja) ── */
    footer {
      position: fixed;
      bottom: -110px;
      left: 0; right: 0;
      height: 110px;
    }
    .footer-content { padding: 0 {$content_padding}px; }
    table.footer-tbl { width: 100%; border-top: 1px solid #ccc; margin-top: 8px; padding-top: 8px; border-collapse: collapse; }
    .footer-tbl td { vertical-align: middle; font-size: 9pt; color: #444; }
    .footer-logo { width: {$footer_logo_width}px; padding-right: 15px; vertical-align: middle; }
    .footer-logo img { width: {$footer_logo_width}px; }
    .footer-contact { text-align: left; }
    .footer-page { text-align: right; font-weight: bold; white-space: nowrap; }
    .footer-page:after { {$pageInfoAfter} }

    .barra-inferior {
      background-color: {$bottom_bar_color};
      color: white; text-align: center;
      padding: 6px 0; font-size: 8pt;
      position: absolute; bottom: 0; left: 0; width: 100%;
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
          <td class="footer-page"></td>
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
        $dompdf->setPaper('A4', 'portrait');
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
