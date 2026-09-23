<?php
/**
 * Directorio institucional (tabla `directory`).
 *
 * Son los directores de escuela, vicerrectores y responsables de área a
 * quienes se les avisa cuando se aprueba una vacante de prácticas, para que
 * la difundan entre sus estudiantes.
 */

// `model/forms.models.php` incluye conection.php con `include` (sin _once), así que
// volver a cargarlo aquí redeclararía la clase. Solo se carga si aún no existe
// — necesario cuando este modelo se usa desde su endpoint AJAX, que no la trae.
if (!class_exists('Conexion')) {
    require_once __DIR__ . '/conection.php';
}

class DirectoryModel
{
    private static ?PDO $pdo = null;

    private static function db(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $pdo = Conexion::conectar();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo = $pdo;
        return $pdo;
    }

    /** Correo normalizado: sin espacios y en minúsculas. */
    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Valida los campos de un contacto.
     * @return string Mensaje de error, o cadena vacía si todo está correcto.
     */
    private static function validate(string $fullName, string $email, string $jobTitle): string
    {
        if (trim($fullName) === '') {
            return 'El nombre no puede estar vacío.';
        }
        if (mb_strlen(trim($fullName)) > 150) {
            return 'El nombre es demasiado largo (máximo 150 caracteres).';
        }
        if (!filter_var(self::normalizeEmail($email), FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }
        if (mb_strlen(self::normalizeEmail($email)) > 150) {
            return 'El correo es demasiado largo (máximo 150 caracteres).';
        }
        if (trim($jobTitle) === '') {
            return 'El cargo no puede estar vacío.';
        }
        if (mb_strlen(trim($jobTitle)) > 100) {
            return 'El cargo es demasiado largo (máximo 100 caracteres).';
        }
        return '';
    }

    public static function listContacts(string $search = ''): array
    {
        $pdo = self::db();
        if ($search !== '') {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, job_title, created_at
                 FROM directory
                 WHERE full_name LIKE :q OR email LIKE :q OR job_title LIKE :q
                 ORDER BY full_name ASC"
            );
            $stmt->execute([':q' => '%' . $search . '%']);
            return $stmt->fetchAll();
        }
        return $pdo->query(
            "SELECT id, full_name, email, job_title, created_at
             FROM directory
             ORDER BY full_name ASC"
        )->fetchAll();
    }

    public static function createContact(string $fullName, string $email, string $jobTitle): array
    {
        $error = self::validate($fullName, $email, $jobTitle);
        if ($error !== '') {
            return ['ok' => false, 'error' => $error];
        }
        try {
            $stmt = self::db()->prepare(
                "INSERT INTO directory (full_name, email, job_title)
                 VALUES (:full_name, :email, :job_title)"
            );
            $stmt->execute([
                ':full_name' => trim($fullName),
                ':email'     => self::normalizeEmail($email),
                ':job_title' => trim($jobTitle),
            ]);
            return ['ok' => true, 'id' => (int) self::db()->lastInsertId()];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'error' => 'Ya existe una persona con ese correo en el directorio.'];
            }
            throw $e;
        }
    }

    public static function updateContact(int $id, string $fullName, string $email, string $jobTitle): array
    {
        $error = self::validate($fullName, $email, $jobTitle);
        if ($error !== '') {
            return ['ok' => false, 'error' => $error];
        }
        try {
            $stmt = self::db()->prepare(
                "UPDATE directory
                 SET full_name = :full_name, email = :email, job_title = :job_title
                 WHERE id = :id"
            );
            $stmt->execute([
                ':full_name' => trim($fullName),
                ':email'     => self::normalizeEmail($email),
                ':job_title' => trim($jobTitle),
                ':id'        => $id,
            ]);
            return ['ok' => true];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'error' => 'Ya existe otra persona con ese correo en el directorio.'];
            }
            throw $e;
        }
    }

    public static function deleteContacts(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare("DELETE FROM directory WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }
}
