<?php
// controller/ajax/mail_bulk/smtp_config.php
// Configuración del servidor de correo propio del Gestor de envío masivo.
require_once __DIR__ . '/_guard.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET': {
        $cfg = MailBulkModel::getSmtpConfigPublic();
        echo json_encode(['ok' => true, 'csrf' => Security::getCsrfToken(), 'config' => $cfg], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'POST': {
        Security::validateCsrf();

        $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ctype, 'application/json') === false) {
            http_response_code(415);
            echo json_encode(['ok' => false, 'error' => 'Content-Type debe ser application/json']);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
            break;
        }

        $host = trim((string) ($data['host'] ?? ''));
        $port = (int) ($data['port'] ?? 587);
        $encryption = in_array($data['encryption'] ?? '', ['tls', 'ssl', 'none'], true) ? $data['encryption'] : 'tls';
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $fromEmail = trim((string) ($data['from_email'] ?? ''));
        $fromName = trim((string) ($data['from_name'] ?? ''));

        if ($host === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Indica el servidor de correo.']);
            break;
        }
        if ($port < 1 || $port > 65535) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'El puerto no es válido.']);
            break;
        }
        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'El correo del remitente no es válido.']);
            break;
        }
        if ($fromName === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Indica el nombre que verán las personas como remitente.']);
            break;
        }

        MailBulkModel::saveSmtpConfig([
            'host' => $host, 'port' => $port, 'encryption' => $encryption,
            'username' => $username, 'password' => $password,
            'from_email' => $fromEmail, 'from_name' => $fromName,
        ], $CURRENT_USER_ID);

        echo json_encode(['ok' => true, 'message' => 'Configuración del correo guardada correctamente.'], JSON_UNESCAPED_UNICODE);
        break;
    }

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
}
