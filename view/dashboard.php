<?php
ob_start();

// Título dinámico según la página activa
$_pagina = isset($_GET['pagina']) ? trim($_GET['pagina']) : 'inicio';
$_titulos = [
    'inicio'                 => 'Inicio',
    'events'                 => 'Eventos',
    'students'               => 'Alumnos',
    'external_students'      => 'Alumnos Externos',
    'areas'                  => 'Áreas',
    'users'                  => 'Usuarios',
    'degrees'                => 'Carreras',
    'courses'                => 'Cursos',
    'register_event'         => 'Registrar Evento',
    'event_types'            => 'Tipos de Evento',
    'internship_companies'   => 'Organismos Receptores',
    'practice_solicitud'     => 'Solicitudes de Prácticas',
    'internship_students'    => 'Alumnos en Prácticas',
    'internship_events'      => 'Eventos de Prácticas',
    'reports_practices'      => 'Reportes de Prácticas',
    'students_in_practices'  => 'Alumnos Asignados',
    'configs'                => 'Configuración',
    'internship_areas'       => 'Áreas de Prácticas',
    'area_practicas'         => 'Mis Áreas PP',
];
$_pageTitle = isset($_titulos[$_pagina]) ? $_titulos[$_pagina] . ' – UNIMO' : 'UNIMO';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <base href="/">
    <title><?= htmlspecialchars($_pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <?php include "css.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
</head>

<body>
    <?php require_once __DIR__ . '/../config/Environment.php'; ?>
    <?php if (Environment::isTesting()): ?>
        <div style="position:fixed;left:0;right:0;bottom:0;z-index:99999;pointer-events:none;background:#b45309;color:#fff;text-align:center;font:600 12px/1.6 Montserrat,sans-serif;padding:2px 8px;letter-spacing:.03em;">
            AMBIENTE DE PRUEBAS · BD <?= htmlspecialchars($_ENV['DB_DATABASE'] ?? '') ?> · los correos no salen: se capturan en Mailpit
        </div>
    <?php endif; ?>
    <div class="loader-section">
        <span class="loader"></span>
    </div>
    <?php include 'config/whiteList.php'; ?>
    <script>
        document.onload = pageLoaded();

        function pageLoaded() {
            let loaderSection = document.querySelector('.loader-section');
            loaderSection.classList.add('loaded');
        }

        function closeModal(modal) {
            $('#' + modal).modal('hide');
        }
    </script>

</html>