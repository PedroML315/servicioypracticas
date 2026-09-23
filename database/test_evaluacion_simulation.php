<?php
/**
 * Script de simulación para pruebas del módulo de Evaluación Integral.
 * Actualizado a la FASE 6 (nuevo flujo de postulación):
 *   prepostulación → entrevista programada → entrevista cerrada → aceptado final.
 *
 * La fase 1 ya no inserta isAcepted=1 "a mano": recorre el flujo nuevo con los
 * métodos del MODELO (PracticasModel), por lo que students_in_practices queda
 * con estado = ACEPTADO_FINAL e isAcepted = 1 sincronizados, y se llenan
 * prepostulaciones_practicas, entrevistas_programadas y entrevistas_practicas.
 * NOTA: al usar solo el modelo (no los controladores) NO se encola ningún correo.
 *
 * Modo de uso (desde CLI o navegador):
 *
 * Fase 0 (Reiniciar de cero al alumno, incluidas las tablas del flujo nuevo):
 * php test_evaluacion_simulation.php fase=0
 *
 * Fase 1 (Flujo nuevo completo hasta ACEPTADO_FINAL + 180h):
 * php test_evaluacion_simulation.php fase=1
 *
 * Fase 2 (Simular 360h):
 * php test_evaluacion_simulation.php fase=2
 *
 * ── Fases para probar los RECORDATORIOS AUTOMÁTICOS ──
 *
 * Fase 3 (Dejar al alumno en N horas exactas, en silencio):
 * php test_evaluacion_simulation.php fase=3 horas=132
 *   Coloca las horas aprobadas en N y marca como ya enviados los
 *   recordatorios que a esas alturas le habrían llegado, igual que en una
 *   práctica real. No encola ningún correo.
 *
 * Fase 4 (Aprobar una asistencia POR EL CONTROLADOR → sí manda correos):
 * php test_evaluacion_simulation.php fase=4
 *   Registra una asistencia de 4 h y la aprueba con
 *   PracticasController::ctrAprobarAsistencia(), que es el camino real de la
 *   interfaz. Si esas 4 h cruzan un umbral (inicio, 135 h o 315 h), se
 *   encolan los 2 recordatorios correspondientes.
 *
 * Combinaciones útiles:
 *   fase=0            → fase=4   probar el correo de INICIO
 *   fase=3 horas=132  → fase=4   probar el de 135 h
 *   fase=3 horas=312  → fase=4   probar el de 315 h
 *   fase=1            → cron     probar el diario de atraso (180 h sin reporte)
 */

require_once __DIR__ . '/../model/conection.php';
require_once __DIR__ . '/../model/PracticasModel.php';

/** Lee un parámetro tanto de la URL como de los argumentos de CLI. */
function paramSimulacion(string $nombre, ?string $default = null): ?string
{
    if (isset($_GET[$nombre])) {
        return (string) $_GET[$nombre];
    }
    foreach ($GLOBALS['argv'] ?? [] as $arg) {
        if (strpos($arg, "$nombre=") === 0) {
            return substr($arg, strlen($nombre) + 1);
        }
    }
    return $default;
}

$fase = paramSimulacion('fase');

if ($fase === null || !in_array($fase, ['0', '1', '2', '3', '4'], true)) {
    die("Debes especificar fase=0, fase=1, fase=2, fase=3 o fase=4\n");
}

$idStudent = 31; // Alumno de prueba fijo

echo "<h2>Simulación Fase $fase para Alumno ID $idStudent</h2>\n";

/**
 * Limpia TODO el rastro de prácticas del alumno, incluidas las tablas del
 * flujo nuevo (Fase 6) y los PDFs de cartas persistidos en disco.
 */
function limpiarAlumno(PDO $conn, int $idStudent): void
{
    // Borrar PDFs de cartas persistidas antes de eliminar sus filas
    $stmt = $conn->prepare("SELECT pdf_path FROM cartas_practicas_profesionales WHERE student_id = ?");
    $stmt->execute([$idStudent]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pdf) {
        if ($pdf && is_file($pdf)) {
            @unlink($pdf);
        }
    }

    $porStudent = [
        'asistencias_practicas'      => 'idStudent',
        'reporte_parcial_practicas'  => 'idStudent',
        'reporte_final_practicas'    => 'idStudent',
        'evaluacion_integral_practicas' => 'idStudent',
        // Tablas del flujo nuevo (Fase 6)
        'prepostulaciones_practicas' => 'idStudent',
        'entrevistas_programadas'    => 'idStudent',
        'entrevistas_practicas'      => 'idStudent',
        'alumno_vacante_bloqueo'     => 'idStudent',
        // Sin esto, los recordatorios automáticos (inicio, 135 h, 315 h) no
        // vuelven a dispararse: su tabla de control los da por enviados.
        'internship_reminders'       => 'idStudent',
        'students_in_practices'      => 'idStudent',
        'cartas_practicas_profesionales' => 'student_id',
    ];

    // Respuestas de la evaluación integral (dependen de la evaluación padre)
    $conn->prepare(
        "DELETE FROM evaluacion_integral_respuestas
          WHERE idEvaluacion IN (SELECT id FROM evaluacion_integral_practicas WHERE idStudent = ?)"
    )->execute([$idStudent]);

    foreach ($porStudent as $tabla => $col) {
        $conn->prepare("DELETE FROM `$tabla` WHERE `$col` = ?")->execute([$idStudent]);
    }
}

try {
    $conn = Conexion::conectar();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar datos del alumno
    $stmt = $conn->prepare("SELECT id, nombre_completo FROM students_practicas WHERE id = ?");
    $stmt->execute([$idStudent]);
    $student = $stmt->fetch();
    if (!$student) die("Error: Alumno $idStudent no existe.\n");
    echo "Alumno: {$student['nombre_completo']}<br>\n";

    if ($fase == '0') {
        // Reiniciar de cero al alumno: borrar todo su historial de prácticas
        // (asistencias, reportes, evaluaciones, flujo de postulación y cartas)
        // para que únicamente pueda seleccionar solicitudes disponibles.
        echo "Reiniciando de cero al alumno (asistencias, reportes, evaluaciones, prepostulaciones, entrevistas, bloqueos, cartas y prácticas)...<br>\n";
        limpiarAlumno($conn, $idStudent);

        echo "<h3 style='color:green;'>Fase 0 Completada.</h3>";
        echo "<ul>
                <li>El alumno $idStudent quedó reiniciado de cero, sin práctica ni postulación activa.</li>
                <li>Se limpiaron también las tablas del flujo nuevo (prepostulaciones, entrevistas, bloqueos de vacante) y las cartas (incluidos sus PDFs).</li>
                <li>Entra como el alumno y verifica que puede prepostularse a las vacantes disponibles con el wizard.</li>
              </ul>";

    } else if ($fase == '1') {
        // Limpiar datos previos de este alumno para empezar limpio
        echo "Limpiando historial previo del alumno...<br>\n";
        limpiarAlumno($conn, $idStudent);

        // Elegir una vacante activa, aprobada y con cupo disponible.
        // Se prefiere la empresa de pruebas (organismo 50 · MONT PR,
        // pedro.m.l@outlook.es) para poder revisar el resultado desde su panel.
        $stmt = $conn->query(
            "SELECT sp.id, sp.organismo_externo_id
               FROM solicitudes_practicantes sp
              WHERE sp.aceptado = 1 AND sp.activo = 1
                AND (SELECT COUNT(*) FROM students_in_practices x
                      WHERE x.idPractica = sp.id AND x.isAcepted = 1) < sp.num_practicantes
              ORDER BY (sp.organismo_externo_id = 50) DESC, sp.id DESC
              LIMIT 1"
        );
        $rowPractica = $stmt->fetch();
        if (!$rowPractica) die("Error: No hay vacantes activas con cupo en solicitudes_practicantes.\n");
        $idPractica = (int) $rowPractica['id'];
        $idOrganismo = (int) ($rowPractica['organismo_externo_id'] ?: 0);

        // Evaluador simulado para entrevistas_practicas.evaluado_por
        $_SESSION['idOrganismo'] = $idOrganismo;

        /* ── FLUJO NUEVO (Fase 6) por la capa de modelo — sin correos ── */

        // 1) Prepostulación (formulario obligatorio) → estado PREPOSTULADO
        echo "1/4 Prepostulando al alumno a la vacante ID $idPractica (organismo $idOrganismo)...<br>\n";
        $prepost = [
            'licenciatura'              => 'Simulación — Licenciatura de prueba',
            'disponibilidad_horario'    => 'Matutino',
            'modalidad'                 => 'Presencial',
            'nivel_office'              => 'Intermedio',
            'herramientas'              => 'Microsoft Excel,Microsoft Word',
            'herramientas_otro'         => null,
            'nivel_ingles'              => 'Intermedio',
            'equipo_remoto'             => 'Sí',
            'disponibilidad_inicio'     => 'Inmediata',
            'area_interes'              => 'Administrativas',
            'acepta_capacitacion'       => 'Sí',
            'objetivo_practicas'        => 'Adquirir experiencia profesional',
            'modalidad_entrevista_pref' => 'Presencial',
            'horario_propuesto'         => 'Lun-Vie 08:00-12:00 (simulación)',
        ];
        $r = PracticasModel::mdlApplyForPractice($idPractica, $idStudent, $prepost);
        if (($r['success'] ?? false) !== true) die("Error en prepostulación: " . ($r['message'] ?? '?') . "\n");

        // 2) Programar entrevista → estado ENTREVISTA_PROGRAMADA
        echo "2/4 Programando entrevista (simulada, ya ocurrida)...<br>\n";
        $r = PracticasModel::mdlProgramarEntrevista($idPractica, $idStudent, [
            'fecha'      => date('Y-m-d', strtotime('-7 days')),
            'hora'       => '10:00:00',
            'modalidad'  => 'Presencial',
            'url_sesion' => null,
            'direccion'  => 'Dirección de simulación #123',
        ], $idOrganismo);
        if (($r['success'] ?? false) !== true) die("Error al programar entrevista: " . ($r['message'] ?? '?') . "\n");
        // Marcar la retro como solicitada para que el cron NO encole correo por esta simulación
        $conn->prepare("UPDATE entrevistas_programadas SET retro_solicitada = 1 WHERE idStudent = ? AND idPractica = ?")
             ->execute([$idStudent, $idPractica]);

        // 3) Cerrar entrevista con evaluación → estado ENTREVISTA_CERRADA
        echo "3/4 Cerrando entrevista con evaluación...<br>\n";
        $r = PracticasModel::mdlCerrarEntrevista($idPractica, $idStudent, [
            'llego_a_tiempo'          => 1,
            'llego_formal'            => 1,
            'calificacion_respuestas' => 5,
            'comentarios'             => 'Entrevista de simulación (script de pruebas).',
        ]);
        if (($r['success'] ?? false) !== true) die("Error al cerrar entrevista: " . ($r['message'] ?? '?') . "\n");

        // 4) Decisión final: aceptar → estado ACEPTADO_FINAL + isAcepted = 1 + start_date
        echo "4/4 Aceptando al alumno (decisión final)...<br>\n";
        $r = PracticasModel::mdlDecisionFinalAceptar($idPractica, $idStudent, date('Y-m-d'), 'Aceptado por simulación de pruebas.');
        if (($r['success'] ?? false) !== true) die("Error en decisión final: " . ($r['message'] ?? '?') . "\n");

        // Generar 180 horas (45 asistencias de 4 horas)
        echo "Generando 45 asistencias de 4 horas (180h total)...<br>\n";
        $stmt = $conn->prepare(
            "INSERT INTO asistencias_practicas
            (idOrganismo, idPractica, idStudent, fecha, hora_entrada, hora_salida, actividad, status, horas_validadas)
            VALUES (?, ?, ?, ?, '08:00:00', '12:00:00', 'Actividades de simulación', 'aprobado', 4)"
        );

        $fecha = new DateTime('2024-01-01');
        for ($i = 0; $i < 45; $i++) {
            // Evitar fines de semana
            while ($fecha->format('N') >= 6) {
                $fecha->modify('+1 day');
            }
            $stmt->execute([$idOrganismo, $idPractica, $idStudent, $fecha->format('Y-m-d')]);
            $fecha->modify('+1 day');
        }

        echo "<h3 style='color:green;'>Fase 1 Completada.</h3>";
        echo "<ul>
                <li>El alumno $idStudent recorrió el flujo nuevo completo: PREPOSTULADO → ENTREVISTA_PROGRAMADA → ENTREVISTA_CERRADA → <strong>ACEPTADO_FINAL</strong> (isAcepted = 1 sincronizado).</li>
                <li>Quedaron pobladas prepostulaciones_practicas, entrevistas_programadas (retro ya marcada, el cron no enviará nada) y entrevistas_practicas (evaluación 5★).</li>
                <li>No se encoló ningún correo (simulación por capa de modelo).</li>
                <li>El alumno tiene 180 horas exactas: verifica el bloqueo de Reporte Parcial y Evaluación Intermedia.</li>
                <li>Luego entra como el organismo de la práctica $idPractica para probar la Evaluación Empresa.</li>
              </ul>";

    } else if ($fase == '2') {
        // Verificar que ya está aceptado en una práctica (flujo nuevo: ACEPTADO_FINAL)
        $stmt = $conn->prepare(
            "SELECT sip.idPractica, sip.estado, sp.organismo_externo_id
             FROM students_in_practices sip
             JOIN solicitudes_practicantes sp ON sip.idPractica = sp.id
             WHERE sip.idStudent = ? AND sip.isAcepted = 1"
        );
        $stmt->execute([$idStudent]);
        $rowPractica = $stmt->fetch();
        if (!$rowPractica) die("Error: El alumno no tiene práctica asignada (estado ACEPTADO_FINAL). Ejecuta la fase 1 primero.\n");
        $idPractica = $rowPractica['idPractica'];
        $idOrganismo = $rowPractica['organismo_externo_id'] ?: 0;
        echo "Práctica ID $idPractica · estado: {$rowPractica['estado']}<br>\n";

        // Generar 180 horas adicionales (total 360h)
        echo "Generando 45 asistencias adicionales de 4 horas (para alcanzar 360h)...<br>\n";
        $stmt = $conn->prepare(
            "INSERT INTO asistencias_practicas
            (idOrganismo, idPractica, idStudent, fecha, hora_entrada, hora_salida, actividad, status, horas_validadas)
            VALUES (?, ?, ?, ?, '08:00:00', '12:00:00', 'Actividades Fase 2', 'aprobado', 4)"
        );

        // Empezar desde abril para fase 2
        $fecha = new DateTime('2024-04-01');
        for ($i = 0; $i < 45; $i++) {
            while ($fecha->format('N') >= 6) {
                $fecha->modify('+1 day');
            }
            $stmt->execute([$idOrganismo, $idPractica, $idStudent, $fecha->format('Y-m-d')]);
            $fecha->modify('+1 day');
        }

        echo "<h3 style='color:green;'>Fase 2 Completada.</h3>";
        echo "<ul>
                <li>El alumno $idStudent ha alcanzado las 360 horas.</li>
                <li>Verifica que aparece el bloqueo del Reporte Final y Evaluación Final.</li>
              </ul>";

    } else if ($fase == '3') {
        /* Coloca al alumno en N horas exactas, sin encolar nada, y siembra los
           recordatorios que a esas alturas ya habría recibido. Así la siguiente
           aprobación (fase 4) dispara únicamente el umbral recién cruzado. */
        $horasObjetivo = (float) paramSimulacion('horas', '132');
        if ($horasObjetivo < 0) die("Error: horas debe ser un número positivo.\n");

        $stmt = $conn->prepare(
            "SELECT sip.idPractica, sp.organismo_externo_id
             FROM students_in_practices sip
             JOIN solicitudes_practicantes sp ON sip.idPractica = sp.id
             WHERE sip.idStudent = ? AND sip.isAcepted = 1"
        );
        $stmt->execute([$idStudent]);
        $rowPractica = $stmt->fetch();
        if (!$rowPractica) die("Error: El alumno no tiene práctica asignada. Ejecuta la fase 1 primero.\n");
        $idPractica = (int) $rowPractica['idPractica'];
        $idOrganismo = (int) ($rowPractica['organismo_externo_id'] ?: 0);

        echo "Colocando al alumno en $horasObjetivo horas exactas...<br>\n";
        $conn->prepare("DELETE FROM asistencias_practicas WHERE idStudent = ? AND idPractica = ?")
             ->execute([$idStudent, $idPractica]);

        $stmt = $conn->prepare(
            "INSERT INTO asistencias_practicas
            (idOrganismo, idPractica, idStudent, fecha, hora_entrada, hora_salida, actividad, status, horas_validadas)
            VALUES (?, ?, ?, ?, '08:00:00', '12:00:00', 'Simulación fase 3', 'aprobado', ?)"
        );
        $fecha = new DateTime('2024-01-01');
        $restante = $horasObjetivo;
        while ($restante > 0) {
            while ($fecha->format('N') >= 6) {
                $fecha->modify('+1 day');
            }
            $bloque = min(4, $restante);
            $stmt->execute([$idOrganismo, $idPractica, $idStudent, $fecha->format('Y-m-d'), $bloque]);
            $restante -= $bloque;
            $fecha->modify('+1 day');
        }

        // Sembrar los recordatorios que ya le habrían llegado con esas horas.
        $conn->prepare("DELETE FROM internship_reminders WHERE idStudent = ? AND idPractica = ?")
             ->execute([$idStudent, $idPractica]);
        $yaEnviados = [];
        $umbrales = [
            'start'       => 0.01,
            'partial_135' => PracticasModel::RECORDATORIO_AVISO_PARCIAL,
            'final_315'   => PracticasModel::RECORDATORIO_AVISO_FINAL,
        ];
        $ins = $conn->prepare(
            "INSERT INTO internship_reminders (idStudent, idPractica, type, last_sent_on)
             VALUES (?, ?, ?, CURDATE())"
        );
        foreach ($umbrales as $type => $minimo) {
            if ($horasObjetivo >= $minimo) {
                $ins->execute([$idStudent, $idPractica, $type]);
                $yaEnviados[] = $type;
            }
        }

        $siguiente = $horasObjetivo + 4;
        $cruzaria = [];
        foreach ($umbrales as $type => $minimo) {
            if ($horasObjetivo < $minimo && $siguiente >= $minimo) {
                $cruzaria[] = $type;
            }
        }

        echo "<h3 style='color:green;'>Fase 3 Completada.</h3>";
        echo "<ul>
                <li>El alumno $idStudent tiene <strong>$horasObjetivo horas</strong> aprobadas en la práctica $idPractica.</li>
                <li>Recordatorios marcados como ya enviados: <strong>" . (implode(', ', $yaEnviados) ?: 'ninguno') . "</strong>.</li>
                <li>Con la siguiente aprobación de 4 h llegaría a $siguiente h y dispararía: <strong>" . (implode(', ', $cruzaria) ?: 'ningún umbral') . "</strong>.</li>
                <li>No se encoló ningún correo. Ejecuta <code>fase=4</code> para provocar esa aprobación.</li>
              </ul>";

    } else if ($fase == '4') {
        /* Aprueba una asistencia por el camino real de la interfaz
           (PracticasController::ctrAprobarAsistencia), que es lo único que
           dispara los recordatorios de umbral. SÍ encola correos. */
        require_once __DIR__ . '/../controller/forms.controller.php';

        $stmt = $conn->prepare(
            "SELECT sip.idPractica, sp.organismo_externo_id
             FROM students_in_practices sip
             JOIN solicitudes_practicantes sp ON sip.idPractica = sp.id
             WHERE sip.idStudent = ? AND sip.isAcepted = 1"
        );
        $stmt->execute([$idStudent]);
        $rowPractica = $stmt->fetch();
        if (!$rowPractica) die("Error: El alumno no tiene práctica asignada. Ejecuta la fase 1 primero.\n");
        $idPractica = (int) $rowPractica['idPractica'];
        $idOrganismo = (int) ($rowPractica['organismo_externo_id'] ?: 0);

        $horasAntes = PracticasModel::mdlCalcularHorasAcumuladas($idStudent, $idPractica);
        echo "Horas antes de aprobar: <strong>$horasAntes</strong><br>\n";

        // Una asistencia nueva, en una fecha libre para no chocar con las simuladas.
        $fecha = (new DateTime('2025-06-02'))->format('Y-m-d');
        $existe = $conn->prepare(
            "SELECT idAsistencia FROM asistencias_practicas
              WHERE idStudent = ? AND idPractica = ? AND fecha = ?"
        );
        $existe->execute([$idStudent, $idPractica, $fecha]);
        while ($existe->fetch()) {
            $fecha = (new DateTime($fecha))->modify('+1 day')->format('Y-m-d');
            $existe->execute([$idStudent, $idPractica, $fecha]);
        }

        echo "Registrando asistencia del $fecha (pendiente)...<br>\n";
        $conn->prepare(
            "INSERT INTO asistencias_practicas
            (idOrganismo, idPractica, idStudent, fecha, hora_entrada, hora_salida, actividad, status, created_at)
            VALUES (?, ?, ?, ?, '08:00:00', '12:00:00', 'Asistencia de prueba (fase 4)', 'pendiente', NOW())"
        )->execute([$idOrganismo, $idPractica, $idStudent, $fecha]);
        $idAsistencia = (int) $conn->lastInsertId();

        $colaAntes = (int) $conn->query("SELECT COALESCE(MAX(id),0) FROM email_queue")->fetchColumn();

        echo "Aprobando por el controlador (camino real de la interfaz)...<br>\n";
        PracticasController::ctrAprobarAsistencia($idAsistencia);

        $horasDespues = PracticasModel::mdlCalcularHorasAcumuladas($idStudent, $idPractica);
        $nuevos = $conn->query(
            "SELECT to_email, subject FROM email_queue WHERE id > $colaAntes ORDER BY id"
        )->fetchAll(PDO::FETCH_ASSOC);
        $tipos = $conn->query(
            "SELECT type FROM internship_reminders WHERE idStudent = $idStudent AND idPractica = $idPractica ORDER BY type"
        )->fetchAll(PDO::FETCH_COLUMN);

        echo "<h3 style='color:green;'>Fase 4 Completada.</h3>";
        echo "<ul>
                <li>Horas: $horasAntes → <strong>$horasDespues</strong>.</li>
                <li>Recordatorios registrados: <strong>" . (implode(', ', $tipos) ?: 'ninguno') . "</strong>.</li>
                <li>Correos encolados (" . count($nuevos) . "):</li>
              </ul><ul>";
        foreach ($nuevos as $c) {
            echo "<li>{$c['to_email']} — {$c['subject']}</li>\n";
        }
        echo "</ul>";
        echo "<p style='color:#b45309;'><strong>Aviso:</strong> estos correos están en la cola y
              <code>process_email_queue.php</code> los enviará de verdad.</p>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
