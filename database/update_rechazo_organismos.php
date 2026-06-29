<?php
/**
 * Migración: Flujo de Rechazo y Corrección de Organismos Externos
 *
 * Idempotente: puede ejecutarse múltiples veces sin errores.
 * Ejecutar desde CLI:  php database/update_rechazo_organismos.php
 * O desde navegador en entorno local.
 *
 * Crea:
 *   - rechazos_organismos
 *   - rechazo_campos
 *   - tokens_correccion_organismos
 *   - historial_correcciones_organismos
 *
 * Modifica:
 *   - organismos_externos: amplía isAcepted a 4 estados, agrega rechazo_activo_id
 *
 * Inserta:
 *   - 4 plantillas de correo en email_templates
 */

require_once __DIR__ . '/../model/conection.php';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migración: Rechazo y Corrección de Organismos Externos...\n";

    // ─── 1. Tabla rechazos_organismos ────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rechazos_organismos` (
      `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `organismo_id`    INT UNSIGNED NOT NULL COMMENT 'FK → organismos_externos.id',
      `motivo_general`  TEXT NOT NULL COMMENT 'Razón general del rechazo',
      `admin_id`        INT UNSIGNED NOT NULL COMMENT 'FK → users.id del admin que rechazó',
      `admin_name`      VARCHAR(200) NOT NULL,
      `estado`          ENUM('pendiente_correccion','corregido','expirado') NOT NULL DEFAULT 'pendiente_correccion',
      `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `corregido_at`    DATETIME DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_rechazo_organismo` (`organismo_id`),
      KEY `idx_rechazo_estado`    (`estado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Registro de cada rechazo de un organismo externo';");
    echo "1. Tabla rechazos_organismos asegurada.\n";

    // ─── 2. Tabla rechazo_campos ─────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rechazo_campos` (
      `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `rechazo_id`      INT UNSIGNED NOT NULL COMMENT 'FK → rechazos_organismos.id',
      `campo`           VARCHAR(80) NOT NULL COMMENT 'Nombre de columna en organismos_externos o \"doc:{nombre_archivo}\"',
      `campo_label`     VARCHAR(120) NOT NULL COMMENT 'Etiqueta legible del campo',
      `estado`          ENUM('incorrecto','faltante','invalido') NOT NULL DEFAULT 'incorrecto',
      `motivo`          TEXT NOT NULL COMMENT 'Razón del error en este campo',
      `observacion`     TEXT DEFAULT NULL COMMENT 'Instrucciones adicionales para el organismo',
      `valor_original`  TEXT DEFAULT NULL COMMENT 'Valor al momento del rechazo',
      `valor_corregido` TEXT DEFAULT NULL COMMENT 'Valor después de la corrección',
      `corregido`       TINYINT(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `idx_campo_rechazo` (`rechazo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Campos específicos marcados como erróneos en un rechazo';");
    echo "2. Tabla rechazo_campos asegurada.\n";

    // ─── 3. Tabla tokens_correccion_organismos ───────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tokens_correccion_organismos` (
      `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `rechazo_id`          INT UNSIGNED NOT NULL COMMENT 'FK → rechazos_organismos.id',
      `organismo_id`        INT UNSIGNED NOT NULL COMMENT 'FK → organismos_externos.id',
      `token`               VARCHAR(64) NOT NULL COMMENT 'Token criptográfico (hex 64 chars)',
      `expira_at`           DATETIME NOT NULL COMMENT 'Fecha/hora de expiración (72h)',
      `usado`               TINYINT(1) NOT NULL DEFAULT 0,
      `usado_at`            DATETIME DEFAULT NULL,
      `otp_code`            VARCHAR(255) DEFAULT NULL COMMENT 'Hash del código OTP activo',
      `otp_expira_at`       DATETIME DEFAULT NULL,
      `otp_intentos`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
      `otp_bloqueado_hasta` DATETIME DEFAULT NULL COMMENT 'Bloqueo por intentos fallidos',
      `otp_reenvios`        TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de reenvíos de OTP',
      `email_intentos`      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Intentos de verificación de email',
      `ip_acceso`           VARCHAR(45) DEFAULT NULL COMMENT 'IP desde donde se accedió',
      `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_token` (`token`),
      KEY `idx_token_organismo` (`organismo_id`),
      KEY `idx_token_rechazo`   (`rechazo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Tokens de acceso temporal para corrección de organismos rechazados';");
    echo "3. Tabla tokens_correccion_organismos asegurada.\n";

    // ─── 4. Tabla historial_correcciones_organismos ──────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `historial_correcciones_organismos` (
      `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `rechazo_id`      INT UNSIGNED NOT NULL,
      `organismo_id`    INT UNSIGNED NOT NULL,
      `campo`           VARCHAR(80) NOT NULL,
      `valor_anterior`  TEXT DEFAULT NULL,
      `valor_nuevo`     TEXT DEFAULT NULL,
      `modificado_por`  VARCHAR(100) NOT NULL DEFAULT 'organismo' COMMENT 'organismo o admin',
      `ip`              VARCHAR(45) DEFAULT NULL,
      `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_hist_organismo` (`organismo_id`),
      KEY `idx_hist_rechazo`   (`rechazo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Historial de cada cambio realizado durante una corrección de organismo';");
    echo "4. Tabla historial_correcciones_organismos asegurada.\n";

    // ─── 5. ALTER organismos_externos ────────────────────────────────────────
    $cols = $pdo->query("SHOW COLUMNS FROM organismos_externos")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('rechazo_activo_id', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos
            ADD COLUMN `rechazo_activo_id` INT UNSIGNED DEFAULT NULL
            COMMENT 'FK → rechazos_organismos.id del rechazo activo actual'");
        echo "5a. Columna rechazo_activo_id agregada a organismos_externos.\n";
    } else {
        echo "5a. Columna rechazo_activo_id ya existe (omitida).\n";
    }

    // Ampliar comentario de isAcepted (MySQL no tiene "modify only comment", se hace completo)
    // Solo ejecutar si la columna no tiene ya los 4 estados documentados
    $colInfo = $pdo->query("SHOW FULL COLUMNS FROM organismos_externos LIKE 'isAcepted'")->fetch(PDO::FETCH_ASSOC);
    if ($colInfo && strpos($colInfo['Comment'] ?? '', '2=rechazado') === false) {
        $pdo->exec("ALTER TABLE organismos_externos
            MODIFY COLUMN `isAcepted` TINYINT(1) NOT NULL DEFAULT 0
            COMMENT '0=pendiente, 1=aceptado, 2=rechazado, 3=corregido pendiente revisión'");
        echo "5b. Comentario de isAcepted actualizado (0=pendiente,1=aceptado,2=rechazado,3=corregido).\n";
    } else {
        echo "5b. Comentario de isAcepted ya actualizado (omitido).\n";
    }

    // ─── 6. Plantillas de correo ─────────────────────────────────────────────
    $templates = [
        'pp_organismo_rechazado' => [
            'name'    => 'Solicitud de Organismo Rechazada (con motivos y enlace)',
            'subject' => 'Tu solicitud de registro como Organismo Receptor ha sido rechazada — UNIMO',
            'html'    => '<p>Estimado(a) <strong>{{empresa}}</strong>,</p>
<p>Tras revisar tu solicitud de registro como Organismo Receptor en el programa de Prácticas Profesionales de la <strong>Universidad Montrer (UNIMO)</strong>, el equipo administrativo ha detectado información que requiere corrección.</p>
<p><strong>Motivo del rechazo:</strong></p>
<blockquote style="border-left:3px solid #dc3545;padding-left:1rem;color:#555;">{{motivoGeneral}}</blockquote>
<p><strong>Campos con errores identificados:</strong></p>
{{{camposHtml}}}
<p>Para corregir tu información, utiliza el siguiente enlace <strong>temporal y seguro</strong> (válido por 72 horas):</p>
<p style="text-align:center;margin:1.5rem 0;">
  <a href="{{enlaceCorreccion}}" style="background:#01643D;color:#fff;padding:.75rem 2rem;border-radius:.5rem;text-decoration:none;font-weight:600;display:inline-block;">
    Corregir mi solicitud
  </a>
</p>
<p style="font-size:.85rem;color:#888;">Este enlace expira el <strong>{{expiraEn}}</strong>. No compartas este enlace con nadie.</p>
<p>Si tienes dudas, contáctanos en <a href="mailto:{{emailPP}}">{{emailPP}}</a>.</p>',
        ],

        'pp_organismo_otp' => [
            'name'    => 'Código de verificación OTP para corrección de organismo',
            'subject' => 'Tu código de verificación — UNIMO Prácticas Profesionales',
            'html'    => '<p>Estimado(a) <strong>{{empresa}}</strong>,</p>
<p>Tu código de verificación para acceder al formulario de corrección es:</p>
<p style="text-align:center;margin:1.5rem 0;">
  <span style="font-size:2.5rem;font-weight:700;letter-spacing:.5rem;color:#01643D;font-family:monospace;">{{otp}}</span>
</p>
<p>Este código es válido por <strong>10 minutos</strong> y solo puede usarse una vez.</p>
<p style="font-size:.85rem;color:#888;">Si no solicitaste este código, ignora este correo. Nadie de UNIMO te pedirá este código por teléfono.</p>',
        ],

        'pp_organismo_corregido_admin' => [
            'name'    => 'Organismo corrigió su solicitud (Notificación Admin)',
            'subject' => 'Organismo corregido pendiente de revisión — {{empresa}}',
            'html'    => '<p>El organismo <strong>{{empresa}}</strong> (ID #{{orgId}}) ha corregido su solicitud de registro y está listo para ser revisado nuevamente.</p>
<p><strong>Fecha de corrección:</strong> {{fechaCorreccion}}</p>
<p><strong>Campos corregidos:</strong></p>
{{{camposCorregidosHtml}}}
<p>Ingresa al panel de administración para revisar y aprobar o rechazar la solicitud.</p>',
        ],

        'pp_organismo_token_expirado' => [
            'name'    => 'Enlace de corrección expirado (Organismo)',
            'subject' => 'Tu enlace de corrección ha expirado — UNIMO',
            'html'    => '<p>Estimado(a) <strong>{{empresa}}</strong>,</p>
<p>El enlace que recibiste para corregir tu solicitud de registro ha <strong>expirado</strong>.</p>
<p>Por favor, contacta al equipo de Prácticas Profesionales de UNIMO en <a href="mailto:{{emailPP}}">{{emailPP}}</a> para que te envíen un nuevo enlace.</p>',
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
    echo "6. Plantillas de correo aseguradas.\n";

    echo "\n✅ Migración 'Rechazo y Corrección de Organismos' completada con éxito.\n";

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
