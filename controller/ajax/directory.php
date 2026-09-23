<?php
/**
 * controller/ajax/directory.php
 * Alta, edición, borrado y búsqueda del directorio institucional
 * (Configuraciones → Directorio institucional).
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

require_once __DIR__ . '/../../config/Security.php';
require_once __DIR__ . '/../../model/DirectoryModel.php';

// Solo administradores (mismo criterio que el resto de Configuraciones).
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

// Security::getCsrfToken() asegura que exista un token en la sesión (lo crea si falta).
Security::getCsrfToken();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    if ($action === 'list') {
        $search = trim((string) ($_GET['q'] ?? ''));
        echo json_encode([
            'ok'       => true,
            'csrf'     => Security::getCsrfToken(),
            'contacts' => DirectoryModel::listContacts($search),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

Security::validateCsrf();

$ctype = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($ctype, 'application/json') === false) {
    http_response_code(415);
    echo json_encode(['ok' => false, 'error' => 'Content-Type debe ser application/json']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

$action = $data['action'] ?? '';

switch ($action) {
    case 'create': {
        $result = DirectoryModel::createContact(
            (string) ($data['full_name'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['job_title'] ?? '')
        );
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'update': {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Identificador no válido.']);
            break;
        }
        $result = DirectoryModel::updateContact(
            $id,
            (string) ($data['full_name'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['job_title'] ?? '')
        );
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'delete':
    case 'bulk_delete': {
        $ids = $data['ids'] ?? ($data['id'] ?? []);
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $deleted = DirectoryModel::deleteContacts($ids);
        echo json_encode(['ok' => true, 'deleted' => $deleted], JSON_UNESCAPED_UNICODE);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
}
