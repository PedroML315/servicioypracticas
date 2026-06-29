<?php

require_once "../../model/forms.models.php";
require_once "../forms.controller.php";

session_status() === PHP_SESSION_NONE && session_start();

if (!isset($_SESSION['logged']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$role    = $_SESSION['user']['role'] ?? '';
$isAdmin = in_array($role, ['admin', 'admin_practicas'], true);
$isStudent = in_array($role, ['alumno_practicas', 'student'], true);

// Student-flow actions are allowed for students; admin actions only for admins
$studentFlowActions = ['start', 'apply', 'applyArea', 'registerAttendance', 'generatePartialReport', 'generateFinalReport', 'generate_constancia', 'saveEvalIntegralAlumno'];
$action = $_POST['action'];

if (!$isAdmin && !($isStudent && in_array($action, $studentFlowActions, true))) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

switch ($action) {

    case 'get_students_practices':
        $response = PracticasController::ctrGetStudentsPractices();
        $semaforos = PracticasModel::mdlGetSemaforoEvaluaciones();
        foreach ($response as &$practica) {
            $studentId = $practica['id'] ?? null;
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

    case 'get_student_by_id':
        $id = (int)($_POST['idStudent'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID no proporcionado']); break; }
        echo json_encode(PracticasController::ctrGetStudentPracticesById($id));
        break;

    case 'update_student_practices':
        $required = ['idstudent', 'matricula', 'nombre_completo', 'email'];
        foreach ($required as $f) {
            if (empty($_POST[$f])) {
                echo json_encode(['success' => false, 'error' => "Campo requerido: $f"]);
                exit;
            }
        }
        $data = [
            'idstudent'          => (int)$_POST['idstudent'],
            'matricula'          => trim($_POST['matricula']),
            'grupo'              => trim($_POST['grupo'] ?? ''),
            'nombre_completo'    => trim($_POST['nombre_completo']),
            'curp'               => trim($_POST['curp'] ?? ''),
            'fecha_nacimiento'   => trim($_POST['fecha_nacimiento'] ?? ''),
            'genero'             => trim($_POST['genero'] ?? 'Otro'),
            'email'              => trim($_POST['email']),
            'telefono'           => trim($_POST['telefono'] ?? ''),
            'programa_academico' => trim($_POST['programa_academico'] ?? ''),
            'periodo'            => (int)($_POST['periodo'] ?? 1),
        ];
        echo json_encode(PracticasController::ctrUpdateStudentPractices($data));
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

    case 'disable_student':
        $id = (int)($_POST['idStudent'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no proporcionado']); break; }
        $result = PracticasController::ctrDisableStudentPractices($id);
        if ($result === 'success') {
            echo json_encode(['success' => true, 'message' => 'Alumno deshabilitado.']);
        } else {
            echo json_encode(['success' => false, 'message' => $result ?? 'Error al deshabilitar.']);
        }
        break;


    // ──── Flujo alumno (keep for student dashboard usage) ────
    case 'start':
    case 'apply':
    case 'applyArea':
    case 'registerAttendance':
    case 'generatePartialReport':
    case 'generateFinalReport':
    case 'saveEvalIntegralAlumno':
    case 'generate_constancia':
        // Re-include original student flow (no auth restriction needed for own session)
        require_once __DIR__ . '/students_flow.php';
        break;

    
    case 'hard_reset_student':
        $idStudent = (int)($_POST['idStudent'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');
        $confirmacion = $_POST['confirmacion'] ?? '';
        if ($confirmacion !== 'CONFIRMAR RESET') { echo json_encode(['success' => false, 'message' => 'Confirmación inválida.']); break; }
        if (!$idStudent || !$motivo) { echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']); break; }
        $result = PracticasModel::mdlHardResetStudent($idStudent, $motivo, $_SESSION['user']['id'], $_SESSION['user']['name'] ?? 'Admin');
        echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Alumno reiniciado.' : 'Error al reiniciar.']);
        break;

    case 'remove_strike_student':
        $idStudent = (int)($_POST['idStudent'] ?? 0);
        if (!$idStudent) { echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']); break; }
        $result = PracticasModel::mdlRemoveStrikeStudent($idStudent);
        echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Strike eliminado.' : 'Error al eliminar strike.']);
        break;

    case 'unblock_student':
        $idStudent = (int)($_POST['idStudent'] ?? 0);
        if (!$idStudent) { echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']); break; }
        $result = PracticasModel::mdlUnblockStudent($idStudent);
        echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Alumno desbloqueado.' : 'Error al desbloquear alumno.']);
        break;

    case 'resend_credentials_student':
        $id = (int)($_POST['idStudent'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no proporcionado']); break; }
        $result = FormsController::ctrResendStudentPracticeCredentials($id);
        if ($result === 'success') {
            echo json_encode(['success' => true, 'message' => 'Credenciales regeneradas y enviadas.']);
        } else {
            echo json_encode(['success' => false, 'message' => $result ?? 'Error interno al regenerar.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'AcciÃ³n no reconocida.']);
}

