<?php

header('Content-Type: application/json');

require_once "../../model/forms.models.php";
require_once "../forms.controller.php";
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();

switch ($_POST['action']) {
    case 'getSolicitudes':
        $response = PracticasController::getSolicitudesPracticas($_SESSION['user']['id']);
        echo json_encode($response);
        break;
    case 'getSolicitudById':
        $idSolicitud = $_POST['id'];
        $response = PracticasController::getSolicitudPracticaById($idSolicitud);
        // ── Seguridad: solo el organismo dueño puede ver su propia solicitud ──
        if (!$response || (int) ($response['organismo_externo_id'] ?? -1) !== (int) $_SESSION['user']['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado.']);
            exit;
        }
        echo json_encode($response);
        break;
    case 'solicitarPracticas':
        // ── Verificar bloqueo de solicitudes (Fase 3: Strikes) ──
        $orgCheck = PracticasModel::mdlGetExternals($_SESSION['user']['id']);
        if ($orgCheck && isset($orgCheck['solicitudes_bloqueadas']) && $orgCheck['solicitudes_bloqueadas'] == 1) {
            echo json_encode([
                'success' => false,
                'icon' => 'error',
                'title' => 'Solicitudes bloqueadas',
                'message' => 'Su organismo tiene bloqueada la solicitud de nuevos practicantes. Motivo: '
                              . ($orgCheck['motivo_bloqueo'] ?? 'No especificado')
            ]);
            break;
        }

        $data = array(
            'licenciatura' => $_POST['licenciatura'],
            'numPract' => $_POST['numPract'],
            'actividades' => $_POST['actividades'],
            'funciones' => $_POST['funciones'] ?? '',
            'objetivos' => $_POST['objetivos'] ?? '',
            'competencias' => $_POST['competencias'] ?? '',
            'resultadosEsperados' => $_POST['resultadosEsperados'] ?? '',
            'apoyoEconomico' => ($_POST['apoyoEconomico'] == 'Sí') ? 1 : 0,
            'montoApoyo' => $_POST['montoApoyo'],
            'fechaLimite' => $_POST['fechaLimite'],
            'modalidad' => $_POST['modalidad'],
            'diaInicio' => $_POST['diaInicio'],
            'diaFin' => $_POST['diaFin'],
            'horaInicio' => $_POST['horaInicio'],
            'horaFin' => $_POST['horaFin'],
            'capacidades' => $_POST['capacidades'],
            'direccionPractica' => $_POST['direccionPractica'],
            'nombreResponsable' => $_POST['nombreResponsable'],
            'contactoResponsable' => $_POST['contactoResponsable'],
            'organismo_externo_id' => $_SESSION['user']['id']
        );
        $response = PracticasController::solicitarPracticas($data);
        echo json_encode($response);
        break;
    case 'updateSolicitud':
        $data = array(
            'idSolicitud' => $_POST['idSolicitud'],
            'licenciatura' => $_POST['licenciatura'],
            'numPract' => $_POST['numPract'],
            'actividades' => $_POST['actividades'],
            'funciones' => $_POST['funciones'] ?? '',
            'objetivos' => $_POST['objetivos'] ?? '',
            'competencias' => $_POST['competencias'] ?? '',
            'resultadosEsperados' => $_POST['resultadosEsperados'] ?? '',
            'apoyoEconomico' => ($_POST['apoyoEconomico'] == 'Sí') ? 1 : 0,
            'montoApoyo' => $_POST['montoApoyo'],
            'fechaLimite' => $_POST['fechaLimite'],
            'modalidad' => $_POST['modalidad'],
            'diaInicio' => $_POST['diaInicio'],
            'diaFin' => $_POST['diaFin'],
            'horaInicio' => $_POST['horaInicio'],
            'horaFin' => $_POST['horaFin'],
            'capacidades' => $_POST['capacidades'],
            'direccionPractica' => $_POST['direccionPractica'],
            'nombreResponsable' => $_POST['nombreResponsable'],
            'contactoResponsable' => $_POST['contactoResponsable']
        );
        // ── Seguridad: verificar que la solicitud pertenece a este organismo ──
        $solCheck = PracticasController::getSolicitudPracticaById($data['idSolicitud']);
        if (!$solCheck || (int) ($solCheck['organismo_externo_id'] ?? -1) !== (int) $_SESSION['user']['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado.']);
            exit;
        }
        $response = PracticasController::updateSolicitudPractica($data);
        echo json_encode($response);
        break;
    case 'deleteSolicitud':
        $idSolicitud = $_POST['id'];
        // ── Seguridad: verificar que la solicitud pertenece a este organismo ──
        $solCheck = PracticasController::getSolicitudPracticaById($idSolicitud);
        if (!$solCheck || (int) ($solCheck['organismo_externo_id'] ?? -1) !== (int) $_SESSION['user']['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado.']);
            exit;
        }
        $response = PracticasController::deleteSolicitudPractica($idSolicitud);
        echo json_encode($response);
        break;
    case 'aceptarProspecto':
        $idStudent = $_POST['idStudent'] ?? '';
        $idSolicitud = $_POST['idSolicitud'] ?? '';
        $fechaInicio = $_POST['fechaInicio'] ?? '';
        $motivo = trim($_POST['motivoAceptacion'] ?? '');
        
        if (strlen($motivo) < 10) {
            echo json_encode(['success' => false, 'message' => 'El motivo de aceptación debe tener al menos 10 caracteres.']);
            exit;
        }

        // ── Seguridad: verificar que la solicitud pertenece a este organismo ──
        $solCheck = PracticasController::getSolicitudPracticaById($idSolicitud);
        if (!$solCheck || (int) ($solCheck['organismo_externo_id'] ?? -1) !== (int) $_SESSION['user']['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado.']);
            exit;
        }
        $response = PracticasController::ctrAceptarProspecto($idSolicitud, $idStudent, $fechaInicio, $motivo);
        echo json_encode($response);
        break;
    case 'rechazarProspecto':
        $idStudent = $_POST['idStudent'] ?? '';
        $idSolicitud = $_POST['idSolicitud'] ?? '';
        $motivo = trim($_POST['motivoRechazo'] ?? '');

        if (strlen($motivo) < 10) {
            echo json_encode(['success' => false, 'message' => 'El motivo de rechazo debe tener al menos 10 caracteres.']);
            exit;
        }

        // ── Seguridad: verificar que la solicitud pertenece a este organismo ──
        $solCheck = PracticasController::getSolicitudPracticaById($idSolicitud);
        if (!$solCheck || (int) ($solCheck['organismo_externo_id'] ?? -1) !== (int) $_SESSION['user']['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado.']);
            exit;
        }
        $response = PracticasController::ctrRechazarProspecto($idSolicitud, $idStudent, $motivo);
        echo json_encode($response);
        break;
    case 'evaluarEntrevista':
        $data = [
            'idStudent' => $_POST['idStudent'] ?? '',
            'idPractica' => $_POST['idSolicitud'] ?? '',
            'llego_a_tiempo' => $_POST['llego_a_tiempo'] ?? 0,
            'llego_formal' => $_POST['llego_formal'] ?? 0,
            'calificacion_respuestas' => $_POST['calificacion_respuestas'] ?? 0,
            'comentarios' => $_POST['comentarios'] ?? ''
        ];
        
        $solCheck = PracticasController::getSolicitudPracticaById($data['idPractica']);
        if (!$solCheck || (int) ($solCheck['organismo_externo_id'] ?? -1) !== (int) $_SESSION['user']['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado.']);
            exit;
        }

        $response = PracticasController::ctrEvaluarEntrevista($data);
        echo json_encode($response);
        break;
    case 'getHistorialAlumnos':
        $response = PracticasModel::mdlGetHistorialAlumnosOrganismo($_SESSION['user']['id']);
        echo json_encode($response);
        break;
    case 'confirmarPresentacion':
        $studentId = (int)$_POST['idStudent'];
        $organismoId = (int)$_SESSION['user']['id'];
        $response = PracticasController::ctrConfirmarPresentacion($studentId, $organismoId);
        echo json_encode($response);
        break;
    case 'getAssistancesPractices':
        $response = PracticasController::getAssistancesPractices($_SESSION['user']['id']);
        echo json_encode($response);
        break;
    case 'getAllPractices':
        $response = PracticasController::getAllPractices($_SESSION['user']['id']);
        $semaforos = PracticasModel::mdlGetSemaforoEvaluaciones();
        foreach ($response as &$practica) {
            if (isset($practica['password'])) {
                unset($practica['password']);
            }
            $studentId = $practica['idStudent'] ?? $practica['id'] ?? null;
            if ($studentId && isset($semaforos[$studentId])) {
                $practica['semaforo_empresa'] = $semaforos[$studentId]['empresa'];
                $practica['semaforo_alumno'] = $semaforos[$studentId]['alumno'];
            } else {
                $practica['semaforo_empresa'] = null;
                $practica['semaforo_alumno'] = null;
            }
        }
        echo json_encode($response);
        break;
    case 'solicitarCapacitacion':
        $response = PracticasController::solicitarCapacitacion($_SESSION['user']['id'], $_POST['matricula'], $_POST['solicitud']);
        echo json_encode($response);
        break;

    case 'buscarSolicitudesCapacitacion':
        $response = PracticasController::ctrGetSolicitudesCapacitacion($_SESSION['user']['id']);
        echo json_encode($response);
        break;

    case 'getDataPracticesStudent':
        $response = PracticasController::getDataPracticesStudent($_SESSION['user']['id'], $_POST['matricula']);
        if (isset($response['password'])) {
            unset($response['password']);
        }
        echo json_encode($response);
        break;
    case 'aprobarAsistencia':
        $idAsistencia = $_POST['idAsistencia'];
        $response = PracticasController::ctrAprobarAsistencia($idAsistencia);
        echo json_encode($response);
        break;
    case 'rechazarAsistencia':
        $idAsistencia = $_POST['idAsistencia'];
        $response = PracticasController::ctrRechazarAsistencia($idAsistencia);
        echo json_encode($response);
        break;
    case 'actualizarHorarios':
        $idAsistencia = $_POST['idAsistencia'];
        $hora_entrada = $_POST['hora_entrada'];
        $hora_salida = $_POST['hora_salida'];
        $response = PracticasController::ctrActualizarHorarios($idAsistencia, $hora_entrada, $hora_salida);
        echo json_encode($response);
        break;
    case 'getReportsPractices':
        $response['parciales'] = PracticasController::getReportsPractices($_SESSION['user']['id']);
        $response['finales'] = PracticasController::getFinalReports($_SESSION['user']['id']);
        echo json_encode($response);
        break;
    case 'getDashboardSummary':
        $response = PracticasController::getDashboardSummary((int) $_SESSION['user']['id']);
        echo json_encode($response);
        break;
    case 'getRecentProspectos':
        $response = PracticasController::getRecentProspectos((int) $_SESSION['user']['id']);
        echo json_encode($response);
        break;
    case 'acceptReport':
        $idReporteParcial = $_POST['idReporteParcial'];
        $response = PracticasController::acceptReport($idReporteParcial);
        echo json_encode($response);
        break;
    case 'rejectReport':
        $idReporteParcial = $_POST['idReporteParcial'];
        $comentarios = $_POST['comentarios'];
        $response = PracticasController::rejectReport($idReporteParcial, $comentarios);
        echo json_encode($response);
        break;
    case 'acceptReportFinal':
        $idReporteFinal = $_POST['idReporteFinal'];
        $response = PracticasController::acceptReportFinal($idReporteFinal);
        echo json_encode($response);
        break;
    case 'rejectReportFinal':
        $idReporteFinal = $_POST['idReporteFinal'];
        $comentarios = $_POST['comentarios'];
        $response = PracticasController::rejectReportFinal($idReporteFinal, $comentarios);
        echo json_encode($response);
        break;
    case 'evaluarParticipanteParcial':
        $idStudent = $_POST['idStudent'];
        $evaluacion = [
            'rubros' => [
                $_POST['rubro_0'],
                $_POST['rubro_1'],
                $_POST['rubro_2'],
                $_POST['rubro_3'],
                $_POST['rubro_4'],
                $_POST['rubro_5']
            ],
            'actitudes' => [
                $_POST['actitud_0'],
                $_POST['actitud_1'],
                $_POST['actitud_2'],
                $_POST['actitud_3'],
                $_POST['actitud_4'],
                $_POST['actitud_5'],
                $_POST['actitud_6'],
                $_POST['actitud_7'],
                $_POST['actitud_8'],
                $_POST['actitud_9']
            ],
            'fortalezas' => $_POST['parcialFortalezas'],
            'debilidades' => $_POST['parcialDebilidades'],
            'idPractica' => $_POST['idPractica'],
            'idReporteParcial' => $_POST['idReporteParcial']
        ];
        $response = PracticasController::evaluarParticipanteParcial($idStudent, $evaluacion);
        echo json_encode($response);
        break;
    case 'evaluarParticipanteFinal':
        $idStudent = $_POST['idStudent'];
        $evaluacion = [
            'rubros' => [
                $_POST['final_rubro_0'],
                $_POST['final_rubro_1'],
                $_POST['final_rubro_2'],
                $_POST['final_rubro_3'],
                $_POST['final_rubro_4'],
                $_POST['final_rubro_5']
            ],
            'actitudes' => [
                $_POST['final_actitud_0'],
                $_POST['final_actitud_1'],
                $_POST['final_actitud_2'],
                $_POST['final_actitud_3'],
                $_POST['final_actitud_4'],
                $_POST['final_actitud_5'],
                $_POST['final_actitud_6'],
                $_POST['final_actitud_7'],
                $_POST['final_actitud_8'],
                $_POST['final_actitud_9']
            ],
            'fortalezas' => $_POST['finalFortalezas'],
            'debilidades' => $_POST['finalDebilidades'],
            'idPractica' => $_POST['idPractica'],
            'idReporteFinal' => $_POST['idReporteFinal']
        ];
        $response = PracticasController::evaluarParticipanteFinal($idStudent, $evaluacion);
        echo json_encode($response);
        break;
    case 'getEvaluacionesRecientesAdmin':
        $count = PracticasModel::mdlGetEvaluacionesRecientesAdmin();
        echo json_encode(['count' => $count]);
        break;
    case 'getEvaluacionesAlumnoCompleta':
        $idStudent = $_POST['idStudent'] ?? 0;
        $evaluaciones = PracticasModel::mdlGetDetalleEvaluacionesAlumno($idStudent);
        echo json_encode($evaluaciones);
        break;
    case 'marcarEvaluacionesVistas':
        $idStudent = $_POST['idStudent'] ?? 0;
        $success = PracticasModel::mdlMarcarEvaluacionesVista($idStudent);
        echo json_encode(['success' => $success]);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}