<?php
/**
 * Migración: Rechazo definitivo "No Procedente" de Organismos Externos
 *
 * Idempotente: puede ejecutarse múltiples veces sin errores.
 * Ejecutar desde CLI:  php database/update_no_procedente_organismos.php
 * O desde navegador en entorno local.
 *
 * Modifica:
 *   - rechazos_organismos: amplía el ENUM estado con 'no_procedente'
 *   - organismos_externos: documenta el estado 4 en isAcepted
 *
 * Inserta:
 *   - plantilla de correo 'pp_organismo_no_procedente'
 */

require_once __DIR__ . '/../model/conection.php';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migración: No Procedente de Organismos Externos...\n";

    // ─── 1. Ampliar ENUM estado en rechazos_organismos ──────────────────────
    $colInfo = $pdo->query("SHOW COLUMNS FROM rechazos_organismos LIKE 'estado'")->fetch(PDO::FETCH_ASSOC);
    if ($colInfo && strpos($colInfo['Type'] ?? '', 'no_procedente') === false) {
        $pdo->exec("ALTER TABLE rechazos_organismos
            MODIFY COLUMN `estado` ENUM('pendiente_correccion','corregido','expirado','no_procedente')
            NOT NULL DEFAULT 'pendiente_correccion'");
        echo "1. ENUM estado ampliado con 'no_procedente'.\n";
    } else {
        echo "1. ENUM estado ya incluye 'no_procedente' (omitido).\n";
    }

    // ─── 2. Documentar el estado 4 en isAcepted ─────────────────────────────
    $orgCol = $pdo->query("SHOW FULL COLUMNS FROM organismos_externos LIKE 'isAcepted'")->fetch(PDO::FETCH_ASSOC);
    if ($orgCol && strpos($orgCol['Comment'] ?? '', '4=no procedente') === false) {
        $pdo->exec("ALTER TABLE organismos_externos
            MODIFY COLUMN `isAcepted` TINYINT(1) NOT NULL DEFAULT 0
            COMMENT '0=pendiente, 1=aceptado, 2=rechazado, 3=corregido pendiente revisión, 4=no procedente'");
        echo "2. Comentario de isAcepted actualizado (incluye 4=no procedente).\n";
    } else {
        echo "2. Comentario de isAcepted ya actualizado (omitido).\n";
    }

    // ─── 3. Plantilla de correo ─────────────────────────────────────────────
    $templates = [
        'pp_organismo_no_procedente' => [
            'name'    => 'Solicitud de Organismo No Procedente (rechazo definitivo)',
            'subject' => 'Resultado de tu solicitud de registro como Organismo Receptor — UNIMO',
            'html'    => '<p>Estimado(a) <strong>{{empresa}}</strong>,</p>
<p>Agradecemos tu interés en formar parte del programa de Prácticas Profesionales de la <strong>Universidad Montrer (UNIMO)</strong> como Organismo Receptor.</p>
<p>Tras una revisión detallada de tu solicitud de registro, lamentamos informarte que <strong>no es posible dar continuidad al proceso de vinculación</strong> en esta ocasión.</p>
<p><strong>Motivo de la resolución:</strong></p>
<blockquote style="border-left:3px solid #dc3545;padding-left:1rem;color:#555;margin:1rem 0;">{{motivo}}</blockquote>
<p>Esta determinación es definitiva para la presente solicitud. Si en el futuro cambian las condiciones que dieron origen a esta resolución, con gusto podrás presentar una nueva solicitud de registro.</p>
<p>Si consideras que existió algún error o deseas información adicional al respecto, puedes contactarnos en <a href="mailto:{{emailPP}}">{{emailPP}}</a>.</p>
<p>Agradecemos tu comprensión y te reiteramos nuestra disposición para atenderte.</p>
<p style="margin-top:1.5rem;">Atentamente,<br><strong>Coordinación de Prácticas Profesionales</strong><br>Universidad Montrer (UNIMO)</p>',
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
    echo "3. Plantilla de correo asegurada.\n";

    echo "\n✅ Migración 'No Procedente de Organismos' completada con éxito.\n";

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
