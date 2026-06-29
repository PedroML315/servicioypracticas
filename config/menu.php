<?php


declare(strict_types=1);
// --------------------------------------
// Configuración
// --------------------------------------
const ROLE_LABELS = [
    'admin_servicio' => 'Administrador de Servicio Social',
    'admin_practicas' => 'Administrador de Prácticas',
    'admin' => 'Administrador',
    'teacher' => 'Encargado',
    'student' => 'Estudiante',
    'organismo_externo' => 'Organismo externo',
    'alumno_practicas' => 'Alumno Prácticas profesionales',
];

const MENU_CONFIG = [
    'admin' => [
        ['type' => 'link', 'href' => 'inicio', 'icon' => 'fa-duotone fa-house', 'text' => 'Tablero'],
        [
            'type' => 'dropdown',
            'id' => 'ppMenu',
            'icon' => 'fa-duotone fa-briefcase',
            'text' => 'Prácticas profesionales',
            'items' => [
                ['href' => 'internship_companies', 'icon' => 'fa-duotone fa-building', 'text' => 'Organismos receptores'],
                ['href' => 'internship_students', 'icon' => 'fa-duotone fa-user-tie', 'text' => 'Estudiantes'],
                ['href' => 'internship_areas', 'icon' => 'fa-duotone fa-building-columns', 'text' => 'Áreas internas'],
            ],
        ],
        [
            'type' => 'dropdown',
            'id' => 'ssMenu',
            'icon' => 'fa-duotone fa-hands-helping',
            'text' => 'Servicio social',
            'items' => [
                ['href' => 'courses', 'icon' => 'fad fa-school', 'text' => 'Ciclo escolar'],
                ['href' => 'degrees', 'icon' => 'fad fa-graduation-cap', 'text' => 'Licenciaturas'],
                ['href' => 'areas', 'icon' => 'fad fa-ball-pile', 'text' => 'Áreas'],
                ['href' => 'events', 'icon' => 'fad fa-calendar', 'text' => 'Eventos'],
                ['href' => 'students', 'icon' => 'fad fa-user-graduate', 'text' => 'Estudiantes internos'],
                ['href' => 'external_students', 'icon' => 'fad fa-user-tie', 'text' => 'Estudiantes externos'],
            ],
        ],
        ['type' => 'link', 'href' => 'configs', 'icon' => 'fad fa-cog', 'text' => 'Configuraciones'],
        ['type' => 'link', 'href' => 'users', 'icon' => 'fa-duotone fa-users', 'text' => 'Admin y encargados'],
    ],
    'admin_servicio' => [
        ['type' => 'link', 'href' => 'inicio', 'icon' => 'fa-duotone fa-house', 'text' => 'Tablero'],
        ['type' => 'link', 'href' => 'areas', 'icon' => 'fad fa-ball-pile', 'text' => 'Áreas'],
        ['type' => 'link', 'href' => 'events', 'icon' => 'fad fa-calendar', 'text' => 'Eventos'],
        ['type' => 'link', 'href' => 'students', 'icon' => 'fad fa-user-graduate', 'text' => 'Estudiantes internos'],
        ['type' => 'link', 'href' => 'external_students', 'icon' => 'fad fa-user-tie', 'text' => 'Estudiantes externos'],
    ],
    'admin_practicas' => [
        ['type' => 'link', 'href' => 'inicio', 'icon' => 'fa-duotone fa-house', 'text' => 'Tablero'],
        ['type' => 'link', 'href' => 'internship_companies', 'icon' => 'fa-duotone fa-building', 'text' => 'Organismos receptores'],
        ['type' => 'link', 'href' => 'internship_students', 'icon' => 'fa-duotone fa-user-tie', 'text' => 'Estudiantes'],
        ['type' => 'link', 'href' => 'internship_areas', 'icon' => 'fa-duotone fa-building-columns', 'text' => 'Áreas internas'],
    ],
    // Roles con menú simple (solo Tablero)
    'teacher' => [
        ['type' => 'link', 'href' => 'inicio', 'icon' => 'fa-duotone fa-house', 'text' => 'Tablero'],
        ['type' => 'link', 'href' => 'students', 'icon' => 'fad fa-user-graduate', 'text' => 'Estudiantes'],
        ['type' => 'link', 'href' => 'area_practicas', 'icon' => 'fa-duotone fa-building-columns', 'text' => 'Mis Áreas PP'],
    ],
    'student' => [['type' => 'link', 'href' => 'inicio', 'icon' => 'fa-duotone fa-house', 'text' => 'Tablero']],
    'alumno_practicas' => [['type' => 'link', 'href' => 'inicio', 'icon' => 'fa-duotone fa-house', 'text' => 'Tablero']],
    'organismo_externo' => [
        ['type' => 'link', 'href' => 'inicio', 'icon' => 'fa-duotone fa-house', 'text' => 'Tablero'],
        ['type' => 'link', 'href' => 'students_in_practices', 'icon' => 'fad fa-user-graduate', 'text' => 'Practicantes']
    ],
    // Menú por defecto
    'default' => [['type' => 'link', 'href' => 'inicio', 'icon' => 'fa-duotone fa-house', 'text' => 'Tablero']],
];