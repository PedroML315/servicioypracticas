<?php
/**
 * Migración: correo de "No autorizar" solicitud de practicantes con motivo.
 *
 * Idempotente: puede ejecutarse múltiples veces sin errores.
 * Ejecutar desde CLI:  php database/update_solicitud_practicantes_no_autorizada.php
 *
 * Actualiza la plantilla 'solicitud_practicantes_rechazada' para incluir el
 * bloque {{{motivoHtml}}} y ajustar la redacción a "no autorizada".
 */

require_once __DIR__ . '/../model/conection.php';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Actualizando plantilla 'solicitud_practicantes_rechazada'...\n";

    $html = '<h2>Hola {{contactName}},</h2>
<p>Lamentamos informarle que la <strong>solicitud de practicantes</strong> con el perfil solicitado: <strong>{{degreeName}}</strong> no ha sido <strong>autorizada</strong> en esta ocasión.</p>
{{{motivoHtml}}}
<p>Agradecemos su interés en colaborar con la Universidad Montrer y lo invitamos a enviar futuras solicitudes para próximas generaciones de estudiantes.</p>
<p>Si requiere más información o apoyo, comuníquese al correo:
<a href="mailto:practicasprofesionales@unimontrer.edu.mx" target="_blank" rel="noopener noreferrer">practicasprofesionales@unimontrer.edu.mx</a>.</p>
<p>Saludos cordiales,<br>Universidad Montrer - Área de Prácticas Profesionales</p>';

    $subject = 'Resultado de tu solicitud de practicantes - UNIMO';
    $name    = 'Solicitud de practicantes no autorizada (con motivo)';
    // El texto plano ignora el placeholder de HTML del motivo.
    $textPlain = strip_tags(str_replace('{{{motivoHtml}}}', '', $html));

    $check = $pdo->prepare("SELECT id FROM email_templates WHERE tkey = ?");
    $check->execute(['solicitud_practicantes_rechazada']);

    if ($check->rowCount() > 0) {
        $upd = $pdo->prepare("UPDATE email_templates SET name = ?, subject = ?, html = ?, text_plain = ? WHERE tkey = ?");
        $upd->execute([$name, $subject, $html, $textPlain, 'solicitud_practicantes_rechazada']);
        echo "   - Template actualizado.\n";
    } else {
        $ins = $pdo->prepare("INSERT INTO email_templates (tkey, name, subject, html, text_plain) VALUES (?, ?, ?, ?, ?)");
        $ins->execute(['solicitud_practicantes_rechazada', $name, $subject, $html, $textPlain]);
        echo "   - Template insertado.\n";
    }

    echo "\n✅ Migración completada con éxito.\n";

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
