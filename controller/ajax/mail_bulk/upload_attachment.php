<?php
// controller/ajax/mail_bulk/upload_attachment.php
// Sube un archivo adjunto para el correo masivo (multipart, un archivo por llamada).
require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token de seguridad inválido. Recarga la página.']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No se recibió el archivo.']);
    exit;
}

$file = $_FILES['file'];

$maxBytes = 10 * 1024 * 1024; // 10 MB por archivo
if ($file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo supera el tamaño máximo permitido (10 MB).']);
    exit;
}

$mime = '';
if (extension_loaded('fileinfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detected = $finfo->file($file['tmp_name']);
    if ($detected) {
        $mime = $detected;
    }
}

$allowed = [
    'application/pdf' => 'pdf',
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'text/plain' => 'txt',
    // Los .docx/.xlsx/.pptx son ZIP por dentro: finfo a veces los detecta como application/zip.
    'application/zip' => null,
];

if (!array_key_exists($mime, $allowed)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Este tipo de archivo no está permitido.']);
    exit;
}

$declaredExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$officeExts = ['docx', 'xlsx', 'pptx'];
if ($mime === 'application/zip') {
    if (!in_array($declaredExt, $officeExts, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Este tipo de archivo no está permitido.']);
        exit;
    }
    $ext = $declaredExt;
} else {
    $ext = $allowed[$mime];
}

$destDir = __DIR__ . '/../../../uploads/mail_bulk/attachments';
if (!is_dir($destDir)) {
    mkdir($destDir, 0775, true);
}
if (!is_writable($destDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el archivo en el servidor.']);
    exit;
}

$safeName = sprintf('%s.%s', bin2hex(random_bytes(16)), $ext);
$destPath = $destDir . DIRECTORY_SEPARATOR . $safeName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el archivo en el servidor.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'path' => $safeName, // ruta relativa dentro de uploads/mail_bulk/attachments/
    'original_name' => $file['name'],
    'size' => $file['size'],
], JSON_UNESCAPED_UNICODE);
