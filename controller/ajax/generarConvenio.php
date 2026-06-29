<?php
// controller/ajax/generarConvenio.php
// Descarga pública del Convenio de Prácticas Profesionales (en blanco, para
// firma autógrafa). Sirve el PDF generado automáticamente al guardar la
// configuración; si aún no existe, lo genera al vuelo desde el JSON.
declare(strict_types=1);

require_once __DIR__ . '/../convenio_render.php';

$CONFIG_PATH = __DIR__ . '/../../config/convenio_config.json';
$DOWNLOAD_NAME = 'Convenio_Practicas_Profesionales.pdf';

$stored = convenioGeneratedPath();

// 1) Si existe el PDF generado al guardar, lo servimos tal cual.
if (is_file($stored) && filesize($stored) > 0) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $DOWNLOAD_NAME . '"');
    header('Content-Length: ' . filesize($stored));
    header('Cache-Control: no-store, max-age=0');
    readfile($stored);
    exit;
}

// 2) Fallback: generar al vuelo desde la configuración.
$config = convenioLoadConfig($CONFIG_PATH);
$dompdf = convenioRenderPdf($config);
$dompdf->stream($DOWNLOAD_NAME, ['Attachment' => true]);
exit;
