<?php
/**
 * Migración: Directorio institucional + aviso de vacante aprobada.
 * ─────────────────────────────────────────────────────────────────────────
 *  1. Crea la tabla `directory` (directorio institucional: directores de
 *     escuela, vicerrectores, etc.) si aún no existe.
 *  2. Inserta la plantilla de correo `directorio_vacante_aprobada`, que se
 *     envía a cada contacto del directorio cuando el administrador acepta
 *     una solicitud de practicantes (módulo Solicitudes de Practicantes).
 *
 *  Idempotente: puede ejecutarse múltiples veces sin errores.
 *  Ejecutar:  php database/migrations/create_directory_notificacion_vacante.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Iniciando migración: directorio institucional y aviso de vacante aprobada...\n\n";

try {

    /* ═════════════════════════════════════════════════════════════════════
     * 1. Tabla directory
     * ═══════════════════════════════════════════════════════════════════ */
    echo "1. Tabla directory...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS directory (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name        VARCHAR(150) NOT NULL,
        email            VARCHAR(150) NOT NULL,
        job_title        VARCHAR(100) NOT NULL,

        created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        UNIQUE KEY uq_directory_email (email),
        KEY idx_directory_full_name (full_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Directorio institucional'");
    echo "   [OK] Tabla 'directory' verificada/creada.\n";

    /* ── Instalaciones donde la tabla ya existía sin el índice único ──
       Un correo repetido haría que esa persona recibiera varias copias
       del aviso de vacante, así que el correo debe ser único. */
    $pdo->exec("UPDATE directory
                SET full_name = TRIM(full_name), email = TRIM(email), job_title = TRIM(job_title)
                WHERE full_name <> TRIM(full_name)
                   OR email <> TRIM(email)
                   OR job_title <> TRIM(job_title)");

    $tieneIndice = (bool) $pdo->query("SHOW INDEX FROM directory WHERE Key_name = 'uq_directory_email'")->fetch();
    if ($tieneIndice) {
        echo "   [INFO] El índice único de 'email' ya existe.\n";
    } else {
        $dups = $pdo->query("SELECT LOWER(email) AS correo, COUNT(*) AS n
                             FROM directory GROUP BY correo HAVING n > 1")->fetchAll(PDO::FETCH_ASSOC);
        if ($dups) {
            echo "   [AVISO] No se pudo aplicar el índice único: hay correos repetidos.\n";
            foreach ($dups as $d) {
                echo "           - {$d['correo']} ({$d['n']} registros)\n";
            }
            echo "           Depura esos registros y vuelve a ejecutar esta migración.\n";
        } else {
            $pdo->exec("ALTER TABLE directory ADD UNIQUE KEY uq_directory_email (email)");
            echo "   [OK] Índice único aplicado sobre 'email'.\n";
        }
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 2. Plantilla de correo
     *    {{...}}  → escapado por interpolate()
     *    {{{...}}} → HTML crudo (bloques armados en el controlador)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n2. Plantilla de correo...\n";
    $templates = [
        [
            'tkey'    => 'directorio_vacante_aprobada',
            'name'    => '[PP] → Directorio: Nueva vacante de prácticas aprobada',
            'subject' => 'Nueva vacante de prácticas profesionales · {{empresa}}',
            'html'    => '<h2 style="color:#01643D;">Nueva vacante de prácticas profesionales</h2>
<p>Estimado(a) <strong>{{contactName}}</strong>,<br><span style="color:#64748b;">{{jobTitle}}</span></p>
<p>El área de Prácticas Profesionales ha <span style="color:#198754;font-weight:700;">APROBADO</span> una nueva solicitud de practicantes de la empresa <strong>{{empresa}}</strong>. Le compartimos los detalles para que pueda difundirla entre los estudiantes de su escuela.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:.95rem;">
<tr><td style="border:1px solid #e2e8f0;width:38%;"><strong>Empresa</strong></td><td style="border:1px solid #e2e8f0;">{{empresa}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Giro</strong></td><td style="border:1px solid #e2e8f0;">{{giro}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Perfil solicitado</strong></td><td style="border:1px solid #e2e8f0;">{{perfil}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Número de practicantes</strong></td><td style="border:1px solid #e2e8f0;">{{numPracticantes}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Modalidad</strong></td><td style="border:1px solid #e2e8f0;">{{modalidad}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Días y horario</strong></td><td style="border:1px solid #e2e8f0;">{{horario}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Apoyo económico</strong></td><td style="border:1px solid #e2e8f0;">{{apoyoEconomico}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Lugar de la práctica</strong></td><td style="border:1px solid #e2e8f0;">{{direccion}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Fecha límite para postularse</strong></td><td style="border:1px solid #e2e8f0;">{{fechaLimite}}</td></tr>
</table>
<h3 style="color:#01643D;margin-top:18px;">Actividades a realizar</h3>
<p style="background:#f8fafc;border-left:4px solid #01643D;padding:10px 14px;">{{{actividadesHtml}}}</p>
<p style="margin-top:16px;">Los estudiantes interesados pueden postularse desde la plataforma de Servicio Social y Prácticas Profesionales:
<a href="https://servicioypracticas.unimontrer.edu.mx/" target="_blank" rel="noopener noreferrer">servicioypracticas.unimontrer.edu.mx</a>.</p>
<p>Si requiere más información, comuníquese al correo
<a href="mailto:practicasprofesionales@unimontrer.edu.mx" target="_blank" rel="noopener noreferrer">practicasprofesionales@unimontrer.edu.mx</a>.</p>
<p>Saludos cordiales,<br>Universidad Montrer - Área de Prácticas Profesionales</p>
<p style="color:#64748b;font-size:.85rem;">Vacante #{{idSolicitud}} · aprobada el {{fechaAprobacion}}.</p>',
            'text_plain' => 'Estimado(a) {{contactName}} ({{jobTitle}}),

El área de Prácticas Profesionales ha APROBADO una nueva solicitud de practicantes de la empresa {{empresa}}. Le compartimos los detalles para que pueda difundirla entre los estudiantes de su escuela.

Empresa: {{empresa}}
Giro: {{giro}}
Perfil solicitado: {{perfil}}
Número de practicantes: {{numPracticantes}}
Modalidad: {{modalidad}}
Días y horario: {{horario}}
Apoyo económico: {{apoyoEconomico}}
Lugar de la práctica: {{direccion}}
Fecha límite para postularse: {{fechaLimite}}

Actividades a realizar:
{{actividades}}

Los estudiantes interesados pueden postularse desde https://servicioypracticas.unimontrer.edu.mx/

Si requiere más información, comuníquese al correo practicasprofesionales@unimontrer.edu.mx.

Saludos cordiales,
Universidad Montrer - Área de Prácticas Profesionales

Vacante #{{idSolicitud}} · aprobada el {{fechaAprobacion}}.',
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

    echo "\n¡Migración completada con éxito!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
