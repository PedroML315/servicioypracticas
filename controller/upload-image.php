<?php
// controller/upload-image.php
declare(strict_types=1);

// Capturar cualquier error fatal
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => "Error PHP [$errno]: $errstr en $errfile:$errline"]);
    exit;
});

try {

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Requiere admin (ajusta a tu política)
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Acceso no autorizado']); exit;
}

// CSRF
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfHeader)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'CSRF inválido']); exit;
}

// Solo POST multipart
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Método no permitido']); exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Error al recibir el archivo (código: ' . $file['error'] . ')']); exit;
}

// Validaciones
$maxBytes = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxBytes) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'La imagen supera 5 MB']); exit;
}

// Detectar mime real — con fallback al tipo declarado por el navegador
$mime = '';
if (extension_loaded('fileinfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detected = $finfo->file($file['tmp_name']);
    if ($detected && $detected !== 'application/octet-stream') {
        $mime = $detected;
    }
}
if (empty($mime)) {
    // Fallback: usar el tipo declarado por el navegador
    $mime = $file['type'] ?? '';
    // Normalizar svg
    if ($mime === 'image/svg') $mime = 'image/svg+xml';
}

$allowed = [
  'image/png'      => 'png',
  'image/jpeg'     => 'jpg',
  'image/jpg'      => 'jpg',
  'image/webp'     => 'webp',
  'image/svg+xml'  => 'svg',
];
if (!isset($allowed[$mime])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Formato no permitido (PNG, JPG, WEBP, SVG). MIME detectado: ' . $mime]); exit;
}

$ext = $allowed[$mime];

// Destino
$destBase = realpath(__DIR__ . '/../view/assets/images');
if ($destBase === false) {
    $destBase = __DIR__ . '/../view/assets/images';
    if (!is_dir($destBase)) {
        if (!mkdir($destBase, 0775, true) && !is_dir($destBase)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo crear el directorio de imágenes']); exit;
        }
    }
}

// Nombre seguro
$slot = preg_replace('~[^a-z0-9_\-]~i', '', $_POST['slot'] ?? 'img');
$filename = sprintf('%s_%s.%s', $slot, bin2hex(random_bytes(8)), $ext);
$destPath = rtrim($destBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

// Verificar permisos antes de intentar guardar
if (!is_writable($destBase)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'El directorio de imágenes no tiene permisos de escritura']); exit;
}

// Guardar
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'No se pudo mover el archivo subido al destino']); exit;
}

// URL pública relativa (funciona en cualquier dominio/entorno)
$publicUrl = 'view/assets/images/' . $filename;

echo json_encode(['ok' => true, 'url' => $publicUrl], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Excepción: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()]);
}
