<?php

/**
 * ─────────────────────────────────────────────────────────────────────────
 *  FASE 6 · Nuevo flujo de postulación (prepostulación → entrevista → final)
 * ─────────────────────────────────────────────────────────────────────────
 *  Migración idempotente (re-ejecutable). Solo cambios de ESQUEMA.
 *  Ver docs/analisis_nuevo_flujo_postulacion.md
 *
 *  Ejecutar:  php database/update_fase6_postulacion.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db = $_ENV['DB_DATABASE'];

/* ── Helpers idempotentes ─────────────────────────────────────────────── */
$tableExists = function (string $t) use ($pdo): bool {
    return (bool) $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
};
$columnExists = function (string $t, string $c) use ($pdo): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$t` LIKE ?");
    $stmt->execute([$c]);
    return (bool) $stmt->fetch();
};
$indexExists = function (string $t, string $idx) use ($pdo): bool {
    $stmt = $pdo->prepare("SHOW INDEX FROM `$t` WHERE Key_name = ?");
    $stmt->execute([$idx]);
    return (bool) $stmt->fetch();
};

echo "Iniciando actualización Fase 6 (nuevo flujo de postulación)...\n";
echo "Base de datos: $db\n\n";

try {

    /* ═════════════════════════════════════════════════════════════════════
     * 1. Tabla prepostulaciones_practicas (respuestas del formulario)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "1. Tabla prepostulaciones_practicas...\n";
    if (!$tableExists('prepostulaciones_practicas')) {
        $pdo->exec("
            CREATE TABLE prepostulaciones_practicas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idStudent INT NOT NULL,
                idPractica INT NOT NULL,
                licenciatura VARCHAR(150) NOT NULL COMMENT 'Campo 1: licenciatura que cursa',
                disponibilidad_horario VARCHAR(30) NOT NULL COMMENT 'Matutino|Vespertino|Tiempo completo|Flexible',
                modalidad VARCHAR(20) NOT NULL COMMENT 'Presencial|Híbrida|Remota',
                nivel_office VARCHAR(20) NOT NULL COMMENT 'Básico|Intermedio|Avanzado',
                herramientas TEXT DEFAULT NULL COMMENT 'Lista de herramientas seleccionadas (separadas por coma)',
                herramientas_otro VARCHAR(150) DEFAULT NULL COMMENT 'Texto libre de la opción Otro',
                nivel_ingles VARCHAR(20) NOT NULL COMMENT 'Básico|Intermedio|Avanzado|No aplica',
                equipo_remoto VARCHAR(15) NOT NULL COMMENT 'Sí|No|No aplica',
                disponibilidad_inicio VARCHAR(30) NOT NULL COMMENT 'Inmediata|En una semana|En dos semanas|En un mes',
                area_interes VARCHAR(80) NOT NULL COMMENT 'Área o tipo de actividades de interés',
                acepta_capacitacion VARCHAR(5) NOT NULL COMMENT 'Sí|No',
                objetivo_practicas VARCHAR(120) NOT NULL COMMENT 'Principal objetivo',
                modalidad_entrevista_pref VARCHAR(15) NOT NULL COMMENT 'Virtual|Presencial',
                horario_propuesto VARCHAR(255) DEFAULT NULL COMMENT 'Horario propuesto (texto libre)',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_prepost_student_practica (idStudent, idPractica),
                KEY idx_prepost_student (idStudent),
                KEY idx_prepost_practica (idPractica)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "   [OK] Tabla prepostulaciones_practicas creada.\n";
    } else {
        echo "   [INFO] prepostulaciones_practicas ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 2. students_in_practices.estado + backfill + UNIQUE(idPractica,idStudent)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "2. Columna estado en students_in_practices...\n";
    if (!$columnExists('students_in_practices', 'estado')) {
        $pdo->exec("
            ALTER TABLE students_in_practices
            ADD COLUMN estado VARCHAR(30) NOT NULL DEFAULT 'PREPOSTULADO'
            COMMENT 'PREPOSTULADO|RECHAZADO_PREPOSTULACION|ENTREVISTA_PROGRAMADA|ENTREVISTA_CERRADA|ACEPTADO_FINAL|RECHAZADO_FINAL'
            AFTER isRejected
        ");
        echo "   [OK] Columna estado añadida.\n";

        // Backfill desde el modelo antiguo (isAcepted 0/1/2)
        $pdo->exec("UPDATE students_in_practices SET estado = 'ACEPTADO_FINAL'          WHERE isAcepted = 1");
        $pdo->exec("UPDATE students_in_practices SET estado = 'RECHAZADO_FINAL'         WHERE isAcepted = 2");
        $pdo->exec("UPDATE students_in_practices SET estado = 'PREPOSTULADO'            WHERE isAcepted = 0");
        echo "   [OK] Backfill de estado aplicado (0→PREPOSTULADO, 1→ACEPTADO_FINAL, 2→RECHAZADO_FINAL).\n";
    } else {
        echo "   [INFO] La columna estado ya existe.\n";
    }

    echo "   → Índice UNIQUE(idPractica, idStudent)...\n";
    if (!$indexExists('students_in_practices', 'uq_sip_practica_student')) {
        // Verificar que no haya duplicados que impidan el UNIQUE
        $dups = $pdo->query("
            SELECT COUNT(*) FROM (
                SELECT idPractica, idStudent FROM students_in_practices
                GROUP BY idPractica, idStudent HAVING COUNT(*) > 1
            ) d
        ")->fetchColumn();
        if ((int) $dups === 0) {
            $pdo->exec("ALTER TABLE students_in_practices ADD UNIQUE KEY uq_sip_practica_student (idPractica, idStudent)");
            echo "      [OK] UNIQUE añadido.\n";
        } else {
            echo "      [AVISO] Hay $dups pares duplicados (idPractica,idStudent); NO se añadió el UNIQUE. Deduplicar antes.\n";
        }
    } else {
        echo "      [INFO] El UNIQUE ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 3. Tabla entrevistas_programadas (agenda de la entrevista)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "3. Tabla entrevistas_programadas...\n";
    if (!$tableExists('entrevistas_programadas')) {
        $pdo->exec("
            CREATE TABLE entrevistas_programadas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idStudent INT NOT NULL,
                idPractica INT NOT NULL,
                fecha DATE NOT NULL,
                hora TIME NOT NULL,
                modalidad VARCHAR(20) NOT NULL COMMENT 'Presencial|Virtual',
                url_sesion VARCHAR(500) DEFAULT NULL COMMENT 'Link Meet/Teams si es Virtual',
                direccion VARCHAR(255) DEFAULT NULL COMMENT 'Dirección de la empresa si es Presencial',
                retro_solicitada TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = el cron ya pidió retroalimentación',
                created_by INT DEFAULT NULL COMMENT 'ID del organismo/admin que programó',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_entprog_student_practica (idStudent, idPractica),
                KEY idx_entprog_student (idStudent),
                KEY idx_entprog_practica (idPractica),
                KEY idx_entprog_retro (retro_solicitada, fecha, hora)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "   [OK] Tabla entrevistas_programadas creada.\n";
    } else {
        echo "   [INFO] entrevistas_programadas ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 4. Deduplicar entrevistas_practicas + UNIQUE(idStudent,idPractica)
     *    (para que funcione el ON DUPLICATE KEY UPDATE de mdlEvaluarEntrevista)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "4. entrevistas_practicas: dedup + UNIQUE(idStudent,idPractica)...\n";
    if (!$indexExists('entrevistas_practicas', 'uq_entprac_student_practica')) {
        // Borrar duplicados conservando el id más alto (evaluación más reciente)
        $del = $pdo->exec("
            DELETE ep FROM entrevistas_practicas ep
            JOIN entrevistas_practicas ep2
              ON ep.idStudent = ep2.idStudent
             AND ep.idPractica = ep2.idPractica
             AND ep.id < ep2.id
        ");
        echo "   [OK] Duplicados eliminados: " . (int) $del . ".\n";
        $pdo->exec("ALTER TABLE entrevistas_practicas ADD UNIQUE KEY uq_entprac_student_practica (idStudent, idPractica)");
        echo "   [OK] UNIQUE añadido a entrevistas_practicas.\n";
    } else {
        echo "   [INFO] El UNIQUE de entrevistas_practicas ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 5. Tabla alumno_vacante_bloqueo (impedir re-postulación a vacante rechazada)
     * ═══════════════════════════════════════════════════════════════════ */
    echo "5. Tabla alumno_vacante_bloqueo...\n";
    if (!$tableExists('alumno_vacante_bloqueo')) {
        $pdo->exec("
            CREATE TABLE alumno_vacante_bloqueo (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idStudent INT NOT NULL,
                idPractica INT NOT NULL,
                motivo TEXT DEFAULT NULL,
                origen VARCHAR(20) NOT NULL DEFAULT 'final' COMMENT 'prepostulacion|final',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_bloqueo_student_practica (idStudent, idPractica),
                KEY idx_bloqueo_student (idStudent),
                KEY idx_bloqueo_practica (idPractica)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "   [OK] Tabla alumno_vacante_bloqueo creada.\n";
    } else {
        echo "   [INFO] alumno_vacante_bloqueo ya existe.\n";
    }

    /* ═════════════════════════════════════════════════════════════════════
     * 6. cartas_practicas_profesionales: pdf_path + idPractica
     * ═══════════════════════════════════════════════════════════════════ */
    echo "6. Columnas nuevas en cartas_practicas_profesionales...\n";
    if (!$columnExists('cartas_practicas_profesionales', 'pdf_path')) {
        $pdo->exec("
            ALTER TABLE cartas_practicas_profesionales
            ADD COLUMN pdf_path VARCHAR(255) DEFAULT NULL COMMENT 'Ruta del PDF persistido para adjuntar en correos'
        ");
        echo "   [OK] Columna pdf_path añadida.\n";
    } else {
        echo "   [INFO] pdf_path ya existe.\n";
    }
    if (!$columnExists('cartas_practicas_profesionales', 'idPractica')) {
        $pdo->exec("
            ALTER TABLE cartas_practicas_profesionales
            ADD COLUMN idPractica INT DEFAULT NULL COMMENT 'Vacante a la que corresponde la carta'
        ");
        echo "   [OK] Columna idPractica añadida.\n";
    } else {
        echo "   [INFO] idPractica ya existe.\n";
    }

    echo "\n¡Actualización Fase 6 completada con éxito!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
