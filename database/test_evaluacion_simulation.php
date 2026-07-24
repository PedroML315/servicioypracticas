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
 */

require_once __DIR__ . '/../model/conection.php';
require_once __DIR__ . '/../model/PracticasModel.php';

$fase = $_GET['fase'] ?? null;
if (!$fase) {
    foreach ($argv as $arg) {
        if (strpos($arg, 'fase=') === 0) {
            $fase = substr($arg, 5);
        }
    }
}

if ($fase === null || !in_array($fase, ['0', '1', '2'], true)) {
    die("Debes especificar fase=0, fase=1 o fase=2\n");
}

$idStudent = 22; // Alumno de prueba fijo

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
        // Se prefiere la empresa de pruebas (organismo 12 · ocontreras) para
        // poder revisar el resultado desde su panel.
        $stmt = $conn->query(
            "SELECT sp.id, sp.organismo_externo_id
               FROM solicitudes_practicantes sp
              WHERE sp.aceptado = 1 AND sp.activo = 1
                AND (SELECT COUNT(*) FROM students_in_practices x
                      WHERE x.idPractica = sp.id AND x.isAcepted = 1) < sp.num_practicantes
              ORDER BY (sp.organismo_externo_id = 12) DESC, sp.id DESC
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
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
