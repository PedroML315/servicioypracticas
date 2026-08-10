<?php
/**
 * Vista previa con datos de prueba de los documentos de Servicio Social.
 *
 * Gemelo de controller/practices/previewCartaPP.php: lo consume por AJAX el
 * botón "Ver PDF de prueba" de los editores del panel de Configuraciones.
 * Recibe la configuración tal como está en pantalla (aunque no se haya guardado)
 * y devuelve los bytes del PDF, que el navegador muestra en un modal. NO toca la
 * base de datos ni el disco: no consume folio y no guarda el archivo.
 *
 * Petición: POST con cabecera X-CSRF-Token y cuerpo JSON
 *   { "doc": "presentacion" | "aceptacion", "config": { ... } }
 * Respuesta: application/pdf, o application/json con {error} si algo falla.
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/cartaServicioGenerator.php';

/** Corta con un JSON de error; el front lo muestra tal cual. */
function previewSsError(int $code, string $msg): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    previewSsError(403, 'Acceso no autorizado.');
}
if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    previewSsError(400, 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    previewSsError(400, 'Petición no válida.');
}

$doc = (string) ($payload['doc'] ?? '');
if (!in_array($doc, ['presentacion', 'aceptacion'], true)) {
    previewSsError(400, 'Documento no válido.');
}

$cfg = $payload['config'] ?? null;
if (!is_array($cfg)) {
    previewSsError(400, 'Configuración no válida.');
}

// Datos de muestra. Van en mayúsculas porque así los imprime el generador real
// con los datos de un alumno de verdad. La fecha sí es la de hoy: es lo que
// llevaría el documento al emitirse.
$comun = [
    'studentName' => 'MARÍA ISABEL CASTAÑEDA OSORNIO',
    'matricula'   => '46684',
    'degreeName'  => 'LICENCIATURA EN RELACIONES COMERCIALES INTERNACIONALES',
    'genero'      => 'la',
    'fecha'       => ppFechaLarga(),
];

try {
    if ($doc === 'presentacion') {
        $html = construirCartaPresentacionServicioHtml($comun + [
            'folio'       => 'DSS-12385-2026',
            'nameUR'      => 'SECRETARÍA DEL MIGRANTE',
            'responsable' => 'LIC. ANA JANELLE SÁNCHEZ VELÁZQUEZ',
            'cargo'       => 'DELEGADA ADMINISTRATIVA',
            'domicilio'   => 'AV. LÁZARO CÁRDENAS 1000, COL. CHAPULTEPEC SUR, C.P. 58260, MORELIA, MICHOACÁN, MÉXICO',
        ], $cfg);
        $nombre = 'Vista_previa_carta_presentacion_servicio.pdf';
    } else {
        // Periodo de ejemplo: hoy + 6 meses, como lo calcula el generador real.
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $fin = (new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->modify('+6 months');
        $fechaTermino = $fin->format('j') . ' de ' . $meses[(int) $fin->format('n') - 1] . ' de ' . $fin->format('Y');

        $html = construirCartaAceptacionServicioHtml($comun + [
            'folio'        => 'CASS-12385-2026',
            'gradoTexto'   => 'NOVENO CUATRIMESTRE',
            'horas'        => '480',
            'meses'        => '6',
            'fechaInicio'  => mb_strtoupper(ppFechaLarga()),
            'fechaTermino' => mb_strtoupper($fechaTermino),
        ], $cfg);
        $nombre = 'Vista_previa_carta_aceptacion_servicio.pdf';
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
    error_log('[previewCartaSS] ' . $e->getMessage());
    previewSsError(500, 'No se pudo generar la vista previa: ' . $e->getMessage());
}
