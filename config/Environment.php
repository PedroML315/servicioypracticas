<?php
/**
 * Environment.php — Interruptor de ambiente: producción o pruebas.
 *
 * Se controla con APP_ENV en .env:
 *   APP_ENV=produccion  → comportamiento normal (valor por omisión si falta la variable,
 *                         para que el servidor siga igual aunque su .env no la tenga).
 *   APP_ENV=pruebas     → la BD debe terminar en _PRUEBAS (si no, se bloquea la conexión),
 *                         ningún correo sale a internet (todos van a Mailpit) y SIL/GES solo
 *                         se consultan en el servidor de SIL_TEST_DB_HOST.
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
     * Datos de conexión al SQL Server de SIL/GES (solo se consultan alumnos por matrícula).
     *
     * En producción salen de SIL_DB_*, y las consultas de GES usan las bases de siempre
     * (ges_db = GES_UNIMO, scm_db = SCM_MontrerDB).
     *
     * En pruebas el servidor sale de SIL_TEST_DB_HOST, el usuario y la contraseña de
     * SIL_TEST_DB_USERNAME/SIL_TEST_DB_PASSWORD (o de SIL_DB_* si faltan), y cada base es
     * una copia *_PRUEBAS: SIL_TEST_DB_DATABASE (por omisión <SIL_DB_DATABASE>_PRUEBAS),
     * SIL_TEST_DB_DATABASE_GES (GES_UNIMO_PRUEBAS) y SIL_TEST_DB_DATABASE_SCM
     * (SCM_MontrerDB_PRUEBAS). La conexión se rechaza si falta SIL_TEST_DB_HOST, si es el
     * mismo servidor que SIL_DB_HOST o si alguna base no termina en _PRUEBAS.
     * En pruebas se confía en el certificado del servidor (un SQL Server local suele tener uno
     * autofirmado que el ODBC Driver 18 rechaza); en producción la cadena no cambia.
     *
     * @return array{host:string,database:string,database_ges:string,ges_db:string,scm_db:string,username:string,password:string,dsn_options:string}
     */
    public static function silServer(): array
    {
        self::loadEnv();
        $real = [
            'host' => (string) ($_ENV['SIL_DB_HOST'] ?? ''),
            'database' => (string) ($_ENV['SIL_DB_DATABASE'] ?? ''),
            'database_ges' => (string) ($_ENV['SIL_DB_DATABASE_GES'] ?? ''),
            // Bases que GesModel escribe con nombre completo en sus consultas
            'ges_db' => 'GES_UNIMO',
            'scm_db' => 'SCM_MontrerDB',
            'username' => (string) ($_ENV['SIL_DB_USERNAME'] ?? ''),
            'password' => trim((string) ($_ENV['SIL_DB_PASSWORD'] ?? ''), '"'), // Quita comillas si existen
            'dsn_options' => '',
        ];
        if (!self::isTesting()) {
            return $real;
        }

        $host = trim((string) ($_ENV['SIL_TEST_DB_HOST'] ?? ''));
        if ($host === '') {
            throw new RuntimeException('Ambiente de pruebas: falta SIL_TEST_DB_HOST; no se conecta al SQL Server real.');
        }
        if (strcasecmp($host, trim($real['host'])) === 0) {
            throw new RuntimeException('Ambiente de pruebas: SIL_TEST_DB_HOST es el mismo servidor que SIL_DB_HOST; se bloqueó la conexión para no leer datos reales.');
        }

        $ges = self::testEnv('SIL_TEST_DB_DATABASE_GES', $real['ges_db'] . self::TEST_DB_SUFFIX);
        $test = [
            'host' => $host,
            'database' => self::testEnv('SIL_TEST_DB_DATABASE', self::isTestDatabase($real['database']) ? $real['database'] : $real['database'] . self::TEST_DB_SUFFIX),
            'database_ges' => $ges,
            'ges_db' => $ges,
            'scm_db' => self::testEnv('SIL_TEST_DB_DATABASE_SCM', $real['scm_db'] . self::TEST_DB_SUFFIX),
            'username' => self::testEnv('SIL_TEST_DB_USERNAME', $real['username']),
            'password' => self::testEnv('SIL_TEST_DB_PASSWORD', $real['password']),
            'dsn_options' => ';TrustServerCertificate=1',
        ];
        foreach (['database', 'ges_db', 'scm_db'] as $key) {
            // El nombre también se escribe dentro de las consultas: solo letras, números y _
            if (!self::isTestDatabase($test[$key]) || !preg_match('/^[A-Za-z0-9_]+$/', $test[$key])) {
                throw new RuntimeException("Ambiente de pruebas: la base de SQL Server '{$test[$key]}' debe terminar en " . self::TEST_DB_SUFFIX . ' y llevar solo letras, números y _; se bloqueó la conexión.');
            }
        }
        return $test;
    }

    /** Valor de una variable SIL_TEST_DB_* (sin comillas), o el indicado si viene vacía. */
    private static function testEnv(string $var, string $default): string
    {
        $value = trim((string) ($_ENV[$var] ?? ''), '"');
        return trim($value) !== '' ? $value : $default;
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
