<?php

require_once "../../model/forms.models.php";
require_once "../../model/PracticasModel.php";

session_status() === PHP_SESSION_NONE && session_start();

if (!isset($_SESSION['logged']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$isOrganismo = $role === 'organismo_externo';

if (!$isOrganismo) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$idOrganismo = (int) $_SESSION['user']['id'];
$action = $_POST['action'];

switch ($action) {
    case 'saveEvalIntegralEmpresa':
        $idStudent = (int)($_POST['idStudent'] ?? 0);
        $idPractica = (int)($_POST['idPractica'] ?? 0);
        $tipoHito = $_POST['tipoHito'] ?? 'intermedia';

        if (!$idStudent || !$idPractica) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            break;
        }

        $respuestas = [];
        $qCount = 12; // Empresa evalúa alumno: 12 preguntas
        for ($i = 1; $i <= $qCount; $i++) {
            if (isset($_POST["q{$i}"])) {
                $val = $_POST["q{$i}"];
                $tipo = 'likert';
                $valNum = null;
                $valTxt = null;

                if (in_array($i, [11, 12])) {
                    $tipo = 'abierta';
                    $valTxt = $val;
                } else if ($i === 9) { // Pregunta Si/No (Recomendaría...)
                    $tipo = 'likert'; // Lo guardamos numérico
                    $valNum = (int)$val;
                } else {
                    if (is_numeric($val)) {
                        $tipo = 'likert';
                        $valNum = (int)$val;
                    } else {
                        $tipo = 'abierta';
                        $valTxt = $val;
                    }
                }

                $respuestas[] = [
                    'index' => $i,
                    'texto' => "Pregunta $i",
                    'tipo' => $tipo,
                    'valorNumerico' => $valNum,
                    'valorTexto' => $valTxt
                ];
            }
        }

        $data = [
            'idStudent' => $idStudent,
            'idPractica' => $idPractica,
            'idOrganismo' => $idOrganismo,
            'tipoHito' => $tipoHito,
            'tipoEvaluador' => 'empresa',
            'respuestas' => $respuestas
        ];

        echo json_encode(PracticasModel::mdlSaveEvaluacionIntegral($data));
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
        break;
}
