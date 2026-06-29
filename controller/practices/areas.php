<?php
require_once "../../model/forms.models.php";
require_once "../forms.controller.php";
require_once "../emails.php";

session_start();

if (!isset($_SESSION['logged']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$isAdmin = in_array($role, ['admin', 'admin_practicas'], true);
$isTeacher = ($role === 'teacher');

if (!$isAdmin && !$isTeacher) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$action = $_POST['action'];

switch ($action) {

    // ── Sólo admin ──────────────────────────────────────────
    case 'getAreas':
        if (!$isAdmin) break;
        echo json_encode(PracticasController::ctrGetAllAreasPracticas());
        break;

    case 'createArea':
        if (!$isAdmin) break;
        $data = [
            'nombre'            => trim($_POST['nombre'] ?? ''),
            'descripcion'       => trim($_POST['descripcion'] ?? ''),
            'cupo'              => (int)($_POST['cupo'] ?? 10),
            'encargado_user_id' => (int)($_POST['encargado_user_id'] ?? 0),
        ];
        if (empty($data['nombre'])) {
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
            break;
        }
        echo json_encode(PracticasController::ctrCreateAreaPractica($data));
        break;

    case 'updateArea':
        if (!$isAdmin) break;
        $data = [
            'id'                => (int)($_POST['id'] ?? 0),
            'nombre'            => trim($_POST['nombre'] ?? ''),
            'descripcion'       => trim($_POST['descripcion'] ?? ''),
            'cupo'              => (int)($_POST['cupo'] ?? 10),
            'encargado_user_id' => (int)($_POST['encargado_user_id'] ?? 0),
        ];
        echo json_encode(PracticasController::ctrUpdateAreaPractica($data));
        break;

    case 'toggleArea':
        if (!$isAdmin) break;
        $id     = (int)($_POST['id'] ?? 0);
        $isOpen = (int)($_POST['isOpen'] ?? 0);
        echo json_encode(PracticasController::ctrToggleAreaPractica($id, $isOpen));
        break;

    case 'deleteArea':
        if (!$isAdmin) break;
        $id = (int)($_POST['id'] ?? 0);
        echo json_encode(PracticasController::ctrDeleteAreaPractica($id));
        break;

    case 'getPostulaciones':
        if (!$isAdmin) break;
        echo json_encode(PracticasController::ctrGetAllPostulacionesAreas());
        break;

    case 'aceptarPostulacion':
        if (!$isAdmin) break;
        $id          = (int)($_POST['id'] ?? 0);
        $fechaInicio = trim($_POST['fecha_inicio'] ?? date('Y-m-d'));
        // Fetch data for email BEFORE updating
        $postData = PracticasController::ctrGetPostulacionAreaById($id);
        $result   = PracticasController::ctrAceptarPostulacionArea($id, $fechaInicio);
        if ($result['success'] && $postData) {
            sendAreaPostulacionAceptada(
                $postData['email'],
                $postData['nombre_completo'],
                $postData['area_nombre'],
                $fechaInicio
            );
        }
        echo json_encode($result);
        break;

    case 'rechazarPostulacion':
        if (!$isAdmin) break;
        $id       = (int)($_POST['id'] ?? 0);
        $postData = PracticasController::ctrGetPostulacionAreaById($id);
        $result   = PracticasController::ctrRechazarPostulacionArea($id);
        if ($result['success'] && $postData) {
            sendAreaPostulacionRechazada(
                $postData['email'],
                $postData['nombre_completo'],
                $postData['area_nombre']
            );
        }
        echo json_encode($result);
        break;

    // ── Admin + Teacher (encargado) ──────────────────────────
    case 'getPendingAsistencias':
        $areaId = (int)($_POST['area_id'] ?? 0);
        // Teacher solo puede ver su área asignada
        if ($isTeacher) {
            $areas = PracticasController::ctrGetAreasByEncargado((int)$_SESSION['user']['id']);
            $allowed = array_column($areas, 'id');
            if (!in_array($areaId, $allowed, true)) {
                echo json_encode(['success' => false, 'message' => 'Sin permisos para esta área.']);
                break;
            }
        }
        echo json_encode(PracticasController::ctrGetPendingAsistenciasArea($areaId));
        break;

    case 'aprobarAsistencia':
        $id = (int)($_POST['id'] ?? 0);
        echo json_encode(PracticasController::ctrAprobarAsistenciaArea($id));
        break;

    case 'rechazarAsistencia':
        $id = (int)($_POST['id'] ?? 0);
        echo json_encode(PracticasController::ctrRechazarAsistenciaArea($id));
        break;

    case 'getStudentsInArea':
        $areaId = (int)($_POST['area_id'] ?? 0);
        echo json_encode(PracticasController::ctrGetStudentsInArea($areaId));
        break;

    case 'getEvaluaciones':
        if (!$isAdmin) { echo json_encode(['success' => false, 'message' => 'Sin permisos']); break; }
        $idStudent  = (int)($_POST['student_id']     ?? 0);
        $idPractica = (int)($_POST['postulacion_id'] ?? 0);
        if (!$idStudent || !$idPractica) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            break;
        }
        $data = PracticasController::ctrGetEvaluacionesByEstudiante($idStudent, $idPractica);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'getMyAreas':
        if (!$isTeacher) break;
        $userId = (int)$_SESSION['user']['id'];
        echo json_encode(PracticasController::ctrGetAreasByEncargado($userId));
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
