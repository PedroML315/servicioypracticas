<?php
/**
 * Flujo de acciones del alumno en prácticas.
 * Incluido desde students.php cuando el rol es alumno_practicas / student.
 * Las variables $action e $idStudent se usan aquí directamente.
 */

require_once __DIR__ . '/../emails.php';

$idStudent = (int) $_SESSION['user']['id'];
$tipoPractica = $_SESSION['user']['tipo_practica'] ?? 'empresa';

/**
 * FASE 6 · Validación server-side del formulario de prepostulación.
 * Devuelve ['ok'=>bool, 'data'=>array, 'error'=>string]. No confía en el cliente.
 */
function validarPrepostulacion(array $post): array
{
    $opciones = [
        'disponibilidad_horario'    => ['Matutino', 'Vespertino', 'Tiempo completo', 'Flexible'],
        'modalidad'                 => ['Presencial', 'Híbrida', 'Remota'],
        'nivel_office'              => ['Básico', 'Intermedio', 'Avanzado'],
        'nivel_ingles'              => ['Básico', 'Intermedio', 'Avanzado', 'No aplica'],
        'equipo_remoto'             => ['Sí', 'No', 'No aplica'],
        'disponibilidad_inicio'     => ['Inmediata', 'En una semana', 'En dos semanas', 'En un mes'],
        'area_interes'              => ['Administrativas', 'Operativas', 'Análisis de datos', 'Desarrollo de proyectos', 'Investigación', 'Cualquier actividad relacionada con mi licenciatura'],
        'acepta_capacitacion'       => ['Sí', 'No'],
        'objetivo_practicas'        => ['Adquirir experiencia profesional', 'Desarrollar habilidades técnicas y profesionales', 'Fortalecer conocimientos en el área de interés', 'Generar oportunidades de contratación', 'Cumplir con el requisito académico'],
        'modalidad_entrevista_pref' => ['Virtual', 'Presencial'],
    ];

    // Licenciatura: texto obligatorio
    $licenciatura = trim(strip_tags((string) ($post['licenciatura'] ?? '')));
    if ($licenciatura === '') {
        return ['ok' => false, 'error' => 'Indica la licenciatura que estás cursando.'];
    }

    $data = ['licenciatura' => mb_substr($licenciatura, 0, 150)];

    // Campos de opción única obligatorios y validados contra su catálogo
    foreach ($opciones as $campo => $valores) {
        $val = trim((string) ($post[$campo] ?? ''));
        if ($val === '' || !in_array($val, $valores, true)) {
            return ['ok' => false, 'error' => 'Falta o es inválido el campo: ' . str_replace('_', ' ', $campo) . '.'];
        }
        $data[$campo] = $val;
    }

    // Herramientas (multi-selección, opcional) + "Otro" (texto libre)
    $herr = $post['herramientas'] ?? [];
    if (is_string($herr)) {
        $herr = $herr === '' ? [] : array_map('trim', explode(',', $herr));
    }
    $herr = array_values(array_filter(array_map(fn($h) => trim(strip_tags((string) $h)), (array) $herr), fn($h) => $h !== ''));
    $data['herramientas'] = $herr ? mb_substr(implode(',', $herr), 0, 500) : null;
    $otro = trim(strip_tags((string) ($post['herramientas_otro'] ?? '')));
    $data['herramientas_otro'] = $otro !== '' ? mb_substr($otro, 0, 150) : null;

    // Horario propuesto (texto libre, opcional)
    $horario = trim(strip_tags((string) ($post['horario_propuesto'] ?? '')));
    $data['horario_propuesto'] = $horario !== '' ? mb_substr($horario, 0, 255) : null;

    return ['ok' => true, 'data' => $data, 'error' => ''];
}

switch ($action) {

    case 'start':
        $studentData = PracticasModel::mdlGetStudentPracticesById($idStudent);
        $nombreCompleto = $studentData['nombre_completo'] ?? $_SESSION['user']['nombre_completo'] ?? 'Alumno';
        $parts = explode(' ', $nombreCompleto);
        $initials = count($parts) > 1 ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1)) : strtoupper(substr($parts[0], 0, 2));
        
        $idPracticaActive = null;
        $postulacion = null;
        $students_in_practices = null;
        
        if ($tipoPractica === 'universidad') {
            $postulacion = PracticasController::ctrGetPostulacionAreaByStudent($idStudent);
            if ($postulacion && $postulacion['status'] == 1) {
                $idPracticaActive = (int) $postulacion['id'];
            }
        } else {
            $students_in_practices = PracticasController::ctrIsStudentRegisteredInPractices($idStudent);
            if ($students_in_practices) {
                $idPracticaActive = (int) $students_in_practices['idPractica'];
            }
        }
        
        $countStrikes = $idPracticaActive ? PracticasModel::mdlGetStrikesCountAlumno($idStudent, $idPracticaActive) : 0;
        $isBajaStrike = ($studentData['status'] ?? '') === 'baja_practicas' || ($studentData['dado_de_baja_por_strike'] ?? 0) == 1 || $countStrikes >= 2;

        $studentInfo = [
            'nombre_completo' => $nombreCompleto,
            'programa_academico' => $studentData['programa_academico'] ?? '',
            'initials' => $initials,
            'countStrikes' => $countStrikes,
            'isBajaStrike' => $isBajaStrike
        ];

        if ($tipoPractica === 'universidad') {
            if ($postulacion && $postulacion['status'] == 1) {
                $response['practices'] = $postulacion;
                $response['asistencias'] = PracticasController::ctrGetAsistenciasArea((int) $postulacion['id'], $idStudent);
                $response['reporte_parcial'] = PracticasController::ctrGetPartialReport((int) $postulacion['id'], $idStudent);
                $response['reporte_final'] = PracticasController::ctrGetFinalReport((int) $postulacion['id'], $idStudent);
                $response['tipo_practica'] = 'universidad';
                $response['type'] = 'practices';
                $response['practicas_finalizadas'] = (int) ($studentData['practicas_finalizadas'] ?? 0);
                $response['fecha_finalizacion'] = $studentData['fecha_finalizacion'] ?? null;
            } else {
                $data = PracticasController::ctrGetAreasPracticasAbiertas($idStudent);
                $data['type'] = 'areas';
                $response = $data;
            }
        } else {
            if ($students_in_practices) {
                $response['practices'] = $students_in_practices;
                $response['asistencias'] = PracticasController::ctrCheckAssistance($students_in_practices['idPractica'], $idStudent);
                $response['reporte_parcial'] = PracticasController::ctrGetPartialReport($students_in_practices['idPractica'], $idStudent);
                $response['reporte_final'] = PracticasController::ctrGetFinalReport($students_in_practices['idPractica'], $idStudent);
                $response['tipo_practica'] = 'empresa';
                $response['type'] = 'practices';
                $response['practicas_finalizadas'] = (int) ($studentData['practicas_finalizadas'] ?? 0);
                $response['fecha_finalizacion'] = $studentData['fecha_finalizacion'] ?? null;
                $response['studentInfo'] = $studentInfo;
                $response['eval_integral_180'] = PracticasModel::mdlGetEstadoEvaluacionesIntegrales($idStudent, $students_in_practices['idPractica'], 'intermedia');
                $response['eval_integral_360'] = PracticasModel::mdlGetEstadoEvaluacionesIntegrales($idStudent, $students_in_practices['idPractica'], 'final');
                echo json_encode($response);
                exit;
            }
            $practices = PracticasController::ctrGetPractices($idStudent);
            // FASE 6 · Bloqueo global: ¿el alumno ya tiene una postulación en proceso?
            $enProceso = PracticasModel::mdlGetPostulacionEnProceso($idStudent);
            $response = [
                'type' => 'solicitudes',
                'practices' => $practices,
                'studentInfo' => $studentInfo,
                'bloqueoActivo' => $enProceso ? true : false,
                'postulacionActiva' => $enProceso ? [
                    'empresa' => $enProceso['empresa'] ?? '',
                    'estado' => $enProceso['estado'] ?? '',
                    'idPractica' => (int) $enProceso['idPractica'],
                ] : null,
            ];
        }
        if (!isset($response['studentInfo'])) {
            $response['studentInfo'] = $studentInfo;
        }
        echo json_encode($response);
        break;

    case 'apply':
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'Falta la vacante a la que deseas postularte.']);
            break;
        }
        $idPractice = (int) $_POST['id'];
        // FASE 6 · Prepostulación obligatoria: validar el formulario en el servidor
        $prep = validarPrepostulacion($_POST);
        if (!$prep['ok']) {
            echo json_encode(['success' => false, 'message' => $prep['error']]);
            break;
        }
        $response = PracticasController::ctrApplyForPractice($idPractice, $idStudent, $prep['data']);
        echo json_encode($response);
        break;

    case 'applyArea':
        if (isset($_POST['area_id'])) {
            $areaId = (int) $_POST['area_id'];
            $response = PracticasController::ctrPostularseArea($idStudent, $areaId);
            if ($response['success'] ?? false) {
                // Notify the teacher/encargado of the area
                $encargado = PracticasModel::mdlGetEncargadoInfoByArea($areaId);
                if ($encargado) {
                    $studentInfo = PracticasModel::mdlGetStudentPracticesById($idStudent);
                    $studentName = $studentInfo['nombre_completo'] ?? 'Un alumno';
                    sendNuevaPostulacionArea(
                        $encargado['email'],
                        $encargado['nombre_completo'],
                        $studentName,
                        $encargado['area_nombre']
                    );
                }
            }
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing area_id']);
        }
        break;

    case 'registerAttendance':
        if (isset($_POST['fechaAsistencia'], $_POST['horaEntrada'], $_POST['horaSalida'])) {
            if ($tipoPractica === 'universidad') {
                $postulacion = PracticasController::ctrGetPostulacionAreaByStudent($idStudent);
                if (!$postulacion || $postulacion['status'] != 1) {
                    echo json_encode(['success' => false, 'message' => 'No tienes una práctica activa.']);
                    exit;
                }
                $data = [
                    'postulacion_id' => (int) $postulacion['id'],
                    'area_id' => (int) $postulacion['area_id'],
                    'student_id' => $idStudent,
                    'fecha' => $_POST['fechaAsistencia'],
                    'hora_entrada' => $_POST['horaEntrada'],
                    'hora_salida' => $_POST['horaSalida'],
                    'actividad' => $_POST['actividadRealizada'],
                ];
                $response = PracticasController::ctrRegisterAttendanceArea($data);
            } else {
                $idPractica = PracticasController::ctrIsStudentRegisteredInPractices($idStudent);
                
                // --- Validar Bloqueo de Evaluaciones Integrales ---
                $bloqueoEval = PracticasModel::mdlCheckBloqueoEvaluaciones($idStudent, $idPractica['idPractica']);
                if ($bloqueoEval !== null) {
                    echo json_encode([
                        'success' => false,
                        'message' => $bloqueoEval['mensaje'],
                        'motivo' => $bloqueoEval['motivo']
                    ]);
                    exit;
                }
                
                $data = [
                    'fechaAsistencia' => $_POST['fechaAsistencia'],
                    'horaEntrada' => $_POST['horaEntrada'],
                    'horaSalida' => $_POST['horaSalida'],
                    'actividad' => $_POST['actividadRealizada'],
                    'idStudent' => $idStudent,
                    'idOrganismo' => $idPractica['organismo_externo_id'],
                    'idPractica' => $idPractica['idPractica'],
                ];
                $response = PracticasController::ctrRegisterAttendance($data);
            }
            echo json_encode($response);
        } else {
            echo json_encode(['error' => 'Missing required fields']);
        }
        break;

    case 'generatePartialReport':
        if ($tipoPractica === 'universidad') {
            $postulacion = PracticasController::ctrGetPostulacionAreaByStudent($idStudent);
            if (!$postulacion || $postulacion['status'] != 1) {
                echo json_encode(['success' => false, 'message' => 'No tienes una práctica activa.']);
                exit;
            }
            $idPracticaVal = (int) $postulacion['id'];
        } else {
            $row = PracticasController::ctrIsStudentRegisteredInPractices($idStudent);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'No tienes una práctica activa.']);
                exit;
            }
            $idPracticaVal = $row['idPractica'];
        }
        $data = [
            'idStudent' => $idStudent,
            'idPractica' => $idPracticaVal,
            'objetivo' => $_POST['objetivoGeneral'],
            'actividades_repotadas' => $_POST['actividadesReportadas'],
        ];
        echo json_encode(PracticasController::ctrGeneratePartialReport($data));
        break;

    case 'generateFinalReport':
        if ($tipoPractica === 'universidad') {
            $postulacion = PracticasController::ctrGetPostulacionAreaByStudent($idStudent);
            if (!$postulacion || $postulacion['status'] != 1) {
                echo json_encode(['success' => false, 'message' => 'No tienes una práctica activa.']);
                exit;
            }
            $idPracticaVal = (int) $postulacion['id'];
        } else {
            $row = PracticasController::ctrIsStudentRegisteredInPractices($idStudent);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'No tienes una práctica activa.']);
                exit;
            }
            $idPracticaVal = $row['idPractica'];
        }
        $data = [
            'idStudent' => $idStudent,
            'idPractica' => $idPracticaVal,
            'objetivo_general' => $_POST['objetivoGeneralFinal'],
            'actividades_realizadas' => $_POST['actividadesRealizadasFinal'],
            'resultados_obtenidos' => $_POST['resultadosObtenidosFinal'],
            'capacitacion_recibida' => $_POST['capacitacionRecibidaFinal'],
            'experiencia_profesional' => $_POST['experienciaProfesionalFinal'],
            'experiencia_personal' => $_POST['experienciaPersonalFinal'],
        ];
        echo json_encode(PracticasController::ctrGenerateFinalReport($data));
        break;

    case 'generate_constancia':
        require_once __DIR__ . '/generarConstanciaAcreditacion.php';
        break;

    case 'saveEvalIntegralAlumno':
        // Parse the incoming answers
        $tipoHito = $_POST['tipoHito'] ?? 'intermedia'; // intermedia or final
        
        if ($tipoPractica === 'universidad') {
            $postulacion = PracticasController::ctrGetPostulacionAreaByStudent($idStudent);
            if (!$postulacion || $postulacion['status'] != 1) {
                echo json_encode(['success' => false, 'message' => 'No tienes una práctica activa.']);
                exit;
            }
            $idPracticaVal = (int) $postulacion['id'];
            $idOrganismo = 0; // Universidad doesn't have an organismo in the same way, but let's default to 0
        } else {
            $row = PracticasController::ctrIsStudentRegisteredInPractices($idStudent);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'No tienes una práctica activa.']);
                exit;
            }
            $idPracticaVal = $row['idPractica'];
            $idOrganismo = $row['organismo_externo_id'];
        }

        $respuestas = [];
        // The form sends array of answers.
        // We expect questions 1-15 for the student form (B)
        $qCount = 15;
        for ($i = 1; $i <= $qCount; $i++) {
            if (isset($_POST["q{$i}"])) {
                $val = $_POST["q{$i}"];
                $tipo = 'likert';
                $valNum = null;
                $valTxt = null;

                if (in_array($i, [14, 15])) {
                    $tipo = 'abierta';
                    $valTxt = $val;
                } else if ($i === 12) { // 12 is Si/No
                    $tipo = 'likert';
                    $valNum = (int)$val;
                } else {
                    if (is_numeric($val)) {
                        $tipo = 'likert';
                        $valNum = (int)$val;
                    } else {
                        $tipo = 'abierta';
                        $valTxt = $val;
                    }
                }

                $respuestas[] = [
                    'index' => $i,
                    'texto' => "Pregunta $i", // We could pass the text from frontend, but this is enough for trazabilidad
                    'tipo' => $tipo,
                    'valorNumerico' => $valNum,
                    'valorTexto' => $valTxt
                ];
            }
        }

        $data = [
            'idStudent' => $idStudent,
            'idPractica' => $idPracticaVal,
            'idOrganismo' => $idOrganismo,
            'tipoHito' => $tipoHito,
            'tipoEvaluador' => 'alumno',
            'respuestas' => $respuestas
        ];

        echo json_encode(PracticasModel::mdlSaveEvaluacionIntegral($data));
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
