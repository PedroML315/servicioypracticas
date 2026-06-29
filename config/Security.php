<?php
/**
 * Security.php — Helpers de seguridad centralizados
 * Sistema: Servicio Social & Prácticas Profesionales UNIMO
 * Incluir en whiteList.php o index.php:
 *   require_once 'config/Security.php';
 *   Security::init();
 */

class Security
{
    /** Duración máxima de sesión inactiva (segundos) */
    const SESSION_TIMEOUT = 1800; // 30 minutos

    /** Máximo de intentos de login antes de bloquear */
    const MAX_LOGIN_ATTEMPTS = 5;

    /** Tiempo de bloqueo tras exceder intentos (segundos) */
    const LOCKOUT_TIME = 900; // 15 minutos

    // ─────────────────────────────────────────────────────────────
    // Inicialización — llamar una sola vez al inicio de cada request
    // ─────────────────────────────────────────────────────────────

    public static function init(): void
    {
        // Configurar cookies de sesión de forma segura
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;

        session_set_cookie_params([
            'lifetime' => 0,                    // Solo mientras el navegador esté abierto
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,             // Solo HTTPS en producción
            'httponly' => true,                 // No accesible por JavaScript
            'samesite' => 'Lax',                // Protección CSRF parcial
        ]);

        // Enviar headers de seguridad HTTP
        self::sendSecurityHeaders();
    }

    // ─────────────────────────────────────────────────────────────
    // Headers de seguridad HTTP
    // ─────────────────────────────────────────────────────────────

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) return;

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // CSP ajustada al sistema (Bootstrap CDN + jQuery inline)
        header(
            "Content-Security-Policy: " .
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com https://ajax.googleapis.com https://kit.fontawesome.com; " .
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://ka-f.fontawesome.com; " .
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://ka-f.fontawesome.com data:; " .
            "img-src 'self' data: blob: https:; " .
            "connect-src 'self' https://ka-f.fontawesome.com https://cdn.jsdelivr.net; " .
            "frame-ancestors 'none';"
        );
    }

    // ─────────────────────────────────────────────────────────────
    // CSRF Token
    // ─────────────────────────────────────────────────────────────

    /**
     * Genera (o recupera) el token CSRF de la sesión actual.
     * Llamar después de session_start().
     */
    public static function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Rota el token CSRF (usar tras operaciones sensibles).
     */
    public static function rotateCsrfToken(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida el token CSRF enviado en POST o en header X-CSRF-Token.
     * Lanza excepción si falla.
     *
     * @throws RuntimeException
     */
    public static function validateCsrf(): void
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $requestToken = $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        if (!$sessionToken || !hash_equals($sessionToken, $requestToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o expirado.']);
            exit;
        }
    }

    /**
     * Genera un campo oculto HTML con el token CSRF.
     * Uso en formularios: <?= Security::csrfField() ?>
     */
    public static function csrfField(): string
    {
        $token = self::getCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    // ─────────────────────────────────────────────────────────────
    // Gestión de sesión
    // ─────────────────────────────────────────────────────────────

    /**
     * Verifica si la sesión ha expirado por inactividad.
     * Llama a esto en cada request autenticado.
     */
    public static function checkSessionExpiry(): void
    {
        if (!isset($_SESSION['logged'])) return;

        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if ((time() - $lastActivity) > self::SESSION_TIMEOUT) {
            self::destroySession();
            header('Location: /login');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    /**
     * Regenera el ID de sesión de forma segura.
     * Llamar después de un login exitoso.
     */
    public static function regenerateSession(): void
    {
        session_regenerate_id(true);
        $_SESSION['last_activity'] = time();
        // Nuevo token CSRF tras regenerar sesión
        self::rotateCsrfToken();
    }

    /**
     * Destruye completamente la sesión.
     */
    public static function destroySession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    // ─────────────────────────────────────────────────────────────
    // Rate limiting de login
    // ─────────────────────────────────────────────────────────────

    /**
     * Verifica si el identificador (email) o IP está bloqueado.
     * Retorna true si está bloqueado.
     */
    public static function isLoginBlocked(string $identifier, string $ip): bool
    {
        try {
            $sql = "SELECT blocked_until FROM login_attempts
                    WHERE (identifier = :identifier OR ip = :ip)
                      AND blocked_until > NOW()
                    LIMIT 1";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute([':identifier' => $identifier, ':ip' => $ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return (bool) $row;
        } catch (Throwable $e) {
            error_log('[Security::isLoginBlocked] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un intento fallido de login y aplica bloqueo si supera el límite.
     */
    public static function recordFailedLogin(string $identifier, string $ip): void
    {
        try {
            $db = Conexion::conectar();

            // Upsert de intentos
            $sql = "INSERT INTO login_attempts (identifier, ip, attempts, last_attempt_at)
                    VALUES (:identifier, :ip, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                        attempts         = attempts + 1,
                        last_attempt_at  = NOW(),
                        blocked_until    = IF(
                            attempts + 1 >= :max_attempts,
                            DATE_ADD(NOW(), INTERVAL :lockout SECOND),
                            blocked_until
                        )";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':identifier'   => $identifier,
                ':ip'           => $ip,
                ':max_attempts' => self::MAX_LOGIN_ATTEMPTS,
                ':lockout'      => self::LOCKOUT_TIME,
            ]);
            $stmt->closeCursor();
        } catch (Throwable $e) {
            error_log('[Security::recordFailedLogin] ' . $e->getMessage());
        }
    }

    /**
     * Limpia los intentos fallidos tras un login exitoso.
     */
    public static function clearLoginAttempts(string $identifier, string $ip): void
    {
        try {
            $sql  = "DELETE FROM login_attempts WHERE identifier = :identifier OR ip = :ip";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute([':identifier' => $identifier, ':ip' => $ip]);
            $stmt->closeCursor();
        } catch (Throwable $e) {
            error_log('[Security::clearLoginAttempts] ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Sanitización de inputs
    // ─────────────────────────────────────────────────────────────

    /**
     * Sanitiza un string para uso general (previene XSS básico).
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitiza un email.
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Valida que un valor sea un entero positivo.
     */
    public static function validateInt(mixed $value): ?int
    {
        $filtered = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $filtered !== false ? (int) $filtered : null;
    }

    // ─────────────────────────────────────────────────────────────
    // Generación segura de contraseñas
    // ─────────────────────────────────────────────────────────────

    /**
     * Genera una contraseña aleatoria segura (reemplaza la función rand() actual).
     */
    public static function generatePassword(int $length = 12): string
    {
        $chars    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%';
        $max      = strlen($chars) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }

    /**
     * Verifica si el usuario actual tiene un rol permitido.
     * Redirige a 404 si no tiene permiso.
     */
    public static function requireRole(string ...$roles): void
    {
        $userRole = $_SESSION['user']['role'] ?? null;
        if (!$userRole || !in_array($userRole, $roles, true)) {
            http_response_code(403);
            include 'view/pages/error404.php';
            exit;
        }
    }

    /**
     * Obtiene la IP del cliente.
     */
    public static function getClientIp(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
