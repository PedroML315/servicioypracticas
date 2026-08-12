<?php
// controller/ajax/mail_bulk/recipients.php
// Alta, edición, borrado, búsqueda e importación CSV de destinatarios.
require_once __DIR__ . '/_guard.php';

$method = $_SERVER['REQUEST_METHOD'];
$isMultipart = stripos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false;

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    if ($action === 'list') {
        $search = trim((string) ($_GET['q'] ?? ''));
        echo json_encode(['ok' => true, 'csrf' => Security::getCsrfToken(), 'recipients' => MailBulkModel::listRecipients($search)], JSON_UNESCAPED_UNICODE);
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

/* ── Importación CSV: multipart, csrf viaje como campo de formulario ── */
if ($isMultipart) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token de seguridad inválido. Recarga la página.']);
        exit;
    }
    if (empty($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No se recibió el archivo CSV.']);
        exit;
    }
    $file = $_FILES['csv'];
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'El archivo supera el tamaño máximo permitido (5 MB).']);
        exit;
    }
    $mime = '';
    if (extension_loaded('fileinfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
    }
    $allowedMimes = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv' || !in_array($mime, $allowedMimes, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'El archivo debe ser un CSV válido (nombre y correo).']);
        exit;
    }

    $report = MailBulkModel::importCsv($file['tmp_name'], $CURRENT_USER_ID);
    echo json_encode($report, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Resto de acciones: JSON ── */
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
        $result = MailBulkModel::createRecipient(
            (string) ($data['name'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['notes'] ?? '')
            , $CURRENT_USER_ID
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
        $result = MailBulkModel::updateRecipient(
            $id,
            (string) ($data['name'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['notes'] ?? '')
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
        $deleted = MailBulkModel::deleteRecipients($ids);
        echo json_encode(['ok' => true, 'deleted' => $deleted], JSON_UNESCAPED_UNICODE);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
}
