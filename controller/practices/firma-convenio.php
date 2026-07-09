<?php
/**
 * controller/practices/firma-convenio.php
 *
 * Endpoint público (con verificación OTP) para que el organismo descargue el
 * convenio generado y suba el convenio firmado de forma autógrafa, mediante un
 * enlace de un solo uso (tabla tokens_convenio_organismos).
 *
 * Espeja la mecánica de controller/practices/correccion-organismo.php.
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../model/forms.models.php';
    require_once __DIR__ . '/../emails.php';
    require_once __DIR__ . '/../../model/notifications.php';
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error de carga: ' . $e->getMessage()]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// La descarga puede llegar por GET; el resto de acciones por POST.
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada.']);
    exit;
}

try {
    switch ($action) {

        // ── Solicitar código OTP ──────────────────────────────────
        case 'request_otp':
            $token = trim($_POST['token'] ?? '');
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Token no válido.']);
                break;
            }

            $data = PracticasModel::mdlGetTokenConvenio($token);
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'El enlace es inválido, ha expirado o ya fue utilizado.']);
                break;
            }

            $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);
            $expira  = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            if (!PracticasModel::mdlSetOtpConvenio($token, $otpHash, $expira)) {
                echo json_encode(['success' => false, 'message' => 'Error al generar el código de acceso.']);
                break;
            }

            $enviado = sendOrganismoOtp($data['email'], $data['empresa'], $otp);
            echo json_encode($enviado
                ? ['success' => true, 'message' => 'Código enviado correctamente. Revisa tu correo.']
                : ['success' => false, 'message' => 'Error al enviar el correo con el código.']);
            break;

        // ── Verificar OTP ─────────────────────────────────────────
        case 'verify_otp':
            $token = trim($_POST['token'] ?? '');
            $otp   = trim($_POST['otp'] ?? '');
            if (!$token || !$otp) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
                break;
            }

            $data = PracticasModel::mdlGetTokenConvenio($token);
            if (!$data || !$data['otp_code']) {
                echo json_encode(['success' => false, 'message' => 'Sesión expirada o inválida. Solicita un nuevo código.']);
                break;
            }

            if ($data['otp_intentos'] >= 5) {
                echo json_encode(['success' => false, 'message' => 'Demasiados intentos fallidos. Solicita un nuevo código.']);
                break;
            }

            if (!password_verify($otp, $data['otp_code'])) {
                PracticasModel::mdlIncrementOtpIntentosConvenio($token);
                echo json_encode(['success' => false, 'message' => 'Código incorrecto.']);
                break;
            }

            // OTP correcto → sesión temporal.
            $_SESSION['firma_token']  = $token;
            $_SESSION['firma_org_id'] = (int) $data['organismo_id'];
            echo json_encode(['success' => true, 'message' => 'Código verificado. Redirigiendo...']);
            break;

        // ── Descargar el convenio generado (autorizado por la sesión OTP) ──
        case 'descargar':
            $token = trim($_GET['token'] ?? $_POST['token'] ?? '');
            if (!isset($_SESSION['firma_token']) || $_SESSION['firma_token'] !== $token) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sesión no verificada.']);
                break;
            }
            $orgId = (int) $_SESSION['firma_org_id'];
            $org   = PracticasModel::mdlGetOrganismoById($orgId);
            $file  = $org['convenio_generado_file'] ?? null;
            $path  = __DIR__ . '/../../uploads/' . $orgId . '/' . $file;
            if (!$file || !is_file($path)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No se encontró el convenio generado.']);
                break;
            }
            // Entregar el PDF como descarga.
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Convenio_' . preg_replace('/[^A-Za-z0-9]/', '_', $org['empresa']) . '.pdf"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: no-store');
            readfile($path);
            exit;

        // ── Subir el convenio firmado ─────────────────────────────
        case 'upload_firmado':
            $token = trim($_POST['token'] ?? '');
            if (!isset($_SESSION['firma_token']) || $_SESSION['firma_token'] !== $token) {
                echo json_encode(['success' => false, 'message' => 'Sesión de firma inválida o expirada.']);
                break;
            }

            // Revalidar token (no usado, no expirado).
            $data = PracticasModel::mdlGetTokenConvenio($token);
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'El enlace ya fue utilizado o expiró.']);
                break;
            }
            $orgId = (int) $data['organismo_id'];

            if (empty($_FILES['convenio']) || ($_FILES['convenio']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Debes adjuntar el convenio firmado en PDF.']);
                break;
            }

            $tmpName  = $_FILES['convenio']['tmp_name'];
            $original = $_FILES['convenio']['name'] ?? '';

            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF.']);
                break;
            }
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeReal = $finfo->file($tmpName);
            if ($mimeReal !== 'application/pdf') {
                echo json_encode(['success' => false, 'message' => 'El archivo no es un PDF válido.']);
                break;
            }

            $baseDir = __DIR__ . '/../../uploads/' . $orgId . '/';
            if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true)) {
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio de uploads.']);
                break;
            }

            // Eliminar firmado anterior si existía (reenvío tras rechazo).
            $prev = $data['convenio_firmado_file'] ?? null;
            if ($prev && is_file($baseDir . $prev)) {
                @unlink($baseDir . $prev);
            }

            $newName = 'convenio_firmado_' . bin2hex(random_bytes(4)) . '.pdf';
            if (!move_uploaded_file($tmpName, $baseDir . $newName)) {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo.']);
                break;
            }

            if (!PracticasModel::mdlSetConvenioFirmado($orgId, $newName)) {
                echo json_encode(['success' => false, 'message' => 'Error al registrar el convenio firmado.']);
                break;
            }

            // Token de un solo uso: invalidar.
            PracticasModel::mdlInvalidateTokenConvenio($token);

            // Limpiar sesión temporal.
            unset($_SESSION['firma_token'], $_SESSION['firma_org_id']);

            // Notificar al admin.
            sendConvenioFirmadoAdmin($data['empresa'], $orgId);
            Notifications::addNotification(
                $_ENV['Current_ID_ADMIN'] ?? 0,
                'admin',
                "El organismo '{$data['empresa']}' firmó y reenvió su convenio. Pendiente de validación final.",
                'organismos_externos',
                $orgId,
                2,
                2
            );

            echo json_encode(['success' => true, 'message' => 'Tu convenio firmado se envió correctamente. La universidad lo validará y te enviará tus credenciales de acceso.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción desconocida.']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
