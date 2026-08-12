<?php
// controller/ajax/mail_bulk/campaigns.php
// Correo de prueba, confirmación/creación de campaña, progreso e historial.
require_once __DIR__ . '/_guard.php';

/** Valida que las rutas de adjuntos declaradas por el cliente sean archivos reales
 *  dentro del directorio protegido del módulo (evita rutas arbitrarias del sistema). */
function mailBulkValidateAttachmentPaths(array $relativePaths): array
{
    $base = realpath(__DIR__ . '/../../../uploads/mail_bulk/attachments');
    $valid = [];
    foreach ($relativePaths as $rel) {
        if (!is_string($rel) || $rel === '') {
            continue;
        }
        $full = realpath(__DIR__ . '/../../../uploads/mail_bulk/attachments/' . ltrim($rel, '/\\'));
        if ($full && $base && str_starts_with($full, $base) && is_file($full)) {
            $valid[] = $full;
        }
    }
    return $valid;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'get_progress') {
        $id = (int) ($_GET['campaign_id'] ?? 0);
        $progress = $id > 0 ? MailBulkModel::getProgress($id) : null;
        if (!$progress) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Envío no encontrado.']);
            exit;
        }
        echo json_encode(['ok' => true] + $progress, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'list_history') {
        echo json_encode(['ok' => true, 'csrf' => Security::getCsrfToken(), 'campaigns' => MailBulkModel::listCampaignHistory()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'get') {
        $id = (int) ($_GET['id'] ?? 0);
        $campaign = $id > 0 ? MailBulkModel::getCampaignById($id) : null;
        if (!$campaign) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Envío no encontrado.']);
            exit;
        }
        $campaign['failed_recipients'] = MailBulkModel::getCampaignFailedRecipients($id);
        echo json_encode(['ok' => true, 'campaign' => $campaign], JSON_UNESCAPED_UNICODE);
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

    /* ── Vista previa: el correo YA saneado y armado, exactamente como saldrá ──
       Se hace en el servidor a propósito. Antes la previa se armaba en el navegador
       con el HTML crudo, así que no mostraba lo que el saneado quitaba: una plantilla
       podía verse bien en pantalla y llegar desarmada al buzón. No envía nada ni toca
       la base de datos. */
    case 'preview': {
        $subject = trim((string) ($data['subject'] ?? ''));
        $bodyHtml = (string) ($data['body_html'] ?? '');
        $useLayout = !isset($data['use_layout']) || (bool) $data['use_layout'];

        $name = (string) ($data['sample_name'] ?? 'Juan Pérez de ejemplo');
        $email = (string) ($data['sample_email'] ?? 'juan.perez@ejemplo.com');

        $clean = MailBulkModel::sanitizeHtml($bodyHtml);
        $renderedSubject = MailBulkModel::interpolatePersonal($subject, $name, $email);
        $renderedBody = MailBulkModel::interpolatePersonal($clean, $name, $email);

        echo json_encode([
            'ok' => true,
            'html' => MailBulkModel::buildEmailHtml($renderedSubject, $renderedBody, $useLayout),
        ], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ── Enviar correo de prueba: nunca toca la lista completa, no pasa por la cola ── */
    case 'send_test': {
        require_once __DIR__ . '/../../../controller/emails.php';

        $to = filter_var(trim((string) ($data['to'] ?? '')), FILTER_VALIDATE_EMAIL);
        $subject = trim((string) ($data['subject'] ?? ''));
        $bodyHtml = (string) ($data['body_html'] ?? '');
        $fromName = trim((string) ($data['from_name'] ?? ''));
        $useLayout = !isset($data['use_layout']) || (bool) $data['use_layout'];
        $attachments = mailBulkValidateAttachmentPaths(is_array($data['attachments'] ?? null) ? $data['attachments'] : []);

        if (!$to) {
            echo json_encode(['ok' => false, 'error' => 'El correo de destino no es válido.']);
            break;
        }
        if ($subject === '' || !MailBulkModel::hasVisibleContent($bodyHtml)) {
            echo json_encode(['ok' => false, 'error' => 'Escribe el asunto y el mensaje antes de enviar la prueba.']);
            break;
        }

        $smtp = MailBulkModel::getSmtpConfigForSending();
        if (!$smtp) {
            echo json_encode(['ok' => false, 'error' => 'Primero configura el servidor de correo de este módulo.']);
            break;
        }

        $clean = MailBulkModel::sanitizeHtml($bodyHtml);
        $sampleName = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '')) ?: 'Persona de ejemplo';
        $renderedSubject = '[PRUEBA] ' . MailBulkModel::interpolatePersonal($subject, $sampleName, $to);
        $renderedBody = MailBulkModel::interpolatePersonal($clean, $sampleName, $to);
        // Misma regla que el envío real, para que la prueba llegue idéntica.
        $renderedBody = MailBulkModel::buildEmailHtml($renderedSubject, $renderedBody, $useLayout);
        $plain = MailBulkModel::htmlToPlainText($renderedBody);

        $errorOut = null;
        $result = MailService::dispatchMail($to, $renderedSubject, $renderedBody, $plain, $fromName, $attachments, $smtp, $errorOut);

        if ($result === 'ok') {
            echo json_encode(['ok' => true, 'message' => "Correo de prueba enviado a {$to}. Revisa la bandeja de entrada (y spam)."], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['ok' => false, 'error' => MailBulkModel::translateMailError($errorOut)], JSON_UNESCAPED_UNICODE);
        }
        break;
    }

    /* ── Confirmar y comenzar el envío masivo ── */
    case 'confirm_send': {
        $clientToken = trim((string) ($data['client_token'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $bodyHtml = (string) ($data['body_html'] ?? '');
        $fromName = trim((string) ($data['from_name'] ?? ''));
        $useLayout = !isset($data['use_layout']) || (bool) $data['use_layout'];
        $recipientIds = is_array($data['recipient_ids'] ?? null) ? $data['recipient_ids'] : [];
        $attachments = mailBulkValidateAttachmentPaths(is_array($data['attachments'] ?? null) ? $data['attachments'] : []);

        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $clientToken)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Falta identificar este envío. Recarga la página e inténtalo de nuevo.']);
            break;
        }
        if ($subject === '' || !MailBulkModel::hasVisibleContent($bodyHtml)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Escribe el asunto y el mensaje antes de enviar.']);
            break;
        }
        if (!$fromName) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Indica el nombre del remitente.']);
            break;
        }

        $smtp = MailBulkModel::getSmtpConfigForSending();
        if (!$smtp) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Primero configura el servidor de correo de este módulo.']);
            break;
        }

        $recipients = MailBulkModel::getRecipientsByIds($recipientIds);
        if (!$recipients) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Selecciona al menos una persona para recibir el correo.']);
            break;
        }

        $clean = MailBulkModel::sanitizeHtml($bodyHtml);

        $result = MailBulkModel::createCampaign($clientToken, $subject, $clean, $fromName, $attachments, $recipients, $CURRENT_USER_ID, $useLayout);
        $campaign = $result['campaign'];

        // Primer lote inmediato para que el administrador vea progreso al instante;
        // el resto lo drena controller/cron/process_mail_bulk_queue.php.
        if ($result['created']) {
            MailBulkModel::processBatch((int) $campaign['id'], $smtp);
            $campaign = MailBulkModel::getCampaignById((int) $campaign['id']);
        }

        echo json_encode([
            'ok' => true,
            'campaign_id' => (int) $campaign['id'],
            'total' => (int) $campaign['total_recipients'],
            'created' => $result['created'],
        ], JSON_UNESCAPED_UNICODE);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
}
