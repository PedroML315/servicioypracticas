<?php

# Autoload composer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../controller/emails.php';
require_once __DIR__ . '/../model/notifications.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function generateRandomPassword($length = 10)
{
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

class FormsController
{

    public function ctrLogin()
    {
        if (!isset($_POST["email"], $_POST["password"]))
            return;

        $table = "users";
        $item = "email";
        $value = trim(filter_var($_POST["email"], FILTER_SANITIZE_EMAIL));
        $response = FormsModel::mdlShowUser($table, $item, $value);

        if ($response && password_verify($_POST["password"], $response["password"])) {
            session_start();
            $_SESSION["logged"] = true;
            $_SESSION["last_activity"] = time();
            unset($response["created_at"]);
            unset($response["password"]);
            if ($response['role'] === 'admin') {
                $response['tipo_servicio'] = FormsModel::mdlGetTipoServicioByUserId($response['id']);
            }
            $_SESSION["user"] = $response;
            echo 'success';
            return;
        } else {
            echo 'error';
        }
    }

    public function ctrLoginStudentService()
    {

        if (!isset($_POST["email"], $_POST["password"]))
            return;

        $student = FormsModel::mdlGetStudent($_POST['email']);
        if ($student && password_verify($_POST["password"], $student["password"])) {
            session_start();
            $_SESSION["logged"] = true;
            $_SESSION["last_activity"] = time();
            unset($student["password"]);
            $student['role'] = 'student';
            $_SESSION["user"] = $student;
            echo 'success';
        } else {
            echo 'error autenticando';
        }
    }

    public function ctrRegisterUser()
    {
        if (!isset($_POST["firstname"]))
            return;

        $table = "users";
        $data = [
            "firstname" => $_POST["firstname"],
            "lastname" => $_POST["lastname"],
            "email" => $_POST["email"],
            "password" => password_hash($_POST["password"], PASSWORD_DEFAULT),
            "role" => $_POST["role"],
            "single_event_type" => $_POST["single_event_type"] ?? false,
            "tipo_servicio" => $_POST["event_type"]
        ];
        $response = FormsModel::mdlRegisterUser($table, $data);
        $response = $response ? 'success' : 'error';

        sendDataToNewUser($_POST["firstname"] . ' ' . $_POST["lastname"], $_POST["email"], $_POST["password"], 'administrativo');
        echo json_encode($response);
    }

    // Simplified wrapper functions
    public function ctrGradeStudent($data)
    {
        return FormsModel::mdlGradeStudent($data);
    }

    public function ctrSearchAreas($idArea)
    {
        return FormsModel::mdlSearchAreas($idArea);
    }

    public function ctrEditArea($editArea, $nameArea)
    {
        return FormsModel::mdlEditArea($editArea, $nameArea);
    }

    public function ctrDeleteArea($deleteArea)
    {
        return FormsModel::mdlDeleteArea($deleteArea);
    }

    public function ctrAddArea($nameArea)
    {
        return FormsModel::mdlAddArea($nameArea);
    }

    public function ctrSearchEventTypes($idEventType, $tipo_servicio = null)
    {
        return FormsModel::mdlSearchEventTypes($idEventType, $tipo_servicio);
    }

    public function ctrEditEventType($id, $n, $a, $p, $b, $s)
    {
        return FormsModel::mdlEditEventTypes($id, $n, $a, $p, $b, $s);
    }

    public function ctrDeleteEventType($id)
    {
        return FormsModel::mdlDeleteEventTypes($id);
    }

    public function ctrAddEventType($n, $p, $b, $a, $s)
    {
        return FormsModel::mdlAddEventTypes($n, $p, $b, $a, $s);
    }

    public function ctrGetCourses($idCourse)
    {
        return FormsModel::mdlGetCourses($idCourse);
    }

    public function ctrAddCourse($n, $s, $e)
    {
        return FormsModel::mdlAddCourse($n, $s, $e);
    }

    public function ctrEditCourse($id, $n, $s, $e)
    {
        return FormsModel::mdlUpdateCourse($id, $n, $s, $e);
    }

    public function ctrDeleteCourse($id)
    {
        return FormsModel::mdlDeleteCourse($id);
    }

    public function ctrSearchStudents($student)
    {
        return FormsModel::mdlSearchStudents($student);
    }

    public function ctrEndSocialService($studentId)
    {
        $student = FormsModel::mdlSearchStudents($studentId);
        if (!$student)
            return;
        $degree = FormsModel::mdlSearchDegrees($student['idDegree']);
        FormsModelPDF::getAceptationCard($student, $degree);
        $response = FormsModelPDF::mdlEndSocialService($student, $degree);
        return $response;
    }

    public function ctrAcceptStudent($student)
    {
        if (FormsModel::mdlAcceptStudent($student) !== 'success')
            return;

        $password = generateRandomPassword();
        $cryptPass = password_hash($password, PASSWORD_DEFAULT);

        if (FormsModel::mdlAddPasswordStudent($cryptPass, $student) === 'success') {
            $studentData = FormsModel::mdlSearchStudents($student);
            if ($studentData) {
                sendPasswordToStudent($studentData["email"], $password);
                return 'success';
            } else {
                return 'error';
            }
        } else {
            return 'error';
        }
    }

    public function ctrDenegateStudent($id)
    {
        $student = FormsModel::mdlSearchStudents($id);
        if (!$student) {
            return 'student_not_found';
        }
        sendRejectServiceSocialApplication($student["email"], $student["firstname"] . ' ' . $student["lastname"] . ' ' . $student["lastnameMom"], 'Razón no especificada');
        return FormsModel::mdlDenegateStudent($id);
    }

    public function ctrDropStudent($id, $r)
    {
        $res = FormsModel::mdlDropStudent($id, $r);
        if ($res === 'success') {
            $student = FormsModel::mdlSearchStudents($id);
            if ($student) {
                $fullName = $student["firstname"] . ' ' . $student["lastname"] . ' ' . $student["lastnameMom"];
                sendDropStudentEmail($student["email"], $fullName, $r, date('Y-m-d'));
            }
        }
        return $res;
    }

    public function ctrGetEvents()
    {
        session_start();
        if ($_SESSION['user']['role'] === 'student') {
            $tipo_servicio = $_SESSION['user']['tipo_servicio'];
            $allEvents = FormsModel::mdlGetEvents();
            $filteredEvents = [];
            foreach ($allEvents as $event) {
                if (
                    $event['typeService'] == $tipo_servicio &&
                    strtotime($event['date']) > strtotime(date('Y-m-d'))
                ) {
                    $studentsRow = self::ctrStudentEvents($event['idEvent']);
                    $studentCount = $studentsRow ? (int) ($studentsRow['students'] ?? 0) : 0;
                    if ($studentCount < $event['vacancies_available']) {
                        $filteredEvents[] = $event;
                    }
                }
            }

            return array_values($filteredEvents);
        }
        return FormsModel::mdlGetEvents();
    }

    public static function ctrAddDegree($d)
    {
        return FormsModel::mdlAddDegree($d);
    }

    public static function ctrSearchDegrees($id)
    {
        return FormsModel::mdlSearchDegrees($id);
    }

    public static function ctrRegisterStudent($data)
    {
        $res = FormsModel::mdlRegisterStudent($data);
        if ($res === 'success') {
            $nombreCompleto = $data["nombre"] . ' ' . $data["apellidoPaterno"] . ' ' . $data["apellidoMaterno"];
            return sendServiceSocialApplicationReceived($data["correoInstitucional"], $nombreCompleto) === 'ok' ? 'successed' : 'error';
        }
        if ($res === 'duplicate' || $res === 'error')
            return 'duplicate';

        if ($data['type'] == 'empresa') {
            $password = generateRandomPassword();
            $cryptPass = password_hash($password, PASSWORD_DEFAULT);
            if (FormsModel::mdlAddPasswordStudent($cryptPass, $res) === 'success') {
                return sendServiceSocialInfo($data["correoInstitucional"], $password) === 'ok' ? 'success' : 'error';
            }
        }
        return 'error';
    }

    public static function ctrEditStudent($data)
    {
        return FormsModel::mdlEditStudent($data);
    }

    public static function ctrApplyEvent($e, $s)
    {
        $response = FormsModel::mdlApplyEvent($e, $s);

        $student = FormsModel::mdlSearchStudents($s);
        $event = FormsModel::mdlSearchEvents($e);
        $user = $event ? FormsModel::mdlSearchUsers($event['idUser']) : null;
        if ($student && $event && $user) {
            sendEventApplicationReceived($student['email'], $student['firstname'] . ' ' . $student['lastname'] . ' ' . $student['lastnameMom'], $event['eventName'], $event['date'], $user['firstname'] . ' ' . $user['lastname'], $user['email']);

        }
        return $response;
    }

    public static function ctrCheckApplicationEvent($e, $s)
    {
        return FormsModel::mdlCheckApplicationEvent($e, $s);
    }

    public static function ctrSearchEvents($id)
    {
        return FormsModel::mdlSearchEvents($id);
    }

    public static function ctrStudentEvents($id)
    {
        return FormsModel::mdlStudentEvents($id);
    }

    public static function ctrEventsCandidates($id)
    {
        return FormsModel::mdlEventsCandidates($id);
    }

    public static function ctrUsersToAreas($id)
    {
        return FormsModel::mdlUsersToAreas($id);
    }

    public static function ctrUpdateUsersToAreas($a, $u)
    {
        return FormsModel::mdlUpdateUsersToAreas($a, $u);
    }

    public static function ctrSearchUsers($id)
    {
        return FormsModel::mdlSearchUsers($id);
    }

    public static function ctrAcceptCandidate($s, $e, $u, $status)
    {
        if ($status == 1) {
            return FormsModel::mdlAcceptCandidate($s, $e, $u);
        }
        $res = FormsModel::mdlDeclineCandidate($s, $e, $u);
        if ($res === 'success') {
            $student = FormsModel::mdlSearchStudents($s);
            $event = FormsModel::mdlSearchEvents($e);
            if ($student && $event) {
                sendDeclineEventEmail(
                    $student['email'],
                    $student['firstname'] . ' ' . $student['lastname'] . ' ' . $student['lastnameMom'],
                    $event['eventName'],
                    $event['date']
                );
            }
        }
        return $res;
    }

    public static function ctrApproveEvent($s, $e, $u, $status)
    {
        if ($status == 1) {
            $points = FormsModel::mdlGetPointsEvent($e);
            $res = FormsModel::mdlApproveEvent($s, $e, $u, $points['points']);
            if ($res === 'success') {
                $student = FormsModel::mdlSearchStudents($s);
                $event = FormsModel::mdlSearchEvents($e);
                sendAproveEventEmail($student['email'], $student['firstname'] . ' ' . $student['lastname'] . ' ' . $student['lastnameMom'], $event['eventName'], $event['date'], $points['points']);
            }
            return $res;
        }
        $res = FormsModel::mdlDeclineEvent($s, $e, $u);
        if ($res === 'success') {
            $student = FormsModel::mdlSearchStudents($s);
            $event = FormsModel::mdlSearchEvents($e);
            sendDeclineEventEmail($student['email'], $student['firstname'] . ' ' . $student['lastname'] . ' ' . $student['lastnameMom'], $event['eventName'], $event['date']);
        }
        return $res;
    }

    public static function ctrStudentEventsPoints($id)
    {
        return FormsModel::mdlStudentEventsPoints($id);
    }

    public static function ctrEditDegree($d)
    {
        return FormsModel::mdlEditDegree($d);
    }

    public static function ctrDeleteDegree($id)
    {
        return FormsModel::mdlDeleteDegree($id);
    }

    public static function ctrGetNoAceptedStudents()
    {
        return FormsModel::mdlGetNoAceptedStudents();
    }

    public static function ctrGetNoAceptedStudentsPractice()
    {
        return FormsModel::mdlGetNoAceptedStudentsPractice();
    }

    public static function ctrRegisterStudentPracticas($data)
    {
        $response = FormsModel::mdlRegisterStudentPracticas($data);
        if ($response['status'] === 'success') {
            Notifications::addNotification(
                $_ENV['Current_ID_ADMIN'],
                'admin', // recipient
                'Se ha recibido una nueva solicitud de registro para prácticas profesionales.', // message
                'internship_students', // related table
                $response['id'], // related id if available
                2, // priority
                2  // status
            );
        }
        return $response;
    }

    public static function ctrAcceptStudentPractice($id)
    {
        $response = FormsModel::mdlAcceptStudentPractice($id);
        if ($response === 'success') {
            $password = generateRandomPassword();
            $cryptPass = password_hash($password, PASSWORD_DEFAULT);
            if (FormsModel::mdlAddPasswordStudentPractice($cryptPass, $id) === 'success') {
                $studentData = PracticasModel::mdlGetStudentPracticesById($id);
                if ($studentData) {
                    sendPracticasInfo($studentData["email"], $password);
                    return 'success';
                } else {
                    return 'error al enviar correo';
                }
            }
        }
    }

    public static function ctrResendStudentPracticeCredentials($id)
    {
        $password = generateRandomPassword();
        $cryptPass = password_hash($password, PASSWORD_DEFAULT);
        if (FormsModel::mdlAddPasswordStudentPractice($cryptPass, $id) === 'success') {
            $studentData = PracticasModel::mdlGetStudentPracticesById($id);
            if ($studentData) {
                sendPracticasInfo($studentData["email"], $password);
                return 'success';
            } else {
                return 'error al obtener datos del estudiante';
            }
        }
        return 'error al guardar contraseña';
    }

    public static function ctrDenegateStudentPractice($id)
    {
        return FormsModel::mdlDenegateStudentPractice($id);
    }

    public static function ctrGetActiveServiceTypes()
    {
        return FormsModel::mdlGetActiveServiceTypes();
    }

    public static function ctrSelectServiceType($serviceType, $idStudent)
    {
        return FormsModel::mdlSelectServiceActive($serviceType, $idStudent);
    }

}

class GESController
{
    public function ctrSearchStudentGES($matricula)
    {
        return [
            'student' => GESModel::mdlSearchStudentGES($matricula),
            'academic' => GESModel::mdlSearchAcademic($matricula),
            'extra' => GESModel::mdlDataExtra($matricula)
        ];
    }
}

class ServicioController
{
    public static function ctrGetTipoServicios()
    {
        return ServicioModel::mdlGetTipoServicios();
    }
}

class PracticasController
{

    public static function ctrGetNewOrganismosExternos()
    {
        return PracticasModel::mdlGetNewOrganismosExternos();
    }

    public static function ctrGetExternals()
    {
        return PracticasModel::mdlGetExternals();
    }

    public static function ctrGetNewsExternals()
    {
        $response = PracticasModel::mdlGetNewsExternals();
        return $response;
    }

    public static function ctrDisableExternal($id)
    {
        return PracticasModel::mdlDisableExternal($id);
    }

    /**
     * Rechaza un organismo externo, guardando los motivos y campos erróneos.
     * Genera un token, lo envía por correo y registra log.
     */
    public static function ctrRejectExternalWithReasons(int $orgId, string $motivoGeneral, array $camposRechazados, int $adminId, string $adminName): array
    {
        $external = PracticasModel::mdlGetExternals($orgId);
        if (!$external) {
            return ['status' => 'error', 'message' => 'Organismo no encontrado'];
        }

        // 1. Guardar rechazo y cambiar estado a 2
        $rechazoId = PracticasModel::mdlRejectOrganismo($orgId, $motivoGeneral, $adminId, $adminName);
        if (!$rechazoId) {
            return ['status' => 'error', 'message' => 'Error al registrar el rechazo'];
        }

        // 2. Guardar campos rechazados
        if (!PracticasModel::mdlSaveRechazoCampos($rechazoId, $camposRechazados)) {
            return ['status' => 'error', 'message' => 'Error al guardar los campos rechazados'];
        }

        // 3. Generar token seguro de 64 chars
        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        }
        $expiraAt = date('Y-m-d H:i:s', strtotime('+72 hours'));

        if (!PracticasModel::mdlCreateTokenCorreccion($rechazoId, $orgId, $token, $expiraAt)) {
            return ['status' => 'error', 'message' => 'Error al generar enlace de corrección'];
        }

        // 4. Enviar correo
        $enlaceCorreccion = "https://servicioypracticas.unimontrer.edu.mx/corregir-organismo/{$token}";
        $correoEnviado = sendOrganismoRechazado(
            $external['email'], 
            $external['empresa'], 
            $motivoGeneral, 
            $camposRechazados, 
            $enlaceCorreccion
        );

        // 5. Auditoría
        require_once __DIR__ . '/../model/LogModel.php';
        LogModel::log(
            $adminId,
            'reject',
            'organisms',
            "Rechazo de organismo #{$orgId} ({$external['empresa']}) con " . count($camposRechazados) . " campos a corregir.",
            ['org_id' => $orgId, 'campos' => $camposRechazados]
        );

        return [
            'status' => 'success',
            'message' => 'Organismo rechazado y correo enviado correctamente',
            'correo_enviado' => $correoEnviado !== false
        ];
    }

    /**
     * Marca un organismo externo como NO PROCEDENTE (rechazo definitivo).
     *
     * Cierra el proceso de vinculación: cambia el estado a 4, registra el
     * motivo y notifica al organismo que su solicitud no puede continuar.
     * No genera enlace de corrección.
     */
    public static function ctrRejectExternalNoProcedente(int $orgId, string $motivo, int $adminId, string $adminName): array
    {
        $external = PracticasModel::mdlGetExternals($orgId);
        if (!$external) {
            return ['status' => 'error', 'message' => 'Organismo no encontrado'];
        }

        // 1. Registrar el rechazo definitivo y cambiar estado a 4
        $rechazoId = PracticasModel::mdlMarcarOrganismoNoProcedente($orgId, $motivo, $adminId, $adminName);
        if (!$rechazoId) {
            return ['status' => 'error', 'message' => 'Error al registrar el rechazo'];
        }

        // 2. Notificar al organismo
        $correoEnviado = sendOrganismoNoProcedente(
            $external['email'],
            $external['empresa'],
            $motivo
        );

        // 3. Auditoría
        require_once __DIR__ . '/../model/LogModel.php';
        LogModel::log(
            $adminId,
            'reject',
            'organisms',
            "Organismo #{$orgId} ({$external['empresa']}) marcado como NO PROCEDENTE.",
            ['org_id' => $orgId, 'motivo' => $motivo]
        );

        return [
            'status' => 'success',
            'message' => 'Solicitud marcada como no procedente y organismo notificado.',
            'correo_enviado' => $correoEnviado !== false
        ];
    }

    /**
     * Aprobar el registro de un organismo.
     *
     * NUEVO FLUJO DE CONVENIOS: aceptar el registro ya NO activa la cuenta ni
     * envía credenciales. Genera el convenio con los datos del organismo y lo
     * envía por correo para su firma. La cuenta se activa hasta validar el
     * convenio firmado (ver ctrAprobarConvenioFinal).
     *
     * Se mantiene el nombre por retrocompatibilidad: todos los puntos de
     * entrada de "aceptar organismo" (companies.php, externals.php,
     * ajax.forms.php) quedan unificados en el nuevo flujo.
     */
    public static function ctrAcceptExternal($id)
    {
        return self::ctrGenerarConvenioOrganismo((int) $id);
    }

    /* =====================================================
     * Nuevo flujo de Convenios Institucionales
     * ===================================================== */

    /**
     * Aprobación inicial del registro: genera el convenio con los datos del
     * organismo, lo guarda, crea un enlace de un solo uso y lo envía por correo
     * (PDF adjunto). NO activa la cuenta ni envía credenciales todavía.
     *
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function ctrGenerarConvenioOrganismo(int $id): array
    {
        $org = PracticasModel::mdlGetExternals($id);
        if (!$org) {
            return ['success' => false, 'message' => 'Organismo no encontrado.'];
        }

        // 1) Generar el PDF personalizado a partir de la plantilla maestra.
        require_once __DIR__ . '/convenio_render.php';
        $cfg = convenioLoadConfig(__DIR__ . '/../config/convenio_config.json');
        if (!$cfg) {
            return ['success' => false, 'message' => 'No hay una plantilla de convenio configurada.'];
        }

        try {
            $dompdf = convenioRenderPdfForOrganismo($cfg, $org);
            $pdfBytes = $dompdf->output();
        } catch (\Throwable $e) {
            error_log('[ctrGenerarConvenioOrganismo] render: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al generar el convenio: ' . $e->getMessage()];
        }

        // 2) Guardar en uploads/{id}/convenio_generado_<hex>.pdf
        $baseDir = __DIR__ . '/../uploads/' . $id . '/';
        if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true)) {
            return ['success' => false, 'message' => 'No se pudo crear el directorio de uploads.'];
        }
        $filename = 'convenio_generado_' . bin2hex(random_bytes(4)) . '.pdf';
        $destino  = $baseDir . $filename;
        if ($pdfBytes === null || file_put_contents($destino, $pdfBytes) === false) {
            return ['success' => false, 'message' => 'No se pudo guardar el convenio generado.'];
        }

        // 3) Persistir estado 'generado'.
        if (!PracticasModel::mdlSetConvenioGenerado($id, $filename)) {
            return ['success' => false, 'message' => 'No se pudo registrar el convenio generado.'];
        }

        // 4) Token de un solo uso (72h) + enlace de firma.
        $token    = bin2hex(random_bytes(32));
        $expiraAt = date('Y-m-d H:i:s', strtotime('+72 hours'));
        if (!PracticasModel::mdlCreateTokenConvenio($id, $token, $expiraAt, 'firma')) {
            return ['success' => false, 'message' => 'No se pudo generar el enlace de firma.'];
        }
        $enlace   = "https://servicioypracticas.unimontrer.edu.mx/firmar-convenio/{$token}";
        $expiraEn = date('d/m/Y H:i', strtotime($expiraAt));

        // 5) Correo al organismo con el PDF adjunto.
        sendConvenioGeneradoOrganismo($org['email'], $org['empresa'], $enlace, $expiraEn, $destino);

        // 6) Auditoría.
        require_once __DIR__ . '/../model/LogModel.php';
        LogModel::log(
            $_SESSION['user']['id'] ?? 0,
            'generate',
            'organisms',
            "Convenio generado y enviado al organismo #{$id} ({$org['empresa']}).",
            ['org_id' => $id]
        );

        return ['success' => true, 'message' => 'Convenio generado y enviado al organismo para su firma.'];
    }

    /**
     * Validación final: aprueba el convenio firmado, activa la cuenta y envía
     * las credenciales de acceso.
     */
    public static function ctrAprobarConvenioFinal(int $id): array
    {
        $org = PracticasModel::mdlGetExternals($id);
        if (!$org) {
            return ['success' => false, 'message' => 'Organismo no encontrado.'];
        }
        if (($org['convenio_estado'] ?? '') !== 'firmado_pendiente') {
            return ['success' => false, 'message' => 'El convenio no está pendiente de validación.'];
        }

        // 1) Marcar validado + activar cuenta (isAcepted = 1).
        if (!PracticasModel::mdlSetConvenioValidadoFinal($id)) {
            return ['success' => false, 'message' => 'No se pudo validar el convenio.'];
        }

        // 2) Generar y guardar credenciales.
        $password  = generateRandomPassword();
        $cryptPass = password_hash($password, PASSWORD_DEFAULT);
        if (!PracticasModel::mdlAddPasswordExternal($cryptPass, $id)) {
            return ['success' => false, 'message' => 'Convenio validado, pero no se pudieron generar las credenciales.'];
        }

        $empresa = $org['empresa'] ?? '';

        // 3) Enviar bienvenida + credenciales.
        sendPracticasOrganismoExternoInfo($org['email'], $password, $empresa);

        // 4) Invalidar cualquier token de firma pendiente.
        require_once __DIR__ . '/../model/LogModel.php';
        LogModel::log(
            $_SESSION['user']['id'] ?? 0,
            'approve',
            'organisms',
            "Convenio validado y cuenta activada para el organismo #{$id} ({$org['empresa']}).",
            ['org_id' => $id]
        );

        return ['success' => true, 'message' => 'Convenio aprobado. Se activó la cuenta y se enviaron las credenciales.'];
    }

    /**
     * Rechazo del convenio firmado: guarda el motivo, crea un nuevo enlace de
     * un solo uso y notifica al organismo para que reenvíe una versión corregida.
     */
    public static function ctrRechazarConvenioFirmado(int $id, string $motivo): array
    {
        $org = PracticasModel::mdlGetExternals($id);
        if (!$org) {
            return ['success' => false, 'message' => 'Organismo no encontrado.'];
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            return ['success' => false, 'message' => 'Debes indicar el motivo del rechazo.'];
        }

        // 1) Guardar motivo + estado 'firmado_rechazado'.
        if (!PracticasModel::mdlSetConvenioRechazado($id, $motivo)) {
            return ['success' => false, 'message' => 'No se pudo registrar el rechazo.'];
        }

        // 2) Nuevo token de un solo uso (recarga) + enlace.
        $token    = bin2hex(random_bytes(32));
        $expiraAt = date('Y-m-d H:i:s', strtotime('+72 hours'));
        if (!PracticasModel::mdlCreateTokenConvenio($id, $token, $expiraAt, 'recarga')) {
            return ['success' => false, 'message' => 'No se pudo generar el nuevo enlace de carga.'];
        }
        $enlace   = "https://servicioypracticas.unimontrer.edu.mx/firmar-convenio/{$token}";
        $expiraEn = date('d/m/Y H:i', strtotime($expiraAt));

        // 3) Correo al organismo con el motivo y el nuevo enlace.
        sendConvenioRechazadoOrganismo($org['email'], $org['empresa'], $motivo, $enlace, $expiraEn);

        require_once __DIR__ . '/../model/LogModel.php';
        LogModel::log(
            $_SESSION['user']['id'] ?? 0,
            'reject',
            'organisms',
            "Convenio firmado rechazado para el organismo #{$id} ({$org['empresa']}).",
            ['org_id' => $id, 'motivo' => $motivo]
        );

        return ['success' => true, 'message' => 'Convenio rechazado. Se notificó al organismo con un nuevo enlace de carga.'];
    }

    public static function ctrLoginOrganismoReceptor()
    {
        if (!isset($_POST["email"], $_POST["password"]))
            return;

        $response = PracticasModel::mdlShowUsersPP("organismos_externos", "email", $_POST["email"]);

        if ($response && !empty($response["password"]) && password_verify($_POST["password"], $response["password"])) {
            // La cuenta solo se habilita cuando el convenio ha sido validado.
            if ((int) ($response['isAcepted'] ?? 0) !== 1) {
                echo 'error La cuenta aún no está activa. Se activará al validar tu convenio firmado.';
                return;
            }
            session_start();
            $_SESSION["logged"] = true;
            $_SESSION["last_activity"] = time();
            unset($response["password"]);
            $response['role'] = 'organismo_externo';
            $_SESSION["user"] = $response;
            echo 'success';
        } else {
            echo 'error Contraseña o correo incorrecto';
        }
    }

    public static function ctrLoginAlumnoPracticas()
    {
        if (!isset($_POST["email"], $_POST["password"]))
            return;

        $response = PracticasModel::mdlShowUsersPP("students_practicas", "email", $_POST["email"]);

        if ($response && password_verify($_POST["password"], $response["password"])) {
            session_start();
            $_SESSION["logged"] = true;
            $_SESSION["last_activity"] = time();
            unset($response["password"]);
            $response['role'] = 'alumno_practicas';
            $_SESSION["user"] = $response;
            echo 'success';
        } else {
            echo 'error Contraseña o correo incorrecto';
        }
    }

    public static function solicitarPracticas($data)
    {
        $response = PracticasModel::mdlSolicitarPracticas($data);
        if ($response['success']) {
            if (!empty($data['habilidades'])) {
                PracticasModel::mdlSaveSolicitudHabilidades($response['id'], $data['habilidades']);
            }
            $organismo = PracticasModel::mdlGetExternals($data['organismo_externo_id']);
            sendSolicitudPracticas($_ENV['Current_Email'], $organismo['nombre_contacto'], $organismo['empresa'], $data['direccionPractica'], $data['actividades']);
            Notifications::addNotification($_ENV['Current_ID_ADMIN'], 'admin', 'Nueva solicitud de prácticas recibida.', null, null, 2, 2);
        }
        return $response;
    }

    public static function getSolicitudesPracticas($id)
    {
        $response = PracticasModel::mdlGetSolicitudesPracticas($id);
        if ($response) {
            foreach ($response as $key => $value) {
                $response[$key]['prospects'] = PracticasModel::mdlGetProspectsBySolicitud($value['id']);
            }
        }
        return $response;
    }

    public static function deleteSolicitudPractica($id)
    {
        return PracticasModel::mdlDeleteSolicitudPractica($id);
    }

    public static function getSolicitudPracticaById($id)
    {
        $solicitud = PracticasModel::mdlGetSolicitudPracticaById($id);
        if ($solicitud) {
            $solicitud['habilidades'] = PracticasModel::mdlGetHabilidadesBySolicitud($id);
        }
        return $solicitud;
    }

    public static function updateSolicitudPractica($data)
    {
        $response = PracticasModel::mdlUpdateSolicitudPractica($data);
        if (!empty($response['success']) && isset($data['habilidades'])) {
            PracticasModel::mdlSaveSolicitudHabilidades($data['idSolicitud'], $data['habilidades']);
        }
        return $response;
    }

    /**
     * Nombre legible del perfil de una vacante: habilidades del nuevo modelo
     * o, en vacantes legadas, la licenciatura solicitada.
     */
    private static function perfilSolicitud($solicitud)
    {
        $habilidades = PracticasModel::mdlGetHabilidadesBySolicitud($solicitud['id']);
        if ($habilidades) {
            $nombres = array_column($habilidades, 'nombre');
            $resumen = implode(', ', array_slice($nombres, 0, 4));
            if (count($nombres) > 4) {
                $resumen .= ' y ' . (count($nombres) - 4) . ' más';
            }
            return $resumen;
        }
        return $solicitud['licenciatura'] ?: 'perfil general';
    }

    public static function ctrGetStudentsPractices()
    {
        $response = PracticasModel::mdlGetStudentsPractices();
        return $response;
    }

    public static function ctrGetStudentPracticesById($idStudent)
    {
        $response = PracticasModel::mdlGetStudentPracticesById($idStudent);
        return $response;
    }

    public static function ctrUpdateStudentPractices($data)
    {
        $response = PracticasModel::mdlUpdateStudentPractices($data);
        return $response;
    }

    public static function ctrDisableStudentPractices($idStudent)
    {
        $response = PracticasModel::mdlDisableStudentPractices($idStudent);
        return $response;
    }

    public static function ctrGetPracticesSolicitudes()
    {
        $response = PracticasModel::mdlGetPracticesSolicitudes();
        return $response;
    }

    public static function ctrGetPractices($idStudent)
    {
        $response = PracticasModel::mdlGetPractices($idStudent);
        return $response;
    }

    public static function ctrIsStudentRegisteredInPractices($idStudent)
    {
        $response = PracticasModel::mdlIsStudentRegisteredInPractices($idStudent);
        return $response;
    }

    /**
     * FASE 6 · Prepostulación del alumno a una vacante.
     * $prepost = respuestas del formulario de prepostulación (obligatorio).
     * Los correos a la empresa se agregan en la Etapa 5 (revisión del usuario).
     */
    public static function ctrApplyForPractice($idPractice, $idStudent, ?array $prepost = null)
    {
        $res = PracticasModel::mdlApplyForPractice($idPractice, $idStudent, $prepost);
        if (($res['success'] ?? false) === true && $prepost) {
            $sol = PracticasModel::mdlGetSolicitudPracticaById($idPractice);
            $org = $sol ? PracticasModel::mdlGetExternals($sol['organismo_externo_id']) : null;
            $stud = PracticasModel::mdlGetStudentPracticesById($idStudent);
            if ($org && !empty($org['email'])) {
                $empresa = $org['empresa'] ?? '';
                sendPrepostulacionEmpresa(
                    $org['email'],
                    $org['nombre_contacto'] ?? '',
                    $stud['nombre_completo'] ?? '',
                    $stud['matricula'] ?? '',
                    'Vacante en ' . $empresa,
                    $empresa,
                    self::buildRespuestasPrepostHtml($prepost),
                    'https://servicioypracticas.unimontrer.edu.mx/'
                );
                Notifications::addNotification(
                    $org['id'],
                    'organismo_externo',
                    'Nueva prepostulación de ' . ($stud['nombre_completo'] ?? 'un alumno') . '.',
                    'students_in_practices',
                    null,
                    2,
                    2
                );
            }
        }
        return $res;
    }

    /** FASE 6 · Construye una tabla HTML con las respuestas de la prepostulación (para el correo a la empresa). */
    private static function buildRespuestasPrepostHtml(array $d): string
    {
        $labels = [
            'licenciatura' => 'Licenciatura',
            'disponibilidad_horario' => 'Disponibilidad de horario',
            'modalidad' => 'Modalidad',
            'nivel_office' => 'Nivel de Office',
            'herramientas' => 'Herramientas',
            'herramientas_otro' => 'Otras herramientas',
            'nivel_ingles' => 'Nivel de inglés',
            'equipo_remoto' => 'Equipo para modalidad remota',
            'disponibilidad_inicio' => 'Disponibilidad de inicio',
            'area_interes' => 'Área de interés',
            'acepta_capacitacion' => 'Acepta capacitación previa',
            'objetivo_practicas' => 'Objetivo principal',
            'modalidad_entrevista_pref' => 'Entrevista preferida',
            'horario_propuesto' => 'Horario propuesto',
        ];
        $rows = '';
        foreach ($labels as $k => $label) {
            $v = $d[$k] ?? '';
            if (is_array($v)) {
                $v = implode(', ', $v);
            }
            $v = trim((string) $v);
            if ($v === '') {
                continue;
            }
            $rows .= '<tr><td style="padding:6px 10px;font-weight:bold;border:1px solid #e2e8f0;background:#f8fafc;">'
                . htmlspecialchars($label) . '</td><td style="padding:6px 10px;border:1px solid #e2e8f0;">'
                . htmlspecialchars($v) . '</td></tr>';
        }
        return '<table style="border-collapse:collapse;width:100%;font-size:14px;">' . $rows . '</table>';
    }

    /* =========================================================================
     * FASE 6 · Controladores del nuevo flujo de postulación
     *  (transiciones de estado; los correos se cablean en la Etapa 5)
     * ===================================================================== */

    /** El organismo rechaza la prepostulación (antes de entrevista). */
    public static function ctrRechazarPrepostulacion($idSolicitud, $idStudent, $motivo)
    {
        $res = PracticasModel::mdlRechazarPrepostulacion($idSolicitud, $idStudent, $motivo);
        if (($res['success'] ?? false) === true) {
            $sol = PracticasModel::mdlGetSolicitudPracticaById($idSolicitud);
            $org = $sol ? PracticasModel::mdlGetExternals($sol['organismo_externo_id']) : null;
            $stud = PracticasModel::mdlGetStudentPracticesById($idStudent);
            if ($stud) {
                sendPracticasProspectRejectedWithReason($stud['email'], $stud['nombre_completo'], $org['empresa'] ?? '', $motivo);
            }
        }
        return $res;
    }

    /** El organismo acepta para entrevista y programa la agenda. */
    public static function ctrProgramarEntrevista($data)
    {
        $res = PracticasModel::mdlProgramarEntrevista(
            $data['idPractica'],
            $data['idStudent'],
            [
                'fecha'      => $data['fecha'],
                'hora'       => $data['hora'],
                'modalidad'  => $data['modalidad'],
                'url_sesion' => $data['url_sesion'] ?? null,
                'direccion'  => $data['direccion'] ?? null,
            ],
            $data['createdBy'] ?? null
        );

        if (($res['success'] ?? false) === true) {
            // FASE 6 · Generar la carta de presentación (SIN vigencia) y persistir el PDF,
            // para poder adjuntarla en los correos de la entrevista.
            require_once __DIR__ . '/practices/cartaPresentacionGenerator.php';
            $sol = PracticasModel::mdlGetSolicitudPracticaById($data['idPractica']);
            $org = $sol ? PracticasModel::mdlGetExternals($sol['organismo_externo_id']) : null;
            $cartaPath = generarCartaPresentacionPP([
                'idStudent'         => $data['idStudent'],
                'idPractica'        => $data['idPractica'],
                'empresa'           => $org['empresa'] ?? '',
                'cargoResponsable'  => '',
                'nombreResponsable' => $sol['nombre_responsable'] ?? ($org['nombre_contacto'] ?? ''),
                'domicilio'         => $sol['direccion_practica'] ?? '',
                'stream'            => false,
            ]);

            // Correos de la entrevista (con la carta adjunta)
            $stud = PracticasModel::mdlGetStudentPracticesById($data['idStudent']);
            $ent = PracticasModel::mdlGetEntrevistaProgramada($data['idPractica'], $data['idStudent']);
            $attach = ($cartaPath && is_file($cartaPath)) ? [$cartaPath] : [];
            $empresa = $org['empresa'] ?? '';
            $fecha = $ent['fecha'] ?? ($data['fecha'] ?? '');
            $hora = isset($ent['hora']) ? substr((string) $ent['hora'], 0, 5) : substr((string) ($data['hora'] ?? ''), 0, 5);
            $modalidad = $ent['modalidad'] ?? ($data['modalidad'] ?? '');

            if ($modalidad === 'Virtual') {
                $url = $ent['url_sesion'] ?? ($data['url_sesion'] ?? '');
                $detalle = '<li><strong>Enlace de la sesión:</strong> <a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></li>';
            } else {
                $detalle = '<li><strong>Dirección:</strong> ' . htmlspecialchars($ent['direccion'] ?? ($data['direccion'] ?? '')) . '</li>';
            }

            if ($stud && !empty($stud['email'])) {
                sendEntrevistaProgramadaAlumno($stud['email'], $stud['nombre_completo'] ?? '', $empresa, $fecha, $hora, $modalidad, $detalle, $attach);
            }
            if ($org && !empty($org['email'])) {
                if ($modalidad === 'Virtual') {
                    sendEntrevistaVirtualEmpresa($org['email'], $org['nombre_contacto'] ?? '', $stud['nombre_completo'] ?? '', $fecha, $hora, $ent['url_sesion'] ?? '', $attach);
                } else {
                    // Recordatorio de entrevista presencial a la empresa (con la carta adjunta)
                    $direccion = $ent['direccion'] ?? ($data['direccion'] ?? '');
                    sendEntrevistaPresencialEmpresa($org['email'], $org['nombre_contacto'] ?? '', $stud['nombre_completo'] ?? '', $fecha, $hora, $direccion, $attach);
                }
            }
        }
        return $res;
    }

    /** El organismo cierra la entrevista y registra la evaluación. */
    public static function ctrCerrarEntrevista($data)
    {
        return PracticasModel::mdlCerrarEntrevista(
            $data['idPractica'],
            $data['idStudent'],
            [
                'llego_a_tiempo'          => $data['llego_a_tiempo'] ?? 0,
                'llego_formal'            => $data['llego_formal'] ?? 0,
                'calificacion_respuestas' => $data['calificacion_respuestas'] ?? 0,
                'comentarios'             => $data['comentarios'] ?? null,
            ]
        );
    }

    /** Resultado final: aceptar al alumno en la práctica. */
    public static function ctrDecisionFinalAceptar($idSolicitud, $idStudent, $fechaInicio, $motivo)
    {
        $res = PracticasModel::mdlDecisionFinalAceptar($idSolicitud, $idStudent, $fechaInicio, $motivo);
        if (($res['success'] ?? false) === true) {
            $sol = PracticasModel::mdlGetSolicitudPracticaById($idSolicitud);
            $org = $sol ? PracticasModel::mdlGetExternals($sol['organismo_externo_id']) : null;
            $stud = PracticasModel::mdlGetStudentPracticesById($idStudent);
            if ($stud) {
                sendPracticasProspectAcceptedWithReason(
                    $stud['email'],
                    $stud['nombre_completo'],
                    $org['empresa'] ?? '',
                    $motivo,
                    dateConfigurated($fechaInicio)
                );
            }
        }
        return $res;
    }

    /** Resultado final: rechazar al alumno tras la entrevista. */
    public static function ctrDecisionFinalRechazar($idSolicitud, $idStudent, $motivo)
    {
        $res = PracticasModel::mdlDecisionFinalRechazar($idSolicitud, $idStudent, $motivo);
        if (($res['success'] ?? false) === true) {
            $sol = PracticasModel::mdlGetSolicitudPracticaById($idSolicitud);
            $org = $sol ? PracticasModel::mdlGetExternals($sol['organismo_externo_id']) : null;
            $stud = PracticasModel::mdlGetStudentPracticesById($idStudent);
            if ($stud) {
                sendPracticasProspectRejectedWithReason($stud['email'], $stud['nombre_completo'], $org['empresa'] ?? '', $motivo);
            }
        }
        return $res;
    }

    public static function ctrNewSolicitudPractices()
    {
        $response = PracticasModel::mdlNewSolicitudesPracticantes();
        return $response;
    }

    public static function ctrGetAllActiveSolicitudesPracticantes()
    {
        return PracticasModel::mdlGetAllActiveSolicitudesPracticantes();
    }

    public static function ctrSearchPractices($idPractices)
    {
        $response = PracticasModel::mdlSearchPractices($idPractices);
        return $response;
    }

    public static function ctrAcceptSolicitudPracticante($id)
    {
        $response = PracticasModel::mdlAcceptSolicitudPracticante($id);
        if ($response === 'success') {
            $solicitud = PracticasModel::mdlGetSolicitudPracticanteById($id);
            $org = PracticasModel::mdlGetExternals($solicitud['organismo_externo_id']);
            if ($solicitud) {
                $perfil = self::perfilSolicitud($solicitud);
                sendSolicitudPracticasAceptada($org['email'], $org['nombre_contacto'], $perfil);
                Notifications::addNotification(
                    $solicitud['organismo_externo_id'],
                    'organismo_externo',
                    'La solicitud de practicantes de ' . $perfil . ' ha sido aceptada.',
                    'students_in_practices',
                    null,
                    2,
                    2
                );
            }
        }
        return $response;
    }

    public static function ctrRejectSolicitudPracticante($idSolicitud, $motivo = '')
    {
        $response = PracticasModel::mdlRejectSolicitudPracticante($idSolicitud);
        if ($response === 'success') {
            $solicitud = PracticasModel::mdlGetSolicitudPracticanteById($idSolicitud);
            $org = PracticasModel::mdlGetExternals($solicitud['organismo_externo_id']);
            if ($solicitud) {
                $perfil = self::perfilSolicitud($solicitud);
                sendSolicitudPracticasRechazada($org['email'], $org['nombre_contacto'], $perfil, $motivo);
                Notifications::addNotification(
                    $solicitud['organismo_externo_id'],
                    'organismo_externo',
                    'La solicitud de practicantes de ' . $perfil . ' ha sido rechazada.',
                    'students_in_practices',
                    null,
                    2,
                    2
                );
            }
        }
        return $response;
    }

    public static function ctrAceptarProspecto($idSolicitud, $idStudent, $fechaInicio, $motivo)
    {
        // 1. Verificar cupo antes de aceptar (doble validación)
        $sol = PracticasModel::mdlGetSolicitudPracticaById($idSolicitud);
        $acceptedCount = PracticasModel::mdlGetAcceptedCount($idSolicitud);
        $numPracticantes = (int) ($sol['num_practicantes'] ?? 0);

        if ($acceptedCount >= $numPracticantes) {
            return ['success' => false, 'message' => 'Se ha llenado el cupo de vacantes para esta práctica.'];
        }

        $res = PracticasModel::mdlAceptarProspecto($idSolicitud, $idStudent, $fechaInicio, $motivo);
        if (isset($res['success']) && $res['success'] === true) {
            $stud = PracticasModel::mdlGetStudentPracticesById($idStudent);
            if ($stud) {
                sendPracticasProspectAcceptedWithReason(
                    $stud['email'],
                    $stud['nombre_completo'],
                    $sol['organismo'] ?? '',
                    $motivo,
                    dateConfigurated($fechaInicio)
                );
            }
            if (!empty($sol['email_contacto'])) {
                sendPracticasProspectAcceptedOrg(
                    $sol['email_contacto'],
                    $sol['nombre_contacto'] ?? '',
                    $stud['nombre_completo'],
                    dateConfigurated($fechaInicio)
                );
            }

            // 2. Comprobar si tras esta aceptación se llenó el cupo
            $acceptedCountPost = PracticasModel::mdlGetAcceptedCount($idSolicitud);

            if ($acceptedCountPost >= $numPracticantes) {
                // Rechazar automáticamente a todos los pendientes
                $pendingStudents = PracticasModel::mdlGetPendingStudentsByPractice($idSolicitud);

                foreach ($pendingStudents as $pending) {
                    self::ctrRechazarProspecto($idSolicitud, $pending['idStudent'], 'Se llenó el cupo');
                }
            }
        }
        return $res;
    }

    public static function ctrConfirmarPresentacion($idStudent, $idOrganismo)
    {
        $response = PracticasModel::mdlConfirmarPresentacion($idStudent, $idOrganismo);
        if ($response['success']) {
            $studentData = PracticasModel::mdlGetStudentPracticesById($idStudent);
            if ($studentData) {
                sendPpCartaPresentacionConfirmada($studentData['email'], $studentData['nombre_completo']);
            }
        }
        return $response;
    }

    public static function ctrRechazarProspecto($idSolicitud, $idStudent, $motivo)
    {
        $res = PracticasModel::mdlRechazarProspecto($idSolicitud, $idStudent, $motivo);
        if (isset($res['success']) && $res['success'] === true) {
            $sol = PracticasModel::mdlGetSolicitudPracticaById($idSolicitud);
            $stud = PracticasModel::mdlGetStudentPracticesById($idStudent);
            if ($stud) {
                sendPracticasProspectRejectedWithReason(
                    $stud['email'],
                    $stud['nombre_completo'],
                    $sol['organismo'] ?? '',
                    $motivo
                );
            }
        }
        return $res;
    }

    public static function ctrEvaluarEntrevista($data)
    {
        return PracticasModel::mdlEvaluarEntrevista($data);
    }

    public static function ctrCheckAssistance($idPractica, $idStudent)
    {
        $response = PracticasModel::mdlCheckAssistance($idPractica, $idStudent);
        return $response;
    }

    public static function ctrGetPartialReport($idPractica, $idStudent)
    {
        $response = PracticasModel::mdlGetPartialReport($idPractica, $idStudent);
        return $response;
    }

    public static function ctrGetFinalReport($idPractica, $idStudent)
    {
        $response = PracticasModel::mdlGetFinalReport($idPractica, $idStudent);
        return $response;
    }

    public static function ctrRegisterAttendance($data)
    {
        $response = PracticasModel::mdlRegisterAttendance($data);
        if ($response['success']) {
            $organismo = PracticasModel::mdlGetExternals($data['idOrganismo']);
            if ($organismo) {
                $student = PracticasModel::mdlGetStudentPracticesById($data['idStudent']);
                if ($student) {
                    sendAssistanceRegisteredEmail($organismo['email'], $student['nombre_completo'], $data['fechaAsistencia'], $organismo['empresa'], $data['horaEntrada'], $data['horaSalida'], $data['actividad']);
                }
            }
        }
        return $response;
    }

    public static function getAssistancesPractices($idOrganismo)
    {
        $response = PracticasModel::mdlGetAssistancesPractices($idOrganismo);
        return $response;
    }

    public static function getAllPractices($idOrganismo)
    {
        $response = PracticasModel::mdlGetAllPractices($idOrganismo);
        return $response;
    }

    public static function getDataPracticesStudent($idOrganismo, $matricula)
    {
        $response = PracticasModel::mdlGetDataPracticesStudent($idOrganismo, $matricula);
        return $response;
    }

    public static function solicitarCapacitacion($idOrganismo, $matricula, $solicitud)
    {
        $response = PracticasModel::mdlSolicitarCapacitacion($idOrganismo, $matricula, $solicitud);
        if ($response['success']) {
            $student = PracticasModel::mdlGetStudentPracticesByMatricula($matricula);
            $studentName = $student ? $student['nombre_completo'] : '';

            // Usar el correo configurado en general_settings.json; si no existe, usar el del .env
            $gsPath = __DIR__ . '/../config/general_settings.json';
            $gsEmail = $_ENV['Current_Email'];
            if (file_exists($gsPath)) {
                $gsData = json_decode(file_get_contents($gsPath), true);
                if (!empty($gsData['email_capacitacion'])) {
                    $gsEmail = $gsData['email_capacitacion'];
                }
            }

            sendSolicitudCapacitacion($gsEmail, $studentName, $matricula, $solicitud);
            Notifications::addNotification(
                $_ENV['Current_ID_ADMIN'],
                'admin',
                'Nueva solicitud de capacitación recibida.',
                null,
                null,
                2,
                2
            );
        }
        return $response;
    }

    /* ═══════════════ Reportes de incidencias de practicantes ═══════════════ */

    /** Etiquetas legibles usadas en correos y notificaciones. */
    public const INCIDENCIA_TIPOS = [
        'inasistencias'  => 'Faltas o retardos',
        'conducta'       => 'Conducta o actitud inadecuada',
        'desempeno'      => 'Bajo desempeño en sus actividades',
        'incumplimiento' => 'Incumplimiento de reglas o políticas',
        'seguridad'      => 'Riesgo de seguridad o daño',
        'otro'           => 'Otro',
    ];
    public const INCIDENCIA_GRAVEDADES = [
        'baja'  => 'Baja — se puede corregir con una llamada de atención',
        'media' => 'Media — requiere intervención de la Universidad',
        'alta'  => 'Alta — afecta gravemente la operación',
    ];
    public const INCIDENCIA_ACCIONES = [
        'orientacion' => 'Que la Universidad oriente al alumno',
        'reunion'     => 'Reunión entre empresa, alumno y Universidad',
        'baja'        => 'Solicitar la BAJA del practicante',
    ];

    /**
     * El organismo externo levanta un reporte de incidencia sobre un practicante.
     * Guarda el reporte, avisa por correo al administrador y manda acuse a la empresa.
     */
    public static function ctrReportarIncidencia($idOrganismo, array $data)
    {
        require_once __DIR__ . '/../model/LogModel.php';

        // ── Seguridad: el alumno debe pertenecer a este organismo ──
        $practicante = PracticasModel::mdlGetPracticanteDeOrganismo($idOrganismo, $data['idStudent']);
        if (!$practicante) {
            return ['success' => false, 'message' => 'El practicante no pertenece a tu organismo.'];
        }

        // ── Anti-duplicado: un reporte por alumno por hora ──
        if (PracticasModel::mdlContarIncidenciasRecientes($idOrganismo, $data['idStudent'], 1) > 0) {
            return [
                'success' => false,
                'message' => 'Ya enviaste un reporte de este practicante hace menos de una hora. '
                           . 'Espera la respuesta del administrador antes de enviar otro.'
            ];
        }

        $response = PracticasModel::mdlCrearReporteIncidencia([
            'idOrganismo'       => $idOrganismo,
            'idStudent'         => $data['idStudent'],
            'idPractica'        => $practicante['idPractica'] ?? null,
            'matricula'         => $practicante['matricula'] ?? null,
            'tipo'              => $data['tipo'],
            'gravedad'          => $data['gravedad'],
            'fecha_incidente'   => $data['fecha_incidente'],
            'descripcion'       => $data['descripcion'],
            'acciones_tomadas'  => $data['acciones_tomadas'],
            'accion_solicitada' => $data['accion_solicitada'],
        ]);

        if (empty($response['success'])) {
            return $response;
        }

        $idIncidencia  = $response['id'] ?? 0;
        $studentName   = $practicante['nombre_completo'] ?? '';
        $empresa       = $practicante['empresa'] ?? '';
        $contactName   = $practicante['nombre_contacto'] ?? '';
        $contactEmail  = $practicante['org_email'] ?? '';
        $tipoLabel     = self::INCIDENCIA_TIPOS[$data['tipo']] ?? $data['tipo'];
        $gravedadLabel = self::INCIDENCIA_GRAVEDADES[$data['gravedad']] ?? $data['gravedad'];
        $accionLabel   = self::INCIDENCIA_ACCIONES[$data['accion_solicitada']] ?? $data['accion_solicitada'];
        $pideBaja      = $data['accion_solicitada'] === 'baja';

        $htmlDeTexto = function (string $txt): string {
            return nl2br(htmlspecialchars($txt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        };

        $vars = [
            'idIncidencia'     => $idIncidencia,
            'studentName'      => $studentName,
            'matricula'        => $practicante['matricula'] ?? '',
            'empresa'          => $empresa,
            'contactName'      => $contactName,
            'contactEmail'     => $contactEmail,
            'tipo'             => $tipoLabel,
            'gravedad'         => $gravedadLabel,
            'accionSolicitada' => $accionLabel,
            'fechaIncidente'   => $data['fecha_incidente']
                ? date('d/m/Y', strtotime($data['fecha_incidente']))
                : 'No especificada',
            'fechaReporte'     => date('d/m/Y H:i'),
            'descripcion'      => $data['descripcion'],
            'descripcionHtml'  => $htmlDeTexto($data['descripcion']),
            'accionesHtml'     => $data['acciones_tomadas']
                ? '<h3 style="color:#01643D;margin-top:18px;">Acciones ya tomadas por la empresa</h3>'
                  . '<p style="background:#f8fafc;border-left:4px solid #94a3b8;padding:10px 14px;">'
                  . $htmlDeTexto($data['acciones_tomadas']) . '</p>'
                : '',
        ];

        // Correo al administrador (área de Prácticas Profesionales)
        $gsPath  = __DIR__ . '/../config/general_settings.json';
        $gsEmail = ppGetAdminEmail();
        if (file_exists($gsPath)) {
            $gsData = json_decode(file_get_contents($gsPath), true);
            if (!empty($gsData['email_pp'])) {
                $gsEmail = $gsData['email_pp'];
            }
        }
        if ($gsEmail) {
            sendReporteIncidenciaAdmin($gsEmail, $vars);
        }

        // Acuse de recibo al organismo
        if ($contactEmail) {
            sendReporteIncidenciaConfirmacion($contactEmail, $vars);
        }

        Notifications::addNotification(
            $_ENV['Current_ID_ADMIN'],
            'admin',
            ($pideBaja ? 'BAJA solicitada: ' : 'Reporte de incidencia: ')
                . $empresa . ' reportó a ' . $studentName . ' (' . $tipoLabel . ').',
            null,
            null,
            2,
            $pideBaja || $data['gravedad'] === 'alta' ? 3 : 2
        );

        LogModel::log(
            LogModel::ACTION_CREATE,
            LogModel::MODULE_PRACTICES,
            null,
            "Reporte de incidencia #{$idIncidencia} de {$empresa} sobre {$studentName} ({$tipoLabel}).",
            [
                'idIncidencia'      => $idIncidencia,
                'idOrganismo'       => $idOrganismo,
                'idStudent'         => $data['idStudent'],
                'tipo'              => $data['tipo'],
                'gravedad'          => $data['gravedad'],
                'accion_solicitada' => $data['accion_solicitada'],
            ]
        );

        $response['message'] = $pideBaja
            ? 'Reporte enviado. El administrador revisará la solicitud de baja y se pondrá en contacto contigo.'
            : 'Reporte enviado. El administrador lo revisará y se pondrá en contacto contigo.';

        return $response;
    }

    public static function ctrGetReportesIncidencia($idOrganismo = null)
    {
        return PracticasModel::mdlGetReportesIncidencia($idOrganismo);
    }

    /* ═══════════ Seguimiento administrativo de incidencias ═══════════ */

    public const INCIDENCIA_ESTADOS = [
        0 => 'Pendiente',
        1 => 'En proceso',
        2 => 'Atendida',
    ];

    /** Convierte texto plano capturado por el admin en HTML seguro para el correo. */
    private static function incidenciaTextoHtml(string $txt): string
    {
        return nl2br(htmlspecialchars($txt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /** Listado + resumen por estado para el panel del administrador. */
    public static function ctrGetIncidenciasAdmin(): array
    {
        return [
            'resumen' => PracticasModel::mdlGetIncidenciasResumen(),
            'items'   => PracticasModel::mdlGetIncidenciasAdmin(),
        ];
    }

    public static function ctrGetIncidenciaDetalle($idIncidencia): array
    {
        return PracticasModel::mdlGetIncidenciaDetalle($idIncidencia);
    }

    /**
     * Cambia el estado del reporte. Al marcarlo como atendido (2) exige la
     * solución y avisa por correo a la empresa y al alumno.
     */
    public static function ctrActualizarEstadoIncidencia($idIncidencia, int $status, ?string $solucion, ?int $adminId)
    {
        require_once __DIR__ . '/../model/LogModel.php';

        $inc = PracticasModel::mdlGetIncidenciaDetalle($idIncidencia);
        if (!$inc) {
            return ['success' => false, 'message' => 'La incidencia no existe.'];
        }

        $response = PracticasModel::mdlActualizarEstadoIncidencia($idIncidencia, $status, $solucion, $adminId);
        if (empty($response['success'])) {
            return $response;
        }

        if ($status === 2) {
            $vars = [
                'idIncidencia' => $idIncidencia,
                'studentName'  => $inc['nombre_completo'] ?? '',
                'empresa'      => $inc['empresa'] ?? '',
                'solucion'     => $solucion,
                'solucionHtml' => self::incidenciaTextoHtml((string) $solucion),
                'fechaCierre'  => date('d/m/Y H:i'),
            ];
            if (!empty($inc['org_email'])) {
                sendIncidenciaCierre($inc['org_email'], $vars + ['destinatarioNombre' => $inc['nombre_contacto'] ?: $inc['empresa']]);
            }
            if (!empty($inc['student_email'])) {
                sendIncidenciaCierre($inc['student_email'], $vars + ['destinatarioNombre' => $inc['nombre_completo']]);
            }
            Notifications::addNotification(
                $inc['idOrganismo'],
                'organismo_externo',
                'Tu reporte de incidencia sobre ' . ($inc['nombre_completo'] ?? 'el practicante') . ' fue atendido.',
                null,
                null,
                2,
                2
            );
        }

        LogModel::log(
            LogModel::ACTION_UPDATE,
            LogModel::MODULE_PRACTICES,
            null,
            "Incidencia #{$idIncidencia} marcada como '" . (self::INCIDENCIA_ESTADOS[$status] ?? $status) . "'.",
            ['idIncidencia' => $idIncidencia, 'status' => $status],
            'success',
            $adminId
        );

        $response['message'] = $status === 2
            ? 'Incidencia marcada como atendida. Se notificó a la empresa y al alumno.'
            : 'Estado actualizado a "' . (self::INCIDENCIA_ESTADOS[$status] ?? $status) . '".';

        return $response;
    }

    /**
     * Envía los comunicados del administrador. Cada parte (alumno y empresa)
     * recibe su propio correo, con su plantilla y su redacción, y se registra
     * como una entrada independiente en la bitácora del caso.
     *
     * @param array $mensajes ['alumno' => texto, 'empresa' => texto]
     */
    public static function ctrEnviarMensajeIncidencia($idIncidencia, string $destinatario, string $asunto, array $mensajes, ?int $adminId)
    {
        $inc = PracticasModel::mdlGetIncidenciaDetalle($idIncidencia);
        if (!$inc) {
            return ['success' => false, 'message' => 'La incidencia no existe.'];
        }

        // Datos comunes del expediente; cada destinatario recibe su propia plantilla
        $base = [
            'idIncidencia'   => $idIncidencia,
            'asunto'         => $asunto,
            'studentName'    => $inc['nombre_completo'] ?? '',
            'matricula'      => $inc['student_matricula'] ?? '',
            'programa'       => $inc['programa_academico'] ?: 'No especificado',
            'empresa'        => $inc['empresa'] ?? '',
            'contactName'    => $inc['nombre_contacto'] ?: ($inc['empresa'] ?? ''),
            'tipoIncidencia' => self::INCIDENCIA_TIPOS[$inc['tipo']] ?? $inc['tipo'],
            'fechaReporte'   => !empty($inc['dateCreated']) ? date('d/m/Y', strtotime($inc['dateCreated'])) : '',
            'fechaEnvio'     => date('d/m/Y H:i'),
            'adminNombre'    => PracticasModel::mdlGetNombreUsuario($adminId) ?: 'Área de Prácticas Profesionales',
        ];

        // Un envío independiente por destinatario, cada uno con su plantilla
        $envios = [
            'alumno' => [
                'aplica' => $destinatario === 'alumno' || $destinatario === 'ambos',
                'email'  => $inc['student_email'] ?? '',
                'send'   => 'sendIncidenciaMensajeAlumno',
                'quien'  => 'el alumno',
                'a'      => 'al alumno',
            ],
            'empresa' => [
                'aplica' => $destinatario === 'empresa' || $destinatario === 'ambos',
                'email'  => $inc['org_email'] ?? '',
                'send'   => 'sendIncidenciaMensajeEmpresa',
                'quien'  => 'la empresa',
                'a'      => 'a la empresa',
            ],
        ];

        $enviados = [];
        $sinCorreo = [];
        foreach ($envios as $clave => $e) {
            $texto = trim((string) ($mensajes[$clave] ?? ''));
            if (!$e['aplica'] || $texto === '') {
                continue;
            }
            if (empty($e['email'])) {
                $sinCorreo[] = $e['quien'];
                continue;
            }

            $e['send']($e['email'], $base + [
                'mensaje'     => $texto,
                'mensajeHtml' => self::incidenciaTextoHtml($texto),
            ]);

            PracticasModel::mdlAddIncidenciaMensaje([
                'idIncidencia' => $idIncidencia,
                'destinatario' => $clave,
                'asunto'       => $asunto,
                'mensaje'      => $texto,
                'enviado_a'    => $e['email'],
                'created_by'   => $adminId,
            ]);

            $enviados[] = $e['a'] . ' (' . $e['email'] . ')';
        }

        if (!$enviados) {
            return [
                'success' => false,
                'message' => $sinCorreo
                    ? 'No hay correo registrado para ' . implode(' ni ', $sinCorreo) . '.'
                    : 'No se envió ningún comunicado.',
            ];
        }

        $aviso = $sinCorreo ? ' No se pudo enviar a ' . implode(' ni ', $sinCorreo) . ' (sin correo registrado).' : '';

        return [
            'success' => true,
            'message' => 'Comunicado enviado ' . implode(' y ', $enviados) . '.' . $aviso,
        ];
    }

    /**
     * Agenda una junta (virtual o presencial) y convoca por correo a los
     * participantes seleccionados.
     */
    public static function ctrAgendarJuntaIncidencia(array $data)
    {
        $idIncidencia = $data['idIncidencia'];
        $inc = PracticasModel::mdlGetIncidenciaDetalle($idIncidencia);
        if (!$inc) {
            return ['success' => false, 'message' => 'La incidencia no existe.'];
        }

        $response = PracticasModel::mdlAddIncidenciaJunta($data);
        if (empty($response['success'])) {
            return $response;
        }

        $esVirtual = $data['modalidad'] === 'Virtual';
        $detalle = $esVirtual
            ? '<li><strong>Enlace de la sesión:</strong> <a href="' . htmlspecialchars($data['url_sesion'], ENT_QUOTES, 'UTF-8') . '">'
              . htmlspecialchars($data['url_sesion'], ENT_QUOTES, 'UTF-8') . '</a></li>'
            : '<li><strong>Lugar:</strong> ' . htmlspecialchars((string) $data['lugar'], ENT_QUOTES, 'UTF-8') . '</li>';

        $vars = [
            'studentName'      => $inc['nombre_completo'] ?? '',
            'empresa'          => $inc['empresa'] ?? '',
            'fecha'            => date('d/m/Y', strtotime($data['fecha'])),
            'hora'             => substr($data['hora'], 0, 5),
            'modalidad'        => $data['modalidad'],
            'detalleModalidad' => $detalle,
            'agenda'           => $data['agenda'] ?: '',
            'agendaHtml'       => $data['agenda']
                ? '<h3 style="color:#01643D;margin-top:18px;">Puntos a tratar</h3><div style="background:#f8fafc;border-left:4px solid #01643D;padding:12px 16px;">'
                  . self::incidenciaTextoHtml($data['agenda']) . '</div>'
                : '',
        ];

        $convocados = [];
        if (!empty($data['invita_alumno']) && !empty($inc['student_email'])) {
            sendIncidenciaJunta($inc['student_email'], $vars + ['destinatarioNombre' => $inc['nombre_completo']]);
            $convocados[] = $inc['student_email'];
        }
        if (!empty($data['invita_empresa']) && !empty($inc['org_email'])) {
            sendIncidenciaJunta($inc['org_email'], $vars + ['destinatarioNombre' => $inc['nombre_contacto'] ?: $inc['empresa']]);
            $convocados[] = $inc['org_email'];
            Notifications::addNotification(
                $inc['idOrganismo'],
                'organismo_externo',
                'Junta de seguimiento programada para el ' . $vars['fecha'] . ' a las ' . $vars['hora']
                    . ' sobre ' . ($inc['nombre_completo'] ?? 'el practicante') . '.',
                null,
                null,
                2,
                3
            );
        }

        // Al convocar una junta el caso pasa a "en proceso" si seguía pendiente
        if ((int) $inc['status'] === 0) {
            PracticasModel::mdlActualizarEstadoIncidencia($idIncidencia, 1, null, $data['created_by'] ?: null);
        }

        $response['message'] = $convocados
            ? 'Junta agendada. Se convocó a: ' . implode(', ', $convocados)
            : 'Junta agendada, pero no se envió ninguna convocatoria (sin correos registrados).';

        return $response;
    }

    public static function ctrGetSolicitudesCapacitacion($idOrganismo)
    {
        $response = PracticasModel::mdlGetSolicitudesCapacitacion($idOrganismo);
        return $response;
    }

    public static function ctrAcceptSolicitudCapacitacion($idSolicitudCap, $comentario)
    {
        $response = PracticasModel::mdlAcceptSolicitudCapacitacion($idSolicitudCap, $comentario);
        if ($response['success']) {
            $solicitud = PracticasModel::mdlGetSolicitudCapacitacionById($idSolicitudCap);
            if ($solicitud) {
                $fechaCreacion = dateConfigurated($solicitud['dateCreated']);
                sendSolicitudCapacitacionAceptada($solicitud['email'], $solicitud['nombre_contacto'], $solicitud['nombre_completo'], $comentario, $fechaCreacion);
                Notifications::addNotification(
                    $solicitud['idOrganismo'],
                    'organismo_externo',
                    'La solicitud de capacitación ha sido aceptada para ' . $solicitud['nombre_completo'] . '.',
                    'students_in_practices',
                    null,
                    2,
                    2
                );
            }
        }
        return $response;
    }

    public static function ctrRejectSolicitudCapacitacion($idSolicitudCap, $comentario)
    {
        $response = PracticasModel::mdlRejectSolicitudCapacitacion($idSolicitudCap, $comentario);
        if ($response['success']) {
            $solicitud = PracticasModel::mdlGetSolicitudCapacitacionById($idSolicitudCap);
            if ($solicitud) {
                $fechaCreacion = dateConfigurated($solicitud['dateCreated']);
                sendSolicitudCapacitacionRechazada(
                    $solicitud['email'],
                    $solicitud['nombre_contacto'],
                    $solicitud['nombre_completo'],
                    $fechaCreacion,
                    $comentario
                );
                Notifications::addNotification(
                    $solicitud['idOrganismo'],
                    'organismo_externo',
                    'La solicitud de capacitación ha sido rechazada para ' . $solicitud['nombre_completo'] . '.',
                    'students_in_practices',
                    null,
                    2,
                    2
                );
            }
        }
        return $response;
    }

    public static function ctrAprobarAsistencia($idAsistencia)
    {
        $response = PracticasModel::mdlAprobarAsistencia($idAsistencia);
        if ($response['success']) {
            $asistencia = PracticasModel::mdlGetAsistenciaById($idAsistencia);
            if ($asistencia) {
                $student = PracticasModel::mdlGetStudentPracticesById($asistencia['idStudent']);
                if ($student) {
                    // --- FASE 3: Lógica de Validación de Horas y Strikes ---
                    $he = new DateTime($asistencia['hora_entrada']);
                    $hs = new DateTime($asistencia['hora_salida']);
                    $interval = $he->diff($hs);
                    $horasReales = $interval->h + ($interval->i / 60.0);
                    $limiteHoras = 4;
                    $tolerancia = 10 / 60; // 10 minutos

                    if ($horasReales > ($limiteHoras + $tolerancia)) {
                        // ¡STRIKE! (excedente > 10 minutos)
                        $horasValidadas = 4.0;
                        PracticasModel::mdlUpdateAsistenciaHorasValidadas($idAsistencia, $horasValidadas, 1);

                        // Validar si idPractica y organismo_externo_id existen (dependiendo de la práctica)
                        $idPractica = $asistencia['idPractica'] ?? 0;
                        $idOrg = $asistencia['organismo_externo_id'] ?? 0;

                        $countAlumno = PracticasModel::mdlGetStrikesCountAlumno($student['id'], $idPractica);
                        $numStrike = $countAlumno + 1;

                        $consecuenciaAlumno = ($numStrike >= 2) ? 'baja_practicas' : 'advertencia';

                        $newStrikesOrg = 0;
                        $consecuenciaEmpresa = 'advertencia';
                        if ($idOrg > 0) {
                            $newStrikesOrg = PracticasModel::mdlIncrementStrikesOrganismo($idOrg);
                            $consecuenciaEmpresa = ($newStrikesOrg >= 2) ? 'bloqueo_solicitudes' : 'advertencia';
                        }

                        PracticasModel::mdlInsertStrike([
                            'idAsistencia' => $idAsistencia,
                            'idStudent' => $student['id'],
                            'idOrganismo' => $idOrg,
                            'idPractica' => $idPractica,
                            'horas_reportadas' => $horasReales,
                            'horas_validadas' => $horasValidadas,
                            'horas_excedente' => $horasReales - 4.0,
                            'tipo_strike' => 'exceso_horas',
                            'consecuencia_alumno' => $consecuenciaAlumno,
                            'consecuencia_empresa' => $consecuenciaEmpresa
                        ]);

                        $orgName = 'Organismo';
                        $emailOrg = '';
                        if ($idOrg > 0) {
                            $org = PracticasModel::mdlGetExternals($idOrg);
                            if ($org) {
                                $orgName = $org['empresa'];
                                $emailOrg = $org['email'];
                            }
                        }

                        if ($consecuenciaAlumno === 'baja_practicas') {
                            PracticasModel::mdlBajaAlumnoPorStrike($student['id'], $idPractica);
                            sendStrikeBajaAlumno($student['email'], $student['nombre_completo'], $asistencia['fecha'], $horasReales, $numStrike);
                        } else {
                            sendStrikeAdvertenciaAlumno($student['email'], $student['nombre_completo'], $asistencia['fecha'], $horasReales, $numStrike);
                        }

                        if ($emailOrg) {
                            sendStrikeOrganismo($emailOrg, $orgName, $student['nombre_completo'], $asistencia['fecha'], $horasReales, $newStrikesOrg);
                        }

                        sendStrikeAdmin($student['nombre_completo'], $orgName, $asistencia['fecha'], $horasReales, $newStrikesOrg);

                        if ($consecuenciaEmpresa === 'bloqueo_solicitudes' && $idOrg > 0) {
                            PracticasModel::mdlBloquearSolicitudesOrganismo($idOrg, "Acumulación de 2 strikes por incumplimiento de horas (último: {$student['nombre_completo']}).");
                            if ($emailOrg) {
                                sendOrganismoBloqueadoAuto($emailOrg, $orgName, "Acumulación de 2 strikes por incumplimiento de horas.");
                            }
                            sendOrganismoBloqueadoAdminNotif($orgName, $student['nombre_completo']);
                        }

                    } else if ($horasReales > 4.0) {
                        // Margen de gracia (entre 4h y menos de 4h 10m)
                        PracticasModel::mdlUpdateAsistenciaHorasValidadas($idAsistencia, 4.0, 0);
                    } else {
                        // Normal (<= 4.0)
                        PracticasModel::mdlUpdateAsistenciaHorasValidadas($idAsistencia, $horasReales, 0);
                    }

                    // Email normal de aprobación
                    sendAssistanceApprovedEmail($student['email'], $student['nombre_completo'], $asistencia['fecha'], $asistencia['hora_entrada'], $asistencia['hora_salida'], $asistencia['actividad']);
                }
            }
        }
        return $response;
    }

    public static function ctrRechazarAsistencia($idAsistencia)
    {
        $response = PracticasModel::mdlRechazarAsistencia($idAsistencia);
        if ($response['success']) {
            $asistencia = PracticasModel::mdlGetAsistenciaById($idAsistencia);
            if ($asistencia) {
                $student = PracticasModel::mdlGetStudentPracticesById($asistencia['idStudent']);
                if ($student) {
                    sendAssistanceRejectedEmail($student['email'], $student['nombre_completo'], $asistencia['fecha'], $asistencia['hora_entrada'], $asistencia['hora_salida'], $asistencia['actividad']);
                }
            }
        }
        return $response;
    }

    public static function ctrActualizarHorarios($idAsistencia, $hora_entrada, $hora_salida)
    {
        $response = PracticasModel::mdlActualizarHorarios($idAsistencia, $hora_entrada, $hora_salida);
        if ($response['success']) {
            $asistencia = PracticasModel::mdlGetAsistenciaById($idAsistencia);
            if ($asistencia) {
                $student = PracticasModel::mdlGetStudentPracticesById($asistencia['idStudent']);
                if ($student) {
                    sendAssistanceUpdatedEmail($student['email'], $student['nombre_completo'], $asistencia['fecha'], $hora_entrada, $hora_salida, $asistencia['actividad']);
                }
            }
        }
        return $response;
    }

    public static function getReportsPractices($idOrganismo)
    {
        $response = PracticasModel::mdlGetParcialReportsPractices($idOrganismo);
        return $response;
    }

    public static function getFinalReports($idOrganismo)
    {
        $response = PracticasModel::mdlGetFinalReports($idOrganismo);
        return $response;
    }

    public static function getDashboardSummary(int $idOrganismo): array
    {
        return PracticasModel::mdlGetDashboardSummary($idOrganismo);
    }

    public static function getRecentProspectos(int $idOrganismo): array
    {
        return PracticasModel::mdlGetRecentProspectos($idOrganismo);
    }

    public static function ctrGetParcialReportsAdmin()
    {
        $response['parciales'] = PracticasModel::mdlGetParcialReportsAdmin();
        $response['finales'] = PracticasModel::mdlGetFinalReportsAdmin();
        return $response;
    }

    public static function ctrGeneratePartialReport($data)
    {
        $response = PracticasModel::mdlGeneratePartialReport($data);
        return $response;
    }

    public static function ctrGenerateFinalReport($data)
    {
        $response = PracticasModel::mdlGenerateFinalReport($data);
        return $response;
    }

    public static function acceptReport($idReporteParcial)
    {
        $response = PracticasModel::mdlAcceptReport($idReporteParcial);
        if ($response['success']) {
            $studentReport = PracticasModel::mdlGetStudentReportById($idReporteParcial);
            if ($studentReport) {
                sendPracticasReportAcceptedEmail($studentReport['email'], $studentReport['nombre_completo'], 'organismo_externo');
                Notifications::addNotification(
                    $_ENV['Current_ID_ADMIN'],
                    'admin',
                    'El reporte parcial ha sido aceptado para el estudiante ' . $studentReport['nombre_completo'] . ' por el organismo externo.',
                    null,
                    null,
                    2,
                    2
                );
            }
        }
        return $response;
    }

    public static function rejectReport($idReporteParcial, $comentarios)
    {
        $response = PracticasModel::mdlRejectReport($idReporteParcial, $comentarios);
        if ($response['success']) {
            $studentReport = PracticasModel::mdlGetStudentReportById($idReporteParcial);
            if ($studentReport) {
                sendPracticasReportRejectedEmail($studentReport['email'], $studentReport['nombre_completo'], 'organismo_externo', $comentarios);
            }
        }
        return $response;
    }

    public static function ctrAcceptReportPracticebyAdmin($idReporteParcial)
    {
        $response = PracticasModel::mdlAcceptReportPracticebyAdmin($idReporteParcial);
        if ($response['success']) {
            $studentReport = PracticasModel::mdlGetStudentReportById($idReporteParcial);
            if ($studentReport) {
                sendPracticasReportAcceptedbyAdminEmail($studentReport['email'], $studentReport['nombre_completo']);
            }
        }
        return $response;
    }

    public static function ctrRejectReportPracticebyAdmin($idReporteParcial, $comentarios)
    {
        $response = PracticasModel::mdlRejectReportPracticebyAdmin($idReporteParcial, $comentarios);
        if ($response['success']) {
            $studentReport = PracticasModel::mdlGetStudentReportById($idReporteParcial);
            if ($studentReport) {
                sendPracticasReportRejectedEmail($studentReport['email'], $studentReport['nombre_completo'], 'admin', $comentarios);
            }
        }
        return $response;
    }

    public static function acceptReportFinal($idReporteFinal)
    {
        $response = PracticasModel::mdlAcceptReportFinal($idReporteFinal);
        if ($response['success']) {
            $studentReport = PracticasModel::mdlGetStudentReportFinalById($idReporteFinal);
            if ($studentReport) {
                sendPracticasReportAcceptedEmail($studentReport['email'], $studentReport['nombre_completo'], 'organismo_externo');
                Notifications::addNotification(
                    $_ENV['Current_ID_ADMIN'],
                    'admin',
                    'El reporte final ha sido aceptado para el estudiante ' . $studentReport['nombre_completo'] . ' por el organismo externo.',
                    null,
                    null,
                    2,
                    2
                );

            }
        }
        return $response;
    }

    public static function rejectReportFinal($idReporteFinal, $comentarios)
    {
        $response = PracticasModel::mdlRejectReportFinal($idReporteFinal, $comentarios);
        if ($response['success']) {
            $studentReport = PracticasModel::mdlGetStudentReportFinalById($idReporteFinal);
            if ($studentReport) {
                sendPracticasReportRejectedEmail($studentReport['email'], $studentReport['nombre_completo'], 'organismo_externo', $comentarios);
            }
        }
        return $response;
    }

    public static function ctrAcceptReportPracticeFinalbyAdmin($idReporteFinal)
    {
        $response = PracticasModel::mdlAcceptReportPracticeFinalbyAdmin($idReporteFinal);
        if ($response['success']) {
            $studentReport = PracticasModel::mdlGetStudentReportFinalById($idReporteFinal);
            if ($studentReport) {
                sendPracticasFinalReportAcceptedbyAdminEmail($studentReport['email'], $studentReport['nombre_completo']);
            }
        }
        return $response;
    }

    public static function ctrRejectReportPracticeFinalbyAdmin($idReporteFinal, $comentario)
    {
        $response = PracticasModel::mdlRejectReportPracticeFinalbyAdmin($idReporteFinal, $comentario);
        if ($response['success']) {
            $studentReport = PracticasModel::mdlGetStudentReportFinalById($idReporteFinal);
            if ($studentReport) {
                sendPracticasReportRejectedEmail($studentReport['email'], $studentReport['nombre_completo'], 'admin', $comentario);
            }
        }
        return $response;
    }

    public static function evaluarParticipanteParcial($idStudent, $evaluacion)
    {
        return PracticasModel::mdlEvaluarParticipanteParcial($idStudent, $evaluacion);
    }

    public static function evaluarParticipanteFinal($idStudent, $evaluacion)
    {
        return PracticasModel::mdlEvaluarParticipanteFinal($idStudent, $evaluacion);
    }

    /* =====================================================
     * Áreas internas de Prácticas Profesionales
     * ===================================================== */

    public static function ctrGetAllAreasPracticas()
    {
        return PracticasModel::mdlGetAllAreasPracticas();
    }

    public static function ctrGetAreaPracticaById(int $id)
    {
        return PracticasModel::mdlGetAreaPracticaById($id);
    }

    public static function ctrCreateAreaPractica(array $data)
    {
        return PracticasModel::mdlCreateAreaPractica($data);
    }

    public static function ctrUpdateAreaPractica(array $data)
    {
        return PracticasModel::mdlUpdateAreaPractica($data);
    }

    public static function ctrToggleAreaPractica(int $id, int $isOpen)
    {
        return PracticasModel::mdlToggleAreaPractica($id, $isOpen);
    }

    public static function ctrDeleteAreaPractica(int $id)
    {
        return PracticasModel::mdlDeleteAreaPractica($id);
    }

    public static function ctrGetAreasPracticasAbiertas(int $studentId)
    {
        return PracticasModel::mdlGetAreasPracticasAbiertas($studentId);
    }

    public static function ctrGetPostulacionAreaByStudent(int $studentId)
    {
        return PracticasModel::mdlGetPostulacionAreaByStudent($studentId);
    }

    public static function ctrPostularseArea(int $studentId, int $areaId)
    {
        return PracticasModel::mdlPostularseArea($studentId, $areaId);
    }

    public static function ctrGetAllPostulacionesAreas()
    {
        return PracticasModel::mdlGetAllPostulacionesAreas();
    }

    public static function ctrGetPostulacionAreaById(int $id)
    {
        return PracticasModel::mdlGetPostulacionAreaById($id);
    }

    public static function ctrGetPostulacionesByArea(int $areaId)
    {
        return PracticasModel::mdlGetPostulacionesByArea($areaId);
    }

    public static function ctrAceptarPostulacionArea(int $id, string $fechaInicio)
    {
        return PracticasModel::mdlAceptarPostulacionArea($id, $fechaInicio);
    }

    public static function ctrRechazarPostulacionArea(int $id)
    {
        return PracticasModel::mdlRechazarPostulacionArea($id);
    }

    public static function ctrGetEvaluacionesByEstudiante(int $idStudent, int $idPractica): array
    {
        return PracticasModel::mdlGetEvaluacionesByEstudiante($idStudent, $idPractica);
    }

    public static function ctrRegisterAttendanceArea(array $data)
    {
        return PracticasModel::mdlRegisterAttendanceArea($data);
    }

    public static function ctrGetAsistenciasArea(int $postulacionId, int $studentId)
    {
        return PracticasModel::mdlGetAsistenciasArea($postulacionId, $studentId);
    }

    public static function ctrGetPendingAsistenciasArea(int $areaId)
    {
        return PracticasModel::mdlGetPendingAsistenciasArea($areaId);
    }

    public static function ctrAprobarAsistenciaArea(int $id)
    {
        return PracticasModel::mdlAprobarAsistenciaArea($id);
    }

    public static function ctrRechazarAsistenciaArea(int $id)
    {
        return PracticasModel::mdlRechazarAsistenciaArea($id);
    }

    public static function ctrGetStudentsInArea(int $areaId)
    {
        return PracticasModel::mdlGetStudentsInArea($areaId);
    }

    public static function ctrGetAreasByEncargado(int $userId)
    {
        return PracticasModel::mdlGetAreasByEncargado($userId);
    }

    // ── Teacher dashboard helpers ──────────────────────────────────

    public static function ctrGetSsStudentsByTeacher(int $teacherUserId)
    {
        return PracticasModel::mdlGetSsStudentsByTeacher($teacherUserId);
    }

    public static function ctrGetAllAsistenciasAreaByStudent(int $studentId, int $areaId)
    {
        return PracticasModel::mdlGetAllAsistenciasAreaByStudent($studentId, $areaId);
    }

    public static function ctrGetStudentAttendanceHistory(int $studentId, int $areaId)
    {
        return PracticasModel::mdlGetStudentAttendanceHistory($studentId, $areaId);
    }

    public static function ctrAddTeacherAnnotation(int $teacherUserId, string $type, int $studentId, string $nota)
    {
        return PracticasModel::mdlAddTeacherAnnotation($teacherUserId, $type, $studentId, $nota);
    }

    public static function ctrGetTeacherAnnotations(int $teacherUserId, string $type, int $studentId)
    {
        return PracticasModel::mdlGetTeacherAnnotations($teacherUserId, $type, $studentId);
    }

    /* =========================================================================
     * REPORTES · Prácticas Profesionales
     * ===================================================================== */

    /** Normaliza y valida los filtros que llegan del panel o de la exportación. */
    public static function ctrNormalizarFiltrosReporte(array $in): array
    {
        $fecha = static function ($v): string {
            $v = trim((string) $v);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        };

        $desde = $fecha($in['desde'] ?? '');
        $hasta = $fecha($in['hasta'] ?? '');
        if ($desde && $hasta && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $empresa = trim((string) ($in['empresa'] ?? ''));
        if (!preg_match('/^(ext|int):\d+$/', $empresa)) {
            $empresa = '';
        }

        $estado = trim((string) ($in['estado'] ?? ''));
        if (!in_array($estado, PracticasModel::REPORTE_ESTADOS, true)) {
            $estado = '';
        }

        $origen = trim((string) ($in['origen'] ?? ''));
        if (!in_array($origen, ['externa', 'interna'], true)) {
            $origen = '';
        }

        return [
            'desde'       => $desde,
            'hasta'       => $hasta,
            'campo_fecha' => (($in['campo_fecha'] ?? 'inicio') === 'fin') ? 'fin' : 'inicio',
            'empresa'     => $empresa,
            'estado'      => $estado,
            'origen'      => $origen,
            'q'           => trim((string) ($in['q'] ?? '')),
        ];
    }

    /** Filtro de texto libre (alumno, matrícula, empresa o programa académico). */
    public static function ctrFiltrarReportePorTexto(array $rows, string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return $rows;
        }
        $needle = mb_strtolower($q, 'UTF-8');

        return array_values(array_filter($rows, static function ($r) use ($needle) {
            $heno = mb_strtolower(implode(' ', [
                $r['nombre_completo'] ?? '',
                $r['matricula'] ?? '',
                $r['empresa'] ?? '',
                $r['programa_academico'] ?? '',
                $r['grupo'] ?? '',
            ]), 'UTF-8');
            return mb_strpos($heno, $needle) !== false;
        }));
    }

    /** Totales del reporte: se calculan sobre el conjunto ya filtrado. */
    public static function ctrResumenReportePracticas(array $rows): array
    {
        $resumen = [
            'total'       => count($rows),
            'en_proceso'  => 0,
            'concluida'   => 0,
            'baja'        => 0,
            'horas'       => 0.0,
            'empresas'    => 0,
            'alumnos'     => 0,
        ];
        $empresas = [];
        $alumnos  = [];

        foreach ($rows as $r) {
            $estado = $r['estado'] ?? 'en_proceso';
            if (isset($resumen[$estado])) {
                $resumen[$estado]++;
            }
            $resumen['horas'] += (float) ($r['horas'] ?? 0);
            $empresas[(string) ($r['empresa_key'] ?? '')] = true;
            $alumnos[(string) ($r['idStudent'] ?? '')]    = true;
        }

        $resumen['horas']    = round($resumen['horas'], 2);
        $resumen['empresas'] = count(array_filter(array_keys($empresas), 'strlen'));
        $resumen['alumnos']  = count(array_filter(array_keys($alumnos), 'strlen'));

        return $resumen;
    }

    /**
     * Reporte de prácticas profesionales para el panel del administrador.
     * El resumen se calcula ignorando el filtro de estado, para que los
     * indicadores del panel sigan sirviendo como accesos directos.
     */
    public static function ctrGetReportePracticas(array $filtros): array
    {
        $f = self::ctrNormalizarFiltrosReporte($filtros);

        $sinEstado = $f;
        $sinEstado['estado'] = '';

        $todos = PracticasModel::mdlGetReportePracticas($sinEstado);
        $todos = self::ctrFiltrarReportePorTexto($todos, $f['q']);

        $items = $f['estado'] === ''
            ? $todos
            : array_values(array_filter($todos, static fn($r) => ($r['estado'] ?? '') === $f['estado']));

        return [
            'success' => true,
            'filtros' => $f,
            'resumen' => self::ctrResumenReportePracticas($todos),
            'items'   => $items,
        ];
    }

    /** Catálogo de empresas y áreas para el selector del reporte. */
    public static function ctrGetEmpresasReporte(): array
    {
        return PracticasModel::mdlGetEmpresasConPracticantes();
    }

    public static function ctrDeleteTeacherAnnotation(int $id, int $teacherUserId)
    {
        return PracticasModel::mdlDeleteTeacherAnnotation($id, $teacherUserId);
    }

}

function dateConfigurated($date)
{
    $dt = new DateTime($date, new DateTimeZone('America/Mexico_City'));

    if (class_exists('IntlDateFormatter')) {
        // Con IntlDateFormatter (más limpio si tienes intl habilitado)
        $formatter = new IntlDateFormatter(
            'es_MX',
            IntlDateFormatter::FULL,
            IntlDateFormatter::SHORT,
            'America/Mexico_City',
            IntlDateFormatter::GREGORIAN,
            "EEEE, d 'de' MMMM yyyy 'a las' HH:mm"
        );
        return $formatter->format($dt);
    } else {
        // Fallback con strftime
        setlocale(LC_TIME, 'es_MX.UTF-8', 'spanish', 'es_MX', 'es');
        $fecha = strftime("%A, %d de %B %Y a las %H:%M", $dt->getTimestamp());
        return mb_convert_encoding($fecha, 'UTF-8', 'ISO-8859-1');
    }
}
