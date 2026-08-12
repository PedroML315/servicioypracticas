<?php
// controller/ajax/mail_bulk/messages.php
// Redacciones guardadas (mensajes reutilizables).
require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    if ($action === 'list') {
        echo json_encode(['ok' => true, 'csrf' => Security::getCsrfToken(), 'messages' => MailBulkModel::listSavedMessages()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'get') {
        $id = (int) ($_GET['id'] ?? 0);
        $msg = $id > 0 ? MailBulkModel::getSavedMessage($id) : null;
        if (!$msg) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Redacción no encontrada.']);
            exit;
        }
        echo json_encode(['ok' => true, 'message' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    case 'save': {
        $name = trim((string) ($data['name'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $bodyHtml = (string) ($data['body_html'] ?? '');
        $useLayout = !isset($data['use_layout']) || (bool) $data['use_layout'];
        $id = !empty($data['id']) ? (int) $data['id'] : null;

        if ($name === '' || $subject === '' || !MailBulkModel::hasVisibleContent($bodyHtml)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Completa el nombre, el asunto y el mensaje antes de guardar.']);
            break;
        }

        $clean = MailBulkModel::sanitizeHtml($bodyHtml);
        $savedId = MailBulkModel::saveSavedMessage($id, $name, $subject, $clean, $CURRENT_USER_ID, $useLayout);
        echo json_encode(['ok' => true, 'id' => $savedId, 'message' => 'Redacción guardada correctamente.'], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'delete': {
        $id = (int) ($data['id'] ?? 0);
        $ok = $id > 0 && MailBulkModel::deleteSavedMessage($id);
        echo json_encode(['ok' => $ok], JSON_UNESCAPED_UNICODE);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
}
