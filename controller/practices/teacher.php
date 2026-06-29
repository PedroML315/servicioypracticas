<?php
declare(strict_types=1);

// Capturar errores PHP fatales y devolverlos como JSON
set_exception_handler(function(\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    exit;
});

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

session_start();
require_once '../../model/forms.models.php';
require_once '../forms.controller.php';

// Solo encargados/teachers pueden usar este endpoint
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado', 'debug_role' => $_SESSION['user']['role'] ?? 'no-session']);
    exit;
}

$teacherId = (int)$_SESSION['user']['id'];
$action = $_POST['action'] ?? '';

try {
switch ($action) {

    // ─── Servicio Social ──────────────────────────────────────────────────────

    case 'getSsStudents':
        // Alumnos en los eventos del teacher
        $students = PracticasController::ctrGetSsStudentsByTeacher($teacherId);
        echo json_encode($students ?: []);
        break;

    // ─── Prácticas Profesionales ──────────────────────────────────────────────

    case 'getAllPendientes':
        // Todas las postulaciones + asistencias pendientes de TODAS las áreas del encargado
        $misAreas  = PracticasModel::mdlGetAreasByEncargado($teacherId) ?: [];
        $areaIds   = array_column($misAreas, 'id');
        $postulaciones = [];
        $asistencias   = [];
        foreach ($areaIds as $aid) {
            $posts = PracticasModel::mdlGetPostulacionesPendientesByArea((int)$aid) ?: [];
            // Inyectar nombre del área en cada registro
            $areaNombre = '';
            foreach ($misAreas as $a) { if ($a['id'] == $aid) { $areaNombre = $a['nombre']; break; } }
            foreach ($posts as &$p) { $p['area_nombre'] = $areaNombre; }
            $postulaciones = array_merge($postulaciones, $posts);

            $asist = PracticasModel::mdlGetPendingAsistenciasByArea((int)$aid) ?: [];
            foreach ($asist as &$as) { $as['area_nombre'] = $areaNombre; }
            $asistencias = array_merge($asistencias, $asist);
        }
        echo json_encode([
            'postulaciones' => $postulaciones,
            'asistencias'   => $asistencias,
        ]);
        break;

    case 'getAspirantesPendientes':
        // Alumnos con solicitud pendiente (isAcepted = 0)
        $aspirantes = PracticasModel::mdlGetStudentsPracticesByStatus(0);
        echo json_encode($aspirantes ?: []);
        break;

    case 'getPostulacionesPendientes':
        $areaId = (int)($_POST['area_id'] ?? 0);
        if (!$areaId) { echo json_encode([]); break; }
        // Verificar que el área pertenece al encargado
        $misAreas = PracticasModel::mdlGetAreasByEncargado($teacherId);
        $areaIds  = array_column($misAreas ?: [], 'id');
        if (!in_array($areaId, $areaIds)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Sin acceso a esa área']);
            break;
        }
        echo json_encode(PracticasModel::mdlGetPostulacionesPendientesByArea($areaId) ?: []);
        break;

    case 'aceptarPostulacion':
        $postId = (int)($_POST['postulacion_id'] ?? 0);
        if (!$postId) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }
        $info   = PracticasModel::mdlGetPostulacionInfo($postId);
        $result = PracticasModel::mdlAceptarPostulacionArea($postId, date('Y-m-d'));
        if (($result['success'] ?? false) && $info) {
            require_once '../emails.php';
            sendAreaPostulacionAceptada($info['email'], $info['nombre_completo'], $info['area_nombre'], date('Y-m-d'));
        }
        echo json_encode(['success' => (bool)($result['success'] ?? false), 'message' => $result['message'] ?? 'Error al aceptar.']);
        break;

    case 'rechazarPostulacion':
        $postId = (int)($_POST['postulacion_id'] ?? 0);
        if (!$postId) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }
        $info   = PracticasModel::mdlGetPostulacionInfo($postId);
        $result = PracticasModel::mdlRechazarPostulacionArea($postId);
        if (($result['success'] ?? false) && $info) {
            require_once '../emails.php';
            sendAreaPostulacionRechazada($info['email'], $info['nombre_completo'], $info['area_nombre']);
        }
        echo json_encode(['success' => (bool)($result['success'] ?? false), 'message' => $result['message'] ?? 'Error al rechazar.']);
        break;

    case 'accept_student':
        $id = (int)($_POST['idStudent'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no proporcionado']); break; }
        $result = FormsController::ctrAcceptStudentPractice($id);
        if ($result === 'success') {
            echo json_encode(['success' => true, 'message' => 'Alumno aceptado.']);
        } else {
            echo json_encode(['success' => false, 'message' => $result ?? 'Error al aceptar.']);
        }
        break;

    case 'denegate_student':
        $id = (int)($_POST['idStudent'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no proporcionado']); break; }
        $result = FormsController::ctrDenegateStudentPractice($id);
        if ($result === 'success') {
            echo json_encode(['success' => true, 'message' => 'Solicitud rechazada.']);
        } else {
            echo json_encode(['success' => false, 'message' => $result ?? 'Error al rechazar.']);
        }
        break;

    case 'getMyAreas':
        $areas = PracticasController::ctrGetAreasByEncargado($teacherId);
        echo json_encode($areas ?: []);
        break;

    case 'getStudentsInArea':
        $areaId = (int)($_POST['area_id'] ?? 0);
        if (!$areaId) { echo json_encode([]); break; }
        $students = PracticasController::ctrGetStudentsInArea($areaId);
        echo json_encode($students ?: []);
        break;

    case 'getStudentAttendanceHistory':
        $studentId = (int)($_POST['student_id'] ?? 0);
        $areaId    = (int)($_POST['area_id'] ?? 0);
        if (!$studentId || !$areaId) { echo json_encode([]); break; }
        $history = PracticasController::ctrGetStudentAttendanceHistory($studentId, $areaId);
        echo json_encode($history ?: []);
        break;

    case 'getStudentChartData':
        $studentId = (int)($_POST['student_id'] ?? 0);
        $areaId    = (int)($_POST['area_id'] ?? 0);
        if (!$studentId || !$areaId) { echo json_encode([]); break; }
        $rows = PracticasController::ctrGetAllAsistenciasAreaByStudent($studentId, $areaId);
        // Agrupar horas por semana (YYYY-WW)
        $byWeek = [];
        foreach ($rows as $row) {
            $week = date('Y-W', strtotime($row['fecha']));
            $byWeek[$week] = ($byWeek[$week] ?? 0) + (float)$row['horas'];
        }
        $labels = array_keys($byWeek);
        $data   = array_values($byWeek);
        echo json_encode(['labels' => $labels, 'data' => $data]);
        break;

    // ─── Anotaciones ─────────────────────────────────────────────────────────

    case 'addAnnotation':
        $type      = in_array($_POST['type'] ?? '', ['servicio', 'practicas']) ? $_POST['type'] : 'practicas';
        $studentId = (int)($_POST['student_id'] ?? 0);
        $nota      = trim($_POST['nota'] ?? '');
        if (!$studentId || $nota === '') {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
            break;
        }
        $result = PracticasController::ctrAddTeacherAnnotation($teacherId, $type, $studentId, $nota);
        echo json_encode($result);
        break;

    case 'getAnnotations':
        $type      = in_array($_POST['type'] ?? '', ['servicio', 'practicas']) ? $_POST['type'] : 'practicas';
        $studentId = (int)($_POST['student_id'] ?? 0);
        if (!$studentId) { echo json_encode([]); break; }
        $annotations = PracticasController::ctrGetTeacherAnnotations($teacherId, $type, $studentId);
        echo json_encode($annotations ?: []);
        break;

    case 'deleteAnnotation':
        $id = (int)($_POST['annotation_id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID inválido']); break; }
        $result = PracticasController::ctrDeleteTeacherAnnotation($id, $teacherId);
        echo json_encode($result);
        break;

    // ─── Reportes del alumno (área universidad) ───────────────────────────────

    case 'getStudentReport':
        $studentId    = (int)($_POST['student_id']    ?? 0);
        $postulacionId = (int)($_POST['postulacion_id'] ?? 0);
        if (!$studentId || !$postulacionId) { echo json_encode(['parcial' => false, 'final' => false]); break; }
        $parcial = PracticasModel::mdlGetPartialReport($postulacionId, $studentId);
        $final   = PracticasModel::mdlGetFinalReport($postulacionId, $studentId);
        echo json_encode([
            'parcial' => $parcial ?: false,
            'final'   => $final   ?: false,
        ]);
        break;

    case 'acceptReport':
        $id = (int)($_POST['report_id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }
        $result = PracticasModel::mdlAcceptReport($id);
        echo json_encode($result);
        break;

    case 'rejectReport':
        $id          = (int)($_POST['report_id']   ?? 0);
        $comentarios = trim($_POST['comentarios']  ?? '');
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }
        $result = PracticasModel::mdlRejectReport($id, $comentarios);
        echo json_encode($result);
        break;

    case 'acceptFinalReport':
        $id = (int)($_POST['report_id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }
        $result = PracticasModel::mdlAcceptReportFinal($id);
        echo json_encode($result);
        break;

    case 'rejectFinalReport':
        $id          = (int)($_POST['report_id']   ?? 0);
        $comentarios = trim($_POST['comentarios']  ?? '');
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }
        $result = PracticasModel::mdlRejectReportFinal($id, $comentarios);
        echo json_encode($result);
        break;

    case 'evaluarParticipanteParcial':
        $idStudent  = (int)($_POST['idStudent']  ?? 0);
        $idPractica = (int)($_POST['idPractica'] ?? 0);
        if (!$idStudent || !$idPractica) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            break;
        }
        $evaluacion = [
            'rubros' => [
                $_POST['rubro_0'] ?? 0, $_POST['rubro_1'] ?? 0,
                $_POST['rubro_2'] ?? 0, $_POST['rubro_3'] ?? 0,
                $_POST['rubro_4'] ?? 0, $_POST['rubro_5'] ?? 0,
            ],
            'actitudes' => [
                $_POST['actitud_0'] ?? '', $_POST['actitud_1'] ?? '',
                $_POST['actitud_2'] ?? '', $_POST['actitud_3'] ?? '',
                $_POST['actitud_4'] ?? '', $_POST['actitud_5'] ?? '',
                $_POST['actitud_6'] ?? '', $_POST['actitud_7'] ?? '',
                $_POST['actitud_8'] ?? '', $_POST['actitud_9'] ?? '',
            ],
            'fortalezas'      => trim($_POST['parcialFortalezas'] ?? ''),
            'debilidades'     => trim($_POST['parcialDebilidades'] ?? ''),
            'idPractica'      => $idPractica,
            'idReporteParcial'=> (int)($_POST['idReporteParcial'] ?? 0),
        ];
        $result = PracticasController::evaluarParticipanteParcial($idStudent, $evaluacion);
        echo json_encode($result);
        break;

    case 'evaluarParticipanteFinal':
        $idStudent  = (int)($_POST['idStudent']  ?? 0);
        $idPractica = (int)($_POST['idPractica'] ?? 0);
        if (!$idStudent || !$idPractica) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            break;
        }
        $evaluacion = [
            'rubros' => [
                $_POST['final_rubro_0'] ?? 0, $_POST['final_rubro_1'] ?? 0,
                $_POST['final_rubro_2'] ?? 0, $_POST['final_rubro_3'] ?? 0,
                $_POST['final_rubro_4'] ?? 0, $_POST['final_rubro_5'] ?? 0,
            ],
            'actitudes' => [
                $_POST['final_actitud_0'] ?? '', $_POST['final_actitud_1'] ?? '',
                $_POST['final_actitud_2'] ?? '', $_POST['final_actitud_3'] ?? '',
                $_POST['final_actitud_4'] ?? '', $_POST['final_actitud_5'] ?? '',
                $_POST['final_actitud_6'] ?? '', $_POST['final_actitud_7'] ?? '',
                $_POST['final_actitud_8'] ?? '', $_POST['final_actitud_9'] ?? '',
            ],
            'fortalezas'    => trim($_POST['finalFortalezas'] ?? ''),
            'debilidades'   => trim($_POST['finalDebilidades'] ?? ''),
            'idPractica'    => $idPractica,
            'idReporteFinal'=> (int)($_POST['idReporteFinal'] ?? 0),
        ];
        $result = PracticasController::evaluarParticipanteFinal($idStudent, $evaluacion);
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida']);
        break;
}
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
}
