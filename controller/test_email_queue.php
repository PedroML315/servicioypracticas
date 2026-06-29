<?php
/**
 * test_email_queue.php
 * ─────────────────────────────────────────────────────────────
 * Diagnóstico y prueba del pipeline de correos SS Interno.
 *
 * USO (CLI — recomendado):
 *   php controller/test_email_queue.php --to=tucorreo@ejemplo.com
 *
 * USO (web — requiere token del .env):
 *   GET /controller/test_email_queue.php?token=<EMAIL_QUEUE_SECRET>&to=tucorreo@ejemplo.com
 *
 * Qué hace:
 *   1. Verifica que cada tkey existe en email_templates.
 *   2. Encola un correo de prueba por cada template (a --to).
 *   3. Muestra estado de email_queue (pendientes / enviados / fallidos).
 * ─────────────────────────────────────────────────────────────
 */

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/model/conection.php';
require_once BASE_PATH . '/model/EmailsModel.php';
require_once BASE_PATH . '/controller/emails.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

/* ── Seguridad ────────────────────────────────────────────── */
$isCli = (PHP_SAPI === 'cli');

if (!$isCli) {
    $token = $_GET['token'] ?? $_SERVER['HTTP_X_QUEUE_TOKEN'] ?? '';
    if (!hash_equals($_ENV['EMAIL_QUEUE_SECRET'] ?? '', $token)) {
        http_response_code(403);
        exit('Acceso denegado');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

/* ── Destinatario de prueba ───────────────────────────────── */
$testTo = '';
if ($isCli) {
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--to=')) {
            $testTo = trim(substr($arg, 5));
        }
    }
} else {
    $testTo = trim($_GET['to'] ?? '');
}

if (!$testTo || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
    echo "ERROR: Proporciona un correo válido con --to=email@ejemplo.com\n";
    exit(1);
}

/* ── Helpers de salida ────────────────────────────────────── */
function out(string $msg): void { echo $msg . "\n"; }
function ok(string $msg): void  { echo "  ✓ {$msg}\n"; }
function err(string $msg): void { echo "  ✗ {$msg}\n"; }
function sep(): void            { echo str_repeat('─', 60) . "\n"; }

/* ── Templates a verificar con variables de prueba ────────── */
$templates = [
    'ss_interno_carta_conclusion_alumno' => [
        'studentName' => 'Alumno Prueba García',
        'folio'       => 'DSS-CCSS-TEST-2026',
        'fechaInicio' => '01/01/2026',
        'fechaFin'    => '30/06/2026',
        'horas'       => '480',
        'meses'       => '6',
    ],
    'ss_interno_carta_presentacion_admin' => [
        'studentName'     => 'Alumno Prueba García',
        'studentEmail'    => $testTo,
        'matricula'       => 'UNIMO-TEST-001',
        'organismoNombre' => 'Organismo de Prueba S.A.',
        'responsable'     => 'Responsable Prueba',
        'fechaEnvio'      => date('d/m/Y H:i'),
    ],
    'ss_interno_carta_practicas_admin' => [
        'studentName'   => 'Alumno Prueba García',
        'studentEmail'  => $testTo,
        'archivoNombre' => 'carta_practicas_test.pdf',
        'fechaEnvio'    => date('d/m/Y H:i'),
    ],
    'ss_interno_solicitud_registro_admin' => [
        'studentName'   => 'Alumno Prueba García',
        'studentEmail'  => $testTo,
        'archivoNombre' => 'solicitud_registro_test.pdf',
        'fechaEnvio'    => date('d/m/Y H:i'),
    ],
    'ss_interno_reporte_parcial_admin' => [
        'studentName'   => 'Alumno Prueba García',
        'studentEmail'  => $testTo,
        'numeroReporte' => '2',
        'archivoNombre' => 'reporte_parcial_2_test.pdf',
        'fechaEnvio'    => date('d/m/Y H:i'),
    ],
    'ss_interno_carta_liberacion_admin' => [
        'studentName'   => 'Alumno Prueba García',
        'studentEmail'  => $testTo,
        'archivoNombre' => 'carta_liberacion_test.pdf',
        'fechaEnvio'    => date('d/m/Y H:i'),
    ],
    'ss_interno_carta_presentacion_aprobada' => [
        'studentName' => 'Alumno Prueba García',
        'comentario'  => 'Documento revisado y aprobado sin observaciones.',
    ],
    'ss_interno_carta_practicas_aprobada' => [
        'studentName' => 'Alumno Prueba García',
        'comentario'  => 'Carta de prácticas aprobada correctamente.',
    ],
    'ss_interno_solicitud_registro_firmada' => [
        'studentName' => 'Alumno Prueba García',
        'comentario'  => 'Documento firmado y sellado por Coordinación.',
    ],
    'ss_interno_reporte_parcial_aprobado' => [
        'studentName'   => 'Alumno Prueba García',
        'numeroReporte' => '2',
        'comentario'    => 'Reporte revisado correctamente.',
        'nextStepMsg'   => '📌 Siguiente paso: sube tu Reporte Parcial #3 en 2 meses.',
    ],
    'ss_interno_liberacion_completa' => [
        'studentName'     => 'Alumno Prueba García',
        'adminName'       => 'Coordinador Prueba',
        'folio'           => 'DSS-CCSS-TEST-2026',
        'fechaLiberacion' => date('d/m/Y H:i'),
        'comentario'      => '',
    ],
    'ss_interno_solicitud_rechazada' => [
        'studentName'   => 'Alumno Prueba García',
        'tipoDocumento' => 'Reporte Parcial #1',
        'adminName'     => 'Coordinador Prueba',
        'comentario'    => 'El documento no cumple con el formato requerido. Por favor revisa el manual.',
    ],
    'ss_interno_carta_conclusion_admin' => [
        'studentName'     => 'Alumno Prueba García',
        'studentEmail'    => $testTo,
        'folio'           => 'DSS-CCSS-TEST-2026',
        'fechaInicio'     => '01/01/2026',
        'fechaFin'        => '30/06/2026',
        'horas'           => '480',
        'fechaGeneracion' => date('d/m/Y H:i'),
    ],
    'servicio_completado' => [
        'studentName'      => 'Alumno Prueba García',
        'horasCompletadas' => '480',
        'horasRequeridas'  => '480',
        'fechaDeteccion'   => date('d/m/Y'),
    ],
    'nuevo_organismo_practicas' => [
        'organismoNombre'   => 'Empresa de Prueba S.A.',
        'organismoContacto' => 'Contacto Prueba',
        'organismoEmail'    => $testTo,
        'organismoTelefono' => '443-000-0000',
        'fechaRegistro'     => date('d/m/Y H:i'),
    ],
];

/* ── PASO 1: Verificar existencia en email_templates ─────── */
sep();
out("PASO 1 — Verificando templates en email_templates:");
sep();

$pdo       = Conexion::conectar();
$missing   = [];
$found     = 0;

foreach (array_keys($templates) as $tkey) {
    $tpl = EmailsModel::getMailTemplateByKey($tkey);
    if ($tpl && (int)($tpl['is_active'] ?? 0) === 1) {
        ok("{$tkey}");
        $found++;
    } elseif ($tpl && (int)($tpl['is_active'] ?? 0) === 0) {
        err("{$tkey}  ← EXISTS pero is_active=0");
        $missing[] = $tkey;
    } else {
        err("{$tkey}  ← NO EXISTE en BD (ejecuta la migración SQL)");
        $missing[] = $tkey;
    }
}

out("");
out("Templates OK: {$found} / " . count($templates));

if ($missing) {
    out("");
    out("⚠️  Templates faltantes — ejecuta las migraciones:");
    foreach ($missing as $t) {
        out("    - {$t}");
    }
}

/* ── PASO 2: Encolar correos de prueba ───────────────────── */
sep();
out("PASO 2 — Encolando correos de prueba → {$testTo}:");
sep();

$enqueued = 0;
$errors   = 0;

foreach ($templates as $tkey => $vars) {
    // Solo probar los que existen en BD
    $tpl = EmailsModel::getMailTemplateByKey($tkey);
    if (!$tpl || (int)($tpl['is_active'] ?? 0) === 0) {
        err("{$tkey}  ← omitido (no existe/inactivo)");
        continue;
    }

    $result = sendTemplateByKey($tkey, $testTo, $vars, 'TEST SS/PP UNIMO');

    if ($result === 'ok') {
        ok("{$tkey}  → encolado");
        $enqueued++;
    } else {
        err("{$tkey}  → FALLÓ (resultado: " . var_export($result, true) . ")");
        $errors++;
    }
}

out("");
out("Encolados: {$enqueued} | Errores: {$errors}");

/* ── PASO 3: Estado actual de email_queue ────────────────── */
sep();
out("PASO 3 — Estado de email_queue:");
sep();

$stats = $pdo->query(
    "SELECT status, COUNT(*) as total FROM email_queue GROUP BY status"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($stats as $row) {
    out("  {$row['status']}: {$row['total']}");
}

// Últimos 20 correos encolados (para confirmar los de prueba)
out("");
out("Últimos {$enqueued} correos recién encolados (pending → {$testTo}):");

$recent = $pdo->prepare(
    "SELECT id, to_email, subject, status, attempts, created_at
     FROM email_queue
     WHERE to_email = :to
     ORDER BY id DESC
     LIMIT :lim"
);
$recent->bindValue(':to', $testTo);
$recent->bindValue(':lim', $enqueued > 0 ? $enqueued : 15, PDO::PARAM_INT);
$recent->execute();
$rows = $recent->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
    foreach ($rows as $r) {
        out("  [{$r['id']}] {$r['status']} | intentos:{$r['attempts']} | {$r['subject']}");
    }
} else {
    out("  (no se encontraron registros para {$testTo})");
}

sep();
out("✅ Diagnóstico completo.");
out("   Ahora ejecuta el procesador para enviar los correos:");
out("   php controller/process_email_queue.php");
sep();
