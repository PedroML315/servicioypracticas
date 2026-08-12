<?php
/**
 * Membrete institucional de las cartas oficiales.
 *
 * Lo comparten los documentos de Prácticas Profesionales (carta de presentación
 * y constancia) y los de Servicio Social (carta de presentación y carta de
 * aceptación, en controller/servicio/cartaServicioGenerator.php), para que todos
 * salgan con la misma hoja membretada.
 *
 * El diseño proviene de la plantilla oficial
 * `storage/templates/carta_plantilla.pdf` (hoja Carta, 612×792 pt):
 * barra lateral gris con el escudo, los domicilios de los 5 campus y el QR, más
 * la barra verde inferior. Se extrajo a una sola imagen de página completa,
 * `view/assets/images/carta_membrete.png` (1749×2247 px ≈ 200 DPI), que se
 * imprime de fondo en cada hoja.
 *
 * Todas las medidas están en puntos (pt) y salieron de medir la plantilla:
 *   - imagen:   629.75 × 809.05 pt, esquina inferior izquierda en (-8.95, 14.30)
 *   - texto:    margen izquierdo 156 pt (justo a la derecha de la barra lateral),
 *               margen derecho 56.5 pt
 *   - barra verde inferior: ocupa de y≈25 pt a y≈56 pt, por eso el margen
 *               inferior del contenido es de 60 pt
 *
 * Para cambiar el diseño hay que reemplazar la plantilla y volver a extraer la
 * imagen con estas mismas medidas.
 */
declare(strict_types=1);

// ppCapitalizar(): las variables de los documentos se imprimen en "Nombre
// Propio", no en mayúsculas. La comparten todas las cartas.
require_once __DIR__ . '/../textoDocumentos.php';

const PP_MEMBRETE_FILE   = 'carta_membrete.png';
const PP_MEMBRETE_W      = 629.75;  // ancho de la imagen, pt
const PP_MEMBRETE_H      = 809.05;  // alto de la imagen, pt
const PP_MEMBRETE_X      = -8.95;   // desplazamiento desde el borde izquierdo de la hoja, pt
const PP_MEMBRETE_TOP    = -31.35;  // desplazamiento desde el borde superior de la hoja, pt

const PP_MARGEN_TOP      = 47.8;    // deja la línea de ciudad/fecha en y≈732.8 pt, como la plantilla
const PP_MARGEN_RIGHT    = 56.5;
const PP_MARGEN_BOTTOM   = 60.0;    // libra la barra verde inferior
const PP_MARGEN_LEFT     = 156.0;   // libra la barra lateral gris

if (!function_exists('ppMembreteCss')) {
    /**
     * Devuelve las reglas @page y .membrete con la geometría de la plantilla.
     *
     * Las coordenadas de la imagen son negativas y relativas a la caja de
     * contenido (así posiciona Dompdf los elementos fijos), por eso se les resta
     * el margen correspondiente para llegar al borde real de la hoja.
     *
     * El `z-index: -1` es imprescindible, no cosmético: Dompdf pinta los
     * elementos posicionados DESPUÉS del contenido en flujo, así que sin él la
     * imagen —que cubre la hoja entera y es opaca— se dibuja encima y deja el
     * documento en blanco. Con z-index negativo se pinta al fondo, antes del texto.
     */
    function ppMembreteCss(
        float $top = PP_MARGEN_TOP,
        float $right = PP_MARGEN_RIGHT,
        float $bottom = PP_MARGEN_BOTTOM,
        float $left = PP_MARGEN_LEFT
    ): string {
        $imgTop  = PP_MEMBRETE_TOP - $top;
        $imgLeft = PP_MEMBRETE_X - $left;
        $w = PP_MEMBRETE_W;
        $h = PP_MEMBRETE_H;

        return <<<CSS
    @page { margin: {$top}pt {$right}pt {$bottom}pt {$left}pt; }
    .membrete {
      position: fixed;
      top: {$imgTop}pt; left: {$imgLeft}pt;
      width: {$w}pt; height: {$h}pt;
      z-index: -1;
    }
CSS;
    }
}

if (!function_exists('ppMembreteImgTag')) {
    /**
     * <img> del membrete listo para insertar, o cadena vacía si falta el PNG
     * (mejor una carta sin membrete que una generación rota).
     *
     * @param callable $toDataUri Conversor a data URI del generador que lo llama.
     */
    function ppMembreteImgTag(callable $toDataUri): string
    {
        $path = __DIR__ . '/../../view/assets/images/' . PP_MEMBRETE_FILE;
        if (!is_file($path)) {
            return '';
        }
        return '<img class="membrete" src="' . $toDataUri(PP_MEMBRETE_FILE) . '" alt="">';
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   Utilidades comunes a todas las cartas que usan este membrete. Viven aquí —y no
   en el generador de Prácticas— para que Servicio Social pueda reutilizarlas sin
   arrastrar el modelo de prácticas ni la conexión a base de datos.
   ───────────────────────────────────────────────────────────────────────────── */

if (!function_exists('ppImgToDataUri')) {
    /**
     * Convierte una URL de imagen a data URI: Dompdf renderiza sin acceso a red,
     * así que primero se busca el archivo en view/assets/images y sólo si no está
     * se intenta descargar.
     */
    function ppImgToDataUri(string $url): string
    {
        if ($url === '') {
            return '';
        }
        $filename = rawurldecode(basename((string) parse_url($url, PHP_URL_PATH)));
        $localPath = __DIR__ . '/../../view/assets/images/' . $filename;
        $data = file_exists($localPath) ? file_get_contents($localPath) : @file_get_contents($url);
        if ($data === false || $data === '') {
            return $url;
        }
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
}

if (!function_exists('ppFechaLarga')) {
    /**
     * Fecha de hoy en español, anclada a Morelia: la zona horaria de PHP no es
     * necesariamente la local (php.ini puede traer otra) y con un desfase de
     * horas el documento se fecharía al día siguiente.
     */
    function ppFechaLarga(): string
    {
        $hoy = new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City'));
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $hoy->format('j') . ' de ' . $meses[(int) $hoy->format('n') - 1] . ' de ' . $hoy->format('Y');
    }
}

if (!function_exists('ppRenderPdf')) {
    /** Renderiza a PDF en hoja Carta, el tamaño de la plantilla institucional. */
    function ppRenderPdf(string $html): \Dompdf\Dompdf
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $dompdf = new \Dompdf\Dompdf();
        $options = $dompdf->getOptions();
        $options->setIsRemoteEnabled(true);
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        return $dompdf;
    }
}
