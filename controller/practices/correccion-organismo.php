<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../model/forms.models.php';
    require_once __DIR__ . '/../emails.php';
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error de carga: ' . $e->getMessage()]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada.']);
    exit;
}

$action = $_POST['action'];

try {
    switch ($action) {
        
        case 'request_otp':
            $token = trim($_POST['token'] ?? '');
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Token no válido.']);
                break;
            }

            // Validar token y obtener info
            $data = PracticasModel::mdlGetTokenCorreccion($token);
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'El enlace es inválido, ha expirado o ya fue utilizado.']);
                break;
            }

            // Generar OTP de 6 dígitos
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);
            $expiraAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Guardar OTP
            if (!PracticasModel::mdlSetOtp($token, $otpHash, $expiraAt)) {
                echo json_encode(['success' => false, 'message' => 'Error al generar el código de acceso.']);
                break;
            }

            // Enviar correo
            $correoEnviado = sendOrganismoOtp($data['email'], $data['empresa'], $otp);
            if ($correoEnviado) {
                echo json_encode(['success' => true, 'message' => 'Código enviado correctamente. Revisa tu correo.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al enviar el correo con el código.']);
            }
            break;

        case 'verify_otp':
            $token = trim($_POST['token'] ?? '');
            $otp = trim($_POST['otp'] ?? '');
            
            if (!$token || !$otp) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
                break;
            }

            $data = PracticasModel::mdlGetTokenCorreccion($token);
            if (!$data || !$data['otp_code']) {
                echo json_encode(['success' => false, 'message' => 'Sesión expirada o inválida. Solicita un nuevo código.']);
                break;
            }

            // Verificar si hay demasiados intentos fallidos
            if ($data['otp_intentos'] >= 5) {
                echo json_encode(['success' => false, 'message' => 'Demasiados intentos fallidos. Solicita un nuevo código.']);
                break;
            }

            if (!password_verify($otp, $data['otp_code'])) {
                // Registrar intento fallido
                PracticasModel::mdlIncrementOtpIntentos($token);
                echo json_encode(['success' => false, 'message' => 'Código incorrecto.']);
                break;
            }

            // OTP Correcto -> Iniciar "sesión" temporal y resetear intentos (poner nuevo hash para invalidarlo despues si hace falta o reset)
            PracticasModel::mdlSetOtp($token, $data['otp_code'], date('Y-m-d H:i:s', strtotime('+1 hour'))); 
            
            $_SESSION['correccion_token'] = $token;
            $_SESSION['correccion_org_id'] = $data['organismo_id'];
            $_SESSION['correccion_rechazo_id'] = $data['rechazo_id'];
            
            echo json_encode(['success' => true, 'message' => 'Código verificado. Redirigiendo...']);
            break;

        case 'save_correccion':
            if (!isset($_SESSION['correccion_token']) || $_SESSION['correccion_token'] !== ($_POST['token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Sesión de corrección inválida o expirada.']);
                break;
            }

            $orgId = $_SESSION['correccion_org_id'];
            $rechazoId = $_SESSION['correccion_rechazo_id'];
            $token = $_SESSION['correccion_token'];

            $camposCorregidos = json_decode($_POST['campos_corregidos'] ?? '[]', true);
            $camposPermitidos = array_column($camposCorregidos, 'campo');
            
            // Subida de archivos (si se corrigieron documentos)
            if (!empty($_FILES)) {
                $uploadDir = __DIR__ . '/../../uploads/' . $orgId . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                foreach ($_FILES as $inputName => $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $filename = basename($file['name']);
                        $campoName = str_replace('file_', 'doc:', $inputName);
                        $targetPath = $uploadDir . $filename;
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            // Encontrar en camposCorregidos y actualizar
                            foreach ($camposCorregidos as &$cc) {
                                if ($cc['campo'] === $campoName) {
                                    $cc['valor_nuevo'] = "Archivo actualizado: $filename";
                                }
                            }
                        }
                    }
                }
            }

            // Datos generales
            $updateData = [];
            $allowedFields = ['empresa', 'tipo_persona', 'giro', 'fecha_constitucion', 'web', 'actividades', 'calle', 'colonia', 'cp', 'ciudad', 'nombre_contacto', 'telefonos', 'celular', 'email', 'rep_legal', 'cargo_legal', 'tel_oficina', 'email_legal'];

            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $updateData[$field] = $_POST[$field];
                    // Asegurarnos que actualizamos valor_nuevo en el log
                    foreach ($camposCorregidos as &$cc) {
                        if ($cc['campo'] === $field) {
                            $cc['valor_nuevo'] = $_POST[$field];
                        }
                    }
                }
            }

            // Actualizar BD
            $result = PracticasModel::mdlUpdateOrganismoFields($orgId, $updateData, $camposPermitidos);
            
            if ($result) {
                // Registrar historial
                PracticasModel::mdlSaveHistorialCorreccion($rechazoId, $orgId, $camposCorregidos, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
                
                // Marcar corregidos
                foreach ($camposCorregidos as $cc) {
                    PracticasModel::mdlMarcarCampoCorregido($rechazoId, $cc['campo'], $cc['valor_nuevo'] ?? '');
                }

                // Cambiar estado
                PracticasModel::mdlMarcarRechazoCorregido($rechazoId, $orgId);
                PracticasModel::mdlInvalidateToken($token);

                // Limpiar sesión temporal
                unset($_SESSION['correccion_token'], $_SESSION['correccion_org_id'], $_SESSION['correccion_rechazo_id']);
                
                // Notificar al admin
                $orgInfo = PracticasModel::mdlGetOrganismoParaCorreccion($orgId);
                sendOrganismoCorregidoAdmin($orgInfo['empresa'] ?? 'Organismo', $orgId, $camposCorregidos);

                echo json_encode(['success' => true, 'message' => 'Tus correcciones han sido enviadas exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No hubieron cambios o error al guardar.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción desconocida.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
