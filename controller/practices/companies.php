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

        // ── Organismos aceptados sin convenio validado (para alertas) ──
        case 'get_convenios_faltantes':
            $faltantes = PracticasModel::mdlGetOrganismosSinConvenio();
            echo json_encode(['success' => true, 'data' => $faltantes]);
            break;

        // ── Cargar / reemplazar el convenio validado (PDF firmado por la institución) ──
        case 'upload_convenio':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); break; }

            $org = PracticasModel::mdlGetOrganismoById($id);
            if (!$org) { echo json_encode(['success' => false, 'message' => 'Organismo no encontrado']); break; }

            if (empty($_FILES['convenio']) || ($_FILES['convenio']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Debes adjuntar el convenio en PDF.']);
                break;
            }

            $tmpName = $_FILES['convenio']['tmp_name'];
            $original = $_FILES['convenio']['name'] ?? '';

            // Validación de extensión (solo PDF)
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF.']);
                break;
            }

            // Validación de tipo MIME real (no confiamos en el cliente)
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeReal = $finfo->file($tmpName);
            if ($mimeReal !== 'application/pdf') {
                echo json_encode(['success' => false, 'message' => 'El archivo no es un PDF válido (MIME: ' . htmlspecialchars($mimeReal) . ').']);
                break;
            }

            $baseDir = __DIR__ . '/../../uploads/' . $id . '/';
            if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true)) {
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio de uploads.']);
                break;
            }
            if (!is_writable($baseDir)) {
                echo json_encode(['success' => false, 'message' => 'Sin permisos de escritura en el directorio de uploads.']);
                break;
            }

            // Nombre único para evitar problemas de caché al reemplazar
            $newName = 'convenio_validado_' . bin2hex(random_bytes(4)) . '.pdf';

            // Eliminar el convenio anterior si existe
            $prev = $org['convenio_validado'] ?? null;
            if ($prev && is_file($baseDir . $prev)) {
                @unlink($baseDir . $prev);
            }

            if (!move_uploaded_file($tmpName, $baseDir . $newName)) {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo.']);
                break;
            }

            $saved = PracticasModel::mdlSetConvenioValidado($id, $newName);
            echo json_encode([
                'success' => (bool)$saved,
                'message' => $saved ? 'Convenio cargado y validado correctamente.' : 'Error al registrar el convenio.',
                'url'     => 'controller/serve_pdf.php?file=' . $id . '/' . rawurlencode($newName),
            ]);
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
            // Listar documentos subidos (excluyendo el convenio validado, que tiene su propio apartado)
            $convenioFile = $org['convenio_validado'] ?? null;
            $uploadsDir = __DIR__ . '/../../uploads/' . $id . '/';
            $docs = [];
            if (is_dir($uploadsDir)) {
                foreach (scandir($uploadsDir) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    if ($convenioFile && $file === $convenioFile) continue;
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $docs[] = [
                        'name' => $file,
                        'url'  => 'uploads/' . $id . '/' . rawurlencode($file),
                        'ext'  => $ext,
                    ];
                }
            }
            $org['documentos'] = $docs;
            // URL segura del convenio validado (si la empresa lo tiene cargado)
            $org['convenio_url'] = ($convenioFile && is_file($uploadsDir . $convenioFile))
                ? 'controller/serve_pdf.php?file=' . $id . '/' . rawurlencode($convenioFile)
                : null;
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
