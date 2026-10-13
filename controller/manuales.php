<?php
/**
 * Entrega los manuales en PDF (config/manuales.php) solo a usuarios con sesión.
 *   controller/manuales.php?m=empresa  -> abre ese manual si el perfil lo tiene permitido
 *   controller/manuales.php            -> abre el manual del perfil; si tiene varios (Coordinación),
 *                                         lleva a la página "Manuales"
 * La carpeta docs/manuales/ está bloqueada en web.config y .htaccess: este es el único acceso.
 */
session_status() === PHP_SESSION_NONE && session_start();

$role = $_SESSION['user']['role'] ?? '';
if (!isset($_SESSION['logged']) || $role === '') {
    http_response_code(401);
    exit('Inicia sesión en la plataforma para consultar el manual.');
}
// La descarga no necesita la sesión abierta: así no bloquea otras peticiones del usuario
session_write_close();

$manuales = require __DIR__ . '/../config/manuales.php';
$permitidos = array_keys(array_filter(
    $manuales,
    static function (array $m) use ($role): bool {
        return $role === 'admin' || in_array($role, $m['roles'], true);
    }
));

$clave = trim((string) ($_GET['m'] ?? ''));
if ($clave === '') {
    if (count($permitidos) > 1) {
        header('Location: ../manuales');
        exit;
    }
    $clave = $permitidos[0] ?? '';
}

if ($clave === '' || !in_array($clave, $permitidos, true)) {
    http_response_code(403);
    exit('No tienes acceso a este manual.');
}

$ruta = __DIR__ . '/../docs/manuales/' . $manuales[$clave]['archivo'];
if (!is_file($ruta)) {
    http_response_code(404);
    exit('El manual no está disponible por el momento.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($ruta) . '"');
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($ruta);
exit;
