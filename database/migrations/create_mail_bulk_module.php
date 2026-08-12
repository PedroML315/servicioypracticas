<?php

/**
 * ─────────────────────────────────────────────────────────────────────────
 *  Gestor de envío masivo de correos — módulo nuevo, aislado
 * ─────────────────────────────────────────────────────────────────────────
 *  Crea las 5 tablas del módulo. No modifica ninguna tabla existente
 *  (en particular, no toca email_queue ni ninguna tabla del correo
 *  transaccional del sistema).
 *
 *  · mail_bulk_smtp_config    → configuración SMTP propia del módulo (fila única, cifrada)
 *  · mail_recipients          → lista de destinatarios administrada por el módulo
 *  · mail_saved_messages      → redacciones reutilizables
 *  · mail_campaigns           → cabecera de cada envío masivo (historial)
 *  · mail_campaign_recipients → cola propia del módulo + snapshot histórico por destinatario
 *
 *  Idempotente: puede ejecutarse múltiples veces sin errores.
 *  Ejecutar:  php database/migrations/create_mail_bulk_module.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tableExists = function (string $t) use ($pdo): bool {
    return (bool) $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
};

echo "Iniciando migración del Gestor de envío masivo de correos...\n\n";

try {

    /* ═════════════════════════════════════════════════════════════════════
     * 1. Configuración SMTP propia del módulo (fila única, id=1)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "1. Tabla mail_bulk_smtp_config...\n";
    if (!$tableExists('mail_bulk_smtp_config')) {
        $pdo->exec("
            CREATE TABLE mail_bulk_smtp_config (
                id           TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
                host         VARCHAR(255) NOT NULL DEFAULT '',
                port         SMALLINT UNSIGNED NOT NULL DEFAULT 587,
                encryption   ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
                username     VARCHAR(255) NOT NULL DEFAULT '',
                password_enc TEXT NULL COMMENT 'Cifrada con config/Crypto.php (AES-256-CBC), nunca texto plano',
                from_email   VARCHAR(255) NOT NULL DEFAULT '',
                from_name    VARCHAR(255) NOT NULL DEFAULT '',
                updated_by   INT UNSIGNED NULL,
                updated_at   DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
              COMMENT='Servidor de correo propio del Gestor de envío masivo (independiente del .env global)'
        ");
        echo "   [OK] Tabla mail_bulk_smtp_config creada.\n";
    } else {
        echo "   [INFO] mail_bulk_smtp_config ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 2. Destinatarios
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n2. Tabla mail_recipients...\n";
    if (!$tableExists('mail_recipients')) {
        $pdo->exec("
            CREATE TABLE mail_recipients (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(150) NOT NULL,
                email      VARCHAR(320) NOT NULL,
                notes      VARCHAR(255) NULL,
                created_by INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_mail_recipients_email (email),
                KEY idx_mail_recipients_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Lista de destinatarios propia del Gestor de envío masivo'
        ");
        echo "   [OK] Tabla mail_recipients creada.\n";
    } else {
        echo "   [INFO] mail_recipients ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 3. Mensajes guardados (redacciones reutilizables)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n3. Tabla mail_saved_messages...\n";
    if (!$tableExists('mail_saved_messages')) {
        $pdo->exec("
            CREATE TABLE mail_saved_messages (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(150) NOT NULL COMMENT 'Nombre interno para identificar la redacción',
                subject    VARCHAR(500) NOT NULL,
                body_html  LONGTEXT NOT NULL COMMENT 'HTML ya saneado con HTMLPurifier',
                created_by INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_mail_saved_messages_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   [OK] Tabla mail_saved_messages creada.\n";
    } else {
        echo "   [INFO] mail_saved_messages ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 4. Campañas (cabecera de cada envío masivo = historial)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n4. Tabla mail_campaigns...\n";
    if (!$tableExists('mail_campaigns')) {
        $pdo->exec("
            CREATE TABLE mail_campaigns (
                id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                client_token     CHAR(36) NOT NULL COMMENT 'UUID generado en el navegador; evita crear campañas duplicadas por doble clic/doble pestaña/recarga',
                subject          VARCHAR(500) NOT NULL,
                body_html        LONGTEXT NOT NULL COMMENT 'Ya saneado; puede contener {nombre} sin interpolar (se interpola por destinatario al enviar)',
                from_name        VARCHAR(255) NOT NULL DEFAULT '',
                attachments      JSON NULL COMMENT 'Array de rutas relativas dentro de uploads/mail_bulk/attachments/',
                status           ENUM('queued','sending','completed','completed_with_errors','failed') NOT NULL DEFAULT 'queued',
                total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
                sent_count       INT UNSIGNED NOT NULL DEFAULT 0,
                failed_count     INT UNSIGNED NOT NULL DEFAULT 0,
                created_by       INT UNSIGNED NULL,
                created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                completed_at     DATETIME NULL,
                UNIQUE KEY uniq_mail_campaigns_client_token (client_token),
                KEY idx_mail_campaigns_status (status),
                KEY idx_mail_campaigns_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
              COMMENT='Historial de envíos masivos'
        ");
        echo "   [OK] Tabla mail_campaigns creada.\n";
    } else {
        echo "   [INFO] mail_campaigns ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 5. Cola propia del módulo + snapshot histórico por destinatario
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n5. Tabla mail_campaign_recipients...\n";
    if (!$tableExists('mail_campaign_recipients')) {
        $pdo->exec("
            CREATE TABLE mail_campaign_recipients (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                campaign_id     INT UNSIGNED NOT NULL,
                recipient_id    INT UNSIGNED NULL COMMENT 'FK a mail_recipients; NULL si luego se borró de la lista',
                recipient_name  VARCHAR(150) NOT NULL COMMENT 'Copia al momento del envío (historial inmutable)',
                recipient_email VARCHAR(320) NOT NULL,
                status          ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
                attempts        TINYINT UNSIGNED NOT NULL DEFAULT 0,
                error_message   VARCHAR(500) NULL COMMENT 'Motivo de fallo en español claro',
                reserved_at     DATETIME NULL COMMENT 'Marca al pasar a sending; libera reservas atascadas tras 2 minutos',
                sent_at         DATETIME NULL,
                CONSTRAINT fk_mcr_campaign  FOREIGN KEY (campaign_id)  REFERENCES mail_campaigns(id)  ON DELETE CASCADE,
                CONSTRAINT fk_mcr_recipient FOREIGN KEY (recipient_id) REFERENCES mail_recipients(id) ON DELETE SET NULL,
                KEY idx_mcr_campaign_status (campaign_id, status),
                KEY idx_mcr_recipient (recipient_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   [OK] Tabla mail_campaign_recipients creada.\n";
    } else {
        echo "   [INFO] mail_campaign_recipients ya existe.\n";
    }

    echo "\n¡Migración del Gestor de envío masivo de correos completada con éxito!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
