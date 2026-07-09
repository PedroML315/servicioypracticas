<?php
/**
 * process_email_queue.php
 * ──────────────────────────────────────────────────────────
 * Procesador de la cola de correos en segundo plano.
 *
 * Se ejecuta vía Windows Task Scheduler (cada minuto) con:
 *   php "C:\Inetpub\vhosts\...\controller\process_email_queue.php"
 *
 * También puede llamarse desde web pasando el token secreto:
 *   GET /controller/process_email_queue.php?token=<EMAIL_QUEUE_SECRET>
 * ──────────────────────────────────────────────────────────
 */

define('MAX_ATTEMPTS',  3);    // Reintentos máximos por correo
define('BATCH_SIZE',   20);    // Correos procesados por ejecución
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/model/conection.php';
require_once BASE_PATH . '/controller/emails.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

/* ── Protección: solo CLI o token válido ─────────────────── */
$isCli = (PHP_SAPI === 'cli');

if (!$isCli) {
    $token = $_GET['token'] ?? $_SERVER['HTTP_X_QUEUE_TOKEN'] ?? '';
    if (!hash_equals($_ENV['EMAIL_QUEUE_SECRET'] ?? '', $token)) {
        http_response_code(403);
        exit('Acceso denegado');
    }
}

/* ── Obtener correos pendientes ──────────────────────────── */
$pdo = Conexion::conectar();

// Incrementar attempts atómicamente para evitar dobles envíos si el procesador
// se ejecuta en paralelo (solo se seleccionan los que aún no superaron el máximo)
$updated = $pdo->prepare(
    "UPDATE email_queue
     SET attempts = attempts + 1
     WHERE status = 'pending'
       AND attempts < :max_attempts
     LIMIT :batch_size"
);
$updated->bindValue(':max_attempts', MAX_ATTEMPTS, PDO::PARAM_INT);
$updated->bindValue(':batch_size',   BATCH_SIZE,   PDO::PARAM_INT);
$updated->execute();

if ($updated->rowCount() === 0) {
    if ($isCli) echo "[EmailQueue] Sin correos pendientes.\n";
    exit(0);
}

// Seleccionar exactamente los que acabamos de marcar (attempts > 0 y status pending)
$stmt = $pdo->prepare(
    "SELECT id, to_email, subject, body, plain_text, from_name, attachments, attempts
     FROM email_queue
     WHERE status = 'pending'
       AND attempts > 0
       AND attempts <= :max_attempts
     ORDER BY created_at ASC
     LIMIT :batch_size"
);
$stmt->bindValue(':max_attempts', MAX_ATTEMPTS, PDO::PARAM_INT);
$stmt->bindValue(':batch_size',   BATCH_SIZE,   PDO::PARAM_INT);
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($emails)) {
    if ($isCli) echo "[EmailQueue] Sin correos pendientes.\n";
    exit(0);
}

/* ── Procesar cada correo ────────────────────────────────── */
$sent = 0; $failed = 0;

foreach ($emails as $row) {
    $attachments = [];
    if (!empty($row['attachments'])) {
        $decoded = json_decode($row['attachments'], true);
        if (is_array($decoded)) {
            $attachments = $decoded;
        }
    }

    $result = MailService::dispatchMail(
        $row['to_email'],
        $row['subject'],
        $row['body'],
        $row['plain_text'],
        $row['from_name'],
        $attachments
    );

    if ($result === 'ok') {
        $upd = $pdo->prepare(
            "UPDATE email_queue SET status = 'sent', sent_at = NOW(), error_log = NULL WHERE id = :id"
        );
        $upd->execute([':id' => $row['id']]);
        $sent++;
        if ($isCli) echo "[EmailQueue] ✓ Enviado id={$row['id']} → {$row['to_email']}\n";
    } else {
        // Si ya alcanzó el máximo de intentos, marcar como failed definitivo
        $newStatus = ($row['attempts'] >= MAX_ATTEMPTS) ? 'failed' : 'pending';
        $upd = $pdo->prepare(
            "UPDATE email_queue SET status = :status, error_log = :err WHERE id = :id"
        );
        $upd->execute([
            ':status' => $newStatus,
            ':err'    => "Fallo en intento {$row['attempts']}",
            ':id'     => $row['id'],
        ]);
        $failed++;
        if ($isCli) echo "[EmailQueue] ✗ Falló  id={$row['id']} → {$row['to_email']} (intento {$row['attempts']})\n";
    }
}

if ($isCli) {
    echo "[EmailQueue] Listo. Enviados: {$sent} | Fallidos: {$failed}\n";
}
