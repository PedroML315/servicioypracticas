<?php
session_start();

$role = $_SESSION['user']['role'] ?? '';
$userId = $_SESSION['user']['id'] ?? '';

// Si no hay sesión válida, redirigir al inicio
if (!isset($_SESSION['logged']) || empty($role)) {
    header("Location: /");
    exit;
}

$file = $_GET['file'] ?? '';
if (empty($file)) {
    header("Location: /");
    exit;
}

// Seguridad: Prevenir Directory Traversal
$file = str_replace(['../', '..\\', "\0"], '', $file);

// Permisos
$isAdmin = in_array($role, ['admin', 'admin_practicas'], true);

// Si es un organismo, verificar si la carpeta coincide con su ID
$folderId = explode('/', str_replace('\\', '/', $file))[0];
$isOwner = ($role === 'organismo' && (string)$userId === (string)$folderId);

// Si no es admin y no es el dueño del archivo, redirigir al inicio
if (!$isAdmin && !$isOwner) {
    header("Location: /");
    exit;
}

$baseDir = realpath(__DIR__ . '/../uploads');
$filePath = realpath($baseDir . DIRECTORY_SEPARATOR . $file);

// Verificar que el archivo real esté dentro del directorio uploads (seguridad extra)
if (!$filePath || strpos($filePath, $baseDir) !== 0 || !file_exists($filePath)) {
    header("HTTP/1.0 404 Not Found");
    echo "Archivo no encontrado.";
    exit;
}

// Determinar el MIME type real
$mimeType = mime_content_type($filePath);
if (!$mimeType) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeMap = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg'=> 'image/jpeg'
    ];
    $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;
