<?php
/**
 * Guard común de los endpoints del Gestor de envío masivo de correos.
 * Incluir al inicio de cada endpoint del módulo (controller/ajax/mail_bulk/*.php).
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

require_once __DIR__ . '/../../../config/Security.php';
require_once __DIR__ . '/../../../model/conection.php';
require_once __DIR__ . '/../../../model/MailBulkModel.php';

// Solo administradores (mismo criterio que el resto de Configuraciones: rol admin exacto).
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

$CURRENT_USER_ID = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;

// Security::getCsrfToken() asegura que exista un token en la sesión (lo crea si falta).
Security::getCsrfToken();
