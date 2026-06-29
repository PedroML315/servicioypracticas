<?php
/**
 * LogModel.php
 * Sistema de Logs centralizado — UNIMO
 * Registra toda la actividad del sistema en la tabla `logs`.
 * Uso:
 *   LogModel::log('login', 'users', '/controller/ajax/ajax.login.php', 'Login exitoso');
 *   LogModel::error('error', 'system', '/index.php', 'Error inesperado', ['exception' => $e->getMessage()]);
 */

class LogModel
{
    // ── Acciones estándar ───────────────────────────────────────
    const ACTION_LOGIN        = 'login';
    const ACTION_LOGIN_FAILED = 'login_failed';
    const ACTION_LOGOUT       = 'logout';
    const ACTION_CREATE       = 'create';
    const ACTION_UPDATE       = 'update';
    const ACTION_DELETE       = 'delete';
    const ACTION_EXPORT       = 'export';
    const ACTION_ERROR        = 'error';

    // ── Módulos ─────────────────────────────────────────────────
    const MODULE_USERS      = 'users';
    const MODULE_STUDENTS   = 'students';
    const MODULE_EVENTS     = 'events';
    const MODULE_PRACTICES  = 'practices';
    const MODULE_ORGANISMS  = 'organisms';
    const MODULE_DOCUMENTS  = 'documents';
    const MODULE_SYSTEM     = 'system';
    const MODULE_AUTH       = 'auth';

    // ────────────────────────────────────────────────────────────

    /**
     * Registra una entrada en el log del sistema.
     * @param string      $action      Tipo de acción (usar constantes ACTION_*)
     * @param string|null $module      Módulo (usar constantes MODULE_*)
     * @param string|null $endpoint    Archivo o URL que generó el log
     * @param string|null $description Descripción legible
     * @param array|null  $payload     Datos extra (NO incluir contraseñas ni tokens)
     * @param string      $status      'success' | 'error' | 'warning'
     * @param int|null    $userId      ID del usuario (null si no autenticado)
     * @param string|null $userType    Rol del usuario
     * @return bool
     */
    public static function log(
        string  $action,
        ?string $module      = null,
        ?string $endpoint    = null,
        ?string $description = null,
        ?array  $payload     = null,
        string  $status      = 'success',
        ?int    $userId      = null,
        ?string $userType    = null
    ): bool {
        // Intentar obtener userId/userType de la sesión si no se pasan
        if ($userId === null && isset($_SESSION['user']['id'])) {
            $userId = (int) $_SESSION['user']['id'];
        }
        if ($userType === null && isset($_SESSION['user']['role'])) {
            $userType = $_SESSION['user']['role'];
        }
        $ip        = self::getClientIp();
        $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
            ? substr($_SERVER['HTTP_USER_AGENT'], 0, 512)
            : null;

        // Endpoint desde server si no se pasa
        if ($endpoint === null) {
            $endpoint = $_SERVER['REQUEST_URI'] ?? null;
        }

        // Sanitizar payload — eliminar campos sensibles
        if (is_array($payload)) {
            $payload = self::sanitizePayload($payload);
        }

        $payloadJson = ($payload !== null) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null;

        try {
            $sql = "INSERT INTO logs
                        (user_id, user_type, action, module, endpoint, description, payload, ip, user_agent, status, created_at)
                    VALUES
                        (:user_id, :user_type, :action, :module, :endpoint, :description, :payload, :ip, :user_agent, :status, NOW())";

            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute([
                ':user_id'     => $userId,
                ':user_type'   => $userType,
                ':action'      => $action,
                ':module'      => $module,
                ':endpoint'    => $endpoint ? substr($endpoint, 0, 255) : null,
                ':description' => $description ? substr($description, 0, 65535) : null,
                ':payload'     => $payloadJson,
                ':ip'          => $ip,
                ':user_agent'  => $userAgent,
                ':status'      => in_array($status, ['success', 'error', 'warning']) ? $status : 'success',
            ]);
            $stmt->closeCursor();
            return true;
        } catch (Throwable $e) {
            // El log NO debe crashear la aplicación
            error_log('[LogModel] Error al escribir log: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Shortcut para loggear errores.
     */
    public static function error(
        string  $module      = 'system',
        string  $endpoint    = '',
        string  $description = '',
        ?array  $payload     = null
    ): bool {
        return self::log(self::ACTION_ERROR, $module, $endpoint, $description, $payload, 'error');
    }

    // ── Consultas de logs ────────────────────────────────────────

    /**
     * Obtener logs con filtros opcionales (solo root/admin).
     * @param array $filters Claves opcionales: user_id, action, module, status, ip, date_from, date_to
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public static function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[]              = 'user_id = :user_id';
            $params[':user_id']   = (int) $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[]             = 'action = :action';
            $params[':action']   = $filters['action'];
        }
        if (!empty($filters['module'])) {
            $where[]             = 'module = :module';
            $params[':module']   = $filters['module'];
        }
        if (!empty($filters['status'])) {
            $where[]             = 'status = :status';
            $params[':status']   = $filters['status'];
        }
        if (!empty($filters['ip'])) {
            $where[]         = 'ip = :ip';
            $params[':ip']   = $filters['ip'];
        }
        if (!empty($filters['date_from'])) {
            $where[]                 = 'created_at >= :date_from';
            $params[':date_from']    = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]               = 'created_at <= :date_to';
            $params[':date_to']    = $filters['date_to'];
        }

        $whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM logs {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $result;
    }

    /**
     * Contar intentos de login fallidos recientes por email+IP.
     */
    public static function countRecentLoginFailures(string $identifier, string $ip, int $minutes = 15): int
    {
        $sql = "SELECT COUNT(*) FROM logs
                WHERE action = 'login_failed'
                  AND (description LIKE :identifier OR ip = :ip)
                  AND created_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute([
            ':identifier' => '%' . $identifier . '%',
            ':ip'         => $ip,
            ':minutes'    => $minutes,
        ]);
        $count = (int) $stmt->fetchColumn();
        $stmt->closeCursor();
        return $count;
    }

    // ── Helpers privados ─────────────────────────────────────────

    /**
     * Obtener IP real del cliente (considerando proxies confiables).
     */
    private static function getClientIp(): string
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

    /**
     * Elimina campos sensibles del payload antes de persistir en log.
     */
    private static function sanitizePayload(array $data): array
    {
        $sensitiveKeys = ['password', 'pass', 'passwd', 'token', 'secret', 'csrf', 'credit_card', 'cvv', 'pin'];
        foreach ($data as $key => $value) {
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos((string) $key, $sensitive) !== false) {
                    $data[$key] = '***REDACTED***';
                    break;
                }
            }
            if (is_array($value)) {
                $data[$key] = self::sanitizePayload($value);
            }
        }
        return $data;
    }
}
