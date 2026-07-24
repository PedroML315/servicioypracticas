<?php
/**
 * Descarga/impresión de la Carta de Presentación por el alumno.
 * FASE 6: delega en el generador reutilizable (SIN vigencia; el PDF se persiste).
 */
require_once __DIR__ . '/cartaPresentacionGenerator.php';

session_start();

$user = $_SESSION['user'] ?? [];
$studentId = (int) ($user['id'] ?? 0);
if (!$studentId) {
    http_response_code(403);
    exit('No autorizado');
}

// Ligar la carta a la vacante en proceso del alumno (si la tiene)
$enProceso = PracticasModel::mdlGetPostulacionEnProceso($studentId);
$idPractica = $enProceso ? (int) $enProceso['idPractica'] : 0;

generarCartaPresentacionPP([
    'idStudent' => $studentId,
    'idPractica' => $idPractica,
    'empresa' => $_POST['empresa'] ?? '',
    'cargoResponsable' => $_POST['cargoResponsable'] ?? '',
    'nombreResponsable' => $_POST['nombreResponsable'] ?? '',
    'domicilio' => $_POST['domicilio'] ?? '',
    'stream' => true,
]);
