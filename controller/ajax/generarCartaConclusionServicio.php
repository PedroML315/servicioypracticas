<?php
declare(strict_types=1);

require_once __DIR__ . '/../../model/forms.models.php';
require_once __DIR__ . '/../forms.controller.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../emails.php';

use Dompdf\Dompdf;

session_start();

// ══════════════════════════════════════════════════════════════════
//  SEGURIDAD – Solo el alumno dueño puede generar su propia carta
// ══════════════════════════════════════════════════════════════════
if (empty($_SESSION['user'])) {
    http_response_code(403);
    exit('No autorizado');
}

$role      = $_SESSION['user']['role'] ?? '';
$idStudent = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);

// Solo alumnos (no admins, no organismos)
if ($role !== 'student') {
    http_response_code(403);
    exit('Esta carta solo puede ser generada por el alumno titular.');
}

if ($idStudent <= 0) {
    http_response_code(400);
    exit('Sesión inválida');
}

// ── CSRF / one-time token de sesión ─────────────────────────────
// Se genera un token en el dashboard y se valida aquí para evitar
// que alguien acceda directamente a la URL sin pasar por la UI.
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN']   ?? '';
$csrfGet    = $_GET['csrf_token']              ?? '';
$csrfToken  = $csrfHeader ?: $csrfGet;
$sessionCsrf = $_SESSION['csrf_token'] ?? '';

if (!$sessionCsrf || !hash_equals($sessionCsrf, $csrfToken)) {
    http_response_code(403);
    exit('Token de seguridad inválido. Recarga la página e intenta de nuevo.');
}

// ── Parámetros de periodo ────────────────────────────────────────
$fechaInicioRaw = trim($_GET['fecha_inicio'] ?? '');
$fechaFinRaw    = trim($_GET['fecha_fin']    ?? '');

if (!$fechaInicioRaw || !$fechaFinRaw) {
    http_response_code(400);
    exit('Debes indicar fecha_inicio y fecha_fin en el formato YYYY-MM-DD.');
}

// Validar formato fecha
$disFechaInicio = \DateTime::createFromFormat('Y-m-d', $fechaInicioRaw);
$disFechaFin    = \DateTime::createFromFormat('Y-m-d', $fechaFinRaw);

if (!$disFechaInicio || !$disFechaFin) {
    http_response_code(400);
    exit('Formato de fecha inválido (debe ser YYYY-MM-DD).');
}

if ($disFechaFin <= $disFechaInicio) {
    http_response_code(400);
    exit('La fecha de fin debe ser posterior a la fecha de inicio.');
}

// ══════════════════════════════════════════════════════════════════
//  VERIFICAR QUE LOS 3 REPORTES ESTÉN APROBADOS
// ══════════════════════════════════════════════════════════════════
$pdo = Conexion::conectar();

$stmtRep = $pdo->prepare("
    SELECT tipo, status
    FROM servicio_social_ijumich
    WHERE student_id = :sid
      AND tipo IN ('reporte_parcial_1','reporte_parcial_2','reporte_parcial_3')
      AND status = 'aprobado'
");
$stmtRep->execute([':sid' => $idStudent]);
$reportesAprobados = $stmtRep->fetchAll(PDO::FETCH_COLUMN, 0);

$required = ['reporte_parcial_1', 'reporte_parcial_2', 'reporte_parcial_3'];
foreach ($required as $r) {
    if (!in_array($r, $reportesAprobados, true)) {
        http_response_code(403);
        exit('Los tres reportes parciales deben estar aprobados para generar esta carta.');
    }
}

// ── Verificar que NO haya ya una carta de conclusión generada ────
// Si ya existe, simplemente la regresa (evita duplicados en BD)
$stmtExist = $pdo->prepare("
    SELECT id FROM cartas_conclusion_servicio
    WHERE student_id = :sid
    ORDER BY id DESC LIMIT 1
");
$stmtExist->execute([':sid' => $idStudent]);
$existente = $stmtExist->fetch(PDO::FETCH_ASSOC);

// ══════════════════════════════════════════════════════════════════
//  DATOS DEL ALUMNO
// ══════════════════════════════════════════════════════════════════
$stmtSt = $pdo->prepare("
    SELECT s.firstname, s.lastname, s.lastnameMom, s.matricula, s.gender, s.idDegree
    FROM student s
    WHERE s.idStudent = :sid
");
$stmtSt->execute([':sid' => $idStudent]);
$student = $stmtSt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    http_response_code(404);
    exit('Alumno no encontrado.');
}

// ══════════════════════════════════════════════════════════════════
//  CARGAR CONFIG JSON
// ══════════════════════════════════════════════════════════════════
$CONFIG_PATH = __DIR__ . '/../../config/carta_conclusion_config.json';
$c = [];
if (file_exists($CONFIG_PATH)) {
    $decoded = json_decode(file_get_contents($CONFIG_PATH), true);
    if (is_array($decoded)) $c = $decoded;
}

function val(array $arr, string $path, $default = '') {
    $tmp = $arr;
    foreach (explode('.', $path) as $k) {
        if (!is_array($tmp) || !array_key_exists($k, $tmp)) return $default;
        $tmp = $tmp[$k];
    }
    return $tmp ?? $default;
}

// ══════════════════════════════════════════════════════════════════
//  CONSTRUIR DATOS
// ══════════════════════════════════════════════════════════════════
$mesesArr = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaHoy = date('j') . ' de ' . $mesesArr[(int)date('n') - 1] . ' de ' . date('Y');

$studentName = mb_strtoupper(trim(
    ($student['firstname'] ?? '') . ' ' . ($student['lastname'] ?? '') . ' ' . ($student['lastnameMom'] ?? '')
));
$matricula   = $student['matricula'] ?? '';
$degree      = FormsModel::mdlSearchDegrees($student['idDegree'] ?? null);
$degreeName  = 'LICENCIATURA EN ' . mb_strtoupper($degree['nameDegree'] ?? 'DESCONOCIDA');

// Formato legible de fechas
$fmtInicio = $disFechaInicio->format('j') . ' de ' . $mesesArr[(int)$disFechaInicio->format('n') - 1] . ' ' . $disFechaInicio->format('Y');
$fmtFin    = $disFechaFin->format('j')    . ' de ' . $mesesArr[(int)$disFechaFin->format('n') - 1]    . ' ' . $disFechaFin->format('Y');

$horas  = (int) val($c, 'config.horas', 480);
$meses  = (int) val($c, 'config.meses', 6);

// ── Folio ────────────────────────────────────────────────────────
// Reutiliza o genera folio
if ($existente) {
    $stmtFol = $pdo->prepare("SELECT code FROM cartas_conclusion_servicio WHERE id = :id");
    $stmtFol->execute([':id' => $existente['id']]);
    $folio = $stmtFol->fetchColumn() ?: 'DSS-CCSS-0-' . date('Y');
} else {
    // Siguiente correlativo del año
    $year = date('Y');
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM cartas_conclusion_servicio WHERE YEAR(created_at) = :y");
    $stmtCount->execute([':y' => $year]);
    $num   = (int)$stmtCount->fetchColumn() + 1;
    $prefix = val($c, 'header.folio_prefix', 'DSS-CCSS');
    $folio = $prefix . '-' . str_pad((string)$num, 3, '0', STR_PAD_LEFT) . '-' . $year;

    // Registrar en BD
    $stmtIns = $pdo->prepare("
        INSERT INTO cartas_conclusion_servicio (code, student_id, fecha_inicio, fecha_fin, horas, meses)
        VALUES (:code, :sid, :fi, :ff, :h, :m)
    ");
    $stmtIns->execute([
        ':code' => $folio,
        ':sid'  => $idStudent,
        ':fi'   => $fechaInicioRaw,
        ':ff'   => $fechaFinRaw,
        ':h'    => $horas,
        ':m'    => $meses,
    ]);

    // ── Notificación por correo solo en primera generación ─────────
    $studentEmail = $_SESSION['user']['email'] ?? '';
    if ($studentEmail !== '') {
        sendCartaConclusionAlumno(
            $studentEmail,
            $studentName,
            $folio,
            $disFechaInicio->format('d/m/Y'),
            $disFechaFin->format('d/m/Y'),
            $horas,
            $meses
        );
    }
    // Notificación al admin SS
    $stEmailNotif = $_SESSION['user']['email'] ?? '';
    sendSsCartaConclusionAdmin(
        $studentName,
        $stEmailNotif,
        $folio,
        $disFechaInicio->format('d/m/Y'),
        $disFechaFin->format('d/m/Y'),
        $horas
    );
}

// ── Placeholders ─────────────────────────────────────────────────
$vars = [
    '{{studentName}}'  => $studentName,
    '{{matricula}}'    => $matricula,
    '{{degreeName}}'   => $degreeName,
    '{{fecha}}'        => $fechaHoy,
    '{{folio}}'        => $folio,
    '{{fechaInicio}}'  => strtolower($fmtInicio),
    '{{fechaFin}}'     => strtolower($fmtFin),
    '{{horas}}'        => (string)$horas,
    '{{meses}}'        => (string)$meses,
];

// ── Config valores ────────────────────────────────────────────────
$headerBarColor  = val($c, 'header.bar_color',          '#006837');
$headerLogoUrl   = val($c, 'header.logo_url',            '');
$headerLogoW     = (int) val($c, 'layout.header_logo_width', 150);
$headerCityLine  = strtr(val($c, 'header.city_line',    'Morelia, Mich., a {{fecha}}.'), $vars);
$headerSubject   = val($c, 'header.subject',             'Carta de conclusión de Servicio Social.');
$showFolio       = (bool) val($c, 'header.show_folio',   true);

$fontFamily      = val($c, 'layout.font_family',         'Arial, sans-serif');
$fontSize        = (int) val($c, 'layout.font_size_pt',  12);
$padding         = (int) val($c, 'layout.content_padding_px', 40);

$recCargo        = val($c, 'recipient.cargo',   'Subdirector de Servicio Social y Pasantes');
$recNombre       = val($c, 'recipient.nombre',  'Lic. Alejandro Cruz Ferreyra');
$recOrganismo    = val($c, 'recipient.organismo','Instituto de la Juventud Michoacana');

$sigLegend       = val($c, 'signature.legend',           'Atentamente');
$sigImgUrl       = val($c, 'signature.signature_img_url', '');
$sigWidth        = (int) val($c, 'signature.signature_width', 200);
$sealImgUrl      = val($c, 'signature.seal_img_url',     '');
$sealW           = (int) val($c, 'signature.seal.width', 240);
$sealTop         = (int) val($c, 'signature.seal.top',   -72);
$sealTopFirma    = (int) val($c, 'signature.seal.top_firma', -20);
$sealLeft        = (int) val($c, 'signature.seal.left_percent', 47);
$sealOpacity     = (float) val($c, 'signature.seal.opacity', 0.8);
$signerName      = val($c, 'signature.signer_name', 'MGH. Karla Mariana Fonseca Munguia');
$signerRole      = val($c, 'signature.signer_role', 'Coordinador de Servicio Social UNIMO');

$footerBarColor  = val($c, 'footer.bottom_bar_color',    '#006837');
$footerLogoUrl   = val($c, 'footer.logo_url',            '');
$footerLogoW     = (int) val($c, 'layout.footer_logo_width', 150);
$footerContact   = val($c, 'footer.contact_line',        '');
$footerText      = val($c, 'footer.bottom_text',         '');

// ── Imágenes a base64 ─────────────────────────────────────────────
function imgToDataUri(string $url): string {
    if ($url === '') return '';
    $filename  = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
    $localPath = __DIR__ . '/../../view/assets/images/' . $filename;
    $data = file_exists($localPath) ? file_get_contents($localPath) : @file_get_contents($url);
    if (!$data) return $url;
    $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = match($ext) {
        'jpg','jpeg' => 'image/jpeg',
        'gif'        => 'image/gif',
        'svg'        => 'image/svg+xml',
        'webp'       => 'image/webp',
        default      => 'image/png',
    };
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

$headerLogoUrl = imgToDataUri($headerLogoUrl);
$sigImgUrl     = imgToDataUri($sigImgUrl);
$sealImgUrl    = imgToDataUri($sealImgUrl);
$footerLogoUrl = imgToDataUri($footerLogoUrl);

// ── Cuerpo ────────────────────────────────────────────────────────
$paragraphs = val($c, 'body.paragraphs', []);
if (empty($paragraphs)) {
    $paragraphs = [
        'En relación a la solicitud de <strong>{{studentName}}</strong>, de la <strong>{{degreeName}}</strong> de <strong>UNIVERSIDAD MONTRER</strong>, con número de matrícula <strong>{{matricula}}</strong>, me permito informarle que ha <strong>concluido</strong> el desarrollo del <strong>Servicio Social</strong> en este organismo receptor denominado <strong>Universidad Montrer</strong>, cubriendo un total <strong>{{horas}} hrs</strong> en un periodo de <strong>{{meses}} meses</strong> del <strong>{{fechaInicio}}</strong> al <strong>{{fechaFin}}</strong>.',
        'Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo.',
    ];
}

$bodyHtml = '';
foreach ($paragraphs as $p) {
    $bodyHtml .= '<p>' . strtr($p, $vars) . '</p>';
}

$folioHtml = $showFolio ? "<div class=\"asunto\"><strong>{$folio}</strong></div>" : '';

// ══════════════════════════════════════════════════════════════════
//  HTML DEL PDF
// ══════════════════════════════════════════════════════════════════
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 0; }
    html, body { margin: 0; padding: 0; font-family: {$fontFamily}; font-size: {$fontSize}pt; }
    .header-bar { background-color: {$headerBarColor}; height: 40px; width: 100%; }
    .contenido  { padding: 10px {$padding}px; padding-bottom: 140px; box-sizing: border-box; }
    table.header { width: 100%; border-bottom: 1px solid #ccc; }
    .header-left  { width: {$headerLogoW}px; text-align: center; }
    .header-left img { width: {$headerLogoW}px; }
    .header-right { text-align: right; vertical-align: top; }
    .fecha   { font-size: 11pt; color: #444; }
    .asunto  { margin-top: 10px; font-weight: bold; }
    .receptor { margin-top: 15px; font-weight: bold; line-height: 1.5; }
    .cuerpo  { margin-top: 20px; text-align: justify; line-height: 1.6; font-size: 11pt; }
    .cuerpo p { margin: 0 0 14px 0; }
    .firma   { position: relative; text-align: center; margin-top: 30px; }
    .firma img.firma-img {
      width: {$sigWidth}px;
      position: absolute;
      top: {$sealTopFirma}px;
      left: 50%;
      transform: translateX(-50%);
      opacity: {$sealOpacity};
      z-index: 1;
    }
    .firma img.sello {
      position: absolute;
      width: {$sealW}px;
      top: {$sealTop}px;
      left: {$sealLeft}%;
      opacity: {$sealOpacity};
      z-index: 0;
    }
    .firma-nombre { font-size: 10pt; font-weight: bold; margin-top: 80px; }
    .firma-cargo  { font-size: 9pt; color: #555; }
    .footer-wrap { position: fixed; bottom: 0; left: 0; right: 0; }
    .footer-bar  { background-color: {$footerBarColor}; height: 10px; width: 100%; }
    .footer-content { background-color: #f5f5f5; padding: 6px {$padding}px; }
    table.footer { width: 100%; }
    .footer-logo-cell { width: {$footerLogoW}px; }
    .footer-logo-cell img { width: {$footerLogoW}px; }
    .footer-text { font-size: 8pt; color: #555; text-align: right; vertical-align: middle; }
  </style>
</head>
<body>
  <div class="header-bar"></div>
  <div class="contenido">
    <table class="header">
      <tr>
        <td class="header-left"><img src="{$headerLogoUrl}" alt="Logo UNIMO"></td>
        <td class="header-right">
          <div class="fecha">{$headerCityLine}</div>
          <div class="asunto">Asunto: <strong>{$headerSubject}</strong></div>
          {$folioHtml}
        </td>
      </tr>
    </table>
    <div class="receptor">
      {$recNombre}<br>
      {$recCargo}<br>
      {$recOrganismo}
    </div>
    <div class="cuerpo">
      <br>
      <p><strong>Presente</strong></p>
      <br>
      {$bodyHtml}
    </div>
    <div class="firma">
      <p>{$sigLegend}</p>
      <img class="sello"     src="{$sealImgUrl}"  alt="">
      <img class="firma-img" src="{$sigImgUrl}"   alt="Firma">
      <div class="firma-nombre">{$signerName}</div>
      <div class="firma-cargo">{$signerRole}</div>
    </div>
  </div>
  <div class="footer-wrap">
    <div class="footer-bar"></div>
    <div class="footer-content">
      <table class="footer">
        <tr>
          <td class="footer-logo-cell"><img src="{$footerLogoUrl}" alt="Logo"></td>
          <td class="footer-text">
            {$footerContact}<br>
            {$footerText}
          </td>
        </tr>
      </table>
    </div>
  </div>
</body>
</html>
HTML;

// ══════════════════════════════════════════════════════════════════
//  GENERAR PDF
// ══════════════════════════════════════════════════════════════════
if (ob_get_length()) ob_end_clean();

$dompdf = new Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'Arial']);
$dompdf->loadHtml($html);
$dompdf->setPaper('Letter', 'portrait');
$dompdf->render();

$safeNombre = preg_replace('/\s+/', '_', $studentName);
$filename   = 'CartaConclusionServicio_' . $safeNombre . '_' . date('Ymd') . '.pdf';

$dompdf->stream($filename, ['Attachment' => true]);
