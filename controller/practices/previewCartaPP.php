<?php
/**
 * Vista previa con datos de prueba de los documentos de Prácticas Profesionales.
 *
 * Lo consume por AJAX el botón "Ver PDF de prueba" de los editores: recibe la
 * configuración tal como está en pantalla (aunque no se haya guardado) y
 * devuelve los bytes del PDF, que el navegador muestra en un modal sin salir de
 * Configuraciones. NO toca la base de datos ni el disco: no consume folio, no
 * marca prácticas como finalizadas y no guarda el archivo.
 *
 * Petición: POST con cabecera X-CSRF-Token y cuerpo JSON
 *   { "doc": "carta" | "constancia", "config": { ... } }
 * Respuesta: application/pdf, o application/json con {error} si algo falla.
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/cartaPresentacionGenerator.php';
require_once __DIR__ . '/constanciaHtml.php';

/** Corta con un JSON de error; el front lo muestra tal cual. */
function previewError(int $code, string $msg): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    previewError(403, 'Acceso no autorizado.');
}
if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    previewError(400, 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    previewError(400, 'Petición no válida.');
}

$doc = (string) ($payload['doc'] ?? '');
if (!in_array($doc, ['carta', 'constancia'], true)) {
    previewError(400, 'Documento no válido.');
}

$cfg = $payload['config'] ?? null;
if (!is_array($cfg)) {
    previewError(400, 'Configuración no válida.');
}

// Datos de muestra: los mismos del documento de la plantilla oficial
// (docs/TEMPLATE CARTA DE PRESENTACIÓN PP ACTUAL.pdf), para que la prueba se vea
// igual que el ejemplo. Van capitalizados palabra por palabra porque así los
// imprime el generador real con los datos de un alumno de verdad (ppCapitalizar).
$comun = [
    'studentName' => 'María Isabel Castañeda Osornio',
    'matricula'   => '46684',
    'degreeName'  => 'Licenciatura en Relaciones Comerciales Internacionales',
    // La fecha sí es la de hoy: es lo que llevaría el documento al emitirse.
    'fecha'       => ppFechaLarga(),
];

try {
    if ($doc === 'carta') {
        $html = construirCartaPresentacionHtml($comun + [
            'genero'           => 'la',
            'folio'            => 'DPP-12385-2026',
            'empresa'          => 'Secretaría del Migrante',
            'cargoResponsable' => 'Delegada Administrativa',
            'responsable'      => 'Lic. Ana Janelle Sánchez Velázquez',
        ], $cfg);
        $nombre = 'Vista_previa_carta_presentacion.pdf';
    } else {
        $html = construirConstanciaHtml($comun + [
            'generoArt'     => 'la alumna',
            'nameOrganismo' => 'Secretaría del Migrante',
            'folio'         => 'CAPP-12385-2026',
        ], $cfg);
        $nombre = 'Vista_previa_constancia.pdf';
    }

    $pdf = ppRenderPdf($html)->output();

    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nombre . '"');
    header('Content-Length: ' . strlen((string) $pdf));
    header('Cache-Control: no-store, max-age=0');
    echo $pdf;
} catch (\Throwable $e) {
    error_log('[previewCartaPP] ' . $e->getMessage());
    previewError(500, 'No se pudo generar la vista previa: ' . $e->getMessage());
}
