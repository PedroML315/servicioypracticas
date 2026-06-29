<?php

/**
 * Script para verificar cartas de presentación expiradas.
 * Debe ejecutarse periódicamente mediante un cron job o tarea programada.
 */

require_once __DIR__ . '/../../model/conection.php';
require_once __DIR__ . '/../../model/PracticasModel.php';
require_once __DIR__ . '/../emails.php';

date_default_timezone_set('America/Mexico_City');

echo "Iniciando verificación de cartas expiradas: " . date('Y-m-d H:i:s') . "\n";

$expiradas = PracticasModel::mdlGetCartasExpiradas();

if (empty($expiradas)) {
    echo "No hay cartas expiradas.\n";
    exit;
}

foreach ($expiradas as $carta) {
    echo "Expirando carta {$carta['code']} del alumno {$carta['student_id']}\n";

    // 1. Marcar como expirada y rechazar postulación
    PracticasModel::mdlExpirarCarta($carta['id'], $carta['student_id']);

    // 2. Notificar al alumno
    sendPpCartaPresentacionExpirada($carta['email'], $carta['nombre_completo']);

    // 3. Notificar al administrador
    sendPpCartaPresentacionExpiradaAdmin($carta['nombre_completo']);
}

echo "Proceso finalizado. Cartas expiradas: " . count($expiradas) . "\n";
