<?php

/**
 * FASE 6 · Solicita retroalimentación a la empresa después de la fecha/hora de la entrevista.
 * Recorre las entrevistas cuya fecha/hora ya pasó (estado ENTREVISTA_PROGRAMADA) y que aún
 * no se han notificado, encola un correo a la empresa y las marca para no reenviar.
 *
 * Debe ejecutarse periódicamente por cron / tarea programada (CLI).
 */

require_once __DIR__ . '/../../model/conection.php';
require_once __DIR__ . '/../../model/PracticasModel.php';
require_once __DIR__ . '/../emails.php';

date_default_timezone_set('America/Mexico_City');

echo "Iniciando solicitud de retroalimentación de entrevistas: " . date('Y-m-d H:i:s') . "\n";

$pendientes = PracticasModel::mdlGetEntrevistasPendientesRetro();

if (empty($pendientes)) {
    echo "No hay entrevistas pendientes de retroalimentación.\n";
    exit;
}

$link = 'https://servicioypracticas.unimontrer.edu.mx/';
$encolados = 0;

foreach ($pendientes as $e) {
    if (empty($e['email_empresa'])) {
        // Sin correo de la empresa: marcar igual para no reintentar en cada corrida
        PracticasModel::mdlMarcarRetroSolicitada((int) $e['idEntrevista']);
        continue;
    }
    echo "Solicitando retroalimentación: alumno {$e['nombre_completo']} / empresa {$e['empresa']}\n";
    sendRetroalimentacionEmpresa(
        $e['email_empresa'],
        $e['nombre_contacto'] ?? '',
        $e['nombre_completo'] ?? '',
        $link
    );
    PracticasModel::mdlMarcarRetroSolicitada((int) $e['idEntrevista']);
    $encolados++;
}

echo "Proceso finalizado. Solicitudes de retroalimentación encoladas: $encolados\n";
