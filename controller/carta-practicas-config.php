<?php
// controller/carta-practicas-config.php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

// Protege el endpoint (solo admin)
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

$CONFIG_PATH = __DIR__ . '/../config/carta_practicas_config.json';

// CSRF bootstrap
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function loadConfig(string $path): array
{
    if (!file_exists($path)) return [];
    $json = file_get_contents($path);
    if (str_starts_with($json, "\xEF\xBB\xBF")) {
        $json = substr($json, 3);
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function saveConfig(string $path, array $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;

    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) return false;
    }

    $fp = fopen($path, 'c+');
    if (!$fp) return false;
    try {
        if (!flock($fp, LOCK_EX)) return false;
        ftruncate($fp, 0);
        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }
    return true;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET': {
        $cfg = loadConfig($CONFIG_PATH);
        echo json_encode(['ok' => true, 'csrf' => $csrf, 'config' => $cfg], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'POST': {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ctype, 'application/json') === false) {
            http_response_code(415);
            echo json_encode(['ok' => false, 'error' => 'Content-Type debe ser application/json'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
            break;
        }

        if (!saveConfig($CONFIG_PATH, $data)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el JSON'], JSON_UNESCAPED_UNICODE);
            break;
        }

        echo json_encode(['ok' => true, 'message' => 'Guardado correctamente'], JSON_UNESCAPED_UNICODE);
        break;
    }

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
}
