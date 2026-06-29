<?php
declare(strict_types=1);

require_once __DIR__ . '/../../model/conection.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../forms.controller.php';

session_start();

// ── Autenticación ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit('No autorizado');
}
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    exit('Solo administradores pueden usar esta función');
}

// ── Parámetros ────────────────────────────────────────────────────────────────
$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('ID requerido');
}

// ── Obtener registro ──────────────────────────────────────────────────────────
$pdo  = Conexion::conectar();
$stmt = $pdo->prepare("SELECT * FROM servicio_social_ijumich WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro || $registro['tipo'] !== 'evaluacion_global') {
    http_response_code(404);
    exit('Registro no encontrado o tipo incorrecto');
}

if (empty($registro['archivo_path'])) {
    http_response_code(404);
    exit('Este registro no tiene archivo adjunto');
}

// ── Resolver ruta absoluta del PDF subido ─────────────────────────────────────
$base   = realpath(__DIR__ . '/../../');
$pdfAbs = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $registro['archivo_path']);

if (!file_exists($pdfAbs)) {
    http_response_code(404);
    exit('Archivo no encontrado en el servidor');
}

// ── Leer JSON de coordenadas ──────────────────────────────────────────────────
$coordPath = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'generated'
           . DIRECTORY_SEPARATOR . 'evaluacion_global_coordinates.json';
$campos    = [];
$firmaConf = null;
$selloConf = null;
if (file_exists($coordPath)) {
    $json = json_decode(file_get_contents($coordPath), true);
    if (is_array($json)) {
        foreach ($json as $key => $campo) {
            if ($key === '_meta') continue;
            if ($key === 'firma')  { $firmaConf = $campo; continue; }
            if ($key === 'sello')  { $selloConf = $campo; continue; }
            if (isset($campo['page'], $campo['x'], $campo['y'], $campo['default_value'])) {
                $campos[] = $campo;
            }
        }
    }
}

// ── Config firma/sello ────────────────────────────────────────────────────────
$configPath = $base . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'carta_presentacion.json';
$cfg        = file_exists($configPath) ? (json_decode(file_get_contents($configPath), true) ?? []) : [];
$sigUrl     = $cfg['signature']['signature_img_url']  ?? '';
$sealUrl    = $cfg['signature']['seal_img_url']       ?? '';
$sigWpx     = (int)($cfg['signature']['signature_width'] ?? 200);
$sealWpx    = (int)($cfg['signature']['seal']['width']   ?? 240);

$resolveImg = static function(string $url) use ($base): string {
    if ($url === '') return '';
    $decoded = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
    $local   = $base . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'assets'
             . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $decoded;
    if (file_exists($local)) return $local;
    $tmp = tempnam(sys_get_temp_dir(), 'img_');
    $raw = @file_get_contents($url);
    if ($raw !== false) { file_put_contents($tmp, $raw); return $tmp; }
    return '';
};

$sigImgPath  = $resolveImg($sigUrl);
$sealImgPath = $resolveImg($sealUrl);
$sigWmm      = (float)round($sigWpx  * 0.264583) * 0.90;
$sealWmm     = (float)round($sealWpx * 0.264583);

// ── Generar PDF en memoria y enviarlo al navegador ────────────────────────────
try {
    $pdf       = new \setasign\Fpdi\Fpdi('P', 'mm');
    $pageCount = $pdf->setSourceFile($pdfAbs);

    for ($i = 1; $i <= $pageCount; $i++) {
        $tplId = $pdf->importPage($i);
        $size  = $pdf->getTemplateSize($tplId);
        $pdf->addPage(($size['width'] > $size['height']) ? 'L' : 'P', [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

        // Rellenar campos de texto
        foreach ($campos as $campo) {
            if ((int)$campo['page'] !== $i) continue;
            $fontSize = isset($campo['font_size']) ? (int)$campo['font_size'] : 9;
            $pdf->SetFont('Helvetica', '', $fontSize);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY((float)$campo['x'], (float)$campo['y']);
            $cw    = isset($campo['width'])  ? (float)$campo['width']  : 30;
            $ch    = isset($campo['height']) ? (float)$campo['height'] : 6;
            $align = $campo['align'] ?? 'C';
            $pdf->Cell($cw, $ch, $campo['default_value'], 0, 0, $align);
        }

        // Firma y sello en la última página
        if ($i === $pageCount) {
            $pageW = (float)$size['width'];
            $pageH = (float)$size['height'];

            if ($sealImgPath) {
                $info = @getimagesize($sealImgPath);
                if ($info && $info[0] > 0) {
                    if ($selloConf && isset($selloConf['x'], $selloConf['y'])) {
                        $sealX = (float)$selloConf['x'];
                        $sealY = (float)$selloConf['y'];
                        $sealW = isset($selloConf['width']) ? (float)$selloConf['width'] : $sealWmm;
                    } else {
                        $sealW = $sealWmm;
                        $sealH = $sealW * ($info[1] / $info[0]);
                        $sealX = ($pageW - $sealW) / 2.0 + 25.0;
                        $sealY = $pageH - 62.0 - $sealH;
                    }
                    $pdf->Image($sealImgPath, $sealX, $sealY, $sealW, 0);
                }
            }
            if ($sigImgPath) {
                $info = @getimagesize($sigImgPath);
                if ($info && $info[0] > 0) {
                    if ($firmaConf && isset($firmaConf['x'], $firmaConf['y'])) {
                        $sigX = (float)$firmaConf['x'];
                        $sigY = (float)$firmaConf['y'];
                        $sigW = isset($firmaConf['width']) ? (float)$firmaConf['width'] : $sigWmm;
                    } else {
                        $sigW = $sigWmm;
                        $sigX = ($pageW - $sigW) / 2.0;
                        $sigY = $pageH - 110.0 - ($sigW * ($info[1] / $info[0]));
                    }
                    $pdf->Image($sigImgPath, $sigX, $sigY, $sigW, 0);
                }
            }
        }
    }

    // Enviar como inline al navegador (sin guardar en disco)
    $pdf->Output('I', 'preview_evaluacion_global_firmada.pdf');

} catch (\Exception $e) {
    http_response_code(500);
    exit('Error al generar la vista previa: ' . $e->getMessage());
}
