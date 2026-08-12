<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

define('EMAIL_CSS_PATH', __DIR__ . '/../view/assets/css/email_templates/email.css');
require_once __DIR__ . '/../model/EmailsModel.php';

class MailService
{

    /**
     * Encola un correo en la tabla email_queue para enviarlo en segundo plano.
     * Retorna 'ok' inmediatamente sin esperar la conexión SMTP.
     */
    public static function sendMail(string $email, string $subject, string $message, string $plainText, string $from_name = '', array $attachments = []): string|false
    {
        try {
            require_once __DIR__ . '/../model/conection.php';
            $pdo = Conexion::conectar();
            // Solo rutas de archivos existentes (evita adjuntos rotos en la cola).
            $attachments = array_values(array_filter($attachments, fn($p) => is_string($p) && is_file($p)));
            $attachmentsJson = $attachments ? json_encode($attachments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
            $stmt = $pdo->prepare(
                "INSERT INTO email_queue (to_email, subject, body, plain_text, from_name, attachments, status, attempts, created_at)
                 VALUES (:to_email, :subject, :body, :plain_text, :from_name, :attachments, 'pending', 0, NOW())"
            );
            $stmt->execute([
                ':to_email'    => $email,
                ':subject'     => $subject,
                ':body'        => $message,
                ':plain_text'  => $plainText,
                ':from_name'   => $from_name,
                ':attachments' => $attachmentsJson,
            ]);
            return 'ok';
        } catch (\Throwable $e) {
            error_log("EmailQueue: no se pudo encolar correo a {$email}: " . $e->getMessage());
            // Fallback: intentar envío directo si la cola falla
            return self::dispatchMail($email, $subject, $message, $plainText, $from_name, $attachments);
        }
    }

    /**
     * Envío SMTP directo (usado por el procesador de cola en segundo plano).
     *
     * @param array{host?:string,port?:int|string,encryption?:string,username?:string,password?:string,from_email?:string,from_name?:string}|null $smtpOverride
     *   Credenciales SMTP alternativas (usadas por el Gestor de envío masivo, que tiene
     *   su propio servidor de correo). Si se omite, se usa el SMTP global de .env como
     *   siempre — no afecta a ninguno de los llamadores existentes.
     * @param string|null $errorOut Por referencia: mensaje crudo de PHPMailer si falla.
     */
    public static function dispatchMail(
        string $email,
        string $subject,
        string $message,
        string $plainText,
        string $from_name = '',
        array $attachments = [],
        ?array $smtpOverride = null,
        ?string &$errorOut = null
    ): string|false {
        $mail = new PHPMailer(true);
        try {
            // Configuración SMTP (propia del módulo si se pasa $smtpOverride, si no la global de .env)
            $mail->isSMTP();
            $mail->Host = $smtpOverride['host'] ?? $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpOverride['username'] ?? $_ENV['SMTP_USER'];
            $mail->Password = $smtpOverride['password'] ?? $_ENV['SMTP_PASS'];
            $encryption = $smtpOverride['encryption'] ?? null;
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'none') {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = $smtpOverride['port'] ?? $_ENV['SMTP_PORT'];

            // Remitente y destinatario
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(
                $smtpOverride['from_email'] ?? $_ENV['FROM_EMAIL'],
                $from_name ?: ($smtpOverride['from_name'] ?? $_ENV['FROM_NAME'])
            );
            $mail->addAddress($email);

            // Adjuntos (solo archivos existentes)
            foreach ($attachments as $path) {
                if (is_string($path) && is_file($path)) {
                    $mail->addAttachment($path);
                }
            }

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = $plainText;

            $mail->send();
            return 'ok';
        } catch (Exception $e) {
            $errorOut = $mail->ErrorInfo;
            error_log("Error al enviar correo: {$mail->ErrorInfo}");
            return false;
        }
    }

}


/* ================= Helpers de plantilla ================= */
function interpolate(string $tpl, array $vars): string
{
    // {{{var}}} sin escape
    $tpl = preg_replace_callback('/\{\{\{\s*([a-zA-Z0-9_]+)\s*\}\}\}/u', function ($m) use ($vars) {
        $k = $m[1];
        return array_key_exists($k, $vars) ? (string) $vars[$k] : '';
    }, $tpl);

    // {{var}} escapado
    $tpl = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/u', function ($m) use ($vars) {
        $k = $m[1];
        $v = array_key_exists($k, $vars) ? (string) $vars[$k] : '';
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }, $tpl);

    return $tpl;
}


/* === Render y envío por tkey === */
function sendTemplateByKey(string $tkey, string $to, array $vars, string $from_name = '', array $attachments = []): string|false
{
    $tpl = EmailsModel::getMailTemplateByKey($tkey);
    if (!$tpl) {
        error_log("email_templates: no existe tkey='{$tkey}'");
        return false;
    }

    $css = is_file(EMAIL_CSS_PATH) ? (string) file_get_contents(EMAIL_CSS_PATH) : '';

    // Variables de sistema + de negocio
    $vars = array_merge([
        'css' => $css,        // si la plantilla usa <style>{{{css}}}</style>
        'year' => date('Y'),
    ], $vars);

    // 1) Subject desde DB (puede traer {{...}})
    $subject = interpolate($tpl['subject'] ?? 'Notificación UNIMO', $vars);
    // lo exponemos por si lo usas dentro del html/text
    $vars['subject'] = $subject;

    // 2) Render de cuerpos
    $htmlRaw = $tpl['html'] ?? '';
    $txtRaw = $tpl['text_plain'] ?? '';

    $htmlRendered = interpolate($htmlRaw, $vars);
    $txtRendered = trim(interpolate($txtRaw, $vars));

    // 3) Si la plantilla ya es HTML completo, la usamos tal cual.
    //    Si es fragmento, la envolvemos en un layout básico.
    $isFullHtml = stripos($htmlRendered, '<html') !== false;

    if ($isFullHtml) {
        $message = $htmlRendered;
    } else {
        // layout mínimo (evitas doble <html> cuando la DB trae documento completo)
        $message = <<<HTML
        <html>
        <head>
            <meta charset="UTF-8">
            <title>{$subject}</title>
            <style>{$css}</style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="https://servicioypracticas.unimontrer.edu.mx/view/assets/images/logo-color.png" alt="Logo UNIMO">
                </div>
                <div class="content">
                    {$htmlRendered}
                </div>
                <div class="footer">
                    Universidad Montrer (UNIMO) • Av Lázaro Cárdenas 1760, Chapultepec Sur, 58260 Morelia, Mich.
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    // Fallback si no hay texto plano
    if ($txtRendered === '' && $message !== '') {
        $txtRendered = strip_tags($message);
    }

    return MailService::sendMail($to, $subject, $message, $txtRendered, $from_name, $attachments);
}

// 1) Nuevo evento
function sendNewEvent(string $subject, string $recipientEmail, string $eventName, string $description)
{
    return sendTemplateByKey('new_event', $recipientEmail, [
        'eventName' => $eventName,
        'description' => $description,
    ]);
}

// 2) Cancelación de evento
function cancelEvent(string $subject, string $recipientEmail, string $eventName, string $description)
{
    // $subject se toma del template
    return sendTemplateByKey('cancel_event', $recipientEmail, [
        'eventName' => $eventName,
        'description' => $description,
    ]);
}

// 3) Enviar contraseña a alumno
function sendPasswordToStudent(string $email, string $password)
{
    $loginUrl = "https://servicioypracticas.unimontrer.edu.mx/?pagina=login&user_type=alumno_servicio&mail={$email}&password={$password}";
    return sendTemplateByKey('password_student', $email, [
        'email' => $email,
        'password' => $password,
        'login_url' => $loginUrl,
    ]);
}

// 4) Servicio Social – info y formatos
function sendServiceSocialInfo(string $email, string $password)
{
    $loginUrl = "https://servicioypracticas.unimontrer.edu.mx/?pagina=login&user_type=alumno_servicio&mail={$email}&password={$password}";
    return sendTemplateByKey('service_social_info', $email, [
        'email' => $email,
        'password' => $password,
        'login_url' => $loginUrl,
    ]);
}

function sendServiceSocialApplicationReceived(string $email, string $nameStudent)
{
    return sendTemplateByKey('social_service_application', $email, [
        'email' => $email,
        'nameStudent' => $nameStudent
    ]);
}

// 5) Organismo Externo – acreditación y acceso
function sendPracticasOrganismoExternoInfo(string $email, string $password, string $empresa)
{
    $loginUrl = "https://servicioypracticas.unimontrer.edu.mx/?pagina=login&user_type=organismo_externo&mail={$email}&password={$password}";
    return sendTemplateByKey('practicas_organismo_externo_info', $email, [
        'email' => $email,
        'empresa' => $empresa,
        'password' => $password,
        'login_url' => $loginUrl,
    ]);
}

// ── Organismos Externos: Rechazo y Corrección ─────────────────────────
function sendOrganismoRechazado(string $email, string $empresa, string $motivoGeneral, array $camposRechazados, string $enlaceCorreccion)
{
    // Generar HTML de campos
    $camposHtml = '<ul style="list-style-type: none; padding: 0;">';
    foreach ($camposRechazados as $campo) {
        $camposHtml .= '<li style="background:#f8f9fa; border-left:4px solid #ffc107; padding:10px; margin-bottom:10px;">';
        $camposHtml .= "<strong>{$campo['campo_label']}:</strong> {$campo['motivo']}";
        if (!empty($campo['observacion'])) {
            $camposHtml .= "<br><small style='color:#6c757d;'>{$campo['observacion']}</small>";
        }
        $camposHtml .= '</li>';
    }
    $camposHtml .= '</ul>';

    $expiraEn = date('d/m/Y H:i', strtotime('+72 hours'));

    return sendTemplateByKey('pp_organismo_rechazado', $email, [
        'empresa' => $empresa,
        'motivoGeneral' => $motivoGeneral,
        'camposHtml' => $camposHtml,
        'enlaceCorreccion' => $enlaceCorreccion,
        'expiraEn' => $expiraEn,
        'emailPP' => ppGetAdminEmail()
    ]);
}

function sendOrganismoNoProcedente(string $email, string $empresa, string $motivo)
{
    return sendTemplateByKey('pp_organismo_no_procedente', $email, [
        'empresa' => $empresa,
        'motivo'  => $motivo,
        'emailPP' => ppGetAdminEmail()
    ]);
}

function sendOrganismoOtp(string $email, string $empresa, string $otp)
{
    return sendTemplateByKey('pp_organismo_otp', $email, [
        'empresa' => $empresa,
        'otp' => $otp
    ]);
}

function sendOrganismoCorregidoAdmin(string $empresa, int $orgId, array $camposCorregidos)
{
    $adminEmail = ppGetAdminEmail();
    if (!$adminEmail) return false;

    // Generar HTML de campos corregidos
    $camposCorregidosHtml = '<ul style="list-style-type: none; padding: 0;">';
    foreach ($camposCorregidos as $campo) {
        $camposCorregidosHtml .= '<li style="background:#f8f9fa; border-left:4px solid #28a745; padding:10px; margin-bottom:10px;">';
        $camposCorregidosHtml .= "<strong>{$campo['campo']}:</strong><br>";
        $valAnterior = $campo['valor_anterior'] ?? '(Valor anterior no capturado)';
        $camposCorregidosHtml .= "<del style='color:#dc3545;'>{$valAnterior}</del> &rarr; ";
        $camposCorregidosHtml .= "<span style='color:#28a745;'>{$campo['valor_nuevo']}</span>";
        $camposCorregidosHtml .= '</li>';
    }
    $camposCorregidosHtml .= '</ul>';

    return sendTemplateByKey('pp_organismo_corregido_admin', $adminEmail, [
        'empresa' => $empresa,
        'orgId' => $orgId,
        'fechaCorreccion' => date('d/m/Y H:i'),
        'camposCorregidosHtml' => $camposCorregidosHtml
    ]);
}

function sendOrganismoTokenExpirado(string $email, string $empresa)
{
    return sendTemplateByKey('pp_organismo_token_expirado', $email, [
        'empresa' => $empresa,
        'emailPP' => ppGetAdminEmail()
    ]);
}

// ── Nuevo flujo de Convenios Institucionales ──────────────────────────

/** Aviso al admin: nuevo organismo registrado, pendiente de validación. */
function sendNuevoOrganismoAdmin(string $empresa, string $contacto, string $correo, int $orgId)
{
    return sendTemplateByKey('pp_nuevo_organismo_admin', ppGetAdminEmail(), [
        'empresa'  => $empresa,
        'contacto' => $contacto,
        'correo'   => $correo,
        'orgId'    => $orgId,
    ]);
}

/** Convenio generado: se envía al organismo con el PDF adjunto + enlace único. */
function sendConvenioGeneradoOrganismo(string $email, string $empresa, string $enlaceFirma, string $expiraEn, string $pdfPath)
{
    return sendTemplateByKey('pp_convenio_generado_organismo', $email, [
        'empresa'     => $empresa,
        'enlaceFirma' => $enlaceFirma,
        'expiraEn'    => $expiraEn,
        'emailPP'     => ppGetAdminEmail(),
    ], '', [$pdfPath]);
}

/** Aviso al admin: el organismo firmó y reenvió el convenio. */
function sendConvenioFirmadoAdmin(string $empresa, int $orgId)
{
    return sendTemplateByKey('pp_convenio_firmado_admin', ppGetAdminEmail(), [
        'empresa' => $empresa,
        'orgId'   => $orgId,
        'fecha'   => date('d/m/Y H:i'),
    ]);
}

/** Convenio firmado rechazado: se pide al organismo corregir y reenviar. */
function sendConvenioRechazadoOrganismo(string $email, string $empresa, string $motivo, string $enlaceFirma, string $expiraEn)
{
    return sendTemplateByKey('pp_convenio_rechazado_organismo', $email, [
        'empresa'     => $empresa,
        'motivo'      => $motivo,
        'enlaceFirma' => $enlaceFirma,
        'expiraEn'    => $expiraEn,
        'emailPP'     => ppGetAdminEmail(),
    ]);
}

function sendRejectServiceSocialApplication(string $email, string $nameStudent, string $reason)
{
    return sendTemplateByKey('reject_service_social_application', $email, [
        'email' => $email,
        'nameStudent' => $nameStudent
    ]);
}

// SS Interno — Carta de Conclusión generada (Alumno)
function sendCartaConclusionAlumno(
    string $email,
    string $studentName,
    string $folio,
    string $fechaInicio,
    string $fechaFin,
    int    $horas,
    int    $meses
): string|false {
    return sendTemplateByKey('ss_interno_carta_conclusion_alumno', $email, [
        'studentName' => $studentName,
        'folio'       => $folio,
        'fechaInicio' => $fechaInicio,
        'fechaFin'    => $fechaFin,
        'horas'       => $horas,
        'meses'       => $meses,
    ]);
}

// ── SS Interno: helper para leer email_ss de .env ──────────────────
function ssGetAdminEmail(): string
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = $_ENV['EMAIL_SS'] ?? $_ENV['Current_Email'] ?? '';
    return $cache;
}

// ── PP: helper para leer email_pp de .env ───────────────────────────
function ppGetAdminEmail(): string
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = $_ENV['EMAIL_PP'] ?? $_ENV['Current_Email'] ?? '';
    return $cache;
}

// SS Interno — helper para etiqueta legible del tipo de documento
function ssTipoLabel(string $tipo): string
{
    return match($tipo) {
        'carta_presentacion'      => 'Carta de Presentación',
        'carta_practicas_interno' => 'Carta de Finalización de Prácticas',
        'solicitud_registro'      => 'Solicitud de Registro IJUMICH',
        'reporte_parcial_1'       => 'Reporte Parcial #1',
        'reporte_parcial_2'       => 'Reporte Parcial #2',
        'reporte_parcial_3'       => 'Reporte Parcial #3',
        'carta_liberacion_interno'       => 'Carta de Liberación IJUMICH',
        'evaluacion_unidad_productiva'   => 'Evaluación de la Unidad Productiva',
        'evaluacion_global'              => 'Evaluación Global',
        default                          => ucwords(str_replace('_', ' ', $tipo)),
    };
}

// SS Interno — Alumno solicita carta de presentación → Admin (Paso 1)
function sendSsCartaPresentacionAdmin(
    string $studentName, string $studentEmail, string $matricula,
    string $organismoNombre, string $responsable
): string|false {
    $adminEmail = ssGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('ss_interno_carta_presentacion_admin', $adminEmail, [
        'studentName'     => $studentName,
        'studentEmail'    => $studentEmail,
        'matricula'       => $matricula,
        'organismoNombre' => $organismoNombre,
        'responsable'     => $responsable,
        'fechaEnvio'      => date('d/m/Y H:i'),
    ]);
}

// SS Interno — Alumno sube carta de prácticas → Admin (Paso 2)
function sendSsCartaPracticasAdmin(
    string $studentName, string $studentEmail, string $archivoNombre
): string|false {
    $adminEmail = ssGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('ss_interno_carta_practicas_admin', $adminEmail, [
        'studentName'   => $studentName,
        'studentEmail'  => $studentEmail,
        'archivoNombre' => $archivoNombre,
        'fechaEnvio'    => date('d/m/Y H:i'),
    ]);
}

// SS Interno — Alumno sube solicitud de registro → Admin (Paso 4)
function sendSsSolicitudRegistroAdmin(
    string $studentName, string $studentEmail, string $archivoNombre
): string|false {
    $adminEmail = ssGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('ss_interno_solicitud_registro_admin', $adminEmail, [
        'studentName'   => $studentName,
        'studentEmail'  => $studentEmail,
        'archivoNombre' => $archivoNombre,
        'fechaEnvio'    => date('d/m/Y H:i'),
    ]);
}

// SS Interno — Alumno sube reporte parcial → Admin (Paso 5)
function sendSsReporteParcialAdmin(
    string $studentName, string $studentEmail, int $numeroReporte, string $archivoNombre
): string|false {
    $adminEmail = ssGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('ss_interno_reporte_parcial_admin', $adminEmail, [
        'studentName'   => $studentName,
        'studentEmail'  => $studentEmail,
        'numeroReporte' => $numeroReporte,
        'archivoNombre' => $archivoNombre,
        'fechaEnvio'    => date('d/m/Y H:i'),
    ]);
}

// SS Interno — Alumno sube carta de liberación → Admin (Paso 7)
function sendSsCartaLiberacionAdmin(
    string $studentName, string $studentEmail, string $archivoNombre
): string|false {
    $adminEmail = ssGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('ss_interno_carta_liberacion_admin', $adminEmail, [
        'studentName'   => $studentName,
        'studentEmail'  => $studentEmail,
        'archivoNombre' => $archivoNombre,
        'fechaEnvio'    => date('d/m/Y H:i'),
    ]);
}

// SS Interno — Alumno sube Evaluación de la Unidad Productiva → Admin (Paso 8)
function sendSsEvaluacionUnidadProductivaAdmin(
    string $studentName, string $studentEmail, string $matricula, string $archivoNombre
): string|false {
    $adminEmail = ssGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('ss_interno_evaluacion_unidad_admin', $adminEmail, [
        'studentName'   => $studentName,
        'studentEmail'  => $studentEmail,
        'matricula'     => $matricula,
        'archivoNombre' => $archivoNombre,
        'fechaEnvio'    => date('d/m/Y H:i'),
    ]);
}

// SS Interno — Alumno sube Evaluación Global → Admin (Paso 9)
function sendSsEvaluacionGlobalAdmin(
    string $studentName, string $studentEmail, string $matricula, string $archivoNombre
): string|false {
    $adminEmail = ssGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('ss_interno_evaluacion_global_admin', $adminEmail, [
        'studentName'   => $studentName,
        'studentEmail'  => $studentEmail,
        'matricula'     => $matricula,
        'archivoNombre' => $archivoNombre,
        'fechaEnvio'    => date('d/m/Y H:i'),
    ]);
}

// SS Interno — Admin aprueba Evaluación Global → Alumno liberado definitivamente (Paso 9)
function sendSsEvaluacionGlobalAlumno(
    string $email, string $studentName, string $adminName, string $comentario = ''
): string|false {
    return sendTemplateByKey('ss_interno_evaluacion_global_alumno', $email, [
        'studentName'     => $studentName,
        'adminName'       => $adminName,
        'fechaLiberacion' => date('d/m/Y H:i'),
        'comentario'      => $comentario,
    ]);
}

// SS Interno — Admin aprueba Paso 1 → Alumno
function sendSsCartaPresentacionAprobada(
    string $email, string $studentName, string $comentario = ''
): string|false {
    return sendTemplateByKey('ss_interno_carta_presentacion_aprobada', $email, [
        'studentName' => $studentName,
        'comentario'  => $comentario,
    ]);
}

// SS Interno — Admin aprueba Paso 2 → Alumno
function sendSsCartaPracticasAprobada(
    string $email, string $studentName, string $comentario = ''
): string|false {
    return sendTemplateByKey('ss_interno_carta_practicas_aprobada', $email, [
        'studentName' => $studentName,
        'comentario'  => $comentario,
    ]);
}

// SS Interno — Admin devuelve solicitud de registro firmada → Alumno (Paso 4)
function sendSsSolicitudRegistroFirmada(
    string $email, string $studentName, string $comentario = ''
): string|false {
    return sendTemplateByKey('ss_interno_solicitud_registro_firmada', $email, [
        'studentName' => $studentName,
        'comentario'  => $comentario,
    ]);
}

// SS Interno — Admin aprueba reporte parcial → Alumno (Paso 5)
function sendSsReporteParcialAprobado(
    string $email, string $studentName, int $numeroReporte, string $comentario = ''
): string|false {
    $nextStepMsg = match($numeroReporte) {
        1 => '📌 Siguiente paso: sube tu Reporte Parcial #2 en 2 meses.',
        2 => '📌 Siguiente paso: sube tu Reporte Parcial #3 en 2 meses.',
        3 => '📌 ¡Todos los reportes aprobados! Ya puedes generar tu Carta de Conclusión (Paso 6).',
        default => '',
    };
    return sendTemplateByKey('ss_interno_reporte_parcial_aprobado', $email, [
        'studentName'   => $studentName,
        'numeroReporte' => $numeroReporte,
        'comentario'    => $comentario,
        'nextStepMsg'   => $nextStepMsg,
    ]);
}

// SS Interno — Admin aprueba carta liberación → Alumno (Paso 7→8)
function sendSsLiberacionCompleta(
    string $email, string $studentName, string $adminName,
    string $folio = '', string $comentario = ''
): string|false {
    return sendTemplateByKey('ss_interno_liberacion_completa', $email, [
        'studentName'     => $studentName,
        'adminName'       => $adminName,
        'folio'           => $folio,
        'fechaLiberacion' => date('d/m/Y H:i'),
        'comentario'      => $comentario,
    ]);
}

// SS Interno — Admin rechaza cualquier paso → Alumno
function sendSsSolicitudRechazada(
    string $email, string $studentName, string $tipo, string $adminName, string $comentario
): string|false {
    return sendTemplateByKey('ss_interno_solicitud_rechazada', $email, [
        'studentName'   => $studentName,
        'tipoDocumento' => ssTipoLabel($tipo),
        'adminName'     => $adminName,
        'comentario'    => $comentario ?: 'Sin comentarios adicionales.',
    ]);
}

// SS Interno — Alumno genera carta conclusión → Admin (Paso 6 notificación)
function sendSsCartaConclusionAdmin(
    string $studentName, string $studentEmail,
    string $folio, string $fechaInicio, string $fechaFin, int $horas
): string|false {
    $adminEmail = ssGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('ss_interno_carta_conclusion_admin', $adminEmail, [
        'studentName'     => $studentName,
        'studentEmail'    => $studentEmail,
        'folio'           => $folio,
        'fechaInicio'     => $fechaInicio,
        'fechaFin'        => $fechaFin,
        'horas'           => $horas,
        'fechaGeneracion' => date('d/m/Y H:i'),
    ]);
}

// 6) Prácticas Profesionales – info y formatos
function sendPracticasInfo(string $email, string $password)
{
    $loginUrl = "https://servicioypracticas.unimontrer.edu.mx/?pagina=login&user_type=alumno_practicas&mail={$email}&password={$password}";
    return sendTemplateByKey('practicas_info', $email, [
        'email' => $email,
        'password' => $password,
        'login_url' => $loginUrl,
    ]);
}

// Fase 1: Prácticas Profesionales - Carta de presentación
function sendPpCartaPresentacionGenerada(string $email, string $studentName, string $fechaVencimiento, int $diasHabilesDisponibles = 2) {
    return sendTemplateByKey('pp_carta_presentacion_generada', $email, [
        'studentName' => $studentName,
        'fechaVencimiento' => $fechaVencimiento,
        'diasHabilesDisponibles' => $diasHabilesDisponibles
    ]);
}
function sendPpCartaPresentacionExpirada(string $email, string $studentName) {
    return sendTemplateByKey('pp_carta_presentacion_expirada', $email, [
        'studentName' => $studentName
    ]);
}
function sendPpCartaPresentacionExpiradaAdmin(string $studentName) {
    $adminEmail = ppGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('pp_carta_presentacion_expirada_admin', $adminEmail, [
        'studentName' => $studentName
    ]);
}
function sendPpCartaPresentacionConfirmada(string $email, string $studentName) {
    return sendTemplateByKey('pp_carta_presentacion_confirmada', $email, [
        'studentName' => $studentName
    ]);
}


// 7) Reporte aceptado por revisor (organismo externo / admin)
function sendPracticasReportAcceptedEmail(string $email, string $studentName, string $userType)
{
    // Normaliza etiqueta para la plantilla
    if ($userType === 'organismo_externo')
        $userType = 'organismo externo';
    return sendTemplateByKey('practicas_report_accepted_by_reviewer', $email, [
        'studentName' => $studentName,
        'userType' => $userType,
    ]);
}

// 8) Reporte parcial aprobado por Admin
function sendPracticasReportAcceptedbyAdminEmail(string $email, string $studentName)
{
    return sendTemplateByKey('practicas_report_accepted_by_admin', $email, [
        'studentName' => $studentName,
    ]);
}

// 9) Reporte rechazado (por revisor/admin)
function sendPracticasReportRejectedEmail(string $email, string $studentName, string $userType, string $comments)
{
    if ($userType === 'organismo_externo')
        $userType = 'organismo externo';
    else
        $userType = 'administrador';

    return sendTemplateByKey('practicas_report_rejected', $email, [
        'studentName' => $studentName,
        'userType' => $userType,
        'comments' => $comments,
    ]);
}

// 10) Reporte final aprobado por Admin
function sendPracticasFinalReportAcceptedbyAdminEmail(string $email, string $studentName)
{
    return sendTemplateByKey('practicas_final_report_accepted_by_admin', $email, [
        'studentName' => $studentName,
    ]);
}

// 11) Nueva solicitud de capacitaciones
function sendSolicitudCapacitacion(string $email, string $studentName, string $matricula, string $solicitud)
{
    return sendTemplateByKey(
        'solicitud_capacitacion',
        $email,
        [
            'studentName' => $studentName,
            'matricula' => $matricula,
            'solicitud' => $solicitud
        ],
        'Solicitud de Capacitaciones - UNIMO'
    );

}

// 11.1) Reporte de incidencia de un practicante → Administrador
function sendReporteIncidenciaAdmin(string $email, array $vars)
{
    return sendTemplateByKey(
        'pp_reporte_incidencia_admin',
        $email,
        $vars,
        'Reporte de Incidencia - UNIMO'
    );
}

// 11.2) Acuse del reporte de incidencia → Organismo externo
function sendReporteIncidenciaConfirmacion(string $email, array $vars)
{
    return sendTemplateByKey(
        'pp_reporte_incidencia_confirmacion',
        $email,
        $vars,
        'Reporte de Incidencia - UNIMO'
    );
}

// 11.3) Seguimiento de incidencia → comunicado del administrador al practicante
function sendIncidenciaMensajeAlumno(string $email, array $vars)
{
    return sendTemplateByKey('pp_incidencia_mensaje_alumno', $email, $vars, 'Prácticas Profesionales - UNIMO');
}

// 11.3b) Seguimiento de incidencia → comunicado del administrador al organismo receptor
function sendIncidenciaMensajeEmpresa(string $email, array $vars)
{
    return sendTemplateByKey('pp_incidencia_mensaje_empresa', $email, $vars, 'Prácticas Profesionales - UNIMO');
}

// 11.4) Seguimiento de incidencia → convocatoria a junta
function sendIncidenciaJunta(string $email, array $vars)
{
    return sendTemplateByKey('pp_incidencia_junta', $email, $vars, 'Prácticas Profesionales - UNIMO');
}

// 11.5) Seguimiento de incidencia → caso atendido con solución
function sendIncidenciaCierre(string $email, array $vars)
{
    return sendTemplateByKey('pp_incidencia_cierre', $email, $vars, 'Prácticas Profesionales - UNIMO');
}

// 12) Solicitud de capacitaciones aceptada
function sendSolicitudCapacitacionAceptada(string $email, string $contactName, string $studentName, string $comentario, string $dateCreated)
{
    return sendTemplateByKey(
        'solicitud_capacitacion_aceptada',
        $email,
        [
            'contactName' => $contactName,
            'studentName' => $studentName,
            'comentario' => $comentario,
            'dateCreated' => $dateCreated
        ],
        'Solicitud Aceptada - UNIMO'
    );

}

// 13) Solicitud de capacitaciones rechazada
function sendSolicitudCapacitacionRechazada(string $email, string $contactName, string $studentName, string $dateCreated, string $comments)
{
    return sendTemplateByKey(
        'solicitud_capacitacion_rechazada',
        $email,
        [
            'contactName' => $contactName,
            'studentName' => $studentName,
            'dateCreated' => $dateCreated,
            'comments' => $comments,
        ],
        'Solicitud Rechazada - UNIMO'
    );
}

// 14) Nueva solicitud de prácticas
function sendSolicitudPracticas(string $email, string $contactName, string $organismoName, string $direccionPractica, string $actividades)
{
    $adminEmail = ppGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey(
        'solicitud_practicantes',
        $adminEmail,
        [
            'contactName' => $contactName,
            'organismoName' => $organismoName,
            'direccionPractica' => $direccionPractica,
            'actividades' => $actividades
        ],
        'Solicitud de Prácticantes - UNIMO'
    );
}

// 15) Envio de datos a nuevo usuario (al registrar cuenta)
function sendDataToNewUser(string $name, string $email, string $password, string $role)
{
    $loginUrl = "https://servicioypracticas.unimontrer.edu.mx/?pagina=login&user_type={$role}&mail={$email}&password={$password}";
    return sendTemplateByKey(
        'new_user_data',
        $email,
        [
            'name'      => $name,
            'email'     => $email,
            'password'  => $password,
            'login_url' => $loginUrl,
            'role'      => $role
        ],
        'Bienvenido a Servicio Social UNIMO - Tus datos de acceso'
    );
}

// 16) Baja de alumno de servicio social
function sendDropStudentEmail(string $email, string $studentName, string $reason, string $date)
{
    return sendTemplateByKey(
        'service_social_dropped',
        $email,
        [
            'studentName' => $studentName,
            'reason'      => $reason,
            'date'        => $date
        ],
        'Baja de servicio social - UNIMO'
    );
}

// 17) Solicitud alumno ingreso a evento de servicio social
function sendEventApplicationReceived(string $email, string $studentName, string $eventName, string $eventDate, string $organizerName, string $organizerEmail)
{
    return sendTemplateByKey(
        'student_event_application',
        $organizerEmail,
        [
            'studentName'   => $studentName,
            'eventName'     => $eventName,
            'eventDate'     => $eventDate,
            'organizerName' => $organizerName,
            'email'=> $email
        ],
        'Solicitud de ingreso a evento - UNIMO'
    );  
}

// 18) Aprobar Participación en el evento por el alumno
function sendAproveEventEmail(string $email, string $studentName, string $eventName, string $eventDate, int $points)
{
    return sendTemplateByKey(
        'event_participation_approved',
        $email,
        [
            'studentName' => $studentName,
            'eventName'   => $eventName,
            'eventDate'   => $eventDate,
            'points'      => $points
        ],
        'Aprobación de participación en evento - UNIMO'
    );
}

// 19) Rechazar Participación en el evento por el alumno
function sendDeclineEventEmail(string $email, string $studentName, string $eventName, string $eventDate)
{
    return sendTemplateByKey(
        'event_participation_rejected',
        $email,
        [
            'studentName' => $studentName,
            'eventName'   => $eventName,
            'eventDate'   => $eventDate
        ],
        'Rechazo de participación en evento - UNIMO'
    );
}

// 20) Registro de asistencia a prácticas profesionales
function sendAssistanceRegisteredEmail(string $email, string $studentName, string $date, string $organismoName, string $horaEntrada, string $horaSalida, string $actividad)
{
    return sendTemplateByKey(
        'assistance_registered',
        $email,
        [
            'studentName'   => $studentName,
            'date'          => $date,
            'organismoName' => $organismoName,
            'horaEntrada'   => $horaEntrada,
            'horaSalida'    => $horaSalida,
            'actividad'     => $actividad
        ],
        'Registro de asistencia - UNIMO'
    );
}

// 21) Aprobación de asistencia a prácticas profesionales
function sendAssistanceApprovedEmail(string $email, string $studentName, string $date, string $horaEntrada, string $horaSalida, string $actividad)
{
    return sendTemplateByKey(
        'assistance_approved',
        $email,
        [
            'studentName' => $studentName,
            'date'        => $date,
            'horaEntrada' => $horaEntrada,
            'horaSalida'  => $horaSalida,
            'actividad'   => $actividad
        ],
        'Aprobación de asistencia - UNIMO'
    );
}

// 22) Rechazo de asistencia a prácticas profesionales
function sendAssistanceRejectedEmail(string $email, string $studentName, string $date, string $horaEntrada, string $horaSalida, string $actividad)
{
    return sendTemplateByKey(
        'assistance_rejected',
        $email,
        [
            'studentName' => $studentName,
            'date'        => $date,
            'horaEntrada' => $horaEntrada,
            'horaSalida'  => $horaSalida,
            'actividad'   => $actividad
        ],
        'Rechazo de asistencia - UNIMO'
    );
}

// 23) Actualización de horarios en asistencia a prácticas profesionales
function sendAssistanceUpdatedEmail(string $email, string $studentName, string $date, string $horaEntrada, string $horaSalida, string $actividad)
{
    return sendTemplateByKey(
        'assistance_updated',
        $email,
        [
            'studentName' => $studentName,
            'date'        => $date,
            'horaEntrada' => $horaEntrada,
            'horaSalida'  => $horaSalida,
            'actividad'   => $actividad
        ],
        'Actualización de horarios de asistencia - UNIMO'
    );
}

// 24) Aceptacion de practicantes para un organismo externo
function sendSolicitudPracticasAceptada(string $email, string $contactName, string $degreeName)
{
    if (!$email) return false;
    return sendTemplateByKey(
        'solicitud_practicantes_aceptada',
        $email,
        [
            'contactName' => $contactName,
            'degreeName' => $degreeName
        ],
        'Solicitud de Prácticantes - UNIMO'
    );
}

// 25) Rechazo de practicantes para un organismo externo
function sendSolicitudPracticasRechazada(string $email, string $contactName, string $degreeName, string $motivo = '') {
    // Se notifica al organismo que envió la solicitud; con copia visible al área.
    $destino = $email !== '' ? $email : ppGetAdminEmail();
    if (!$destino) return false;

    // Bloque de motivo (solo si el administrador capturó uno)
    $motivoHtml = '';
    if (trim($motivo) !== '') {
        $motivoSeguro = nl2br(htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'));
        $motivoHtml = '<p><strong>Motivo:</strong></p>'
            . '<blockquote style="border-left:3px solid #dc3545;padding-left:1rem;color:#555;margin:1rem 0;">'
            . $motivoSeguro . '</blockquote>';
    }

    return sendTemplateByKey(
        'solicitud_practicantes_rechazada',
        $destino,
        [
            'contactName' => $contactName,
            'degreeName' => $degreeName,
            'motivoHtml' => $motivoHtml
        ],
        'Solicitud de Prácticantes - UNIMO'
    );
}

// 26) Notificación de nueva postulación a práctica (estudiante)
function sendPracticesApplicationReceived(string $email, string $studentName, string $practiceTitle)
{
    return sendTemplateByKey('practices_application_received', $email, [
        'studentName' => $studentName,
        'practice' => $practiceTitle
    ], 'Postulación recibida - UNIMO');
}

// 27) Notificación de nueva postulación a práctica (organismo externo)
function sendPracticesApplicationReceivedOrg(string $email, string $contactName, string $studentName, int $idPractice)
{
    return sendTemplateByKey('practices_application_received_org', $email, [
        'contactName' => $contactName,
        'studentName' => $studentName,
        'practice' => 'Práctica #' . $idPractice
    ], 'Nueva postulación a práctica - UNIMO');
}

// 28) Notificación de aceptación de prospecto a prácticas (estudiante) con motivo
function sendPracticasProspectAcceptedWithReason(string $email, string $studentName, string $organismoName, string $motivo, string $fechaInicio)
{
    return sendTemplateByKey(
        'pp_prospecto_aceptado_motivo',
        $email,
        [
            'studentName'   => $studentName,
            'organismoName' => $organismoName,
            'motivo'        => $motivo,
            'fechaInicio'   => $fechaInicio
        ],
        'Aceptación a prácticas - UNIMO'
    );
}

function sendPracticasProspectAcceptedOrg(string $email, string $contactName, string $studentName, string $fechaInicio)
{
    return sendTemplateByKey(
        'practicas_prospect_accepted_org',
        $email,
        [
            'contactName' => $contactName,
            'studentName' => $studentName,
            'fechaInicio' => $fechaInicio
        ],
        'Prospecto aceptado - UNIMO'
    );
}

function sendPracticasProspectRejectedWithReason(string $email, string $studentName, string $organismoName, string $motivo)
{
    return sendTemplateByKey(
        'pp_prospecto_rechazado_motivo',
        $email,
        [
            'studentName'   => $studentName,
            'organismoName' => $organismoName,
            'motivo'        => $motivo
        ],
        'Resultado de postulación - UNIMO'
    );
}

/* ═══════════════ FASE 6 · Correos del nuevo flujo de postulación ═══════════════ */

// Prepostulación recibida → empresa (con todas las respuestas del formulario + enlace)
function sendPrepostulacionEmpresa(string $email, string $contactName, string $studentName, string $matricula, string $practiceTitle, string $empresa, string $respuestasHtml, string $link)
{
    return sendTemplateByKey('pp_prepostulacion_empresa', $email, [
        'contactName' => $contactName,
        'studentName' => $studentName,
        'matricula' => $matricula,
        'practiceTitle' => $practiceTitle,
        'empresa' => $empresa,
        'respuestas' => $respuestasHtml,
        'link' => $link,
    ], 'Nueva prepostulación - UNIMO');
}

// Entrevista programada → alumno (con la carta de presentación adjunta)
function sendEntrevistaProgramadaAlumno(string $email, string $studentName, string $empresa, string $fecha, string $hora, string $modalidad, string $detalleModalidadHtml, array $attachments = [])
{
    return sendTemplateByKey('pp_entrevista_programada_alumno', $email, [
        'studentName' => $studentName,
        'empresa' => $empresa,
        'fecha' => $fecha,
        'hora' => $hora,
        'modalidad' => $modalidad,
        'detalleModalidad' => $detalleModalidadHtml,
    ], 'Entrevista programada - UNIMO', $attachments);
}

// Entrevista virtual → empresa (carta del alumno + enlace de la sesión)
function sendEntrevistaVirtualEmpresa(string $email, string $contactName, string $studentName, string $fecha, string $hora, string $url, array $attachments = [])
{
    return sendTemplateByKey('pp_entrevista_virtual_empresa', $email, [
        'contactName' => $contactName,
        'studentName' => $studentName,
        'fecha' => $fecha,
        'hora' => $hora,
        'url' => $url,
    ], 'Entrevista virtual - UNIMO', $attachments);
}

// Recordatorio de entrevista presencial → empresa (carta del alumno + fecha/hora/lugar)
function sendEntrevistaPresencialEmpresa(string $email, string $contactName, string $studentName, string $fecha, string $hora, string $direccion, array $attachments = [])
{
    return sendTemplateByKey('pp_entrevista_presencial_empresa', $email, [
        'contactName' => $contactName,
        'studentName' => $studentName,
        'fecha' => $fecha,
        'hora' => $hora,
        'direccion' => $direccion,
    ], 'Entrevista presencial - UNIMO', $attachments);
}

// Solicitud de retroalimentación tras la entrevista → empresa (cron)
function sendRetroalimentacionEmpresa(string $email, string $contactName, string $studentName, string $link)
{
    return sendTemplateByKey('pp_retroalimentacion_empresa', $email, [
        'contactName' => $contactName,
        'studentName' => $studentName,
        'link' => $link,
    ], 'Retroalimentación de entrevista - UNIMO');
}

// 29) Aceptación de postulación a área interna de prácticas profesionales
function sendAreaPostulacionAceptada(string $email, string $studentName, string $areaNombre, string $fechaInicio): string|false
{
    $css = is_file(EMAIL_CSS_PATH) ? (string) file_get_contents(EMAIL_CSS_PATH) : '';
    $subject  = 'Tu postulación fue aceptada – Área ' . $areaNombre;
    $fechaFmt = date('d/m/Y', strtotime($fechaInicio));
    $html = <<<HTML
    <html>
    <head><meta charset="UTF-8"><style>{$css}</style></head>
    <body>
      <div class="container">
        <div class="header">
          <img src="https://servicioypracticas.unimontrer.edu.mx/view/assets/images/logo-color.png" alt="UNIMO">
        </div>
        <div class="content">
          <h2 style="color:#198754;">¡Postulación aceptada!</h2>
          <p>Hola <strong>{$studentName}</strong>,</p>
          <p>Nos complace informarte que tu postulación al área interna de prácticas profesionales <strong>{$areaNombre}</strong> ha sido <strong style="color:#198754;">aceptada</strong>.</p>
          <p><strong>Fecha de inicio:</strong> {$fechaFmt}</p>
          <p>A partir de esta fecha podrás registrar tus asistencias en el sistema. El encargado del área aprobará cada registro.</p>
          <p>Ingresa al sistema para continuar:</p>
          <p style="text-align:center;">
            <a href="https://servicioypracticas.unimontrer.edu.mx/" style="background:#198754;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:600;">Ir al sistema</a>
          </p>
        </div>
        <div class="footer">Universidad Montrer (UNIMO) • Av Lázaro Cárdenas 1760, Chapultepec Sur, 58260 Morelia, Mich.</div>
      </div>
    </body>
    </html>
    HTML;
    $plain = "Hola {$studentName}, tu postulación al área {$areaNombre} fue aceptada. Fecha de inicio: {$fechaFmt}. Ingresa al sistema para registrar tus asistencias.";
    return MailService::sendMail($email, $subject, $html, $plain);
}

// 31) Notificación al encargado (docente) cuando un alumno se postula a su área
function sendNuevaPostulacionArea(string $teacherEmail, string $teacherName, string $studentName, string $areaNombre): string|false
{
    $css = is_file(EMAIL_CSS_PATH) ? (string) file_get_contents(EMAIL_CSS_PATH) : '';
    $subject = 'Nueva postulación en tu área – ' . $areaNombre;
    $html = <<<HTML
    <html>
    <head><meta charset="UTF-8"><style>{$css}</style></head>
    <body>
      <div class="container">
        <div class="header">
          <img src="https://servicioypracticas.unimontrer.edu.mx/view/assets/images/logo-color.png" alt="UNIMO">
        </div>
        <div class="content">
          <h2 style="color:#0d6efd;">Nueva postulación recibida</h2>
          <p>Hola <strong>{$teacherName}</strong>,</p>
          <p>El alumno <strong>{$studentName}</strong> se ha postulado al área de prácticas profesionales <strong>{$areaNombre}</strong> a tu cargo.</p>
          <p>Ingresa al sistema para revisar y gestionar la postulación:</p>
          <p style="text-align:center;">
            <a href="https://servicioypracticas.unimontrer.edu.mx/" style="background:#0d6efd;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:600;">Ir al sistema</a>
          </p>
        </div>
        <div class="footer">Universidad Montrer (UNIMO) • Av Lázaro Cárdenas 1760, Chapultepec Sur, 58260 Morelia, Mich.</div>
      </div>
    </body>
    </html>
    HTML;
    $plain = "Hola {$teacherName}, el alumno {$studentName} se ha postulado al área {$areaNombre}. Ingresa al sistema para gestionarla.";
    return MailService::sendMail($teacherEmail, $subject, $html, $plain);
}

// 30) Rechazo de postulación a área interna de prácticas profesionales
function sendAreaPostulacionRechazada(string $email, string $studentName, string $areaNombre): string|false
{
    $css = is_file(EMAIL_CSS_PATH) ? (string) file_get_contents(EMAIL_CSS_PATH) : '';
    $subject = 'Resultado de tu postulación – Área ' . $areaNombre;
    $html = <<<HTML
    <html>
    <head><meta charset="UTF-8"><style>{$css}</style></head>
    <body>
      <div class="container">
        <div class="header">
          <img src="https://servicioypracticas.unimontrer.edu.mx/view/assets/images/logo-color.png" alt="UNIMO">
        </div>
        <div class="content">
          <h2 style="color:#dc3545;">Postulación no aceptada</h2>
          <p>Hola <strong>{$studentName}</strong>,</p>
          <p>Lamentamos informarte que tu postulación al área <strong>{$areaNombre}</strong> <strong style="color:#dc3545;">no fue aceptada</strong> en esta ocasión.</p>
          <p>Si tienes dudas, comunícate con la coordinación de prácticas profesionales para más información.</p>
          <p style="text-align:center;">
            <a href="https://servicioypracticas.unimontrer.edu.mx/" style="background:#0d6efd;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:600;">Ir al sistema</a>
          </p>
        </div>
        <div class="footer">Universidad Montrer (UNIMO) • Av Lázaro Cárdenas 1760, Chapultepec Sur, 58260 Morelia, Mich.</div>
      </div>
    </body>
    </html>
    HTML;
    $plain = "Hola {$studentName}, tu postulación al área {$areaNombre} no fue aceptada. Contacta a la coordinación para más información.";
    return MailService::sendMail($email, $subject, $html, $plain);
}

// --- FASE 3: EMAILS DE STRIKES Y BLOQUEOS ---

function sendStrikeAdvertenciaAlumno(string $email, string $studentName, string $fecha, float $horasReales, int $numStrike)
{
    return sendTemplateByKey('pp_strike_advertencia_alumno', $email, [
        'studentName' => $studentName,
        'fecha' => $fecha,
        'horasReales' => $horasReales,
        'numStrike' => $numStrike
    ]);
}

function sendStrikeBajaAlumno(string $email, string $studentName, string $fecha, float $horasReales, int $numStrike)
{
    return sendTemplateByKey('pp_strike_baja_alumno', $email, [
        'studentName' => $studentName,
        'fecha' => $fecha,
        'horasReales' => $horasReales,
        'numStrike' => $numStrike
    ]);
}

function sendStrikeOrganismo(string $email, string $orgName, string $studentName, string $fecha, float $horasReales, int $strikesCount)
{
    return sendTemplateByKey('pp_strike_organismo', $email, [
        'orgName' => $orgName,
        'studentName' => $studentName,
        'fecha' => $fecha,
        'horasReales' => $horasReales,
        'strikesCount' => $strikesCount
    ]);
}

function sendStrikeAdmin(string $studentName, string $orgName, string $fecha, float $horasReales, int $strikesCount)
{
    $adminEmail = ppGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('pp_strike_admin', $adminEmail, [
        'studentName' => $studentName,
        'orgName' => $orgName,
        'fecha' => $fecha,
        'horasReales' => $horasReales,
        'strikesCount' => $strikesCount
    ]);
}

function sendOrganismoBloqueadoAuto(string $email, string $orgName, string $motivo)
{
    return sendTemplateByKey('pp_organismo_bloqueado_auto', $email, [
        'orgName' => $orgName,
        'motivo' => $motivo
    ]);
}

function sendOrganismoBloqueadoAdminNotif(string $orgName, string $studentName)
{
    $adminEmail = ppGetAdminEmail();
    if (!$adminEmail) return false;
    return sendTemplateByKey('pp_organismo_bloqueado_admin_notif', $adminEmail, [
        'orgName' => $orgName,
        'studentName' => $studentName
    ]);
}
