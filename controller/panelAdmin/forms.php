<?php

header('Content-Type: application/json');

require_once "../../model/forms.models.php";
require_once "../forms.controller.php";
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();

switch ($_POST['action']) {
    case 'getSolicitudesExternos':
        $response = PracticasController::getSolicitudesPracticas($_SESSION['user']['id']);
        echo json_encode($response);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
    
}