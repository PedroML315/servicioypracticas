<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once "../../model/forms.models.php";
require_once "../forms.controller.php";
require_once __DIR__ . '/../../vendor/autoload.php';

// ── CRIT-002: Arrancar sesión una sola vez al inicio ──────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
// ──────────────────────────────────────────────────────────────────────────

use setasign\Fpdi\Fpdi;

/**
 * Estampa firma y sello centrados en la última página usando FPDI.
 * Posicionamiento idéntico al original: firma centrada, sello 25mm a la derecha del centro.
 */
function stampPdfWithFirmaYSello(string $pdfAbsPath): bool
{
    if (!file_exists($pdfAbsPath)) return false;

    $configPath = __DIR__ . '/../../config/carta_presentacion.json';
    $c       = file_exists($configPath) ? (json_decode(file_get_contents($configPath), true) ?? []) : [];
    $sigUrl  = $c['signature']['signature_img_url'] ?? '';
    $sealUrl = $c['signature']['seal_img_url'] ?? '';
    $sigWpx  = (int)($c['signature']['signature_width'] ?? 200);
    $sealWpx = (int)($c['signature']['seal']['width'] ?? 240);
    $base    = __DIR__ . '/../../';

    $resolveImg = static function(string $url) use ($base): string {
        if ($url === '') return '';
        $decoded = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
        $local   = $base . 'view/assets/images/' . $decoded;
        if (file_exists($local)) return $local;
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $raw = @file_get_contents($url);
        if ($raw !== false) { file_put_contents($tmp, $raw); return $tmp; }
        return '';
    };

    $sigImgPath  = $resolveImg($sigUrl);
    $sealImgPath = $resolveImg($sealUrl);
    if (!$sigImgPath && !$sealImgPath) {
        error_log('[stampFpdi] No se encontraron imágenes de firma/sello');
        return false;
    }

    /* ── Posicionamiento idéntico al original ── */
    $sigWmm  = (float)round($sigWpx  * 0.264583) * 0.90;
    $sealWmm = (float)round($sealWpx * 0.264583);
    $firmaFromBot = 105.0;  // mm desde borde inferior
    $sealFromBot  = 58.0;   // mm desde borde inferior

    try {
        $pdf        = new \setasign\Fpdi\Fpdi('P', 'mm');
        $pageCount  = $pdf->setSourceFile($pdfAbsPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size  = $pdf->getTemplateSize($tplId);
            $pdf->addPage(($size['width'] > $size['height']) ? 'L' : 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

            if ($i === $pageCount) {
                $pageW = (float)$size['width'];
                $pageH = (float)$size['height'];

                // Sello: centrado + 25mm a la derecha, coordenada desde arriba
                if ($sealImgPath) {
                    $info = @getimagesize($sealImgPath);
                    if ($info && $info[0] > 0) {
                        $sealH = $sealWmm * ($info[1] / $info[0]);
                        $sealX = ($pageW - $sealWmm) / 2.0 + 25.0;
                        $sealY = $pageH - $sealFromBot - $sealH;
                        $pdf->Image($sealImgPath, $sealX, $sealY, $sealWmm, 0);
                    }
                }

                // Firma: centrada
                if ($sigImgPath) {
                    $info = @getimagesize($sigImgPath);
                    if ($info && $info[0] > 0) {
                        $sigH = $sigWmm * ($info[1] / $info[0]);
                        $sigX = ($pageW - $sigWmm) / 2.0;
                        $sigY = $pageH - $firmaFromBot - $sigH;
                        $pdf->Image($sigImgPath, $sigX, $sigY, $sigWmm, 0);
                    }
                }
            }
        }

        // Escribir a temporal y reemplazar el original
        $tmp = $pdfAbsPath . '.tmp.pdf';
        $pdf->Output('F', $tmp);
        if (!file_exists($tmp)) return false;
        rename($tmp, $pdfAbsPath);
        return true;

    } catch (\Exception $e) {
        error_log('[stampFpdi] Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Estampa firma/sello y rellena los campos del PDF "Evaluación de la Unidad Productiva".
 * Lee las coordenadas desde storage/generated/evaluacion_unidad_productiva_coordinates.json.
 * Guarda el resultado como {nombre}_firmado.{ext} en el mismo directorio.
 *
 * @param  string $pdfAbsPath   Ruta absoluta al PDF original subido por el alumno
 * @param  string $outputAbsPath Ruta absoluta de salida para el PDF procesado
 * @return bool
 */
function stampEvaluacionUnidadProductiva(string $pdfAbsPath, string $outputAbsPath): bool
{
    if (!file_exists($pdfAbsPath)) return false;

    /* ── Leer JSON de coordenadas ── */
    $coordPath = __DIR__ . '/../../storage/generated/evaluacion_unidad_productiva_coordinates.json';
    $campos    = [];
    $firmaConf = null;
    $selloConf = null;
    if (file_exists($coordPath)) {
        $json = json_decode(file_get_contents($coordPath), true);
        if (is_array($json)) {
            foreach ($json as $key => $campo) {
                if ($key === '_meta') continue;
                if ($key === 'firma')  { $firmaConf = $campo; continue; }
                if ($key === 'sello')  { $selloConf = $campo; continue; }
                if (isset($campo['page'], $campo['x'], $campo['y'], $campo['default_value'])) {
                    $campos[] = $campo;
                }
            }
        }
    }

    /* ── Config firma/sello ── */
    $configPath = __DIR__ . '/../../config/carta_presentacion.json';
    $cfg     = file_exists($configPath) ? (json_decode(file_get_contents($configPath), true) ?? []) : [];
    $sigUrl  = $cfg['signature']['signature_img_url']  ?? '';
    $sealUrl = $cfg['signature']['seal_img_url']       ?? '';
    $sigWpx  = (int)($cfg['signature']['signature_width'] ?? 200);
    $sealWpx = (int)($cfg['signature']['seal']['width']   ?? 240);
    $base    = __DIR__ . '/../../';

    $resolveImg = static function(string $url) use ($base): string {
        if ($url === '') return '';
        $decoded = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
        $local   = $base . 'view/assets/images/' . $decoded;
        if (file_exists($local)) return $local;
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $raw = @file_get_contents($url);
        if ($raw !== false) { file_put_contents($tmp, $raw); return $tmp; }
        return '';
    };

    $sigImgPath  = $resolveImg($sigUrl);
    $sealImgPath = $resolveImg($sealUrl);

    $sigWmm  = (float)round($sigWpx  * 0.264583) * 0.90;
    $sealWmm = (float)round($sealWpx * 0.264583);

    try {
        $pdf       = new \setasign\Fpdi\Fpdi('P', 'mm');
        $pageCount = $pdf->setSourceFile($pdfAbsPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size  = $pdf->getTemplateSize($tplId);
            $pdf->addPage(($size['width'] > $size['height']) ? 'L' : 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

            /* ── Relleno de campos de texto en la página correspondiente ── */
            foreach ($campos as $campo) {
                if ((int)$campo['page'] !== $i) continue;
                $fontSize = isset($campo['font_size']) ? (int)$campo['font_size'] : 9;
                $pdf->SetFont('Helvetica', '', $fontSize);
                $pdf->SetTextColor(0, 0, 0);
                $cx = (float)$campo['x'];
                $cy = (float)$campo['y'];
                $cw = isset($campo['width'])  ? (float)$campo['width']  : 30;
                $ch = isset($campo['height']) ? (float)$campo['height'] : 6;
                $align = $campo['align'] ?? 'C';
                $pdf->SetXY($cx, $cy);
                $pdf->Cell($cw, $ch, $campo['default_value'], 0, 0, $align);
            }

            /* ── Firma y sello (posiciones desde JSON o fallback centrado) ── */
            if ($i === $pageCount) {
                $pageW = (float)$size['width'];
                $pageH = (float)$size['height'];

                // Sello
                if ($sealImgPath) {
                    $info = @getimagesize($sealImgPath);
                    if ($info && $info[0] > 0) {
                        $sealH = $sealWmm * ($info[1] / $info[0]);
                        if ($selloConf && isset($selloConf['x'], $selloConf['y'])) {
                            $sealX = (float)$selloConf['x'];
                            $sealY = (float)$selloConf['y'];
                            $sealW = isset($selloConf['width']) ? (float)$selloConf['width'] : $sealWmm;
                        } else {
                            $sealW = $sealWmm;
                            $sealX = ($pageW - $sealW) / 2.0 + 25.0;
                            $sealY = $pageH - 62.0 - $sealH;
                        }
                        $pdf->Image($sealImgPath, $sealX, $sealY, $sealW, 0);
                    }
                }
                // Firma
                if ($sigImgPath) {
                    $info = @getimagesize($sigImgPath);
                    if ($info && $info[0] > 0) {
                        $sigH = $sigWmm * ($info[1] / $info[0]);
                        if ($firmaConf && isset($firmaConf['x'], $firmaConf['y'])) {
                            $sigX = (float)$firmaConf['x'];
                            $sigY = (float)$firmaConf['y'];
                            $sigW = isset($firmaConf['width']) ? (float)$firmaConf['width'] : $sigWmm;
                        } else {
                            $sigW = $sigWmm;
                            $sigX = ($pageW - $sigW) / 2.0;
                            $sigY = $pageH - 110.0 - $sigH;
                        }
                        $pdf->Image($sigImgPath, $sigX, $sigY, $sigW, 0);
                    }
                }
            }
        }

        $pdf->Output('F', $outputAbsPath);
        return file_exists($outputAbsPath);

    } catch (\Exception $e) {
        error_log('[stampEvalUnidad] Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Estampa firma/sello y rellena los campos del PDF "Evaluación Global".
 * Lee las coordenadas desde storage/generated/evaluacion_global_coordinates.json.
 */
function stampEvaluacionGlobal(string $pdfAbsPath, string $outputAbsPath): bool
{
    if (!file_exists($pdfAbsPath)) return false;

    $coordPath = __DIR__ . '/../../storage/generated/evaluacion_global_coordinates.json';
    $campos    = [];
    $firmaConf = null;
    $selloConf = null;
    if (file_exists($coordPath)) {
        $json = json_decode(file_get_contents($coordPath), true);
        if (is_array($json)) {
            foreach ($json as $key => $campo) {
                if ($key === '_meta') continue;
                if ($key === 'firma')  { $firmaConf = $campo; continue; }
                if ($key === 'sello')  { $selloConf = $campo; continue; }
                if (isset($campo['page'], $campo['x'], $campo['y'], $campo['default_value'])) {
                    $campos[] = $campo;
                }
            }
        }
    }

    $configPath = __DIR__ . '/../../config/carta_presentacion.json';
    $cfg     = file_exists($configPath) ? (json_decode(file_get_contents($configPath), true) ?? []) : [];
    $sigUrl  = $cfg['signature']['signature_img_url']  ?? '';
    $sealUrl = $cfg['signature']['seal_img_url']       ?? '';
    $sigWpx  = (int)($cfg['signature']['signature_width'] ?? 200);
    $sealWpx = (int)($cfg['signature']['seal']['width']   ?? 240);
    $base    = __DIR__ . '/../../';

    $resolveImg = static function(string $url) use ($base): string {
        if ($url === '') return '';
        $decoded = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
        $local   = $base . 'view/assets/images/' . $decoded;
        if (file_exists($local)) return $local;
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $raw = @file_get_contents($url);
        if ($raw !== false) { file_put_contents($tmp, $raw); return $tmp; }
        return '';
    };

    $sigImgPath  = $resolveImg($sigUrl);
    $sealImgPath = $resolveImg($sealUrl);
    $sigWmm  = (float)round($sigWpx  * 0.264583) * 0.90;
    $sealWmm = (float)round($sealWpx * 0.264583);

    try {
        $pdf       = new \setasign\Fpdi\Fpdi('P', 'mm');
        $pageCount = $pdf->setSourceFile($pdfAbsPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size  = $pdf->getTemplateSize($tplId);
            $pdf->addPage(($size['width'] > $size['height']) ? 'L' : 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

            foreach ($campos as $campo) {
                if ((int)$campo['page'] !== $i) continue;
                $fontSize = isset($campo['font_size']) ? (int)$campo['font_size'] : 9;
                $pdf->SetFont('Helvetica', '', $fontSize);
                $pdf->SetTextColor(0, 0, 0);
                $cx = (float)$campo['x'];
                $cy = (float)$campo['y'];
                $cw = isset($campo['width'])  ? (float)$campo['width']  : 30;
                $ch = isset($campo['height']) ? (float)$campo['height'] : 6;
                $align = $campo['align'] ?? 'C';
                $pdf->SetXY($cx, $cy);
                $pdf->Cell($cw, $ch, $campo['default_value'], 0, 0, $align);
            }

            if ($i === $pageCount) {
                $pageW = (float)$size['width'];
                $pageH = (float)$size['height'];
                if ($sealImgPath) {
                    $info = @getimagesize($sealImgPath);
                    if ($info && $info[0] > 0) {
                        if ($selloConf && isset($selloConf['x'], $selloConf['y'])) {
                            $sealX = (float)$selloConf['x'];
                            $sealY = (float)$selloConf['y'];
                            $sealW = isset($selloConf['width']) ? (float)$selloConf['width'] : $sealWmm;
                        } else {
                            $sealW = $sealWmm;
                            $sealH = $sealW * ($info[1] / $info[0]);
                            $sealX = ($pageW - $sealW) / 2.0 + 25.0;
                            $sealY = $pageH - 62.0 - $sealH;
                        }
                        $pdf->Image($sealImgPath, $sealX, $sealY, $sealW, 0);
                    }
                }
                if ($sigImgPath) {
                    $info = @getimagesize($sigImgPath);
                    if ($info && $info[0] > 0) {
                        if ($firmaConf && isset($firmaConf['x'], $firmaConf['y'])) {
                            $sigX = (float)$firmaConf['x'];
                            $sigY = (float)$firmaConf['y'];
                            $sigW = isset($firmaConf['width']) ? (float)$firmaConf['width'] : $sigWmm;
                        } else {
                            $sigW = $sigWmm;
                            $sigX = ($pageW - $sigW) / 2.0;
                            $sigY = $pageH - 110.0 - ($sigW * ($info[1] / $info[0]));
                        }
                        $pdf->Image($sigImgPath, $sigX, $sigY, $sigW, 0);
                    }
                }
            }
        }

        $pdf->Output('F', $outputAbsPath);
        return file_exists($outputAbsPath);
    } catch (\Exception $e) {
        error_log('[stampEvalGlobal] Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Estampa firma y sello en la esquina inferior derecha usando FPDI.
 * Soporta cualquier versión de PDF con tabla xref tradicional.
 */
function stampSolicitudRegistroBotDer(string $pdfAbsPath, string $outputAbsPath): bool
{
    if (!file_exists($pdfAbsPath)) return false;

    /* ── Config firma/sello ── */
    $configPath = __DIR__ . '/../../config/carta_presentacion.json';
    $cfg = file_exists($configPath) ? (json_decode(file_get_contents($configPath), true) ?? []) : [];
    $sigUrl  = $cfg['signature']['signature_img_url']  ?? '';
    $sealUrl = $cfg['signature']['seal_img_url']        ?? '';
    $sigWpx  = (int)($cfg['signature']['signature_width'] ?? 200);
    $sealWpx = (int)($cfg['signature']['seal']['width']   ?? 180);
    $base    = __DIR__ . '/../../';

    $resolveImg = static function(string $url) use ($base): string {
        if ($url === '') return '';
        $decoded = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));
        $local   = $base . 'view/assets/images/' . $decoded;
        if (file_exists($local)) return $local;
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $raw = @file_get_contents($url);
        if ($raw !== false) { file_put_contents($tmp, $raw); return $tmp; }
        return '';
    };

    $sigImgPath  = $resolveImg($sigUrl);
    $sealImgPath = $resolveImg($sealUrl);
    if (!$sigImgPath && !$sealImgPath) {
        error_log('[stampFpdi] No se encontraron imágenes de firma/sello');
        return false;
    }

    /* ── Posicionamiento (mm) ── */
    $marginR    = 18.0;
    $marginB    = 22.0;
    $sigWmm     = round($sigWpx  * 0.264583);
    $sealWmm    = round($sealWpx * 0.264583);
    $sigWmmAdj  = $sigWmm  * 0.80;   // 20% más pequeña
    $sealWmmAdj = $sealWmm * 0.90;   // 10% más pequeña

    try {
        $pdf = new \setasign\Fpdi\Fpdi('P', 'mm');

        $pageCount = $pdf->setSourceFile($pdfAbsPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size  = $pdf->getTemplateSize($tplId);
            $orient = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->addPage($orient, [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

            // Estampar solo en la última página
            if ($i === $pageCount) {
                $pageW = (float)$size['width'];
                $pageH = (float)$size['height'];

                // ── Sello ──
                if ($sealImgPath) {
                    $info = @getimagesize($sealImgPath);
                    if ($info && $info[0] > 0) {
                        $aspect  = $info[1] / $info[0];
                        $sealHmm = $sealWmmAdj * $aspect;
                        $sealX   = $pageW - $marginR - $sealWmmAdj + 8.0;
                        $sealY   = $pageH - ($marginB - 5.0) - $sealHmm; // y desde arriba
                        $pdf->Image($sealImgPath, $sealX, $sealY, $sealWmmAdj, 0);
                    }
                }

                // ── Firma ──
                if ($sigImgPath) {
                    $info = @getimagesize($sigImgPath);
                    if ($info && $info[0] > 0) {
                        $aspect = $info[1] / $info[0];
                        $sigHmm = $sigWmmAdj * $aspect;
                        $sigX   = $pageW - $marginR - $sigWmmAdj;
                        $sigY   = $pageH - ($marginB + 32.0) - $sigHmm; // y desde arriba
                        $pdf->Image($sigImgPath, $sigX, $sigY, $sigWmmAdj, 0);
                    }
                }
            }
        }

        $pdf->Output('F', $outputAbsPath);
        return file_exists($outputAbsPath);

    } catch (\Exception $e) {
        error_log('[stampFpdi] Error: ' . $e->getMessage());
        return false;
    }
}
if (isset($_POST['search'])) {

    // ── CRIT-002: Guard con whitelist pública para páginas de inscripción ─
    // search+action combos que no requieren sesión (páginas de registro público)
    $publicSearchCombos = [
        'degrees' => [null],               // loadDegrees() en inscripcionServicio
        'student' => ['addStudent'],       // submit de inscripcionServicio
    ];
    $currentSearch = $_POST['search'] ?? '';
    $currentAction = $_POST['action'] ?? null;
    $isPublicCombo = isset($publicSearchCombos[$currentSearch])
        && in_array($currentAction, $publicSearchCombos[$currentSearch], true);

    if (!$isPublicCombo && empty($_SESSION['logged'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Sesión no iniciada']);
        exit;
    }
    // ─────────────────────────────────────────────────────────────────────

    switch ($_POST['search']) {

        case 'users':
            $user = $_POST['user'] ?? null;
            $controller = new FormsController();

            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'usersToAreas') {
                    $users = $controller->ctrUsersToAreas($_POST['idArea']);
                } elseif ($_POST['action'] === 'updateUsersToArea') {
                    $users = $controller->ctrUpdateUsersToAreas($_POST['idArea'], $_POST['idUser']);
                }
            } else {
                $users = $controller->ctrSearchUsers($user);
            }

            echo json_encode($users);
            break;

        case 'areas':
            $area = $_POST['area'] ?? null;
            $controller = new FormsController();

            if (isset($_POST['editArea'])) {
                $areas = $controller->ctrEditArea($_POST['editArea'], $_POST['nameArea']);
            } elseif (isset($_POST['deleteArea'])) {
                $areas = $controller->ctrDeleteArea($_POST['deleteArea']);
            } elseif (isset($_POST['addArea'])) {
                $areas = $controller->ctrAddArea($_POST['nameArea']);
            } else {
                $areas = $controller->ctrSearchAreas($area);
            }

            echo json_encode($areas);
            break;

        case 'event_types':
            $eventType = $_POST['eventType'] ?? null;
            $controller = new FormsController();

            if (isset($_POST['editEventType'])) {
                $eventTypes = $controller->ctrEditEventType(
                    $_POST['editEventType'],
                    $_POST['editEventTypeName'],
                    $_POST['editAreaEncargada'],
                    $_POST['editEventTypePoints'],
                    $_POST['editEventTypeBenefits'],
                    $_POST['editEventTypeService']
                );
            } elseif (isset($_POST['deleteEventType'])) {
                $eventTypes = $controller->ctrDeleteEventType($_POST['deleteEventType']);
            } elseif (isset($_POST['eventTypePoints'])) {
                $eventTypes = $controller->ctrAddEventType(
                    $_POST['eventTypeName'],
                    $_POST['eventTypePoints'],
                    $_POST['eventTypeBenefits'],
                    $_POST['areaEncargada'],
                    $_POST['eventTypeService']
                );
            } else {
                $eventTypes = $controller->ctrSearchEventTypes($eventType);
            }

            echo json_encode($eventTypes);
            break;

        case 'courses':
            $course = $_POST['idCourse'] ?? null;
            $controller = new FormsController();

            if (isset($_POST['editCourse'])) {
                $courses = $controller->ctrEditCourse($_POST['idCourse'], $_POST['nameCourse'], $_POST['startCourse'], $_POST['endCourse']);
            } elseif (isset($_POST['deleteCourse'])) {
                $courses = $controller->ctrDeleteCourse($_POST['deleteCourse']);
            } elseif (isset($_POST['addCourse'])) {
                $courses = $controller->ctrAddCourse($_POST['nameCourse'], $_POST['startCourse'], $_POST['endCourse']);
            } else {
                $courses = $controller->ctrGetCourses($course);
            }

            echo json_encode($courses);
            break;

        case 'student':
            $student = $_POST['idStudent'] ?? null;
            $controller = new FormsController();

            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'noaceptedStudents':
                        $students = $controller->ctrGetNoAceptedStudents();
                        break;
                    case 'acceptStudent':
                        $students = $controller->ctrAcceptStudent($_POST['idStudent']);
                        break;
                    case 'denegateStudent':
                        $students = $controller->ctrDenegateStudent($_POST['idStudent']);
                        break;
                    case 'noAceptedStudentsPractice':
                        $students = $controller->ctrGetNoAceptedStudentsPractice();
                        break;
                    case 'acceptStudentPractice':
                        $students = $controller->ctrAcceptStudentPractice($_POST['idStudent']);
                        break;
                    case 'denegateStudentPractice':
                        $students = $controller->ctrDenegateStudentPractice($_POST['idStudent']);
                        break;
                    case 'disable_student_Practice':
                        $students = PracticasController::ctrDisableStudentPractices($_POST['idStudent']);
                        break;
                    case 'dropStudent':
                        $students = $controller->ctrDropStudent($_POST['idStudent'], $_POST['reason']);
                        break;
                    case 'getStudent':
                        $students = $controller->ctrSearchStudents($student);
                        break;
                    case 'end social service':
                        $students = $controller->ctrEndSocialService($_POST['idStudent']);
                        break;
                    case 'addStudent':
                        $data = array_merge($_POST, [
                            'parentesco' => ($_POST['parentesco'] != 'Otro') ? $_POST['parentesco'] : $_POST['otroParentesco'],
                            'type' => $_POST['tipoPractica']
                        ]);
                        $students = $controller->ctrRegisterStudent($data);
                        break;
                    case 'editStudent':
                        $data = array_merge($_POST, [
                            'nombre' => $_POST['firstname'],
                            'apellidos' => $_POST['lastname'],
                            'idStudent' => $_POST['idStudent']
                        ]);
                        $students = $controller->ctrEditStudent($data);
                        break;
                    default:
                        $students = 'none';
                }
            } else {
                $students = $controller->ctrSearchStudents($student);
            }

            echo json_encode($students);
            break;
        case 'practices':
            $idSolicitud = $_POST['idSolicitud'] ?? null;
            $controller = new PracticasController();
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'newSolicitudesPracticantes':
                        $practices = $controller->ctrNewSolicitudPractices();
                        break;
                    case 'allSolicitudesPracticantes':
                        $practices = $controller->ctrGetAllActiveSolicitudesPracticantes();
                        break;
                    case 'acceptSolicitudPracticante':
                        $practices = $controller->ctrAcceptSolicitudPracticante($idSolicitud);
                        break;
                    case 'rejectSolicitudPracticante':
                        $motivo = trim($_POST['motivo'] ?? '');
                        $practices = $controller->ctrRejectSolicitudPracticante($idSolicitud, $motivo);
                        break;
                    default:
                        $practices = 'none';
                }
            } else {
                $practices = $controller->ctrSearchPractices($idSolicitud);
            }
            echo json_encode($practices);
            break;
        case 'organismos_externos':
            $idOrganismo = $_POST['idOrganismo'] ?? null;
            $controller = new PracticasController();
            $externals = [];
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'getNewOrganismosExternos':
                        $externals = $controller->ctrGetNewOrganismosExternos();
                        $path = __DIR__ . "/../../uploads/";

                        foreach ($externals as &$external) {
                            $dir   = rtrim($path, "/\\") . DIRECTORY_SEPARATOR . $external['id'] . DIRECTORY_SEPARATOR;
                            $paths = glob($dir . '*') ?: [];                 // todas las entradas
                            $files = array_filter($paths, 'is_file');        // sólo archivos (no carpetas)
                            $external['files'] = array_values(array_map('basename', $files));
                        }
                        unset($external); // buena práctica al usar foreach por referencia

                        break;
                    case 'acceptOrganismoExterno':
                        $externals = $controller->ctrAcceptExternal($idOrganismo);
                        break;
                    case 'rejectOrganismoExterno':
                        $externals = $controller->ctrDisableExternal($idOrganismo);
                        break;
                }
            } else {
                $externals = $controller->ctrGetExternals();
            }

            echo json_encode($externals);
            break;
        case 'degrees':
            $degree = $_POST['idDegree'] ?? null;
            $controller = new FormsController();

            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'editDegree') {
                    $data = [
                        'idDegree' => $degree,
                        'nameDegree' => $_POST['nameDegree'],
                        'minPoints' => $_POST['minPoints']
                    ];
                    $degrees = $controller->ctrEditDegree($data);
                } elseif ($_POST['action'] === 'deleteDegree') {
                    $degrees = $controller->ctrDeleteDegree($degree);
                }
            } else {
                $degrees = $controller->ctrSearchDegrees($degree);
            }

            echo json_encode($degrees);
            break;

        case 'event':
            $event = $_POST['idEvent'] ?? null;
            $controller = new FormsController();

            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'applyEvent':
                        $events = $controller->ctrApplyEvent($_POST['idEvent'], $_POST['idStudent']);
                        break;
                    case 'checkApplication':
                        $events = $controller->ctrCheckApplicationEvent($_POST['idEvent'], $_POST['idStudent']);
                        break;
                    case 'studentEvents':
                        $events = $controller->ctrStudentEvents($event);
                        break;
                    case 'lookCandidates':
                        $events = $controller->ctrEventsCandidates($event);
                        break;
                    case 'acceptCandidate':
                        $events = $controller->ctrAcceptCandidate($_POST['idStudent'], $_POST['idEvent'], $_POST['idUser'], $_POST['status']);
                        break;
                    case 'approveEvent':
                        $events = $controller->ctrApproveEvent($_POST['idStudent'], $_POST['idEvent'], $_POST['idUser'], $_POST['status']);
                        break;
                }
            } else {
                $events = $controller->ctrSearchEvents($event);
            }

            echo json_encode($events);
            break;

        case 'studentEvents':
            $student = $_POST['idStudent'] ?? null;
            $controller = new FormsController();
            echo json_encode($controller->ctrStudentEventsPoints($student));
            break;

        case 'students_history':
            $type = $_POST['type'] ?? 'universidad';
            if ($type === 'externo') {
                echo json_encode(FormsModel::mdlGetExternalStudentsWithHistory());
            } else {
                echo json_encode(FormsModel::mdlGetStudentsWithHistory());
            }
            break;

        case 'reports':
            $action = $_POST['action'];

            $controller = new PracticasController();
            switch ($action) {
                case 'getReportsPractices':
                    $response = json_encode($controller->ctrGetParcialReportsAdmin());
                    break;
                case 'acceptReportPractice':
                    $idReporteParcial = $_POST['idReporte'];
                    $response = json_encode($controller->ctrAcceptReportPracticebyAdmin($idReporteParcial));
                    break;
                case 'rejectReportPractice':
                    $idReporteParcial = $_POST['idReporte'];
                    $comentario = $_POST['comentario'];
                    $response = json_encode($controller->ctrRejectReportPracticebyAdmin($idReporteParcial, $comentario));
                    break;
                case 'acceptReportPracticeFinal':
                    $idReporteFinal = $_POST['idReporte'];
                    $response = json_encode($controller->ctrAcceptReportPracticeFinalbyAdmin($idReporteFinal));
                    break;
                case 'rejectReportPracticeFinal':
                    $idReporteFinal = $_POST['idReporte'];
                    $comentario = $_POST['comentario'];
                    $response = json_encode($controller->ctrRejectReportPracticeFinalbyAdmin($idReporteFinal, $comentario));
                    break;
                case 'getSolicitudesCapacitacion':
                    if (session_status() === PHP_SESSION_NONE) session_start();
                    $response = json_encode($controller->ctrGetSolicitudesCapacitacion(null));
                    break;
                case 'acceptSolicitudCapacitacion':
                    $idSolicitudCap = $_POST['idSolicitud'];
                    $comentario = $_POST['comentario'];
                    $response = json_encode($controller->ctrAcceptSolicitudCapacitacion($idSolicitudCap, $comentario));
                    break;
                case 'rejectSolicitudCapacitacion':
                    $idSolicitudCap = $_POST['idSolicitud'];
                    $comentario = $_POST['comentario'];
                    $response = json_encode($controller->ctrRejectSolicitudCapacitacion($idSolicitudCap, $comentario));
                    break;
                default:
                    $response = json_encode(['error' => 'Invalid action']);
                    break;
            }
            echo $response;
            break;

        // ── Incidencias de practicantes: seguimiento desde el panel del admin ──
        case 'incidencias':
            $role = $_SESSION['user']['role'] ?? '';
            if (!in_array($role, ['admin', 'admin_practicas'], true)) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                break;
            }
            $action  = $_POST['action'] ?? '';
            $adminId = (int) ($_SESSION['user']['id'] ?? 0);
            $idInc   = (int) ($_POST['idIncidencia'] ?? 0);

            switch ($action) {
                case 'getIncidencias':
                    echo json_encode(PracticasController::ctrGetIncidenciasAdmin());
                    break;

                case 'getIncidencia':
                    echo json_encode(PracticasController::ctrGetIncidenciaDetalle($idInc) ?: []);
                    break;

                case 'updateEstado':
                    $status   = (int) ($_POST['status'] ?? -1);
                    $solucion = trim((string) ($_POST['solucion'] ?? ''));
                    if ($idInc <= 0 || !in_array($status, [0, 1, 2], true)) {
                        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
                        break;
                    }
                    if ($status === 2 && mb_strlen($solucion) < 15) {
                        echo json_encode(['success' => false, 'message' => 'Describe la solución con al menos 15 caracteres.']);
                        break;
                    }
                    echo json_encode(PracticasController::ctrActualizarEstadoIncidencia(
                        $idInc,
                        $status,
                        $status === 2 ? strip_tags($solucion) : null,
                        $adminId
                    ));
                    break;

                case 'enviarMensaje':
                    $destinatario = trim((string) ($_POST['destinatario'] ?? ''));
                    $asunto       = trim((string) ($_POST['asunto'] ?? ''));
                    if ($idInc <= 0 || !in_array($destinatario, ['alumno', 'empresa', 'ambos'], true)) {
                        echo json_encode(['success' => false, 'message' => 'Selecciona a quién quieres escribir.']);
                        break;
                    }
                    if (mb_strlen($asunto) < 4 || mb_strlen($asunto) > 180) {
                        echo json_encode(['success' => false, 'message' => 'El asunto debe tener entre 4 y 180 caracteres.']);
                        break;
                    }

                    // Cada parte lleva su propio texto; con un solo destinatario
                    // se acepta el campo 'mensaje'.
                    $mensajes = [];
                    if ($destinatario === 'ambos') {
                        $mensajes['alumno']  = trim((string) ($_POST['mensaje_alumno'] ?? ''));
                        $mensajes['empresa'] = trim((string) ($_POST['mensaje_empresa'] ?? ''));
                    } else {
                        $mensajes[$destinatario] = trim((string) ($_POST['mensaje'] ?? ''));
                    }

                    $etiquetas = ['alumno' => 'para el alumno', 'empresa' => 'para la empresa'];
                    $corto = null;
                    foreach ($mensajes as $clave => $texto) {
                        if (mb_strlen($texto) < 15) {
                            $corto = $etiquetas[$clave];
                            break;
                        }
                        if (mb_strlen($texto) > 4000) {
                            $corto = null;
                            echo json_encode(['success' => false, 'message' => 'El mensaje ' . $etiquetas[$clave] . ' es demasiado largo (máximo 4000 caracteres).']);
                            break 2;
                        }
                        $mensajes[$clave] = strip_tags($texto);
                    }
                    if ($corto !== null) {
                        echo json_encode(['success' => false, 'message' => 'El mensaje ' . $corto . ' debe tener al menos 15 caracteres.']);
                        break;
                    }

                    echo json_encode(PracticasController::ctrEnviarMensajeIncidencia(
                        $idInc,
                        $destinatario,
                        strip_tags($asunto),
                        $mensajes,
                        $adminId
                    ));
                    break;

                case 'agendarJunta':
                    $modalidad = trim((string) ($_POST['modalidad'] ?? ''));
                    $fecha     = trim((string) ($_POST['fecha'] ?? ''));
                    $hora      = trim((string) ($_POST['hora'] ?? ''));
                    $url       = trim((string) ($_POST['url_sesion'] ?? ''));
                    $lugar     = trim((string) ($_POST['lugar'] ?? ''));
                    $agenda    = trim((string) ($_POST['agenda'] ?? ''));
                    $invAlumno = !empty($_POST['invita_alumno']) && $_POST['invita_alumno'] !== 'false' ? 1 : 0;
                    $invEmp    = !empty($_POST['invita_empresa']) && $_POST['invita_empresa'] !== 'false' ? 1 : 0;

                    if ($idInc <= 0 || !in_array($modalidad, ['Virtual', 'Presencial'], true)) {
                        echo json_encode(['success' => false, 'message' => 'Selecciona la modalidad de la junta.']);
                        break;
                    }
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
                        echo json_encode(['success' => false, 'message' => 'Indica una fecha y hora válidas.']);
                        break;
                    }
                    if ($modalidad === 'Virtual' && !filter_var($url, FILTER_VALIDATE_URL)) {
                        echo json_encode(['success' => false, 'message' => 'Indica el enlace de la sesión (Meet/Teams).']);
                        break;
                    }
                    if ($modalidad === 'Presencial' && $lugar === '') {
                        echo json_encode(['success' => false, 'message' => 'Indica el lugar de la junta.']);
                        break;
                    }
                    if (!$invAlumno && !$invEmp) {
                        echo json_encode(['success' => false, 'message' => 'Selecciona al menos un convocado.']);
                        break;
                    }
                    echo json_encode(PracticasController::ctrAgendarJuntaIncidencia([
                        'idIncidencia'   => $idInc,
                        'modalidad'      => $modalidad,
                        'fecha'          => $fecha,
                        'hora'           => strlen($hora) === 5 ? $hora . ':00' : $hora,
                        'url_sesion'     => $modalidad === 'Virtual' ? $url : '',
                        'lugar'          => $modalidad === 'Presencial' ? strip_tags($lugar) : '',
                        'agenda'         => strip_tags($agenda),
                        'invita_alumno'  => $invAlumno,
                        'invita_empresa' => $invEmp,
                        'created_by'     => $adminId,
                    ]));
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
                    break;
            }
            break;

        // ── Reportes del panel del administrador (Prácticas Profesionales) ──
        case 'reportes':
            $role = $_SESSION['user']['role'] ?? '';
            if (!in_array($role, ['admin', 'admin_practicas'], true)) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                break;
            }

            switch ($_POST['action'] ?? '') {
                case 'getCatalogoEmpresas':
                    echo json_encode(PracticasController::ctrGetEmpresasReporte());
                    break;

                case 'getReportePracticas':
                    echo json_encode(PracticasController::ctrGetReportePracticas([
                        'desde'       => $_POST['desde'] ?? '',
                        'hasta'       => $_POST['hasta'] ?? '',
                        'campo_fecha' => $_POST['campo_fecha'] ?? 'inicio',
                        'empresa'     => $_POST['empresa'] ?? '',
                        'estado'      => $_POST['estado'] ?? '',
                        'origen'      => $_POST['origen'] ?? '',
                        'q'           => $_POST['q'] ?? '',
                    ]));
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
                    break;
            }
            break;

        case 'servicesTypeActives':
            $controller = new FormsController();
            $response = json_encode($controller->ctrGetActiveServiceTypes());
            echo $response;
            break;

        // ── IJUMICH: gestión desde el panel de administrador ──
        case 'ijumich_requests':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['error' => 'No autorizado']);
                break;
            }
            $action    = $_POST['action'] ?? '';
            $adminId   = (int) ($_SESSION['user']['idUser'] ?? 0);
            switch ($action) {
                case 'getPendingRequests':
                    echo json_encode(ServicioModel::mdlGetSolicitudesIjumichPendientes());
                    break;
                case 'approveRequest':
                    $id         = (int) ($_POST['id'] ?? 0);
                    $comentario = $_POST['comentario'] ?? null;
                    // Obtener registro para saber tipo y archivo_path antes de aprobar
                    $registro   = ServicioModel::mdlGetSolicitudIjumichById($id);
                    $ok = ServicioModel::mdlAprobarSolicitudIjumich($id, $adminId, $comentario);
                    // Si es reporte parcial con PDF adjunto, estampar firma y sello (centrado)
                    if ($ok && $registro &&
                        in_array($registro['tipo'], ['reporte_parcial_1','reporte_parcial_2','reporte_parcial_3']) &&
                        !empty($registro['archivo_path'])) {
                        $pdfAbs = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR
                                  . str_replace('/', DIRECTORY_SEPARATOR, $registro['archivo_path']);
                        stampPdfWithFirmaYSello($pdfAbs);
                    }
                    // Si es solicitud_registro, estampar solo firma+sello en esquina inferior derecha
                    if ($ok && $registro && $registro['tipo'] === 'solicitud_registro' &&
                        !empty($registro['archivo_path'])) {
                        $pdfAbs    = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR
                                     . str_replace('/', DIRECTORY_SEPARATOR, $registro['archivo_path']);
                        $baseName  = pathinfo($pdfAbs, PATHINFO_FILENAME);
                        $ext       = pathinfo($pdfAbs, PATHINFO_EXTENSION);
                        $dir       = dirname($pdfAbs);
                        $outAbs    = $dir . DIRECTORY_SEPARATOR . $baseName . '_firmado.' . $ext;
                        if (stampSolicitudRegistroBotDer($pdfAbs, $outAbs)) {
                            $studentId      = (int)$registro['student_id'];
                            $relFirmado     = 'uploads/' . $studentId . '/' . $baseName . '_firmado.' . $ext;
                            $nombreFirmado  = $baseName . '_firmado.' . $ext;
                            ServicioModel::mdlGuardarArchivoFirmado($id, $relFirmado, $nombreFirmado);
                        }
                    }
                    // Si es evaluacion_unidad_productiva, rellenar campos, firmar y sellar
                    if ($ok && $registro && $registro['tipo'] === 'evaluacion_unidad_productiva' &&
                        !empty($registro['archivo_path'])) {
                        $pdfAbs   = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR
                                    . str_replace('/', DIRECTORY_SEPARATOR, $registro['archivo_path']);
                        $baseName = pathinfo($pdfAbs, PATHINFO_FILENAME);
                        $ext      = pathinfo($pdfAbs, PATHINFO_EXTENSION);
                        $dir      = dirname($pdfAbs);
                        $outAbs   = $dir . DIRECTORY_SEPARATOR . $baseName . '_firmado.' . $ext;
                        if (stampEvaluacionUnidadProductiva($pdfAbs, $outAbs)) {
                            $studentId     = (int)$registro['student_id'];
                            $relFirmado    = 'uploads/' . $studentId . '/' . $baseName . '_firmado.' . $ext;
                            $nombreFirmado = $baseName . '_firmado.' . $ext;
                            ServicioModel::mdlGuardarArchivoFirmado($id, $relFirmado, $nombreFirmado);
                        }
                    }
                    // Si es evaluacion_global, rellenar campos, firmar y sellar
                    if ($ok && $registro && $registro['tipo'] === 'evaluacion_global' &&
                        !empty($registro['archivo_path'])) {
                        $pdfAbs   = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR
                                    . str_replace('/', DIRECTORY_SEPARATOR, $registro['archivo_path']);
                        $baseName = pathinfo($pdfAbs, PATHINFO_FILENAME);
                        $ext      = pathinfo($pdfAbs, PATHINFO_EXTENSION);
                        $dir      = dirname($pdfAbs);
                        $outAbs   = $dir . DIRECTORY_SEPARATOR . $baseName . '_firmado.' . $ext;
                        if (stampEvaluacionGlobal($pdfAbs, $outAbs)) {
                            $studentId     = (int)$registro['student_id'];
                            $relFirmado    = 'uploads/' . $studentId . '/' . $baseName . '_firmado.' . $ext;
                            $nombreFirmado = $baseName . '_firmado.' . $ext;
                            ServicioModel::mdlGuardarArchivoFirmado($id, $relFirmado, $nombreFirmado);
                        }
                    }
                    // ── Correo al alumno por aprobación ──
                    if ($ok && $registro) {
                        $pdo2   = Conexion::conectar();
                        $stSt   = $pdo2->prepare("SELECT firstname, lastname, lastnameMom, email FROM student WHERE idStudent = :id LIMIT 1");
                        $stSt->execute([':id' => (int)$registro['student_id']]);
                        $stData       = $stSt->fetch(PDO::FETCH_ASSOC);
                        $stName       = trim(($stData['firstname'] ?? '') . ' ' . ($stData['lastname'] ?? '') . ' ' . ($stData['lastnameMom'] ?? ''));
                        $stEmail      = $stData['email'] ?? '';
                        $adminName    = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? ''));
                        $tipoReg      = $registro['tipo'] ?? '';
                        $comentStr    = $comentario ?? '';
                        if ($stEmail) {
                            switch ($tipoReg) {
                                case 'carta_presentacion':
                                    sendSsCartaPresentacionAprobada($stEmail, $stName, $comentStr);
                                    break;
                                case 'carta_practicas_interno':
                                    sendSsCartaPracticasAprobada($stEmail, $stName, $comentStr);
                                    break;
                                case 'solicitud_registro':
                                    sendSsSolicitudRegistroFirmada($stEmail, $stName, $comentStr);
                                    break;
                                case 'reporte_parcial_1':
                                    sendSsReporteParcialAprobado($stEmail, $stName, 1, $comentStr);
                                    break;
                                case 'reporte_parcial_2':
                                    sendSsReporteParcialAprobado($stEmail, $stName, 2, $comentStr);
                                    break;
                                case 'reporte_parcial_3':
                                    sendSsReporteParcialAprobado($stEmail, $stName, 3, $comentStr);
                                    break;
                                case 'carta_liberacion_interno':
                                    // Carta de liberación aprobada → notificar al alumno que puede subir Evaluación Unidad (Paso 8)
                                    break;
                                case 'evaluacion_unidad_productiva':
                                    // Paso 8 aprobado → notificar al alumno que puede subir Evaluación Global (Paso 9)
                                    break;
                                case 'evaluacion_global':
                                    // Paso 9 aprobado → Liberación definitiva del Servicio Social
                                    sendSsEvaluacionGlobalAlumno($stEmail, $stName, $adminName, $comentStr);
                                    break;
                            }
                        }
                    }
                    echo json_encode(['success' => $ok]);
                    break;
                case 'rejectRequest':
                    $id        = (int) ($_POST['id'] ?? 0);
                    $comentario = $_POST['comentario'] ?? '';
                    $regRej    = ServicioModel::mdlGetSolicitudIjumichById($id);
                    $ok = ServicioModel::mdlRechazarSolicitudIjumich($id, $adminId, $comentario);
                    // ── Correo al alumno por rechazo ──
                    if ($ok && $regRej) {
                        $pdo2 = Conexion::conectar();
                        $stSt = $pdo2->prepare("SELECT firstname, lastname, lastnameMom, email FROM student WHERE idStudent = :id LIMIT 1");
                        $stSt->execute([':id' => (int)$regRej['student_id']]);
                        $stData    = $stSt->fetch(PDO::FETCH_ASSOC);
                        $stName    = trim(($stData['firstname'] ?? '') . ' ' . ($stData['lastname'] ?? '') . ' ' . ($stData['lastnameMom'] ?? ''));
                        $stEmail   = $stData['email'] ?? '';
                        $adminName = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? ''));
                        if ($stEmail) {
                            sendSsSolicitudRechazada($stEmail, $stName, $regRej['tipo'] ?? '', $adminName, $comentario);
                        }
                    }
                    echo json_encode(['success' => $ok]);
                    break;
                default:
                    echo json_encode(['error' => 'Acción no reconocida']);
            }
            break;

        case 'AllDataEvents':
            if (session_status() === PHP_SESSION_NONE) session_start();
            // Respuesta consolidada para catálogos usados en eventos (una sola llamada)
            $controller = new FormsController();

            // Cargar datasets base (sin filtros → completo)
            $users = $controller->ctrSearchUsers(null);
            // Filtrar y modificar usuarios: quitar admins, eliminar password, agregar fullName y displayName
            $users = array_values(array_map(function($user) {
                if ($user['role'] === 'admin') {
                    return null; // Omitir administradores
                }
                unset($user['password']); // Eliminar campo password
                $user['fullName'] = $user['firstname'] . ' ' . $user['lastname'];
                $user['displayName'] = $user['firstname'] . ' ' . substr($user['lastname'], 0, 1) . '.';
                return $user;
            }, $users));
            $users = array_filter($users); // Eliminar los nulls (admins)
            $users = array_values($users); // Reindexar el array
            $areas = $controller->ctrSearchAreas(null);
            $services = $controller->ctrGetActiveServiceTypes();
            $tipoServicio = $_SESSION['user']['tipo_servicio']['idTipoSer'] ?? null;
            $event_types = $controller->ctrSearchEventTypes(null, $tipoServicio);

            // Estructura unificada
            $payload = [
                'users' => $users,
                'areas' => $areas,
                'services' => $services,
                'event_types' => $event_types,
                'user_tipo_servicio' => $_SESSION['user']['tipo_servicio'] ?? null
            ];

            echo json_encode($payload);
            break;
    }

} elseif (isset($_POST['action']) && !isset($_POST['search'])) {

    // ── CRIT-002: Acciones públicas (sin sesión) vs privadas (con sesión) ─
    $publicActions = ['checkMatricula', 'registerStudentPracticas', 'getServicesActives'];
    if (!in_array($_POST['action'], $publicActions, true) && empty($_SESSION['logged'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Sesión no iniciada']);
        exit;
    }
    // ─────────────────────────────────────────────────────────────────────

    $controller = new FormsController();
    switch ($_POST['action']) {
        case 'checkMatricula':
            $matricula = $_POST['matricula'];
            $Gescontroller = new GESController();
            echo json_encode($Gescontroller->ctrSearchStudentGES($matricula));
            break;
        case 'registerStudentPracticas':
            $data = array(
                'matricula' => $_POST['matricula'],
                'grupo' => $_POST['grupo'],
                'nombre' => $_POST['nombre'],
                'curp' => $_POST['curp'],
                'nacimiento' => $_POST['nacimiento'],
                'genero' => $_POST['genero'],
                'email' => $_POST['email'],
                'telefono' => $_POST['telefono'],
                'programa' => $_POST['programa'],
                'periodo' => $_POST['periodo'],
                'tipoPractica' => $_POST['tipoPractica']
            );
            echo json_encode($controller->ctrRegisterStudentPracticas($data));
            break;

        case 'selectServiceActive':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $serviceType = $_POST['serviceType'];
            $idStudent = $_SESSION['user']['idStudent'];
            $response = json_encode($controller->ctrSelectServiceType($serviceType, $idStudent));
            $_SESSION['user']['tipo_servicio'] = $serviceType;
            $_SESSION['user']['loginOn'] = 1;
            echo $response;
            break;

        case 'getServicesActives':
            $response = json_encode($controller->ctrGetActiveServiceTypes());
            echo $response;
            break;

        // ── IJUMICH: historial del alumno (para su propio panel) ──
        case 'get_historial_ijumich':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) {
                echo json_encode([]);
                break;
            }
            $studentId = (int) ($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            echo json_encode($studentId ? ServicioModel::mdlGetHistorialIjumichAlumno($studentId) : []);
            break;
        case 'solicitar_carta_presentacion_ijumich':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                break;
            }
            $studentId = (int) ($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) {
                echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
                break;
            }
            $data = [
                'student_id'           => $studentId,
                'nombre_organismo'     => trim($_POST['nombre_organismo']     ?? ''),
                'rfc_clave'            => trim($_POST['rfc_clave']            ?? ''),
                'area_departamento'    => trim($_POST['area_departamento']    ?? ''),
                'telefono_organismo'   => trim($_POST['telefono_organismo']   ?? ''),
                'email_organismo'      => trim($_POST['email_organismo']      ?? ''),
                'pagina_web'           => trim($_POST['pagina_web']           ?? ''),
                'calle_numero'         => trim($_POST['calle_numero']         ?? ''),
                'colonia'              => trim($_POST['colonia']              ?? ''),
                'codigo_postal'        => trim($_POST['codigo_postal']        ?? ''),
                'municipio'            => trim($_POST['municipio']            ?? ''),
                'estado'               => trim($_POST['estado']               ?? ''),
                'pais'                 => trim($_POST['pais']                 ?? 'México'),
                'estudios_responsable' => trim($_POST['estudios_responsable'] ?? ''),
                'responsable'          => trim($_POST['responsable']          ?? ''),
                'puesto_responsable'   => trim($_POST['puesto_responsable']   ?? ''),
                'telefono_responsable' => trim($_POST['telefono_responsable'] ?? ''),
                'email_responsable'    => trim($_POST['email_responsable']    ?? ''),
                'domicilio'            => trim($_POST['domicilio']            ?? ''),
                'observaciones'        => trim($_POST['observaciones']        ?? ''),
            ];
            if (empty($data['nombre_organismo']) || empty($data['responsable'])) {
                echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
                break;
            }
            $ok = ServicioModel::mdlSolicitarCartaPresentacion($data);
            // ── Correo al admin SS ──
            if ($ok) {
                $stName  = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '') . ' ' . ($_SESSION['user']['lastnameMom'] ?? ''));
                $stEmail = $_SESSION['user']['email'] ?? '';
                $stMatr  = (string) ($_SESSION['user']['matricula'] ?? '');
                sendSsCartaPresentacionAdmin($stName, $stEmail, $stMatr, $data['nombre_organismo'], $data['responsable']);
            }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Solicitud registrada' : 'Error al guardar']);
            break;

        // ── IJUMICH: carga de carta de acreditación de prácticas (alumno) ──
        case 'cargar_carta_practicas_ijumich':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                break;
            }
            $studentId = (int) ($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) {
                echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
                break;
            }
            if (empty($_FILES['carta_practicas']['tmp_name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
                break;
            }
            $file      = $_FILES['carta_practicas'];
            $maxBytes  = 5 * 1024 * 1024;
            if ($file['size'] > $maxBytes) {
                echo json_encode(['success' => false, 'message' => 'El archivo supera los 5 MB']);
                break;
            }
            $allowed = ['application/pdf','image/jpeg','image/png','image/jpg'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
                break;
            }
            $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName  = 'practicas_ijumich_' . time() . '_' . $studentId . '.' . $ext;
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . DIRECTORY_SEPARATOR . $studentId . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destPath = $uploadDir . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo en el servidor']);
                break;
            }
            $relativePath = 'uploads/' . $studentId . '/' . $safeName;
            $ok = ServicioModel::mdlCargarCartaPracticas([
                'student_id'     => $studentId,
                'archivo_path'   => $relativePath,
                'archivo_nombre' => $file['name'],
                'observaciones'  => trim($_POST['observaciones'] ?? ''),
            ]);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Carta de prácticas registrada correctamente' : 'Error al registrar']);
            break;

        // ── IJUMICH: carga de carta de liberación (alumno) ──
        case 'cargar_carta_liberacion_ijumich':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                break;
            }
            $studentId = (int) ($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) {
                echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
                break;
            }
            if (empty($_FILES['carta_liberacion']['tmp_name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
                break;
            }
            $file      = $_FILES['carta_liberacion'];
            $maxBytes  = 5 * 1024 * 1024;
            if ($file['size'] > $maxBytes) {
                echo json_encode(['success' => false, 'message' => 'El archivo supera los 5 MB']);
                break;
            }
            $allowed = ['application/pdf','image/jpeg','image/png','image/jpg'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
                break;
            }
            $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName  = 'liberacion_ijumich_' . time() . '_' . $studentId . '.' . $ext;
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . DIRECTORY_SEPARATOR . $studentId . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destPath = $uploadDir . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo en el servidor']);
                break;
            }
            $relativePath = 'uploads/' . $studentId . '/' . $safeName;
            $ok = ServicioModel::mdlCargarCartaLiberacion([
                'student_id'     => $studentId,
                'archivo_path'   => $relativePath,
                'archivo_nombre' => $file['name'],
                'observaciones'  => trim($_POST['observaciones'] ?? ''),
            ]);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Carta cargada correctamente' : 'Error al registrar']);
            break;

        // ── INTERNO: historial del alumno ──
        case 'get_historial_interno':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) { echo json_encode([]); break; }
            $studentId = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            echo json_encode($studentId ? ServicioModel::mdlGetHistorialInternoAlumno($studentId) : []);
            break;

        // ── INTERNO: carga de carta de finalización de prácticas ──
        case 'cargar_carta_practicas_interno':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']); break;
            }
            $studentId = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Sesión inválida']); break; }
            if (empty($_FILES['carta_practicas_interno']['tmp_name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']); break;
            }
            $file     = $_FILES['carta_practicas_interno'];
            $maxBytes = 5 * 1024 * 1024;
            if ($file['size'] > $maxBytes) { echo json_encode(['success' => false, 'message' => 'El archivo supera los 5 MB']); break; }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
            $allowedMimes = ['application/pdf','image/jpeg','image/png','image/jpg'];
            if (!in_array($mime, $allowedMimes)) { echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']); break; }
            $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName  = 'practicas_interno_' . time() . '_' . $studentId . '.' . $ext;
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . DIRECTORY_SEPARATOR . $studentId . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']); break;
            }
            $relativePath = 'uploads/' . $studentId . '/' . $safeName;
            $ok = ServicioModel::mdlCargarCartaPracticasInterno([
                'student_id'     => $studentId,
                'archivo_path'   => $relativePath,
                'archivo_nombre' => $file['name'],
                'observaciones'  => trim($_POST['observaciones'] ?? ''),
            ]);
            // ── Correo al admin SS ──
            if ($ok) {
                $stName  = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '') . ' ' . ($_SESSION['user']['lastnameMom'] ?? ''));
                $stEmail = $_SESSION['user']['email'] ?? '';
                sendSsCartaPracticasAdmin($stName, $stEmail, $file['name']);
            }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Documento enviado correctamente' : 'Error al registrar']);
            break;

        // ── INTERNO: carga de reporte parcial (1, 2 o 3) ──
        case 'cargar_reporte_parcial':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']); break;
            }
            $studentId = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Sesión inválida']); break; }
            $numReporte = (int)($_POST['numero_reporte'] ?? 0);
            if (!in_array($numReporte, [1, 2, 3])) {
                echo json_encode(['success' => false, 'message' => 'Número de reporte inválido']); break;
            }
            if (empty($_FILES['reporte_parcial']['tmp_name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']); break;
            }
            $file     = $_FILES['reporte_parcial'];
            $maxBytes = 5 * 1024 * 1024;
            if ($file['size'] > $maxBytes) { echo json_encode(['success' => false, 'message' => 'El archivo supera los 5 MB']); break; }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
            $allowedMimes = ['application/pdf','image/jpeg','image/png','image/jpg'];
            if (!in_array($mime, $allowedMimes)) { echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']); break; }
            $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName  = 'reporte_parcial_' . $numReporte . '_' . time() . '_' . $studentId . '.' . $ext;
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . DIRECTORY_SEPARATOR . $studentId . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']); break;
            }
            $relativePath = 'uploads/' . $studentId . '/' . $safeName;
            $ok = ServicioModel::mdlCargarReporteParcial([
                'student_id'     => $studentId,
                'numero_reporte' => $numReporte,
                'archivo_path'   => $relativePath,
                'archivo_nombre' => $file['name'],
                'observaciones'  => trim($_POST['observaciones'] ?? ''),
            ]);
            // ── Correo al admin SS ──
            if ($ok) {
                $stName  = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '') . ' ' . ($_SESSION['user']['lastnameMom'] ?? ''));
                $stEmail = $_SESSION['user']['email'] ?? '';
                sendSsReporteParcialAdmin($stName, $stEmail, $numReporte, $file['name']);
            }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Reporte enviado correctamente' : 'Error al registrar']);
            break;

        // ── INTERNO: solicitar carta de aceptación de servicio social ──
        case 'solicitar_carta_aceptacion_interno':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) { echo json_encode(['success' => false, 'message' => 'No autorizado']); break; }
            $studentId = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Sesión inválida']); break; }
            $ok = ServicioModel::mdlSolicitarCartaAceptacionServicio($studentId);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Solicitud registrada' : 'Error al registrar']);
            break;

        // ── INTERNO: carga de solicitud de registro (IJUMICH) ──
        case 'cargar_solicitud_registro':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']); break;
            }
            $studentId = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Sesión inválida']); break; }
            if (empty($_FILES['solicitud_registro']['tmp_name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']); break;
            }
            $file     = $_FILES['solicitud_registro'];
            $maxBytes = 5 * 1024 * 1024;
            if ($file['size'] > $maxBytes) { echo json_encode(['success' => false, 'message' => 'El archivo supera los 5 MB']); break; }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
            if ($mime !== 'application/pdf') { echo json_encode(['success' => false, 'message' => 'Solo se permite PDF']); break; }
            $ext       = 'pdf';
            $safeName  = 'solicitud_registro_' . time() . '_' . $studentId . '.' . $ext;
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . DIRECTORY_SEPARATOR . $studentId . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']); break;
            }
            $relativePath = 'uploads/' . $studentId . '/' . $safeName;
            $ok = ServicioModel::mdlCargarSolicitudRegistro([
                'student_id'     => $studentId,
                'archivo_path'   => $relativePath,
                'archivo_nombre' => $file['name'],
                'observaciones'  => trim($_POST['observaciones'] ?? ''),
            ]);
            // ── Correo al admin SS ──
            if ($ok) {
                $stName  = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '') . ' ' . ($_SESSION['user']['lastnameMom'] ?? ''));
                $stEmail = $_SESSION['user']['email'] ?? '';
                sendSsSolicitudRegistroAdmin($stName, $stEmail, $file['name']);
            }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Solicitud enviada correctamente' : 'Error al registrar']);
            break;

        // ── INTERNO: carga de carta de liberación ──
        case 'cargar_carta_liberacion_interno':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) { echo json_encode(['success' => false, 'message' => 'No autorizado']); break; }
            $studentId = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Sesión inválida']); break; }
            if (empty($_FILES['carta_liberacion_interno']['tmp_name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']); break;
            }
            $file     = $_FILES['carta_liberacion_interno'];
            $maxBytes = 5 * 1024 * 1024;
            if ($file['size'] > $maxBytes) { echo json_encode(['success' => false, 'message' => 'El archivo supera los 5 MB']); break; }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
            $allowedMimes = ['application/pdf','image/jpeg','image/png','image/jpg'];
            if (!in_array($mime, $allowedMimes)) { echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']); break; }
            $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName  = 'liberacion_interno_' . time() . '_' . $studentId . '.' . $ext;
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . DIRECTORY_SEPARATOR . $studentId . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']); break;
            }
            $relativePath = 'uploads/' . $studentId . '/' . $safeName;
            $ok = ServicioModel::mdlCargarCartaLiberacionInterno([
                'student_id'     => $studentId,
                'archivo_path'   => $relativePath,
                'archivo_nombre' => $file['name'],
                'observaciones'  => trim($_POST['observaciones'] ?? ''),
            ]);
            // ── Correo al admin SS ──
            if ($ok) {
                $stName  = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '') . ' ' . ($_SESSION['user']['lastnameMom'] ?? ''));
                $stEmail = $_SESSION['user']['email'] ?? '';
                sendSsCartaLiberacionAdmin($stName, $stEmail, $file['name']);
            }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Carta cargada correctamente' : 'Error al registrar']);
            break;

        // ── INTERNO: Paso 8 – carga de Evaluación de la Unidad Productiva ──
        case 'cargar_evaluacion_unidad_productiva':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) { echo json_encode(['success' => false, 'message' => 'No autorizado']); break; }
            $studentId = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Sesión inválida']); break; }
            if (empty($_FILES['evaluacion_unidad_productiva']['tmp_name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']); break;
            }
            $file     = $_FILES['evaluacion_unidad_productiva'];
            $maxBytes = 10 * 1024 * 1024;
            if ($file['size'] > $maxBytes) { echo json_encode(['success' => false, 'message' => 'El archivo supera los 10 MB']); break; }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
            if ($mime !== 'application/pdf') { echo json_encode(['success' => false, 'message' => 'Solo se permite PDF']); break; }
            $ext       = 'pdf';
            $safeName  = 'eval_unidad_' . time() . '_' . $studentId . '.' . $ext;
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . DIRECTORY_SEPARATOR . $studentId . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']); break;
            }
            $relativePath = 'uploads/' . $studentId . '/' . $safeName;
            $ok = ServicioModel::mdlCargarEvaluacionUnidadProductiva([
                'student_id'     => $studentId,
                'archivo_path'   => $relativePath,
                'archivo_nombre' => $file['name'],
                'observaciones'  => trim($_POST['observaciones'] ?? ''),
            ]);
            // ── Correo al admin SS ──
            if ($ok) {
                require_once __DIR__ . '/../emails.php';
                $stName  = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '') . ' ' . ($_SESSION['user']['lastnameMom'] ?? ''));
                $stEmail = $_SESSION['user']['email'] ?? '';
                $stMatr  = (string)($_SESSION['user']['matricula'] ?? '');
                sendSsEvaluacionUnidadProductivaAdmin($stName, $stEmail, $stMatr, $file['name']);
            }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Evaluación enviada correctamente' : 'Error al registrar']);
            break;

        case 'cargar_evaluacion_global':
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) { echo json_encode(['success' => false, 'message' => 'No autorizado']); break; }
            $studentId = (int)($_SESSION['user']['idStudent'] ?? $_SESSION['user']['idUser'] ?? 0);
            if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Sesión inválida']); break; }
            if (empty($_FILES['evaluacion_global']['tmp_name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']); break;
            }
            $file     = $_FILES['evaluacion_global'];
            $maxBytes = 10 * 1024 * 1024;
            if ($file['size'] > $maxBytes) { echo json_encode(['success' => false, 'message' => 'El archivo supera los 10 MB']); break; }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
            if ($mime !== 'application/pdf') { echo json_encode(['success' => false, 'message' => 'Solo se permite PDF']); break; }
            $safeName  = 'eval_global_' . time() . '_' . $studentId . '.pdf';
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . DIRECTORY_SEPARATOR . $studentId . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']); break;
            }
            $relativePath = 'uploads/' . $studentId . '/' . $safeName;
            $ok = ServicioModel::mdlCargarEvaluacionGlobal([
                'student_id'     => $studentId,
                'archivo_path'   => $relativePath,
                'archivo_nombre' => $file['name'],
                'observaciones'  => trim($_POST['observaciones'] ?? ''),
            ]);
            if ($ok) {
                require_once __DIR__ . '/../emails.php';
                $stName  = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '') . ' ' . ($_SESSION['user']['lastnameMom'] ?? ''));
                $stEmail = $_SESSION['user']['email'] ?? '';
                $stMatr  = (string)($_SESSION['user']['matricula'] ?? '');
                sendSsEvaluacionGlobalAdmin($stName, $stEmail, $stMatr, $file['name']);
            }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Evaluación global enviada correctamente' : 'Error al registrar']);
            break;
    }
}

if (
    isset($_POST['firstname'], $_POST['lastname'], $_POST['email'], $_POST['password'], $_POST['role'])
) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
        $controller = new FormsController();
        $controller->ctrRegisterUser();
    } else {
        echo json_encode(['error' => 'No autorizado']);
    }
}

if (isset($_POST['nombreLicenciatura'], $_POST['puntajeMinimo'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
        $data = [
            'nameDegree' => $_POST['nombreLicenciatura'],
            'minPoints' => $_POST['puntajeMinimo']
        ];
        $controller = new FormsController();
        echo $controller->ctrAddDegree($data);
    } else {
        echo json_encode(['error' => 'No autorizado']);
    }
}
