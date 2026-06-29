<?php
require_once __DIR__ . '/Security.php';
Security::init();
session_start();

// Verificar expiración de sesión en cada request autenticado
Security::checkSessionExpiry();

// Sanitización segura (FILTER_SANITIZE_STRING fue eliminado en PHP 8.1)
$pagina = isset($_GET['pagina']) ? htmlspecialchars(trim($_GET['pagina']), ENT_QUOTES, 'UTF-8') : 'inicio';
$pagina = $pagina ?: 'inicio';

// ── Rutas Dinámicas ──
if (strpos($pagina, 'corregir-organismo/') === 0) {
    $_GET['token'] = substr($pagina, 19);
    $pagina = 'corregir_organismo';
}

$navWithoutLogin = [
    'login',
    'inscripcionPracticas',
    'inscripcionServicio',
    'inscripcionEmpresas',
    'corregir_organismo'
];

$navWithLogin = [
    // Páginas permitidas para cada rol
    'admin_servicio' => [
        'inicio',
        'events',
        'students',
        'external_students',
        'areas',
    ],
    'admin_practicas' => [
        'inicio',
        'internship_companies',
        'practice_solicitud',
        'internship_students',
        'internship_events',
        'reports_practices',
        'internship_areas',
    ],
    'admin' => [
        'inicio',
        'users',
        'events',
        'event_types',
        'students',
        'external_students',
        'register_event',
        'courses',
        'areas',
        'degrees',
        'internship_companies',
        'practice_solicitud',
        'internship_students',
        'internship_events',
        'reports_practices',
        'internship_areas',
        'configs'
    ],
    'teacher' => [
        'inicio',
        'students',
        'area_practicas'
    ],
    'organismo_externo' => [
        'inicio',
        'students_in_practices'
    ],
    'other' => [
        'inicio'
    ]
];

// Verificar si el usuario está logueado
if (!isset($_SESSION['logged'])) {
    // Permitir el acceso a login o RegisterStudent sin estar logueado
    if (in_array($pagina, $navWithoutLogin, true)) {
        if ($pagina == 'inscripcionServicio') {
            $pagina = 'RegisterStudent';
        } elseif ($pagina == 'inscripcionPracticas') {
            $pagina = 'practicas/RegisterPracticas';
        } elseif ($pagina == 'inscripcionEmpresas') {
            $pagina = 'practicas/RegisterEmpresas';
        } elseif ($pagina == 'OrganismoReceptor') {
            $pagina = 'practicas/organismoReceptor';
        } elseif ($pagina == 'corregir_organismo') {
            $pagina = 'practicas/corregir_organismo';
        }
        include_once 'view/pages/' . $pagina . '.php';
    } else {
        // Redirigir al login si intenta acceder a otras páginas sin estar logueado
        header("Location: /login");
        exit();
    }
} else {
    if ($pagina == 'login') {
        header("Location: ./");
        exit();
    }

    // --- BEGIN: bloque seguro para role/type_admin ---
    $user = $_SESSION['user'] ?? [];
    $userRole = $user['role'] ?? null;

    // Evitar "array offset on bool" si 'tipo_servicio' no es arreglo
    $tipoServicio = $user['tipo_servicio'] ?? null;
    $typeAdmin = is_array($tipoServicio) ? ($tipoServicio['type_admin'] ?? null) : null;

    if ($userRole === 'admin' && $typeAdmin === 1) {
        $role = 'admin_servicio';
    } elseif ($userRole === 'admin' && $typeAdmin === 2) {
        $role = 'admin_practicas';
    } else {
        $role = $userRole ?: '';
        if (in_array($role, ['alumno_practicas', 'student'], true)) {
            $role = 'other';
        }
    }
    // --- END ---
    
    $navAllowed = $navWithLogin[$role] ?? ['inicio'];

    includeAuthPages($pagina, $role, $navAllowed);
}

function includeUserPages($pagina)
{
    include 'view/pages/navs/header.php';
    include 'view/js.php';
    includeCommonComponents();

    // Detectar dinámicamente si está en subcarpeta "practicas/"
    // o en la raíz de view/pages/
    $paths = [
        "view/pages/$pagina.php",
        "view/pages/practicas/$pagina.php"
    ];

    foreach ($paths as $file) {
        if (file_exists($file)) {
            include $file;
            return;
        }
    }

    // Si no encontramos el archivo
    includeError404();
}

function includeAuthPages($pagina, $role, $navs)
{
    if ($role === 'admin') {
        $whitelist = $navs;
    } elseif ($role === 'admin_servicio') {
        $whitelist = $navs;
    } elseif ($role === 'admin_practicas') {
        $whitelist = $navs;
    } elseif ($role === 'teacher') {
        $whitelist = $navs;
    } elseif ($role === 'organismo_externo') {
        $whitelist = $navs;
        // require_once 'view/pages/navs/notifications.php';
    } else {
        $whitelist = ['inicio'];
    }

    if (!in_array($pagina, $whitelist, true)) {
        includeError404();
        return;
    }

    // Ya no es necesario reasignar $pagina aquí: includeUserPages lo resuelve
    includeUserPages($pagina);
}

function includeCommonComponents()
{
    include 'view/pages/navs/sidebar.php';
}

function includeError404()
{
    include 'view/pages/error404.php';
}
