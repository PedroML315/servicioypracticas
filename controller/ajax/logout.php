<?php
// Iniciar la sesión si no está iniciada
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea, también se puede eliminar la cookie de sesión.
// Esto eliminará la sesión completamente, no solo los datos de la sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Devolver 'ok' como respuesta (para indicar que el cierre de sesión fue exitoso)
echo 'ok';