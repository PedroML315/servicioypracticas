<?php

/**
 * ─────────────────────────────────────────────────────────────────────────
 *  Reportes de incidencias del organismo externo sobre un practicante
 * ─────────────────────────────────────────────────────────────────────────
 *  Crea la tabla `reportes_incidencias` y las plantillas de correo
 *  `pp_reporte_incidencia_admin` y `pp_reporte_incidencia_confirmacion`.
 *
 *  Idempotente: puede ejecutarse múltiples veces sin errores.
 *  Ejecutar:  php database/migrations/update_reportes_incidencias.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tableExists = function (string $t) use ($pdo): bool {
    return (bool) $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
};

echo "Iniciando migración de reportes de incidencias...\n\n";

try {

    /* ═════════════════════════════════════════════════════════════════════
     * 1. Tabla reportes_incidencias
     * ═══════════════════════════════════════════════════════════════════ */
    echo "1. Tabla reportes_incidencias...\n";
    if (!$tableExists('reportes_incidencias')) {
        $pdo->exec("
            CREATE TABLE reportes_incidencias (
                idIncidencia INT AUTO_INCREMENT PRIMARY KEY,
                idOrganismo INT NOT NULL,
                idStudent INT NOT NULL,
                idPractica INT DEFAULT NULL COMMENT 'Vacante en la que ocurrió la incidencia',
                matricula VARCHAR(30) DEFAULT NULL,
                tipo VARCHAR(30) NOT NULL COMMENT 'inasistencias|conducta|desempeno|incumplimiento|seguridad|otro',
                gravedad VARCHAR(10) NOT NULL DEFAULT 'media' COMMENT 'baja|media|alta',
                fecha_incidente DATE DEFAULT NULL COMMENT 'Fecha en que ocurrió (opcional)',
                descripcion TEXT NOT NULL COMMENT 'Qué hizo el alumno / narración de los hechos',
                acciones_tomadas TEXT DEFAULT NULL COMMENT 'Qué ha hecho la empresa para resolverlo',
                accion_solicitada VARCHAR(20) NOT NULL DEFAULT 'orientacion' COMMENT 'orientacion|reunion|baja',
                status TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = pendiente, 1 = atendida, 2 = cerrada',
                respuesta_admin TEXT DEFAULT NULL,
                dateCreated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                dateUpdate DATETIME DEFAULT NULL,
                KEY idx_inc_organismo (idOrganismo),
                KEY idx_inc_student (idStudent),
                KEY idx_inc_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "   [OK] Tabla reportes_incidencias creada.\n";
    } else {
        echo "   [INFO] reportes_incidencias ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 2. Plantillas de correo
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n2. Plantillas de correo...\n";

    $templates = [
        [
            'tkey' => 'pp_reporte_incidencia_admin',
            'name' => 'Reporte de incidencia de un practicante (admin)',
            'subject' => 'Reporte de incidencia · {{studentName}} ({{empresa}})',
            'html' => '<h2 style="color:#01643D;">Nuevo reporte de incidencia</h2>
<p>La empresa <strong>{{empresa}}</strong> levantó un reporte de incidencia sobre un practicante.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:.95rem;">
  <tr><td style="border:1px solid #e2e8f0;"><strong>Alumno</strong></td><td style="border:1px solid #e2e8f0;">{{studentName}} (matrícula {{matricula}})</td></tr>
  <tr><td style="border:1px solid #e2e8f0;"><strong>Empresa</strong></td><td style="border:1px solid #e2e8f0;">{{empresa}}</td></tr>
  <tr><td style="border:1px solid #e2e8f0;"><strong>Contacto</strong></td><td style="border:1px solid #e2e8f0;">{{contactName}} · {{contactEmail}}</td></tr>
  <tr><td style="border:1px solid #e2e8f0;"><strong>Tipo de incidencia</strong></td><td style="border:1px solid #e2e8f0;">{{tipo}}</td></tr>
  <tr><td style="border:1px solid #e2e8f0;"><strong>Gravedad</strong></td><td style="border:1px solid #e2e8f0;">{{gravedad}}</td></tr>
  <tr><td style="border:1px solid #e2e8f0;"><strong>Fecha del incidente</strong></td><td style="border:1px solid #e2e8f0;">{{fechaIncidente}}</td></tr>
  <tr><td style="border:1px solid #e2e8f0;"><strong>Acción solicitada</strong></td><td style="border:1px solid #e2e8f0;">{{accionSolicitada}}</td></tr>
</table>
<h3 style="color:#01643D;margin-top:18px;">¿Qué ocurrió?</h3>
<p style="background:#f8fafc;border-left:4px solid #01643D;padding:10px 14px;">{{{descripcionHtml}}}</p>
{{{accionesHtml}}}
<p style="margin-top:16px;">Se recomienda contactar a la empresa y al alumno para dar seguimiento.</p>
<p style="color:#64748b;font-size:.85rem;">Reporte #{{idIncidencia}} · registrado el {{fechaReporte}}.</p>',
            'text_plain' => 'Nuevo reporte de incidencia de {{empresa}} sobre {{studentName}} (matrícula {{matricula}}). Tipo: {{tipo}}. Gravedad: {{gravedad}}. Fecha del incidente: {{fechaIncidente}}. Acción solicitada: {{accionSolicitada}}. Descripción: {{descripcion}}. Contacto: {{contactName}} · {{contactEmail}}.',
        ],
        [
            'tkey' => 'pp_reporte_incidencia_confirmacion',
            'name' => 'Reporte de incidencia recibido (empresa)',
            'subject' => 'Recibimos tu reporte sobre {{studentName}}',
            'html' => '<h2 style="color:#01643D;">Recibimos tu reporte</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p>Registramos el reporte de incidencia del practicante <strong>{{studentName}}</strong>. El área de Prácticas Profesionales lo revisará y se pondrá en contacto contigo para dar seguimiento.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:.95rem;">
  <tr><td style="border:1px solid #e2e8f0;"><strong>Tipo de incidencia</strong></td><td style="border:1px solid #e2e8f0;">{{tipo}}</td></tr>
  <tr><td style="border:1px solid #e2e8f0;"><strong>Gravedad</strong></td><td style="border:1px solid #e2e8f0;">{{gravedad}}</td></tr>
  <tr><td style="border:1px solid #e2e8f0;"><strong>Acción solicitada</strong></td><td style="border:1px solid #e2e8f0;">{{accionSolicitada}}</td></tr>
</table>
<h3 style="color:#01643D;margin-top:18px;">Lo que reportaste</h3>
<p style="background:#f8fafc;border-left:4px solid #01643D;padding:10px 14px;">{{{descripcionHtml}}}</p>
<p style="margin-top:16px;">Si necesitas agregar información, responde a este correo o comunícate al
<a href="mailto:practicasprofesionales@unimontrer.edu.mx">practicasprofesionales@unimontrer.edu.mx</a>.</p>
<p style="color:#64748b;font-size:.85rem;">Reporte #{{idIncidencia}} · registrado el {{fechaReporte}}.</p>',
            'text_plain' => 'Hola {{contactName}}, recibimos tu reporte de incidencia sobre {{studentName}} (#{{idIncidencia}}). Tipo: {{tipo}}. Gravedad: {{gravedad}}. Acción solicitada: {{accionSolicitada}}. Descripción: {{descripcion}}. El área de Prácticas Profesionales dará seguimiento.',
        ],
    ];

    foreach ($templates as $t) {
        $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE tkey = ?");
        $stmt->execute([$t['tkey']]);
        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO email_templates (tkey, name, subject, html, text_plain) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$t['tkey'], $t['name'], $t['subject'], $t['html'], $t['text_plain']]);
            echo "   [OK] Plantilla insertada: {$t['tkey']}\n";
        } else {
            echo "   [INFO] La plantilla ya existe: {$t['tkey']}\n";
        }
    }

    echo "\n¡Migración de reportes de incidencias completada con éxito!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
