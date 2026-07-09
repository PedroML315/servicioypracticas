<?php
// controller/ajax/mail_templates.php
declare(strict_types=1);
require_once "../../model/forms.models.php";

header('Content-Type: application/json');

session_start();
if ($_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// ===== Inputs =====
$action = $_POST['action'] ?? '';

// ===== Actions =====
switch ($action) {
    case 'list':
        $templates = EmailsModel::getMailTemplates();
        echo json_encode(['success' => true, 'templates' => $templates]);
        break;
    case 'get':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $templates = EmailsModel::getMailTemplates();
            $template = null;
            foreach ($templates as $tpl) {
                if ($tpl['id'] === $id) {
                    $template = $tpl;
                    break;
                }
            }
            if ($template) {
                echo json_encode(['success' => true, 'template' => $template]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de plantilla no válido.']);
        }
        break;

    case 'save':
        $id = intval($_POST['id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $html = $_POST['html'] ?? '';
        $idUser = $_SESSION['user']['id'] ?? 0;
        if ($id > 0 && $subject !== '' && $html !== '') {
            $text_plain = strip_tags($html); // Simple conversion to plain text
            $result = EmailsModel::updateMailTemplate($id, $subject, $html, $text_plain, $idUser);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Plantilla actualizada correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la plantilla.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos de plantilla no válidos.']);
        }
        break;

    case 'send_test':
        // Envía al correo indicado el HTML ya renderizado por el editor
        // (idéntico a la vista previa), de forma directa (sin pasar por la cola).
        if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) ($_POST['csrf_token'] ?? ''))) {
            echo json_encode(['success' => false, 'message' => 'Sesión no válida. Recarga la página.']);
            break;
        }
        $to = filter_var(trim($_POST['to'] ?? ''), FILTER_VALIDATE_EMAIL);
        $subject = trim($_POST['subject'] ?? '');
        $html = $_POST['html'] ?? '';
        if (!$to) {
            echo json_encode(['success' => false, 'message' => 'El correo de destino no es válido.']);
            break;
        }
        if ($subject === '' || trim($html) === '') {
            echo json_encode(['success' => false, 'message' => 'Asunto y contenido no pueden estar vacíos.']);
            break;
        }
        require_once __DIR__ . '/../emails.php';
        $subjectTest = '[PRUEBA] ' . $subject;
        $plain = trim(strip_tags($html));
        $result = MailService::dispatchMail($to, $subjectTest, $html, $plain);
        if ($result === 'ok') {
            echo json_encode(['success' => true, 'message' => "Correo de prueba enviado a {$to}. Revisa la bandeja de entrada (y spam)."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo enviar el correo de prueba. Revisa la configuración de correo del servidor.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}