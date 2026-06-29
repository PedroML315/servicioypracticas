<?php
// submit_evaluation.php
header('Content-Type: application/json');

// Ajusta la ruta según tu proyecto
require_once "../../model/forms.models.php";

// ── Rate limiting simple por IP (máx. 5 registros por hora) ──────────────────
session_start();
$ipKey = 'reg_org_' . preg_replace('/[^a-f0-9:.]/', '', $_SERVER['REMOTE_ADDR'] ?? '');
$now   = time();
if (!isset($_SESSION[$ipKey])) {
    $_SESSION[$ipKey] = ['count' => 0, 'since' => $now];
}
if ($now - $_SESSION[$ipKey]['since'] > 3600) {
    $_SESSION[$ipKey] = ['count' => 0, 'since' => $now];
}
$_SESSION[$ipKey]['count']++;
if ($_SESSION[$ipKey]['count'] > 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Demasiados intentos. Intenta de nuevo en una hora.']);
    exit;
}

// Detectar si se excedió post_max_size (PHP vacía $_POST silenciosamente)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
    $maxSize = ini_get('post_max_size');
    echo json_encode([
        'success' => false,
        'message' => "Los archivos enviados superan el límite permitido ({$maxSize}). Reduzca el tamaño de los documentos e intente de nuevo."
    ]);
    exit;
}

try {
    // 1) Recuperar y limpiar datos del formulario
    $trim = fn($key) => trim($_POST[$key] ?? '');

    $data = [
        'tipoPersona'       => $trim('tipoPersona'),
        'empresa'           => $trim('empresa'),
        'giro'              => $trim('giro'),
        'fecha_constitucion'=> $trim('fecha_constitucion') ?: null,
        'web'               => $trim('web'),
        'calle'             => $trim('calle'),
        'cp'                => $trim('cp'),
        'colonia'           => $trim('colonia'),
        'ciudad'            => $trim('ciudad'),
        'telefonos'         => $trim('telefonos'),
        'email'             => $trim('email'),
        'nombre_contacto'   => $trim('nombre_contacto'),
        'celular'           => $trim('celular'),
        'rep_legal'         => $trim('rep_legal'),
        'cargo_legal'       => $trim('cargo_legal'),
        'email_legal'       => $trim('email_legal'),
        'tel_oficina'       => $trim('tel_oficina'),
        'actividades'       => $trim('actividades'),
    ];

    // 2) Validar campos obligatorios
    $required = [
        'tipoPersona'    => 'Tipo de persona',
        'empresa'        => 'Nombre de la empresa/organismo',
        'giro'           => 'Giro / actividad principal',
        'calle'          => 'Calle (dirección)',
        'cp'             => 'Código postal',
        'colonia'        => 'Colonia',
        'ciudad'         => 'Ciudad',
        'email'          => 'Correo electrónico',
        'nombre_contacto'=> 'Nombre del contacto',
    ];
    $missing = [];
    foreach ($required as $field => $label) {
        if ($data[$field] === '') {
            $missing[] = $label;
        }
    }
    if (!empty($missing)) {
        echo json_encode([
            'success' => false,
            'message' => 'Faltan campos obligatorios: ' . implode(', ', $missing) . '.'
        ]);
        exit;
    }

    // 3) Validar formato de email(s)
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico no tiene un formato válido.']);
        exit;
    }
    if ($data['email_legal'] !== '' && !filter_var($data['email_legal'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'El correo del representante legal no tiene un formato válido.']);
        exit;
    }

    // 4) Validar longitudes máximas para evitar overflow
    $maxLengths = [
        'empresa' => 150, 'giro' => 100, 'web' => 255, 'calle' => 150,
        'cp' => 10, 'colonia' => 100, 'ciudad' => 100, 'telefonos' => 100,
        'email' => 100, 'nombre_contacto' => 100, 'celular' => 20,
        'rep_legal' => 100, 'cargo_legal' => 100, 'email_legal' => 100,
        'tel_oficina' => 20,
    ];
    foreach ($maxLengths as $field => $max) {
        if (mb_strlen($data[$field]) > $max) {
            echo json_encode(['success' => false, 'message' => "El campo '{$field}' excede la longitud máxima permitida ({$max} caracteres)."]);
            exit;
        }
    }

    // 5) Llamar al método de tu modelo
    $result = PracticasModel::saveOrganismoExterno($data);

    // 6) Si el modelo indica error, devolvemos mensaje y salimos
    if (empty($result['success']) || $result['success'] !== true) {
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Error al guardar los datos.'
        ]);
        exit;
    }

    // 7) Procesar archivos subidos
    // Tipos MIME permitidos: solo PDF e imágenes
    $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    // Extensiones permitidas (minúsculas)
    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];

    $savedFiles = [];
    if (!empty($_FILES['docs']['name']) && is_array($_FILES['docs']['name'])) {
        // Directorio destino basado en el ID generado por el modelo
        $baseDir = __DIR__ . '/../../uploads/' . $result['id'] . '/';
        if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true)) {
            throw new Exception('No se pudo crear el directorio de uploads: ' . $baseDir);
        }
        if (!is_writable($baseDir)) {
            throw new Exception('No hay permisos de escritura en el directorio: ' . $baseDir);
        }

        // Recorremos cada archivo usando la clave del array (el tipo de documento)
        foreach ($_FILES['docs']['name'] as $key => $originalName) {
            if ($_FILES['docs']['error'][$key] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['docs']['tmp_name'][$key];

                // ── Validación de extensión ──────────────────────────────
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts, true)) {
                    throw new Exception("Tipo de archivo no permitido: {$originalName}. Solo se aceptan PDF e imágenes (JPG, PNG, GIF, WEBP).");
                }

                // ── Validación de tipo MIME real (no confiamos en el cliente) ──
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeReal = $finfo->file($tmpName);
                if (!in_array($mimeReal, $allowedMimes, true)) {
                    throw new Exception("El archivo {$originalName} no es un PDF ni una imagen válida (MIME: {$mimeReal}).");
                }

                // ── Protección extra: doble extensión y nombres maliciosos ──
                $safeKey  = preg_replace('/[^a-z0-9_\-]/i', '_', $key);
                $newName  = $safeKey . '.' . $ext;
                $destino  = $baseDir . $newName;

                if (move_uploaded_file($tmpName, $destino)) {
                    $savedFiles[] = $newName;
                } else {
                    throw new Exception("Error al mover el archivo {$originalName} a {$destino}");
                }
            } else {
                throw new Exception("Error de subida en {$originalName}: código {$_FILES['docs']['error'][$key]}");
            }
        }
    }

    // 8) Respuesta final: todo OK
    echo json_encode([
        'success' => true,
        'message' => 'Datos y archivos guardados correctamente.',
        'id' => $result['id'],
        'files' => $savedFiles
    ]);

} catch (Exception $e) {
    // 9) Captura de excepciones
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
