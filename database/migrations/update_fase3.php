<?php
require_once __DIR__ . '/../../model/conection.php';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migración Fase 3...\n";

    // 1. Crear tabla strikes_practicas
    $pdo->exec("CREATE TABLE IF NOT EXISTS `strikes_practicas` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `idAsistencia` INT NOT NULL COMMENT 'FK -> asistencias_practicas.idAsistencia',
      `idStudent` INT NOT NULL COMMENT 'FK -> students_practicas.id',
      `idOrganismo` INT UNSIGNED NOT NULL COMMENT 'FK -> organismos_externos.id',
      `idPractica` INT NOT NULL COMMENT 'FK -> solicitudes_practicantes.id',
      `horas_reportadas` DECIMAL(5,2) NOT NULL COMMENT 'Horas reales registradas por el alumno',
      `horas_validadas` DECIMAL(5,2) NOT NULL DEFAULT 4.00 COMMENT 'Máximo 4.00',
      `horas_excedente` DECIMAL(5,2) NOT NULL COMMENT 'Horas por encima de 4',
      `tipo_strike` ENUM('exceso_horas') NOT NULL DEFAULT 'exceso_horas',
      `consecuencia_alumno` ENUM('advertencia','baja_practicas') DEFAULT NULL,
      `consecuencia_empresa` ENUM('advertencia','bloqueo_solicitudes') DEFAULT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_strike_organismo` (`idOrganismo`),
      KEY `idx_strike_student` (`idStudent`),
      KEY `idx_strike_asistencia` (`idAsistencia`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "1. Tabla strikes_practicas asegurada.\n";

    // 2. Crear tabla historial_bloqueos_organismos
    $pdo->exec("CREATE TABLE IF NOT EXISTS `historial_bloqueos_organismos` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `idOrganismo` INT UNSIGNED NOT NULL,
      `accion` ENUM('bloqueo','desbloqueo') NOT NULL,
      `motivo` TEXT NOT NULL,
      `admin_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL=automático',
      `admin_name` VARCHAR(100) DEFAULT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_hbo_organismo` (`idOrganismo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "2. Tabla historial_bloqueos_organismos asegurada.\n";

    // 3. ALTER organismos_externos
    $cols = $pdo->query("SHOW COLUMNS FROM organismos_externos")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('strikes_count', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos ADD COLUMN strikes_count INT UNSIGNED NOT NULL DEFAULT 0");
    }
    if (!in_array('solicitudes_bloqueadas', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos ADD COLUMN solicitudes_bloqueadas TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!in_array('motivo_bloqueo', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos ADD COLUMN motivo_bloqueo TEXT DEFAULT NULL");
    }
    if (!in_array('fecha_bloqueo', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos ADD COLUMN fecha_bloqueo DATETIME DEFAULT NULL");
    }
    if (!in_array('bloqueado_por', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos ADD COLUMN bloqueado_por INT UNSIGNED DEFAULT NULL");
    }
    echo "3. Columnas de strikes/bloqueo en organismos_externos aseguradas.\n";

    // 4. ALTER asistencias_practicas
    $colsAsist = $pdo->query("SHOW COLUMNS FROM asistencias_practicas")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('horas_validadas', $colsAsist)) {
        $pdo->exec("ALTER TABLE asistencias_practicas ADD COLUMN horas_validadas DECIMAL(5,2) DEFAULT NULL");
    }
    if (!in_array('tiene_strike', $colsAsist)) {
        $pdo->exec("ALTER TABLE asistencias_practicas ADD COLUMN tiene_strike TINYINT(1) NOT NULL DEFAULT 0");
    }
    echo "4. Columnas de validación en asistencias_practicas aseguradas.\n";

    // 5. ALTER students_practicas
    $colsStud = $pdo->query("SHOW COLUMNS FROM students_practicas")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('dado_de_baja_por_strike', $colsStud)) {
        $pdo->exec("ALTER TABLE students_practicas ADD COLUMN dado_de_baja_por_strike TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!in_array('fecha_baja_strike', $colsStud)) {
        $pdo->exec("ALTER TABLE students_practicas ADD COLUMN fecha_baja_strike DATETIME DEFAULT NULL");
    }
    echo "5. Columnas de baja por strike en students_practicas aseguradas.\n";

    // 6. Insertar plantillas de correo
    $templates = [
        'pp_strike_advertencia_alumno' => [
            'name' => 'Advertencia de incumplimiento de horario (Alumno)',
            'subject' => 'Advertencia: Incumplimiento de horario de prácticas',
            'html' => '<p>Estimado(a) {{studentName}},</p><p>Te informamos que el día {{fecha}} has registrado una asistencia de {{horasReales}} horas. El máximo permitido por día es de 4 horas.</p><p>Esto ha generado un <strong>1er Strike</strong> (incumplimiento). Este correo sirve como advertencia oficial. Si acumulas un segundo strike, serás dado de baja automáticamente de tus prácticas profesionales.</p><p>Por favor, respeta los lineamientos del programa.</p>'
        ],
        'pp_strike_baja_alumno' => [
            'name' => 'Baja de prácticas por incumplimiento (Alumno)',
            'subject' => 'Baja Oficial de Prácticas Profesionales por Incumplimiento',
            'html' => '<p>Estimado(a) {{studentName}},</p><p>Te informamos que el día {{fecha}} has vuelto a exceder el límite de horas permitidas, registrando {{horasReales}} horas.</p><p>Al ser tu <strong>2do Strike</strong>, el sistema te ha dado de <strong>BAJA OFICIALMENTE</strong> de tus prácticas profesionales con la empresa actual, de acuerdo con el reglamento vigente.</p><p>Deberás reiniciar el proceso de prácticas con otro organismo receptor.</p>'
        ],
        'pp_strike_organismo' => [
            'name' => 'Notificación de strike generado (Organismo)',
            'subject' => 'Notificación de incumplimiento de horario de practicante',
            'html' => '<p>Estimado(a) {{orgName}},</p><p>Le informamos que el practicante {{studentName}} ha registrado {{horasReales}} horas el día {{fecha}}, excediendo el límite de 4 horas diarias permitido por la Universidad.</p><p>Esto genera un "strike" en el historial de su empresa. Actualmente su empresa tiene <strong>{{strikesCount}} strike(s)</strong> acumulados de un máximo de 2.</p><p>Le recordamos que si su empresa acumula 2 strikes, el sistema bloqueará automáticamente la posibilidad de solicitar nuevos practicantes en el futuro.</p>'
        ],
        'pp_strike_admin' => [
            'name' => 'Notificación de strike (Admin)',
            'subject' => 'Alerta de sistema: Strike generado en Prácticas Profesionales',
            'html' => '<p>Se ha generado un strike en el sistema de Prácticas Profesionales.</p><ul><li>Alumno: {{studentName}}</li><li>Empresa: {{orgName}}</li><li>Fecha de asistencia: {{fecha}}</li><li>Horas registradas: {{horasReales}}</li><li>Strikes acumulados de la empresa: {{strikesCount}}</li></ul><p>Por favor, revise el panel de administración si desea ver más detalles.</p>'
        ],
        'pp_organismo_bloqueado_auto' => [
            'name' => 'Bloqueo automático de solicitudes (Organismo)',
            'subject' => 'Bloqueo automático de nuevas solicitudes de practicantes',
            'html' => '<p>Estimado(a) {{orgName}},</p><p>Le informamos que debido a que ha acumulado 2 strikes por incumplimiento en el límite de horas diarias de sus practicantes, el sistema ha <strong>bloqueado automáticamente</strong> la creación de nuevas solicitudes de practicantes para su empresa.</p><p>Motivo: {{motivo}}</p><p>Nota: Los practicantes que actualmente se encuentran en su empresa no se ven afectados por este bloqueo y podrán continuar hasta finalizar su periodo.</p><p>Si desea apelar esta decisión, por favor póngase en contacto con el administrador de Prácticas Profesionales de la Universidad.</p>'
        ],
        'pp_organismo_bloqueado_admin_notif' => [
            'name' => 'Notificación de bloqueo automático de empresa (Admin)',
            'subject' => 'Alerta: Empresa bloqueada automáticamente',
            'html' => '<p>El sistema ha bloqueado automáticamente a la empresa <strong>{{orgName}}</strong> debido a la acumulación de 2 strikes.</p><p>El practicante que generó el segundo strike fue: {{studentName}}.</p><p>La empresa ya no podrá solicitar nuevos practicantes a menos que un administrador la desbloquee manualmente.</p>'
        ]
    ];

    $stmtCheck = $pdo->prepare("SELECT id FROM email_templates WHERE tkey = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO email_templates (tkey, name, subject, html, text_plain) VALUES (?, ?, ?, ?, ?)");
    $stmtUpdate = $pdo->prepare("UPDATE email_templates SET name = ?, subject = ?, html = ?, text_plain = ? WHERE tkey = ?");

    foreach ($templates as $key => $data) {
        $stmtCheck->execute([$key]);
        if ($stmtCheck->rowCount() > 0) {
            $stmtUpdate->execute([$data['name'], $data['subject'], $data['html'], $data['html'], $key]);
            echo "   - Template '$key' actualizado.\n";
        } else {
            $stmtInsert->execute([$key, $data['name'], $data['subject'], $data['html'], $data['html']]);
            echo "   - Template '$key' insertado.\n";
        }
    }
    echo "6. Plantillas de correo aseguradas.\n";

    echo "Migración Fase 3 completada con éxito.\n";

} catch (PDOException $e) {
    echo "Error PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
