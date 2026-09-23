<?php
/**
 * Migración: Recordatorios automáticos de prácticas profesionales.
 * ─────────────────────────────────────────────────────────────────────────
 *  1. Crea la tabla `internship_reminders`, que lleva el control de qué
 *     recordatorio se le envió a cada practicante para no repetirlo.
 *  2. Inserta las 10 plantillas de correo (5 momentos × alumno/empresa).
 *  3. Siembra los recordatorios de una sola vez para los practicantes que ya
 *     rebasaron cada umbral, para que al activar el cron no reciban avisos
 *     retroactivos de algo que ocurrió hace semanas.
 *
 *  Momentos:
 *    start           → primera asistencia aprobada
 *    partial_135     → al cumplir 135 h (se acerca el reporte parcial de 180)
 *    final_315       → al cumplir 315 h (se acerca el reporte final de 360)
 *    overdue_partial → pasó las 180 h sin reporte parcial (diario, 12:00)
 *    overdue_final   → pasó las 360 h sin reporte final   (diario, 12:00)
 *
 *  Idempotente: puede ejecutarse múltiples veces sin errores.
 *  Ejecutar:  php database/migrations/create_internship_reminders.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Iniciando migración: recordatorios de prácticas profesionales...\n\n";

try {

    /* ═════════════════════════════════════════════════════════════════════
     * 1. Tabla internship_reminders
     *
     *  Notas sobre el DDL original:
     *   - La llave única nombraba `idInternship`, columna que no existe;
     *     la columna correcta es `idPractica`.
     *   - Se agregaron los tipos `overdue_*` y la columna `last_sent_on`:
     *     los recordatorios de atraso se repiten a diario, así que se
     *     necesita saber si ya salió el de HOY sin perder la unicidad que
     *     protege a los de una sola vez.
     * ═══════════════════════════════════════════════════════════════════ */
    echo "1. Tabla internship_reminders...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS internship_reminders (
        id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
        idStudent     INT NOT NULL,
        idPractica    INT NOT NULL,
        type          ENUM('start','partial_135','final_315','overdue_partial','overdue_final') NOT NULL,
        last_sent_on  DATE NOT NULL COMMENT 'Último día en que se envió; los overdue_* se reenvían si es anterior a hoy',
        created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY uq_reminder (idStudent, idPractica, type),
        KEY idx_reminder_type (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Control de recordatorios enviados por practicante'");
    echo "   [OK] Tabla 'internship_reminders' verificada/creada.\n";

    /* ═════════════════════════════════════════════════════════════════════
     * 2. Plantillas de correo
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n2. Plantillas de correo...\n";

    $pie = '<p>Si requiere apoyo, comuníquese al correo
<a href="mailto:practicasprofesionales@unimontrer.edu.mx" target="_blank" rel="noopener noreferrer">practicasprofesionales@unimontrer.edu.mx</a>.</p>
<p>Saludos cordiales,<br>Universidad Montrer - Área de Prácticas Profesionales</p>';

    $tablaDatos = '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:.95rem;">
<tr><td style="border:1px solid #e2e8f0;width:40%;"><strong>Practicante</strong></td><td style="border:1px solid #e2e8f0;">{{studentName}} · {{matricula}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Empresa</strong></td><td style="border:1px solid #e2e8f0;">{{empresa}}</td></tr>
<tr><td style="border:1px solid #e2e8f0;"><strong>Horas acumuladas</strong></td><td style="border:1px solid #e2e8f0;">{{horas}} de 360</td></tr>
</table>';

    $templates = [

        /* ── Inicio de prácticas ── */
        [
            'tkey'    => 'pp_recordatorio_inicio_empresa',
            'name'    => '[PP] Recordatorio → Empresa: el practicante inició',
            'subject' => '{{studentName}} inició sus prácticas profesionales',
            'html'    => '<h2 style="color:#01643D;">El practicante ha iniciado su proceso</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p>Le informamos que <strong>{{studentName}}</strong> registró su primera jornada de prácticas profesionales en <strong>{{empresa}}</strong>, y la asistencia ya fue validada.</p>
' . $tablaDatos . '
<h3 style="color:#01643D;margin-top:18px;">¿Qué sigue?</h3>
<p>Le pedimos dar seguimiento puntual a sus actividades y validar sus asistencias en la plataforma. Durante el proceso se le solicitarán dos entregas:</p>
<ul>
<li><strong>Reporte parcial</strong> al cumplir 180 horas.</li>
<li><strong>Reporte final</strong> al cumplir 360 horas.</li>
</ul>
<p>Le enviaremos un recordatorio antes de cada una.</p>
' . $pie,
            'text_plain' => 'Hola {{contactName}}, le informamos que {{studentName}} ({{matricula}}) registró su primera jornada de prácticas profesionales en {{empresa}} y la asistencia ya fue validada. Horas acumuladas: {{horas}} de 360. Le pedimos dar seguimiento a sus actividades y validar sus asistencias. Durante el proceso habrá dos entregas: el reporte parcial a las 180 horas y el reporte final a las 360 horas. Le avisaremos antes de cada una. Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],
        [
            'tkey'    => 'pp_recordatorio_inicio_alumno',
            'name'    => '[PP] Recordatorio → Alumno: iniciaste tus prácticas',
            'subject' => 'Iniciaste tus prácticas profesionales en {{empresa}}',
            'html'    => '<h2 style="color:#01643D;">¡Comenzaste tus prácticas profesionales!</h2>
<p>Hola <strong>{{studentName}}</strong>,</p>
<p>Tu primera jornada en <strong>{{empresa}}</strong> quedó registrada y validada. A partir de aquí empiezan a contar tus horas.</p>
' . $tablaDatos . '
<h3 style="color:#01643D;margin-top:18px;">Lo que debes tener presente</h3>
<ul>
<li>Registra tus asistencias el mismo día; la empresa debe validarlas.</li>
<li>Al llegar a <strong>180 horas</strong> deberás entregar tu <strong>reporte parcial</strong>.</li>
<li>Al llegar a <strong>360 horas</strong> deberás entregar tu <strong>reporte final</strong>.</li>
</ul>
<p>Si no entregas cada reporte a tiempo, el sistema bloqueará el registro de nuevas asistencias.</p>
<p>Puedes consultar tu avance en
<a href="https://servicioypracticas.unimontrer.edu.mx/" target="_blank" rel="noopener noreferrer">servicioypracticas.unimontrer.edu.mx</a>.</p>
' . $pie,
            'text_plain' => 'Hola {{studentName}}, tu primera jornada en {{empresa}} quedó registrada y validada. Horas acumuladas: {{horas}} de 360. Recuerda: registra tus asistencias el mismo día y pide a la empresa que las valide. Al llegar a 180 horas entregas tu reporte parcial y al llegar a 360 horas tu reporte final; si no los entregas a tiempo el sistema bloquea el registro de asistencias. Consulta tu avance en https://servicioypracticas.unimontrer.edu.mx/ Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],

        /* ── 135 h: se acerca el reporte parcial ── */
        [
            'tkey'    => 'pp_recordatorio_135_empresa',
            'name'    => '[PP] Recordatorio → Empresa: próximo reporte parcial (180 h)',
            'subject' => '{{studentName}} se acerca a las 180 horas',
            'html'    => '<h2 style="color:#01643D;">Se acerca el reporte parcial</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p><strong>{{studentName}}</strong> acumula <strong>{{horas}} horas</strong> de prácticas en <strong>{{empresa}}</strong>. Al llegar a <strong>180 horas</strong> deberá generarse el <strong>reporte parcial</strong>.</p>
' . $tablaDatos . '
<h3 style="color:#01643D;margin-top:18px;">¿Qué se necesita de usted?</h3>
<ul>
<li>Revisar y aprobar el reporte parcial cuando el practicante lo entregue.</li>
<li>Completar la <strong>evaluación integral de 180 horas</strong>.</li>
</ul>
<p>Mientras esas entregas no estén completas, el practicante no podrá seguir registrando asistencias.</p>
' . $pie,
            'text_plain' => 'Hola {{contactName}}, {{studentName}} ({{matricula}}) acumula {{horas}} horas de prácticas en {{empresa}}. Al llegar a 180 horas deberá generarse el reporte parcial. Se necesita que revise y apruebe ese reporte cuando el practicante lo entregue, y que complete la evaluación integral de 180 horas. Mientras esas entregas no estén completas, el practicante no podrá seguir registrando asistencias. Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],
        [
            'tkey'    => 'pp_recordatorio_135_alumno',
            'name'    => '[PP] Recordatorio → Alumno: próximo reporte parcial (180 h)',
            'subject' => 'Prepara tu reporte parcial: vas en {{horas}} horas',
            'html'    => '<h2 style="color:#01643D;">Ya casi llegas a las 180 horas</h2>
<p>Hola <strong>{{studentName}}</strong>,</p>
<p>Llevas <strong>{{horas}} horas</strong> acumuladas en <strong>{{empresa}}</strong>. Al llegar a <strong>180 horas</strong> deberás entregar tu <strong>reporte parcial</strong>.</p>
' . $tablaDatos . '
<h3 style="color:#01643D;margin-top:18px;">Ve preparándolo</h3>
<p>El reporte parcial te pedirá el <strong>objetivo</strong> de tus prácticas y las <strong>actividades que has realizado</strong>. Ir anotándolas desde ahora te ahorrará trabajo.</p>
<p><strong>Importante:</strong> al cumplir 180 horas el sistema bloquea el registro de nuevas asistencias hasta que entregues el reporte y se complete la evaluación integral.</p>
<p>Entrégalo en
<a href="https://servicioypracticas.unimontrer.edu.mx/" target="_blank" rel="noopener noreferrer">servicioypracticas.unimontrer.edu.mx</a>.</p>
' . $pie,
            'text_plain' => 'Hola {{studentName}}, llevas {{horas}} horas acumuladas en {{empresa}}. Al llegar a 180 horas deberás entregar tu reporte parcial, que te pedirá el objetivo de tus prácticas y las actividades realizadas; ve anotándolas desde ahora. Importante: al cumplir 180 horas el sistema bloquea el registro de nuevas asistencias hasta que entregues el reporte y se complete la evaluación integral. Entrégalo en https://servicioypracticas.unimontrer.edu.mx/ Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],

        /* ── 315 h: se acerca el reporte final ── */
        [
            'tkey'    => 'pp_recordatorio_315_empresa',
            'name'    => '[PP] Recordatorio → Empresa: próxima conclusión (360 h)',
            'subject' => '{{studentName}} está por concluir sus prácticas',
            'html'    => '<h2 style="color:#01643D;">Se acerca la conclusión de las prácticas</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p><strong>{{studentName}}</strong> acumula <strong>{{horas}} horas</strong> en <strong>{{empresa}}</strong>. Al llegar a <strong>360 horas</strong> concluye su proceso de prácticas profesionales y deberá generarse el <strong>reporte final</strong>.</p>
' . $tablaDatos . '
<h3 style="color:#01643D;margin-top:18px;">¿Qué se necesita de usted?</h3>
<ul>
<li>Revisar y aprobar el reporte final cuando el practicante lo entregue.</li>
<li>Completar la <strong>evaluación integral final de 360 horas</strong>.</li>
</ul>
<p>Con esas entregas se cierra formalmente el proceso y el practicante puede recibir su constancia de acreditación.</p>
' . $pie,
            'text_plain' => 'Hola {{contactName}}, {{studentName}} ({{matricula}}) acumula {{horas}} horas en {{empresa}}. Al llegar a 360 horas concluye su proceso de prácticas profesionales y deberá generarse el reporte final. Se necesita que revise y apruebe ese reporte cuando el practicante lo entregue, y que complete la evaluación integral final de 360 horas. Con esas entregas se cierra formalmente el proceso y el practicante puede recibir su constancia de acreditación. Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],
        [
            'tkey'    => 'pp_recordatorio_315_alumno',
            'name'    => '[PP] Recordatorio → Alumno: próximo reporte final (360 h)',
            'subject' => 'Prepara tu reporte final: vas en {{horas}} horas',
            'html'    => '<h2 style="color:#01643D;">Estás por concluir tus prácticas</h2>
<p>Hola <strong>{{studentName}}</strong>,</p>
<p>Llevas <strong>{{horas}} horas</strong> acumuladas en <strong>{{empresa}}</strong>. Al llegar a <strong>360 horas</strong> concluyes tus prácticas profesionales y deberás entregar tu <strong>reporte final</strong>.</p>
' . $tablaDatos . '
<h3 style="color:#01643D;margin-top:18px;">Ve preparándolo</h3>
<p>El reporte final te pedirá tu objetivo general, las actividades realizadas, los resultados obtenidos, la capacitación recibida y tu experiencia profesional y personal.</p>
<p><strong>Importante:</strong> al cumplir 360 horas el sistema bloquea el registro de nuevas asistencias hasta que entregues el reporte y se complete la evaluación integral final.</p>
<p>Entrégalo en
<a href="https://servicioypracticas.unimontrer.edu.mx/" target="_blank" rel="noopener noreferrer">servicioypracticas.unimontrer.edu.mx</a>.</p>
' . $pie,
            'text_plain' => 'Hola {{studentName}}, llevas {{horas}} horas acumuladas en {{empresa}}. Al llegar a 360 horas concluyes tus prácticas profesionales y deberás entregar tu reporte final, que te pedirá tu objetivo general, actividades realizadas, resultados obtenidos, capacitación recibida y tu experiencia profesional y personal. Importante: al cumplir 360 horas el sistema bloquea el registro de nuevas asistencias hasta que entregues el reporte y se complete la evaluación integral final. Entrégalo en https://servicioypracticas.unimontrer.edu.mx/ Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],

        /* ── Atraso: pasó las 180 h sin reporte parcial (diario) ── */
        [
            'tkey'    => 'pp_recordatorio_atraso_parcial_empresa',
            'name'    => '[PP] Recordatorio diario → Empresa: falta el reporte parcial',
            'subject' => 'Pendiente: reporte parcial de {{studentName}}',
            'html'    => '<h2 style="color:#b45309;">Falta el reporte parcial</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p><strong>{{studentName}}</strong> superó las <strong>180 horas</strong> de prácticas en <strong>{{empresa}}</strong> y todavía <strong>no se ha entregado el reporte parcial</strong>.</p>
' . $tablaDatos . '
<p style="background:#fffbeb;border-left:4px solid #f59e0b;padding:10px 14px;">
Mientras el reporte no se entregue, el practicante <strong>no puede registrar nuevas asistencias</strong> y sus horas dejan de avanzar.
</p>
<p>Le pedimos apoyarnos recordándole al practicante que lo entregue, y tener listas la revisión del reporte y la evaluación integral de 180 horas.</p>
<p>Este aviso se repetirá a diario hasta que el reporte sea entregado.</p>
' . $pie,
            'text_plain' => 'Hola {{contactName}}, {{studentName}} ({{matricula}}) superó las 180 horas de prácticas en {{empresa}} y todavía no se ha entregado el reporte parcial. Horas acumuladas: {{horas}} de 360. Mientras el reporte no se entregue, el practicante no puede registrar nuevas asistencias y sus horas dejan de avanzar. Le pedimos recordarle al practicante que lo entregue, y tener listas la revisión del reporte y la evaluación integral de 180 horas. Este aviso se repetirá a diario hasta que el reporte sea entregado. Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],
        [
            'tkey'    => 'pp_recordatorio_atraso_parcial_alumno',
            'name'    => '[PP] Recordatorio diario → Alumno: falta tu reporte parcial',
            'subject' => 'Pendiente: tu reporte parcial de 180 horas',
            'html'    => '<h2 style="color:#b45309;">Falta tu reporte parcial</h2>
<p>Hola <strong>{{studentName}}</strong>,</p>
<p>Ya superaste las <strong>180 horas</strong> en <strong>{{empresa}}</strong> y aún <strong>no has entregado tu reporte parcial</strong>.</p>
' . $tablaDatos . '
<p style="background:#fffbeb;border-left:4px solid #f59e0b;padding:10px 14px;">
<strong>Tu registro de asistencias está bloqueado.</strong> Las horas que trabajes no se contarán hasta que entregues el reporte.
</p>
<p>Entrégalo cuanto antes en
<a href="https://servicioypracticas.unimontrer.edu.mx/" target="_blank" rel="noopener noreferrer">servicioypracticas.unimontrer.edu.mx</a>.</p>
<p>Este recordatorio se repetirá a diario hasta que lo entregues.</p>
' . $pie,
            'text_plain' => 'Hola {{studentName}}, ya superaste las 180 horas en {{empresa}} y aún no has entregado tu reporte parcial. Horas acumuladas: {{horas}} de 360. Tu registro de asistencias está bloqueado: las horas que trabajes no se contarán hasta que entregues el reporte. Entrégalo cuanto antes en https://servicioypracticas.unimontrer.edu.mx/ Este recordatorio se repetirá a diario hasta que lo entregues. Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],

        /* ── Atraso: pasó las 360 h sin reporte final (diario) ── */
        [
            'tkey'    => 'pp_recordatorio_atraso_final_empresa',
            'name'    => '[PP] Recordatorio diario → Empresa: falta el reporte final',
            'subject' => 'Pendiente: reporte final de {{studentName}}',
            'html'    => '<h2 style="color:#b45309;">Falta el reporte final</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p><strong>{{studentName}}</strong> superó las <strong>360 horas</strong> de prácticas en <strong>{{empresa}}</strong> y todavía <strong>no se ha entregado el reporte final</strong>.</p>
' . $tablaDatos . '
<p style="background:#fffbeb;border-left:4px solid #f59e0b;padding:10px 14px;">
El proceso <strong>no puede cerrarse</strong> hasta que se entregue el reporte, y el practicante no puede recibir su constancia de acreditación.
</p>
<p>Le pedimos apoyarnos recordándole al practicante que lo entregue, y tener listas la revisión del reporte y la evaluación integral final de 360 horas.</p>
<p>Este aviso se repetirá a diario hasta que el reporte sea entregado.</p>
' . $pie,
            'text_plain' => 'Hola {{contactName}}, {{studentName}} ({{matricula}}) superó las 360 horas de prácticas en {{empresa}} y todavía no se ha entregado el reporte final. Horas acumuladas: {{horas}} de 360. El proceso no puede cerrarse hasta que se entregue el reporte, y el practicante no puede recibir su constancia de acreditación. Le pedimos recordarle al practicante que lo entregue, y tener listas la revisión del reporte y la evaluación integral final de 360 horas. Este aviso se repetirá a diario hasta que el reporte sea entregado. Dudas: practicasprofesionales@unimontrer.edu.mx',
        ],
        [
            'tkey'    => 'pp_recordatorio_atraso_final_alumno',
            'name'    => '[PP] Recordatorio diario → Alumno: falta tu reporte final',
            'subject' => 'Pendiente: tu reporte final de 360 horas',
            'html'    => '<h2 style="color:#b45309;">Falta tu reporte final</h2>
<p>Hola <strong>{{studentName}}</strong>,</p>
<p>Ya cumpliste las <strong>360 horas</strong> en <strong>{{empresa}}</strong> y aún <strong>no has entregado tu reporte final</strong>.</p>
' . $tablaDatos . '
<p style="background:#fffbeb;border-left:4px solid #f59e0b;padding:10px 14px;">
<strong>Tus prácticas no pueden cerrarse</strong> hasta que lo entregues, y sin el cierre no se puede emitir tu constancia de acreditación.
</p>
<p>Entrégalo cuanto antes en
<a href="https://servicioypracticas.unimontrer.edu.mx/" target="_blank" rel="noopener noreferrer">servicioypracticas.unimontrer.edu.mx</a>.</p>
<p>Este recordatorio se repetirá a diario hasta que lo entregues.</p>
' . $pie,
            'text_plain' => 'Hola {{studentName}}, ya cumpliste las 360 horas en {{empresa}} y aún no has entregado tu reporte final. Horas acumuladas: {{horas}} de 360. Tus prácticas no pueden cerrarse hasta que lo entregues, y sin el cierre no se puede emitir tu constancia de acreditación. Entrégalo cuanto antes en https://servicioypracticas.unimontrer.edu.mx/ Este recordatorio se repetirá a diario hasta que lo entregues. Dudas: practicasprofesionales@unimontrer.edu.mx',
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

    /* ═════════════════════════════════════════════════════════════════════
     * 3. Siembra para practicantes que ya rebasaron los umbrales
     *
     *  Sin esto, la primera corrida del cron mandaría el correo de "iniciaste
     *  tus prácticas" a alguien que lleva 200 horas. Solo se siembran los
     *  recordatorios de una sola vez; los `overdue_*` sí deben dispararse
     *  para quienes realmente traen el reporte atrasado.
     * ═══════════════════════════════════════════════════════════════════ */
    echo "\n3. Siembra de umbrales ya rebasados...\n";

    $horasSql = "COALESCE((
        SELECT SUM(COALESCE(a.horas_validadas,
                            TIMESTAMPDIFF(MINUTE, a.hora_entrada, a.hora_salida) / 60))
          FROM asistencias_practicas a
         WHERE a.idStudent = sp.idStudent AND a.idPractica = sp.idPractica
           AND a.status = 'aprobado'
    ), 0)";

    $umbrales = [
        'start'       => 0.01,
        'partial_135' => 135,
        'final_315'   => 315,
    ];

    foreach ($umbrales as $type => $minimo) {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO internship_reminders (idStudent, idPractica, type, last_sent_on)
             SELECT sp.idStudent, sp.idPractica, :type, CURDATE()
               FROM students_in_practices sp
              WHERE sp.isAcepted = 1
                AND $horasSql >= :minimo"
        );
        $stmt->execute([':type' => $type, ':minimo' => $minimo]);
        echo "   [OK] $type: {$stmt->rowCount()} practicante(s) marcados como ya notificados.\n";
    }

    echo "\n¡Migración completada con éxito!\n";
    echo "\nSiguiente paso: programar el cron diario a las 12:00\n";
    echo "  C:\\xampp\\php\\php.exe C:\\xampp\\htdocs\\servicioypracticas.unimontrer.edu.mx\\controller\\cron\\cron_recordatorios_practicas.php\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
