<?php

require_once "../../model/forms.models.php";
require_once "../forms.controller.php";

// ── Seguridad: sólo administradores autenticados pueden acceder ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sessionUser = $_SESSION['user'] ?? null;
$sessionRole = is_array($sessionUser) ? ($sessionUser['role'] ?? '') : '';
if (empty($_SESSION['logged']) || !in_array($sessionRole, ['admin', 'superadmin', 'coordinador'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'get_externals':
            $response = PracticasController::ctrGetExternals();
            
            $path = __DIR__ . "/../../uploads/";

            foreach ($response as &$external) {
                $dir   = rtrim($path, "/\\") . DIRECTORY_SEPARATOR . $external['id'] . DIRECTORY_SEPARATOR;
                $paths = glob($dir . '*') ?: [];                 // todas las entradas
                $files = array_filter($paths, 'is_file');        // sólo archivos (no carpetas)
                $external['files'] = array_values(array_map('basename', $files));
            }
            unset($external); // buena práctica al usar foreach por referencia
            echo json_encode($response);
            break;
        case 'accept_external':
            if (isset($_POST['id'])) {
                $id = $_POST['id'];
                $response = PracticasController::ctrAcceptExternal($id);
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'ID not provided']);
            }
            break;
        case 'disable_external':
            if (isset($_POST['id'])) {
                $id = $_POST['id'];
                $response = PracticasController::ctrDisableExternal($id);
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'ID not provided']);
            }
            break;
    }
} else {
    echo json_encode(['error' => 'No action specified']);
}