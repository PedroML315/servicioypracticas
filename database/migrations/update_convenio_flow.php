<?php
/**
 * Migración: Nuevo flujo de Convenios Institucionales de Organismos Externos
 *
 * Idempotente: puede ejecutarse múltiples veces sin errores.
 * Ejecutar desde CLI:  php database/update_convenio_flow.php
 * O desde navegador en entorno local.
 *
 * Crea / modifica:
 *   - organismos_externos: agrega convenio_estado + archivos y fechas del convenio.
 *   - tokens_convenio_organismos: enlaces de un solo uso para subir el convenio firmado.
 *   - email_queue: agrega columna attachments (JSON) para adjuntar PDFs.
 *
 * Inserta / actualiza:
 *   - 4 plantillas de correo en email_templates.
 */

require_once __DIR__ . '/../../model/conection.php';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migración: Nuevo flujo de Convenios Institucionales...\n";

    // ─── 1. ALTER organismos_externos ────────────────────────────────────────
    $cols = $pdo->query("SHOW COLUMNS FROM organismos_externos")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('convenio_estado', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos
            ADD COLUMN `convenio_estado`
              ENUM('ninguno','generado','firmado_pendiente','firmado_rechazado','validado')
              NOT NULL DEFAULT 'ninguno'
            COMMENT 'Sub-flujo del convenio institucional (independiente de isAcepted)'");
        echo "1a. Columna convenio_estado agregada.\n";
    } else {
        echo "1a. Columna convenio_estado ya existe (omitida).\n";
    }

    $addCol = function (string $name, string $ddl) use ($pdo, $cols) {
        if (!in_array($name, $cols)) {
            $pdo->exec("ALTER TABLE organismos_externos ADD COLUMN {$ddl}");
            echo "    - Columna {$name} agregada.\n";
        } else {
            echo "    - Columna {$name} ya existe (omitida).\n";
        }
    };
    $addCol('convenio_generado_file', "`convenio_generado_file` VARCHAR(255) DEFAULT NULL COMMENT 'PDF generado por la plataforma (uploads/{id}/)'");
    $addCol('convenio_generado_at',   "`convenio_generado_at` DATETIME DEFAULT NULL");
    $addCol('convenio_firmado_file',  "`convenio_firmado_file` VARCHAR(255) DEFAULT NULL COMMENT 'PDF firmado autógrafamente y escaneado por el organismo'");
    $addCol('convenio_firmado_at',    "`convenio_firmado_at` DATETIME DEFAULT NULL");
    $addCol('convenio_motivo_rechazo',"`convenio_motivo_rechazo` TEXT DEFAULT NULL COMMENT 'Motivo del último rechazo del convenio firmado'");
    echo "1b. Columnas de archivos/fechas del convenio aseguradas.\n";

    // Backfill: organismos ya activos se consideran 'validado' para no romper su acceso.
    $affected = $pdo->exec("UPDATE organismos_externos
        SET convenio_estado = 'validado'
        WHERE isAcepted = 1 AND convenio_estado = 'ninguno'");
    echo "1c. Backfill convenio_estado='validado' para organismos activos (filas: {$affected}).\n";

    // ─── 2. Tabla tokens_convenio_organismos ─────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tokens_convenio_organismos` (
      `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `organismo_id`        INT UNSIGNED NOT NULL COMMENT 'FK → organismos_externos.id',
      `token`               VARCHAR(64) NOT NULL COMMENT 'Token criptográfico (hex 64 chars)',
      `tipo`                ENUM('firma','recarga') NOT NULL DEFAULT 'firma'
                            COMMENT 'firma = primer envío; recarga = reenvío tras rechazo',
      `expira_at`           DATETIME NOT NULL COMMENT 'Fecha/hora de expiración',
      `usado`               TINYINT(1) NOT NULL DEFAULT 0,
      `usado_at`            DATETIME DEFAULT NULL,
      `otp_code`            VARCHAR(255) DEFAULT NULL COMMENT 'Hash del código OTP activo',
      `otp_expira_at`       DATETIME DEFAULT NULL,
      `otp_intentos`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
      `otp_bloqueado_hasta` DATETIME DEFAULT NULL,
      `otp_reenvios`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
      `ip_acceso`           VARCHAR(45) DEFAULT NULL,
      `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_token_convenio` (`token`),
      KEY `idx_token_conv_organismo` (`organismo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Tokens de un solo uso para subir el convenio firmado por el organismo';");
    echo "2. Tabla tokens_convenio_organismos asegurada.\n";

    // ─── 3. ALTER email_queue: soporte de adjuntos ───────────────────────────
    $eqCols = $pdo->query("SHOW COLUMNS FROM email_queue")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('attachments', $eqCols)) {
        $pdo->exec("ALTER TABLE email_queue
            ADD COLUMN `attachments` TEXT DEFAULT NULL
            COMMENT 'JSON array de rutas absolutas de archivos a adjuntar'");
        echo "3. Columna email_queue.attachments agregada.\n";
    } else {
        echo "3. Columna email_queue.attachments ya existe (omitida).\n";
    }

    // ─── 4. Plantillas de correo ─────────────────────────────────────────────
    // Fragmentos HTML (sendTemplateByKey los envuelve en el layout con logo/pie).
    // Diseño sobrio y consistente con las plantillas pp_* existentes (verde #01643D).
    $btn = 'background:#01643D;color:#fff;padding:.75rem 2rem;border-radius:.5rem;text-decoration:none;font-weight:600;display:inline-block;';

    $templates = [
        'pp_nuevo_organismo_admin' => [
            'name'    => 'Nuevo organismo registrado (Notificación Admin)',
            'subject' => 'Nuevo organismo pendiente de validación — {{empresa}}',
            'html'    => '<p>Se ha registrado un nuevo <strong>Organismo Receptor</strong> en la plataforma de Prácticas Profesionales y está pendiente de validación.</p>
<table style="width:100%;border-collapse:collapse;margin:1rem 0;">
  <tr><td style="padding:6px 0;color:#6E6E73;">Organismo:</td><td style="padding:6px 0;font-weight:600;">{{empresa}}</td></tr>
  <tr><td style="padding:6px 0;color:#6E6E73;">Contacto:</td><td style="padding:6px 0;">{{contacto}}</td></tr>
  <tr><td style="padding:6px 0;color:#6E6E73;">Correo:</td><td style="padding:6px 0;">{{correo}}</td></tr>
  <tr><td style="padding:6px 0;color:#6E6E73;">Folio interno:</td><td style="padding:6px 0;">#{{orgId}}</td></tr>
</table>
<p>Ingresa al panel de administración (Organismos Receptores) para revisar la información. Al aprobar el registro, la plataforma generará automáticamente el convenio y lo enviará al organismo para su firma.</p>',
        ],

        'pp_convenio_generado_organismo' => [
            'name'    => 'Convenio generado — firma y reenvío (Organismo)',
            'subject' => 'Tu convenio de colaboración está listo para firma — UNIMO',
            'html'    => '<p>Estimado(a) <strong>{{empresa}}</strong>,</p>
<p>¡Buenas noticias! Tu registro como Organismo Receptor ha sido aprobado por la <strong>Universidad Montrer (UNIMO)</strong>. Adjunto a este correo encontrarás el <strong>convenio de colaboración</strong> generado con tus datos.</p>
<p><strong>Para completar el proceso:</strong></p>
<ol style="padding-left:1.2rem;">
  <li>Descarga e <strong>imprime</strong> el convenio adjunto.</li>
  <li>Fírmalo de forma <u>autógrafa</u> (firma a mano; no digital).</li>
  <li>Escanéalo y <strong>súbelo</strong> mediante el siguiente enlace seguro.</li>
</ol>
<p style="text-align:center;margin:1.5rem 0;">
  <a href="{{enlaceFirma}}" style="' . $btn . '">Subir convenio firmado</a>
</p>
<p style="font-size:.85rem;color:#8E8E93;">Este enlace es <strong>personal, de un solo uso</strong> y expira el <strong>{{expiraEn}}</strong>. No lo compartas con nadie.</p>
<p style="font-size:.9rem;">Por el momento tu cuenta <strong>aún no está activa</strong>: las credenciales de acceso se enviarán una vez validemos el convenio firmado.</p>
<p>¿Dudas? Escríbenos a <a href="mailto:{{emailPP}}">{{emailPP}}</a>.</p>',
        ],

        'pp_convenio_firmado_admin' => [
            'name'    => 'Convenio firmado recibido (Notificación Admin)',
            'subject' => 'Convenio firmado recibido — {{empresa}}',
            'html'    => '<p>El organismo <strong>{{empresa}}</strong> (folio #{{orgId}}) firmó, escaneó y reenvió correctamente su convenio de colaboración.</p>
<p><strong>Fecha de recepción:</strong> {{fecha}}</p>
<p>El documento está <strong>pendiente de validación final</strong>. Ingresa al panel de administración (Organismos Receptores) para revisarlo y aprobarlo o rechazarlo.</p>
<p style="font-size:.9rem;color:#6E6E73;">Al aprobar el convenio se activará la cuenta del organismo y se enviarán sus credenciales de acceso automáticamente.</p>',
        ],

        'pp_convenio_rechazado_organismo' => [
            'name'    => 'Convenio firmado rechazado — recarga (Organismo)',
            'subject' => 'Tu convenio requiere corrección — UNIMO',
            'html'    => '<p>Estimado(a) <strong>{{empresa}}</strong>,</p>
<p>Revisamos el convenio firmado que nos enviaste y, lamentablemente, <strong>no pudo ser validado</strong> por el siguiente motivo:</p>
<blockquote style="border-left:3px solid #B00020;padding-left:1rem;color:#555;margin:1rem 0;">{{motivo}}</blockquote>
<p>Por favor corrige lo indicado, vuelve a firmar el convenio de forma autógrafa, escanéalo y cárgalo nuevamente mediante el siguiente enlace seguro:</p>
<p style="text-align:center;margin:1.5rem 0;">
  <a href="{{enlaceFirma}}" style="' . $btn . '">Cargar convenio corregido</a>
</p>
<p style="font-size:.85rem;color:#8E8E93;">Este enlace es <strong>personal, de un solo uso</strong> y expira el <strong>{{expiraEn}}</strong>.</p>
<p>¿Dudas? Escríbenos a <a href="mailto:{{emailPP}}">{{emailPP}}</a>.</p>',
        ],
    ];

    $stmtCheck  = $pdo->prepare("SELECT id FROM email_templates WHERE tkey = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO email_templates (tkey, name, subject, html, text_plain) VALUES (?, ?, ?, ?, ?)");
    $stmtUpdate = $pdo->prepare("UPDATE email_templates SET name = ?, subject = ?, html = ?, text_plain = ? WHERE tkey = ?");

    foreach ($templates as $key => $data) {
        $stmtCheck->execute([$key]);
        if ($stmtCheck->rowCount() > 0) {
            $stmtUpdate->execute([$data['name'], $data['subject'], $data['html'], strip_tags($data['html']), $key]);
            echo "   - Template '$key' actualizado.\n";
        } else {
            $stmtInsert->execute([$key, $data['name'], $data['subject'], $data['html'], strip_tags($data['html'])]);
            echo "   - Template '$key' insertado.\n";
        }
    }
    echo "4. Plantillas de correo aseguradas.\n";

    echo "\n✅ Migración 'Nuevo flujo de Convenios Institucionales' completada con éxito.\n";

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
