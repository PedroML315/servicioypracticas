<?php

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../model/forms.models.php';
    require_once __DIR__ . '/../forms.controller.php';
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error de carga: ' . $e->getMessage()]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_status() === PHP_SESSION_NONE && session_start();
}

if (!isset($_SESSION['logged']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$role    = $_SESSION['user']['role'] ?? '';
$isAdmin = in_array($role, ['admin', 'admin_practicas'], true);

if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {

        // ── Dashboard principal: organismos + estadísticas ────────
        case 'get_dashboard':
            $organismos   = PracticasModel::mdlGetOrganismosConEstudiantes();
            $sinOrganismo = PracticasModel::mdlGetStudentsSinOrganismo();
            $stats        = PracticasModel::mdlGetOrganismoStats();
            echo json_encode([
                'success'       => true,
                'organismos'    => $organismos,
                'sin_organismo' => $sinOrganismo,
                'stats'         => $stats,
            ]);
            break;

        // ── Estudiantes de un organismo específico ────────────────
        case 'get_students_by_organismo':
            $id = (int)($_POST['organismo_id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }
            $students = PracticasModel::mdlGetStudentsByOrganismo($id);
            echo json_encode(['success' => true, 'data' => $students]);
            break;

        // ── Aceptar organismo ─────────────────────────────────────
        case 'accept_external':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }
            $result = PracticasController::ctrAcceptExternal($id);
            echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Organismo aceptado. Se envió el correo.' : 'Error al aceptar.']);
            break;

        // ── Rechazar organismo con motivos ────────────────────────
        case 'reject_external_with_reasons':
            $id = (int)($_POST['id'] ?? 0);
            $motivoGeneral = trim($_POST['motivo_general'] ?? '');
            $campos = json_decode($_POST['campos'] ?? '[]', true);

            if (!$id || !$motivoGeneral || empty($campos)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos o no se seleccionaron campos erróneos.']);
                break;
            }

            $adminId = $_SESSION['user']['id'] ?? 0;
            $adminName = $_SESSION['user']['name'] ?? 'Administrador';

            $result = PracticasController::ctrRejectExternalWithReasons($id, $motivoGeneral, $campos, $adminId, $adminName);

            echo json_encode(['success' => $result['status'] === 'success', 'message' => $result['message']]);
            break;

        // ── Detalle completo de un organismo ──────────────────────
        case 'get_organismo_details':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }
            $org = PracticasModel::mdlGetOrganismoById($id);
            if (!$org) { echo json_encode(['success' => false, 'message' => 'Organismo no encontrado']); break; }
            // Listar documentos subidos
            $uploadsDir = __DIR__ . '/../../uploads/' . $id . '/';
            $docs = [];
            if (is_dir($uploadsDir)) {
                foreach (scandir($uploadsDir) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $docs[] = [
                        'name' => $file,
                        'url'  => 'uploads/' . $id . '/' . rawurlencode($file),
                        'ext'  => $ext,
                    ];
                }
            }
            $org['documentos'] = $docs;
            echo json_encode(['success' => true, 'data' => $org]);
            break;

        // ── Deshabilitar organismo ────────────────────────────────

        // -- Historial de solicitudes de practicantes de un organismo ------
        case 'get_historial_solicitudes':
            $id = (int)($_POST['organismo_id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }
            $solicitudes = PracticasModel::mdlGetHistorialSolicitudesByOrganismo($id);
            $total = count($solicitudes);
            $aceptadas = 0; $rechazadas = 0; $pendientes = 0; $totalPracticantes = 0;
            foreach ($solicitudes as $s) {
                $totalPracticantes += (int)$s['num_practicantes'];
                if ($s['activo'] == 0 && $s['aceptado'] == 0) { $rechazadas++; }
                elseif ($s['aceptado'] == 1) { $aceptadas++; }
                else { $pendientes++; }
            }
            echo json_encode([
                'success'     => true,
                'solicitudes' => $solicitudes,
                'stats'       => [
                    'total'              => $total,
                    'aceptadas'          => $aceptadas,
                    'rechazadas'         => $rechazadas,
                    'pendientes'         => $pendientes,
                    'total_practicantes' => $totalPracticantes,
                ],
            ]);
            break;

        // -- Postulantes de una solicitud (historial) -----------------------
        case 'get_postulados_solicitud':
            $id = (int)($_POST['solicitud_id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }
            $postulados = PracticasModel::mdlGetPostuladosByHistorialSolicitud($id);
            echo json_encode(['success' => true, 'data' => $postulados]);
            break;
        case 'disable_external':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }
            $result = PracticasModel::mdlDisableExternal($id);
            echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Organismo deshabilitado.' : 'Error al deshabilitar.']);
            break;

        
        case 'block_external':
            $id = (int)($_POST['id'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');
            if (!$id || !$motivo) { echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']); break; }
            $result = PracticasModel::mdlBlockExternal($id, $motivo, $_SESSION['user']['id'], $_SESSION['user']['name'] ?? 'Admin');
            echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Organismo bloqueado.' : 'Error al bloquear.']);
            break;

        case 'unblock_external':
            $id = (int)($_POST['id'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }
            $result = PracticasModel::mdlUnblockExternal($id, $motivo, $_SESSION['user']['id'], $_SESSION['user']['name'] ?? 'Admin');
            echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Organismo desbloqueado.' : 'Error al desbloquear.']);
            break;

        case 'remove_strike_org':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }
            $result = PracticasModel::mdlRemoveStrikeOrganismo($id);
            echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Strike eliminado del organismo.' : 'Error al eliminar strike.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida: ' . htmlspecialchars($action)]);
    }
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'file'    => basename($e->getFile()) . ':' . $e->getLine(),
    ]);
}
