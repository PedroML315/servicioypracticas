<?php
/**
 * Recordatorios automáticos de prácticas profesionales.
 * ─────────────────────────────────────────────────────────────────────────
 *  Recorre a los practicantes activos y encola los recordatorios de ATRASO
 *  que correspondan — siempre a la empresa y al alumno:
 *
 *    overdue_partial  pasó 180 h sin reporte parcial        (diario)
 *    overdue_final    pasó 360 h sin reporte final          (diario)
 *
 *  Los recordatorios de una sola vez (inicio, 135 h y 315 h) NO se mandan
 *  aquí: salen en el momento en que se aprueba la asistencia que cruza el
 *  umbral, desde `PracticasController::ctrRecordatoriosPorUmbral()`.
 *
 *  Los envíos quedan registrados en `internship_reminders`, así que correr
 *  el script dos veces el mismo día no duplica ningún correo.
 *
 *  Debe ejecutarse una vez al día a las 12:00 por tarea programada (CLI):
 *    C:\xampp\php\php.exe <ruta>\controller\cron\cron_recordatorios_practicas.php
 *
 *  Con `--dry-run` muestra exactamente qué correos saldrían, sin encolar
 *  ninguno ni marcar nada como enviado. Útil para revisar antes de activar
 *  la tarea programada.
 * ─────────────────────────────────────────────────────────────────────────
 */

// Solo por línea de comandos (Programador de tareas). Abierto por URL
// imprimiría nombres de alumnos con sus horas y dispararía correos fuera
// de horario.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once __DIR__ . '/../../model/conection.php';
require_once __DIR__ . '/../../model/PracticasModel.php';
require_once __DIR__ . '/../emails.php';

date_default_timezone_set('America/Mexico_City');

$dryRun = in_array('--dry-run', $argv ?? [], true);

$hoy = date('Y-m-d');
echo "Recordatorios de prácticas profesionales: " . date('Y-m-d H:i:s') . "\n";
if ($dryRun) {
    echo "*** SIMULACIÓN: no se encola ningún correo ni se marca nada. ***\n";
}
echo "\n";

$practicantes = PracticasModel::mdlGetPracticantesParaRecordatorio();

if (!$practicantes) {
    echo "No hay practicantes activos.\n";
    exit;
}

$enviados = PracticasModel::mdlGetRecordatoriosEnviados();
$totalCorreos = 0;
$totalOmitidos = 0;

/** Toca reenviar si nunca se envió, o si el último envío fue antes de hoy. */
$toca = function (array $p, string $type) use ($enviados, $hoy): bool {
    $clave = "{$p['idStudent']}-{$p['idPractica']}-{$type}";
    return !isset($enviados[$clave]) || $enviados[$clave] < $hoy;
};

foreach ($practicantes as $p) {
    $horas = (float) $p['horas'];
    $vars = [
        'studentName' => $p['studentName'] ?? '',
        'matricula'   => $p['matricula'] ?? '',
        'empresa'     => $p['empresa'] ?? '',
        'contactName' => $p['contactName'] ?? '',
        'horas'       => rtrim(rtrim(number_format($horas, 2, '.', ''), '0'), '.'),
    ];

    // Qué reportes están vencidos según las horas acumuladas.
    $pendientes = [];

    if ($horas >= PracticasModel::RECORDATORIO_LIMITE_PARCIAL && !$p['tieneReporteParcial']) {
        $pendientes[] = 'overdue_partial';
    }
    if ($horas >= PracticasModel::RECORDATORIO_LIMITE_FINAL && !$p['tieneReporteFinal']) {
        $pendientes[] = 'overdue_final';
    }

    foreach ($pendientes as $type) {
        if (!$toca($p, $type)) {
            continue;
        }

        $destinos = [
            'empresa' => trim((string) $p['empresaEmail']),
            'alumno'  => trim((string) $p['studentEmail']),
        ];

        $encolados = 0;
        foreach ($destinos as $quien => $correo) {
            if ($correo === '') {
                echo "  [!] {$p['studentName']} · $type: sin correo de $quien\n";
                continue;
            }
            if (!$dryRun) {
                sendRecordatorioPracticas($type, $quien, $correo, $vars);
            }
            $encolados++;
        }

        if ($encolados === 0) {
            // Sin ningún destinatario no hay nada que registrar: si mañana
            // capturan el correo, el recordatorio saldrá entonces.
            $totalOmitidos++;
            continue;
        }

        if (!$dryRun) {
            PracticasModel::mdlRegistrarRecordatorio(
                (int) $p['idStudent'],
                (int) $p['idPractica'],
                $type
            );
        }
        $totalCorreos += $encolados;
        $marca = $dryRun ? '·' : '✓';
        echo "  $marca {$p['studentName']} ({$vars['horas']} h) · $type → "
            . implode(', ', array_keys(array_filter($destinos, fn($c) => $c !== ''))) . "\n";
    }
}

echo "\n" . ($dryRun ? "Correos que se enviarían: " : "Correos encolados: ") . "$totalCorreos\n";
if ($totalOmitidos) {
    echo "Recordatorios omitidos por falta de correos: $totalOmitidos\n";
}
echo "Terminado: " . date('Y-m-d H:i:s') . "\n";
