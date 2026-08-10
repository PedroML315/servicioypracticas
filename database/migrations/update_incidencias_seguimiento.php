<?php

/**
 * ─────────────────────────────────────────────────────────────────────────
 *  Seguimiento administrativo de los reportes de incidencias
 * ─────────────────────────────────────────────────────────────────────────
 *  · reportes_incidencias: fechas de atención/cierre, solución y responsable.
 *    Semántica de `status`: 0 = pendiente, 1 = en proceso, 2 = atendida.
 *  · incidencia_mensajes: bitácora de comunicación con alumno / empresa.
 *  · incidencia_juntas: reuniones (virtuales o presenciales) convocadas.
 *  · Plantillas de correo pp_incidencia_mensaje / _junta / _cierre.
 *
 *  Idempotente: puede ejecutarse múltiples veces sin errores.
 *  Ejecutar:  php database/migrations/update_incidencias_seguimiento.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tableExists = function (string $t) use ($pdo): bool {
    return (bool) $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
};
$columnExists = function (string $t, string $c) use ($pdo): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$t` LIKE ?");
    $stmt->execute([$c]);
    return (bool) $stmt->fetch();
};

echo "Iniciando migración de seguimiento de incidencias...\n\n";

try {

    if (!$tableExists('reportes_incidencias')) {
        echo "[ERROR] Falta la tabla reportes_incidencias. Ejecuta primero update_reportes_incidencias.php\n";
        exit(1);
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 1. Columnas de seguimiento en reportes_incidencias
     * ═══════════════════════════════════════════════════════════════════ */
    echo "1. Columnas de seguimiento en reportes_incidencias...\n";

    $nuevas = [
        'fecha_atencion' => "ADD COLUMN fecha_atencion DATETIME DEFAULT NULL COMMENT 'Cuándo el admin comenzó a atender (pasó a en proceso)'",
        'fecha_cierre'   => "ADD COLUMN fecha_cierre DATETIME DEFAULT NULL COMMENT 'Cuándo se marcó como atendida / finalizada'",
        'solucion'       => "ADD COLUMN solucion TEXT DEFAULT NULL COMMENT 'Cuál fue la solución al caso'",
        'atendido_por'   => "ADD COLUMN atendido_por INT DEFAULT NULL COMMENT 'users.id del administrador responsable'",
    ];
    foreach ($nuevas as $col => $sql) {
        if (!$columnExists('reportes_incidencias', $col)) {
            $pdo->exec("ALTER TABLE reportes_incidencias $sql");
            echo "   [OK] Columna $col añadida.\n";
        } else {
            echo "   [INFO] $col ya existe.\n";
        }
    }

    // Semántica nueva de status (0 pendiente · 1 en proceso · 2 atendida)
    $pdo->exec("
        ALTER TABLE reportes_incidencias
        MODIFY COLUMN status TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '0 = pendiente, 1 = en proceso, 2 = atendida'
    ");
    echo "   [OK] Comentario de status actualizado (0 pendiente · 1 en proceso · 2 atendida).\n";

    /* ═════════════════════════════════════════════════════════════════════
     * 2. Bitácora de comunicación
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n2. Tabla incidencia_mensajes...\n";
    if (!$tableExists('incidencia_mensajes')) {
        $pdo->exec("
            CREATE TABLE incidencia_mensajes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idIncidencia INT NOT NULL,
                destinatario VARCHAR(10) NOT NULL COMMENT 'alumno|empresa|ambos',
                asunto VARCHAR(180) NOT NULL,
                mensaje TEXT NOT NULL,
                enviado_a VARCHAR(255) DEFAULT NULL COMMENT 'Correos a los que se envió',
                created_by INT DEFAULT NULL COMMENT 'users.id del administrador',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_incmsg_incidencia (idIncidencia)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "   [OK] Tabla incidencia_mensajes creada.\n";
    } else {
        echo "   [INFO] incidencia_mensajes ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 3. Juntas de seguimiento
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n3. Tabla incidencia_juntas...\n";
    if (!$tableExists('incidencia_juntas')) {
        $pdo->exec("
            CREATE TABLE incidencia_juntas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idIncidencia INT NOT NULL,
                modalidad VARCHAR(15) NOT NULL COMMENT 'Virtual|Presencial',
                fecha DATE NOT NULL,
                hora TIME NOT NULL,
                url_sesion VARCHAR(500) DEFAULT NULL COMMENT 'Enlace Meet/Teams si es Virtual',
                lugar VARCHAR(255) DEFAULT NULL COMMENT 'Dirección o sala si es Presencial',
                agenda TEXT DEFAULT NULL COMMENT 'Puntos a tratar en la junta',
                invita_alumno TINYINT(1) NOT NULL DEFAULT 1,
                invita_empresa TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT DEFAULT NULL COMMENT 'users.id del administrador',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_incjun_incidencia (idIncidencia),
                KEY idx_incjun_fecha (fecha, hora)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "   [OK] Tabla incidencia_juntas creada.\n";
    } else {
        echo "   [INFO] incidencia_juntas ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 4. Plantillas de correo del seguimiento
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n4. Plantillas de correo...\n";

    $templates = [
        [
            'tkey' => 'pp_incidencia_mensaje',
            'name' => 'Seguimiento de incidencia · mensaje del administrador',
            'subject' => '{{asunto}}',
            'html' => '<h2 style="color:#01643D;">Seguimiento de prácticas profesionales</h2>
<p>Hola <strong>{{destinatarioNombre}}</strong>,</p>
<p>Te escribimos respecto al seguimiento del practicante <strong>{{studentName}}</strong> en <strong>{{empresa}}</strong>.</p>
<div style="background:#f8fafc;border-left:4px solid #01643D;padding:12px 16px;margin:14px 0;">{{{mensajeHtml}}}</div>
<p>Puedes responder directamente a este correo o comunicarte al
<a href="mailto:practicasprofesionales@unimontrer.edu.mx">practicasprofesionales@unimontrer.edu.mx</a>.</p>
<p>Saludos cordiales,<br>Universidad Montrer - Área de Prácticas Profesionales</p>',
            'text_plain' => 'Hola {{destinatarioNombre}}, sobre el seguimiento de {{studentName}} en {{empresa}}: {{mensaje}} — Universidad Montrer, Prácticas Profesionales.',
        ],
        [
            'tkey' => 'pp_incidencia_junta',
            'name' => 'Seguimiento de incidencia · convocatoria a junta',
            'subject' => 'Junta de seguimiento · {{studentName}} ({{fecha}})',
            'html' => '<h2 style="color:#01643D;">Junta de seguimiento</h2>
<p>Hola <strong>{{destinatarioNombre}}</strong>,</p>
<p>La Universidad Montrer convoca a una junta de seguimiento sobre el practicante <strong>{{studentName}}</strong> en <strong>{{empresa}}</strong>.</p>
<ul>
  <li><strong>Fecha:</strong> {{fecha}}</li>
  <li><strong>Hora:</strong> {{hora}}</li>
  <li><strong>Modalidad:</strong> {{modalidad}}</li>
  {{{detalleModalidad}}}
</ul>
{{{agendaHtml}}}
<p>Tu asistencia es muy importante para llegar a un acuerdo. Si no puedes asistir, responde a este correo para reprogramar.</p>
<p>Saludos cordiales,<br>Universidad Montrer - Área de Prácticas Profesionales</p>',
            'text_plain' => 'Junta de seguimiento sobre {{studentName}} en {{empresa}}. Fecha: {{fecha}}, Hora: {{hora}}, Modalidad: {{modalidad}}. {{agenda}}',
        ],
        [
            'tkey' => 'pp_incidencia_cierre',
            'name' => 'Seguimiento de incidencia · caso atendido',
            'subject' => 'Caso atendido · {{studentName}}',
            'html' => '<h2 style="color:#01643D;">Caso atendido</h2>
<p>Hola <strong>{{destinatarioNombre}}</strong>,</p>
<p>El reporte de incidencia sobre el practicante <strong>{{studentName}}</strong> en <strong>{{empresa}}</strong> ha sido <strong>atendido</strong> por el área de Prácticas Profesionales.</p>
<h3 style="color:#01643D;margin-top:18px;">Solución acordada</h3>
<div style="background:#f0fdf4;border-left:4px solid #01643D;padding:12px 16px;">{{{solucionHtml}}}</div>
<p style="margin-top:16px;">Si tienes dudas o el problema continúa, comunícate al
<a href="mailto:practicasprofesionales@unimontrer.edu.mx">practicasprofesionales@unimontrer.edu.mx</a>.</p>
<p style="color:#64748b;font-size:.85rem;">Reporte #{{idIncidencia}} · cerrado el {{fechaCierre}}.</p>',
            'text_plain' => 'El reporte de incidencia #{{idIncidencia}} sobre {{studentName}} en {{empresa}} fue atendido. Solución: {{solucion}}. Cerrado el {{fechaCierre}}.',
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

    echo "\n¡Migración de seguimiento de incidencias completada con éxito!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
