<?php
/**
 * Carta de Presentación de Servicio Social a partir de la sesión del alumno y
 * los datos del organismo enviados por POST.
 *
 * El flujo vigente descarga la carta desde generarCartaIjumich.php (que la saca
 * del registro de la solicitud); este punto de entrada se conserva para las
 * llamadas directas con los datos del organismo. Ambos arman el documento con la
 * misma plantilla institucional.
 */
declare(strict_types=1);

require_once __DIR__ . "/../../model/forms.models.php";
require_once __DIR__ . "/../forms.controller.php";
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../servicio/cartaServicioGenerator.php";

session_start();

// === Datos dinámicos ===
$nameUR = $_POST['nameUR'] ?? '';
$responsable = $_POST['responsable'] ?? '';
$cargo = $_POST['cargoResponsable'] ?? '';
$domicilio = $_POST['domicilio'] ?? '';
$user = $_SESSION['user'] ?? [];

$studentName = strtoupper(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '') . ' ' . ($user['lastnameMom'] ?? '')));
$matricula = $user['matricula'] ?? '';
$genero = (($user['gender'] ?? 1) == 1 ? 'el' : 'la');

$degree = FormsModel::mdlSearchDegrees($user['idDegree'] ?? null);
$degreeName = 'LICENCIATURA EN ' . strtoupper($degree['nameDegree'] ?? 'DESCONOCIDA');

$folio = ServicioModel::generateFolio($user['idStudent'] ?? null);

// === PDF sobre la plantilla institucional ===
$html = construirCartaPresentacionServicioHtml([
    'studentName' => $studentName,
    'matricula'   => $matricula,
    'degreeName'  => $degreeName,
    'genero'      => $genero,
    'fecha'       => ppFechaLarga(),
    'folio'       => $folio,
    'nameUR'      => $nameUR,
    'responsable' => $responsable,
    'cargo'       => $cargo,
    'domicilio'   => $domicilio,
], ssCargarConfig(__DIR__ . '/../../config/carta_presentacion.json'));

ppRenderPdf($html)->stream("CartaPresentacion_{$matricula}.pdf", ["Attachment" => false]);
exit;
