<?php
/**
 * Environment.php — Interruptor de ambiente: producción o pruebas.
 *
 * Se controla con APP_ENV en .env:
 *   APP_ENV=produccion  → comportamiento normal (valor por omisión si falta la variable,
 *                         para que el servidor siga igual aunque su .env no la tenga).
 *   APP_ENV=pruebas     → la BD debe terminar en _PRUEBAS (si no, se bloquea la conexión)
 *                         y ningún correo sale a internet: todos van a Mailpit.
 *
 * Una BD cuyo nombre termina en _PRUEBAS fuerza el modo pruebas aunque APP_ENV falte o
 * diga otra cosa, y un valor de APP_ENV desconocido también cae en pruebas: ante la duda,
 * el lado seguro es no tocar producción.
 */

class Environment
{
    public const PRODUCCION = 'produccion';
    public const PRUEBAS = 'pruebas';

    private const TEST_DB_SUFFIX = '_PRUEBAS';
    private const PRODUCTION_ALIASES = ['produccion', 'producción', 'production', 'prod'];

    private static function loadEnv(): void
    {
        if (isset($_ENV['DB_DATABASE'])) {
            return;
        }
        require_once __DIR__ . '/../vendor/autoload.php';
        Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();
    }

    public static function name(): string
    {
        self::loadEnv();
        if (self::isTestDatabase($_ENV['DB_DATABASE'] ?? '')) {
            return self::PRUEBAS;
        }
        $env = strtolower(trim((string) ($_ENV['APP_ENV'] ?? '')));
        if ($env === '' || in_array($env, self::PRODUCTION_ALIASES, true)) {
            return self::PRODUCCION;
        }
        return self::PRUEBAS;
    }

    public static function isTesting(): bool
    {
        return self::name() === self::PRUEBAS;
    }

    public static function isTestDatabase(string $database): bool
    {
        return strcasecmp(substr($database, -strlen(self::TEST_DB_SUFFIX)), self::TEST_DB_SUFFIX) === 0;
    }

    /**
     * En pruebas solo se permite conectar a bases *_PRUEBAS; cualquier otra se considera
     * de producción y la conexión se rechaza antes de abrirse.
     */
    public static function assertDatabaseAllowed(string $database): void
    {
        if (self::isTesting() && !self::isTestDatabase($database)) {
            throw new RuntimeException("Ambiente de pruebas: la base '{$database}' no termina en " . self::TEST_DB_SUFFIX . '; se bloqueó la conexión para no tocar producción.');
        }
    }

    /**
     * Servidor SMTP de captura (Mailpit) que se usa en pruebas en lugar de cualquier SMTP real.
     *
     * @return array{host:string,port:int}
     */
    public static function testMailServer(): array
    {
        self::loadEnv();
        return [
            'host' => trim((string) ($_ENV['MAIL_TEST_HOST'] ?? '')) ?: 'mailpit',
            'port' => (int) ($_ENV['MAIL_TEST_PORT'] ?? 0) ?: 1025,
        ];
    }
}
