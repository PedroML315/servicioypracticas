<?php
/**
 * process_mail_bulk_queue.php
 * ──────────────────────────────────────────────────────────
 * Procesador en segundo plano de la cola PROPIA del Gestor de envío
 * masivo de correos (tabla mail_campaign_recipients). Completamente
 * independiente de controller/process_email_queue.php: usa el SMTP
 * propio del módulo (mail_bulk_smtp_config), nunca el .env global,
 * y nunca toca la tabla email_queue del correo transaccional.
 *
 * Se ejecuta vía Windows Task Scheduler (cada minuto) con:
 *   php "...\controller\cron\process_mail_bulk_queue.php"
 *
 * También puede llamarse desde web pasando el token secreto:
 *   GET /controller/cron/process_mail_bulk_queue.php?token=<MAIL_BULK_QUEUE_SECRET>
 * ──────────────────────────────────────────────────────────
 */

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/model/conection.php';
require_once BASE_PATH . '/model/MailBulkModel.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

/* ── Protección: solo CLI o token válido ─────────────────── */
$isCli = (PHP_SAPI === 'cli');

if (!$isCli) {
    $token = $_GET['token'] ?? $_SERVER['HTTP_X_QUEUE_TOKEN'] ?? '';
    if (!hash_equals($_ENV['MAIL_BULK_QUEUE_SECRET'] ?? '', $token)) {
        http_response_code(403);
        exit('Acceso denegado');
    }
}

$smtp = MailBulkModel::getSmtpConfigForSending();
if (!$smtp) {
    if ($isCli) echo "[MailBulk] Servidor de correo del módulo no configurado. Nada que hacer.\n";
    exit(0);
}

/* ── Procesar un lote por cada campaña activa ────────────── */
$pdo = Conexion::conectar();
$stmt = $pdo->query("
    SELECT id FROM mail_campaigns
    WHERE status IN ('queued', 'sending')
      AND id IN (SELECT DISTINCT campaign_id FROM mail_campaign_recipients WHERE status IN ('pending', 'sending'))
");
$campaignIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$campaignIds) {
    if ($isCli) echo "[MailBulk] Sin envíos pendientes.\n";
    exit(0);
}

foreach ($campaignIds as $campaignId) {
    $result = MailBulkModel::processBatch((int) $campaignId, $smtp);
    if ($isCli) {
        echo "[MailBulk] Campaña #{$campaignId}: enviados {$result['sent']}, fallidos {$result['failed']}, pendientes {$result['remaining']}\n";
    }
}
