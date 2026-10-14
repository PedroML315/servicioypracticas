<?php
/**
 * Revisión de archivos PHP del servidor.
 *
 * En 2026-10 apareció un PHP ajeno dentro de fontawesome/, una carpeta que vivía en el servidor
 * fuera del repositorio. web.config ya impide ejecutar cualquier PHP fuera de index.php,
 * controller/ y database/; esta revisión sirve para ENTERARSE si alguien vuelve a dejar uno:
 *
 *  1. Archivos ejecutables (.php, .phtml, .phar…) en carpetas que nunca deben tenerlos
 *     (uploads/, view/assets/, docs/, storage/ o cualquier carpeta que no sea del proyecto).
 *  2. PHP de las zonas que sí se ejecutan (index.php, controller/, database/) que no coinciden
 *     con la huella generada desde el repositorio (config/integridad_php.php): nuevos o cambiados.
 *  3. Patrones típicos de puertas traseras en cualquier PHP fuera de vendor/.
 *
 * Uso:
 *   - Navegador, con sesión de administrador:  /controller/seguridad/revisar_archivos.php
 *   - Línea de comandos:                       php controller/seguridad/revisar_archivos.php
 *   - Regenerar la huella (en la copia local, antes de subir cambios de PHP):
 *                                              php controller/seguridad/revisar_archivos.php --generar
 */

declare(strict_types=1);

$esCli = PHP_SAPI === 'cli';
if (!$esCli) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['logged']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(404);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
}

$raiz = realpath(__DIR__ . '/../..');
$huellaArchivo = $raiz . '/config/integridad_php.php';

$extEjecutables = '/\.(php[0-9]*|phtml|phar|pht|phps)$/i';
// Carpetas del proyecto donde puede haber PHP. Fuera de ellas, cualquier PHP es sospechoso.
$carpetasConPhp = ['config', 'controller', 'database', 'model', 'vendor', 'view'];
// Dentro de view/ el PHP vive en pages/ y en los .php de la raíz de view/; nunca en assets/.
$prohibidasDentro = ['view/assets', 'uploads', 'docs', 'storage'];
// Zonas que IIS sí ejecuta (ver regla "Solo PHP permitidos" de web.config).
$zonasEjecutables = fn(string $rel) => $rel === 'index.php' || str_starts_with($rel, 'controller/') || str_starts_with($rel, 'database/');

$patrones = [
    'eval de texto codificado'      => '/\beval\s*\(\s*(base64_decode|gzinflate|gzuncompress|gzdecode|str_rot13|hex2bin|rawurldecode)\s*\(/i',
    'ejecución con datos del usuario' => '/(?<![>\w$])(eval|assert|system|shell_exec|passthru|exec|popen|proc_open|pcntl_exec)\s*\([^;]{0,120}\$_(GET|POST|REQUEST|COOKIE|SERVER|FILES)/i',
    'función armada con datos del usuario' => '/\$_(GET|POST|REQUEST|COOKIE)\s*\[[^\]]+\]\s*\(/i',
    'preg_replace con /e'           => '/preg_replace\s*\(\s*([\'"]).*\/e[a-z]*\1/i',
    'create_function'               => '/\bcreate_function\s*\(/i',
    'escritura de archivo PHP'      => '/(file_put_contents|fwrite|move_uploaded_file)\s*\([^;]{0,160}\.ph(p|tml|ar)\b/i',
];

/** SHA-256 sin importar los saltos de línea (CRLF en Windows, LF en el servidor). */
function huellaDe(string $ruta): string
{
    return hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($ruta)));
}

/** Recorre el sitio y devuelve rutas relativas (con /) de todos los archivos. */
function archivosDelSitio(string $raiz): array
{
    $lista = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($raiz) + 1));
        if (str_starts_with($rel, '.git/') || str_starts_with($rel, 'node_modules/')) continue;
        $lista[] = $rel;
    }
    sort($lista);
    return $lista;
}

// ── Generar la huella (solo CLI, en la copia del repositorio) ───────────────
if ($esCli && in_array('--generar', $argv ?? [], true)) {
    $huella = [];
    foreach (archivosDelSitio($raiz) as $rel) {
        if (preg_match($extEjecutables, $rel) && $zonasEjecutables($rel)) {
            $huella[$rel] = huellaDe($raiz . '/' . $rel);
        }
    }
    $php = "<?php\n// Huella SHA-256 de los PHP que IIS ejecuta (index.php, controller/, database/).\n"
         . "// Se genera con: php controller/seguridad/revisar_archivos.php --generar\n"
         . "// Regenerarla cuando se agreguen o cambien PHP en esas carpetas, antes de subir.\n"
         . 'return ' . var_export($huella, true) . ";\n";
    file_put_contents($huellaArchivo, $php);
    echo 'Huella generada: ' . count($huella) . " archivos en config/integridad_php.php\n";
    exit;
}

// ── Revisión ────────────────────────────────────────────────────────────────
$huella = is_file($huellaArchivo) ? (require $huellaArchivo) : [];
$hallazgos = ['fuera' => [], 'nuevos' => [], 'cambiados' => [], 'faltan' => [], 'patrones' => []];
$vistos = [];
$totalPhp = 0;

foreach (archivosDelSitio($raiz) as $rel) {
    if (!preg_match($extEjecutables, $rel)) continue;
    $totalPhp++;
    $primera = explode('/', $rel)[0];
    $enCarpetaPermitida = $rel === 'index.php' || (in_array($primera, $carpetasConPhp, true) && str_contains($rel, '/'));
    $enProhibida = false;
    foreach ($prohibidasDentro as $p) {
        if (str_starts_with($rel, $p . '/')) { $enProhibida = true; break; }
    }
    if (!$enCarpetaPermitida || $enProhibida) {
        $hallazgos['fuera'][] = [$rel, date('Y-m-d H:i', (int) filemtime($raiz . '/' . $rel))];
    }

    if ($zonasEjecutables($rel)) {
        $vistos[$rel] = true;
        if (!isset($huella[$rel])) {
            $hallazgos['nuevos'][] = [$rel, date('Y-m-d H:i', (int) filemtime($raiz . '/' . $rel))];
        } elseif (!hash_equals($huella[$rel], huellaDe($raiz . '/' . $rel))) {
            $hallazgos['cambiados'][] = [$rel, date('Y-m-d H:i', (int) filemtime($raiz . '/' . $rel))];
        }
    }

    if ($primera !== 'vendor' && $rel !== 'controller/seguridad/revisar_archivos.php') {
        $contenido = (string) @file_get_contents($raiz . '/' . $rel);
        foreach ($patrones as $nombre => $regex) {
            if (preg_match($regex, $contenido)) {
                $hallazgos['patrones'][] = [$rel, $nombre];
            }
        }
    }
}
foreach (array_keys($huella) as $rel) {
    if (!isset($vistos[$rel])) $hallazgos['faltan'][] = [$rel, ''];
}

$secciones = [
    'fuera'     => ['PHP en carpetas donde no debe haber código', 'Revisar y BORRAR: nadie de la plataforma pone PHP ahí.'],
    'patrones'  => ['Código con patrones de puerta trasera', 'Revisar el archivo a mano.'],
    'nuevos'    => ['PHP ejecutable que no está en la huella del repositorio', 'Si no lo subió el equipo, es ajeno. Si es nuevo del repo, regenerar la huella.'],
    'cambiados' => ['PHP ejecutable distinto al del repositorio', 'Si no hubo cambios subidos, alguien lo modificó en el servidor.'],
    'faltan'    => ['PHP de la huella que ya no está', 'Normalmente es un archivo que se borró del repo; regenerar la huella.'],
];
$hayAlerta = $hallazgos['fuera'] || $hallazgos['patrones'] || $hallazgos['nuevos'] || $hallazgos['cambiados'];

if ($esCli) {
    echo "Revisión de archivos PHP — " . date('Y-m-d H:i') . "\n";
    echo "PHP revisados: {$totalPhp} | huella: " . (count($huella) ?: 'NO EXISTE') . " archivos\n\n";
    foreach ($secciones as $k => [$titulo, $accion]) {
        echo ($hallazgos[$k] ? '[!] ' : '[ok] ') . $titulo . ': ' . count($hallazgos[$k]) . "\n";
        foreach ($hallazgos[$k] as [$a, $b]) echo "     - {$a}" . ($b !== '' ? "  ({$b})" : '') . "\n";
        if ($hallazgos[$k]) echo "     → {$accion}\n";
    }
    echo "\n" . ($hayAlerta ? "HAY HALLAZGOS QUE REVISAR.\n" : "Sin hallazgos.\n");
    exit($hayAlerta ? 1 : 0);
}

$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Revisión de archivos PHP</title>
<style>
  body { font-family: "Segoe UI", Arial, sans-serif; margin: 2rem; color: #1f2937; background: #f8fafc; }
  h1 { color: #01643d; margin-bottom: .25rem; }
  .estado { padding: .75rem 1rem; border-radius: .6rem; font-weight: 600; margin: 1rem 0 1.5rem; }
  .ok { background: #dcfce7; color: #166534; } .alerta { background: #fee2e2; color: #991b1b; }
  section { background: #fff; border: 1px solid #e5e7eb; border-radius: .8rem; padding: 1rem 1.25rem; margin-bottom: 1rem; }
  h2 { font-size: 1.05rem; margin: 0 0 .35rem; } .accion { color: #6b7280; font-size: .9rem; margin: 0 0 .5rem; }
  code { background: #f1f5f9; padding: .1rem .35rem; border-radius: .3rem; }
  li { margin: .2rem 0; }
</style>
</head>
<body>
  <h1>Revisión de archivos PHP</h1>
  <div><?= $e(date('Y-m-d H:i')) ?> · PHP revisados: <?= $totalPhp ?> · huella: <?= count($huella) ?: 'no existe' ?> archivos</div>
  <div class="estado <?= $hayAlerta ? 'alerta' : 'ok' ?>"><?= $hayAlerta ? 'Hay hallazgos que revisar.' : 'Sin hallazgos.' ?></div>
  <?php foreach ($secciones as $k => [$titulo, $accion]): ?>
    <section>
      <h2><?= $hallazgos[$k] ? '⚠️' : '✅' ?> <?= $e($titulo) ?>: <?= count($hallazgos[$k]) ?></h2>
      <?php if ($hallazgos[$k]): ?>
        <p class="accion"><?= $e($accion) ?></p>
        <ul>
          <?php foreach ($hallazgos[$k] as [$a, $b]): ?>
            <li><code><?= $e($a) ?></code><?= $b !== '' ? ' · ' . $e($b) : '' ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</body>
</html>
