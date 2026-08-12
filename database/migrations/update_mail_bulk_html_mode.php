<?php

/**
 * ─────────────────────────────────────────────────────────────────────────
 *  Envío masivo de correos — modo “Código HTML” y diseño institucional
 * ─────────────────────────────────────────────────────────────────────────
 *  Agrega la columna `use_layout` a las dos tablas que guardan el cuerpo del
 *  correo. Indica si, al enviar, el mensaje debe envolverse en el diseño
 *  institucional (logo arriba, pie abajo y email.css) o si debe salir tal
 *  cual lo escribió el administrador —el caso de una plantilla propia hecha
 *  en el modo “Código HTML”—.
 *
 *  Por qué la columna: hasta ahora la vista previa mostraba el diseño
 *  institucional pero el envío mandaba el HTML sin él, así que lo que se veía
 *  no era lo que llegaba al buzón. Ahora la decisión se guarda con la campaña
 *  y una sola función del modelo (MailBulkModel::buildEmailHtml) arma el
 *  correo tanto para la previa como para el envío.
 *
 *  Valor por omisión 1 (con diseño institucional) para que las campañas y
 *  redacciones ya guardadas conserven el aspecto que mostraba la vista previa.
 *
 *  · mail_campaigns.use_layout
 *  · mail_saved_messages.use_layout
 *
 *  Idempotente: puede ejecutarse múltiples veces sin errores.
 *  Ejecutar:  php database/migrations/update_mail_bulk_html_mode.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tableExists = function (string $t) use ($pdo): bool {
    return (bool) $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
};

$columnExists = function (string $table, string $column) use ($pdo): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :c");
    $stmt->execute([':c' => $column]);
    return (bool) $stmt->fetch();
};

echo "Migración: modo Código HTML del Gestor de envío masivo de correos...\n\n";

try {
    $targets = [
        'mail_campaigns'      => 'Campañas enviadas',
        'mail_saved_messages' => 'Redacciones guardadas',
    ];

    foreach ($targets as $table => $label) {
        echo "· {$label} ({$table})...\n";

        if (!$tableExists($table)) {
            echo "   [AVISO] La tabla no existe. Ejecuta primero create_mail_bulk_module.php.\n";
            continue;
        }

        if ($columnExists($table, 'use_layout')) {
            echo "   [INFO] La columna use_layout ya existe.\n";
            continue;
        }

        $pdo->exec("
            ALTER TABLE `{$table}`
            ADD COLUMN use_layout TINYINT(1) NOT NULL DEFAULT 1
                COMMENT '1 = envolver en el diseño institucional (logo y pie); 0 = enviar el HTML tal cual'
            AFTER body_html
        ");
        echo "   [OK] Columna use_layout agregada.\n";
    }

    echo "\nMigración completada correctamente.\n";
} catch (PDOException $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
