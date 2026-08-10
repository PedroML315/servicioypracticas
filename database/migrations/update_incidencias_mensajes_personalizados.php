<?php

/**
 * ─────────────────────────────────────────────────────────────────────────
 *  Correos de seguimiento de incidencias · versiones personalizadas
 * ─────────────────────────────────────────────────────────────────────────
 *  Sustituye la plantilla genérica `pp_incidencia_mensaje` por dos plantillas
 *  con destinatario y redacción propios:
 *    · pp_incidencia_mensaje_alumno   → dirigido al practicante
 *    · pp_incidencia_mensaje_empresa  → dirigido al organismo receptor
 *
 *  Idempotente: puede ejecutarse múltiples veces sin errores.
 *  Ejecutar:  php database/migrations/update_incidencias_mensajes_personalizados.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Actualizando correos de seguimiento de incidencias...\n\n";

/* Estilos en línea reutilizados (los clientes de correo ignoran el CSS externo) */
$tabla = 'cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:.92rem;margin:14px 0;"';
$tdL   = 'style="border:1px solid #e2e8f0;background:#f8fafc;font-weight:600;color:#475569;width:38%;"';
$tdR   = 'style="border:1px solid #e2e8f0;color:#0f172a;"';
$cita  = 'style="background:#f8fafc;border-left:4px solid #01643D;padding:14px 18px;color:#334155;line-height:1.6;"';
$firma = '<p style="margin-top:22px;">Atentamente,<br>
<strong>{{adminNombre}}</strong><br>
Área de Prácticas Profesionales · Universidad Montrer<br>
<a href="mailto:practicasprofesionales@unimontrer.edu.mx">practicasprofesionales@unimontrer.edu.mx</a></p>
<p style="color:#64748b;font-size:.78rem;border-top:1px solid #e2e8f0;padding-top:10px;">
Este mensaje forma parte del expediente de seguimiento <strong>#{{idIncidencia}}</strong> y fue emitido el {{fechaEnvio}}.
Le solicitamos conservarlo para futuras referencias.</p>';

$templates = [
    /* ───────────────────────── Practicante ───────────────────────── */
    [
        'tkey' => 'pp_incidencia_mensaje_alumno',
        'name' => 'Seguimiento de incidencia · comunicado al practicante',
        'subject' => '{{asunto}}',
        'html' => '<h2 style="color:#01643D;">Comunicado del Área de Prácticas Profesionales</h2>
<p>Estimado(a) <strong>{{studentName}}</strong>:</p>
<p>Por este conducto, la Universidad Montrer da seguimiento institucional a una situación reportada por el organismo receptor
<strong>{{empresa}}</strong> en relación con el desarrollo de tus prácticas profesionales.</p>
<table ' . $tabla . '>
  <tr><td ' . $tdL . '>Folio de seguimiento</td><td ' . $tdR . '>#{{idIncidencia}}</td></tr>
  <tr><td ' . $tdL . '>Practicante</td><td ' . $tdR . '>{{studentName}} · Matrícula {{matricula}}</td></tr>
  <tr><td ' . $tdL . '>Programa académico</td><td ' . $tdR . '>{{programa}}</td></tr>
  <tr><td ' . $tdL . '>Organismo receptor</td><td ' . $tdR . '>{{empresa}}</td></tr>
  <tr><td ' . $tdL . '>Motivo del seguimiento</td><td ' . $tdR . '>{{tipoIncidencia}}</td></tr>
  <tr><td ' . $tdL . '>Asunto</td><td ' . $tdR . '>{{asunto}}</td></tr>
</table>
<h3 style="color:#01643D;font-size:1rem;margin-bottom:6px;">Comunicación del área</h3>
<div ' . $cita . '>{{{mensajeHtml}}}</div>
<p style="margin-top:16px;">Te solicitamos atender esta comunicación a la brevedad y responder a este correo exponiendo tu punto de vista
sobre lo señalado. Tu participación es indispensable para resolver el caso y para la acreditación formal de tus prácticas profesionales.</p>
<p>Quedamos atentos a tu respuesta.</p>' . $firma,
        'text_plain' => 'Estimado(a) {{studentName}}: La Universidad Montrer da seguimiento a una situación reportada por {{empresa}} respecto a tus prácticas profesionales. Folio #{{idIncidencia}}. Matrícula {{matricula}}. Motivo: {{tipoIncidencia}}. Asunto: {{asunto}}. Comunicación del área: {{mensaje}} Te solicitamos responder a este correo a la brevedad exponiendo tu punto de vista. Atentamente, {{adminNombre}}, Área de Prácticas Profesionales, Universidad Montrer.',
    ],

    /* ────────────────────── Organismo receptor ───────────────────── */
    [
        'tkey' => 'pp_incidencia_mensaje_empresa',
        'name' => 'Seguimiento de incidencia · comunicado al organismo receptor',
        'subject' => '{{asunto}}',
        'html' => '<h2 style="color:#01643D;">Seguimiento institucional · Prácticas Profesionales</h2>
<p>Estimado(a) <strong>{{contactName}}</strong>:</p>
<p>En atención al reporte de incidencia presentado por <strong>{{empresa}}</strong> respecto al practicante
<strong>{{studentName}}</strong>, Universidad Montrer le comparte la siguiente comunicación sobre el estado del caso.</p>
<table ' . $tabla . '>
  <tr><td ' . $tdL . '>Folio de seguimiento</td><td ' . $tdR . '>#{{idIncidencia}}</td></tr>
  <tr><td ' . $tdL . '>Organismo receptor</td><td ' . $tdR . '>{{empresa}}</td></tr>
  <tr><td ' . $tdL . '>Practicante</td><td ' . $tdR . '>{{studentName}} · Matrícula {{matricula}}</td></tr>
  <tr><td ' . $tdL . '>Programa académico</td><td ' . $tdR . '>{{programa}}</td></tr>
  <tr><td ' . $tdL . '>Motivo del reporte</td><td ' . $tdR . '>{{tipoIncidencia}}</td></tr>
  <tr><td ' . $tdL . '>Reporte levantado el</td><td ' . $tdR . '>{{fechaReporte}}</td></tr>
</table>
<h3 style="color:#01643D;font-size:1rem;margin-bottom:6px;">Comunicación del área</h3>
<div ' . $cita . '>{{{mensajeHtml}}}</div>
<p style="margin-top:16px;">Agradecemos la disposición de <strong>{{empresa}}</strong> para dar seguimiento conjunto a este asunto.
Si cuenta con información adicional o desea precisar algún punto, puede remitirlo respondiendo a este correo; será incorporado
al expediente del caso.</p>
<p>Reiteramos nuestro compromiso de acompañar a ambas partes hasta la resolución del mismo.</p>' . $firma,
        'text_plain' => 'Estimado(a) {{contactName}}: En atención al reporte presentado por {{empresa}} respecto al practicante {{studentName}} (matrícula {{matricula}}), la Universidad Montrer le comparte lo siguiente. Folio #{{idIncidencia}}. Motivo: {{tipoIncidencia}}. Reporte levantado el {{fechaReporte}}. Comunicación del área: {{mensaje}} Si cuenta con información adicional puede responder a este correo. Atentamente, {{adminNombre}}, Área de Prácticas Profesionales, Universidad Montrer.',
    ],
];

try {
    foreach ($templates as $t) {
        $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE tkey = ?");
        $stmt->execute([$t['tkey']]);
        if ($stmt->fetch()) {
            $upd = $pdo->prepare("UPDATE email_templates SET name = ?, subject = ?, html = ?, text_plain = ? WHERE tkey = ?");
            $upd->execute([$t['name'], $t['subject'], $t['html'], $t['text_plain'], $t['tkey']]);
            echo "   [OK] Plantilla actualizada: {$t['tkey']}\n";
        } else {
            $ins = $pdo->prepare("INSERT INTO email_templates (tkey, name, subject, html, text_plain) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$t['tkey'], $t['name'], $t['subject'], $t['html'], $t['text_plain']]);
            echo "   [OK] Plantilla insertada: {$t['tkey']}\n";
        }
    }

    /* Cada envío se registra por separado: ya no se guardan filas 'ambos'. */
    $pdo->exec("
        ALTER TABLE incidencia_mensajes
        MODIFY COLUMN destinatario VARCHAR(10) NOT NULL
        COMMENT 'alumno|empresa (una fila por destinatario; ambos = 2 filas)'
    ");
    echo "   [OK] Comentario de incidencia_mensajes.destinatario actualizado.\n";

    /* La genérica queda sin uso: ya nadie la referencia desde el código. */
    $del = $pdo->prepare("DELETE FROM email_templates WHERE tkey = ?");
    $del->execute(['pp_incidencia_mensaje']);
    echo $del->rowCount()
        ? "   [OK] Plantilla genérica 'pp_incidencia_mensaje' eliminada (sustituida por las dos anteriores).\n"
        : "   [INFO] La plantilla genérica 'pp_incidencia_mensaje' ya no existía.\n";

    echo "\n¡Correos de seguimiento actualizados con éxito!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
