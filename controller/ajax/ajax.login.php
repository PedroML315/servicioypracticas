<?php
require_once "../../model/forms.models.php";
require_once "../forms.controller.php";

$user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'alumno_practicas';

if (isset($_POST['email']) && isset($_POST['password'])) {
    switch ($user_type) {
        case 'administrativo':
            $login = new FormsController();
            $login->ctrLogin();
            break;
        case 'alumno_servicio':
            $login = new FormsController();
            $login->ctrLoginStudentService();
            break;
        case 'alumno_practicas':
            $login = new PracticasController();
            $login->ctrLoginAlumnoPracticas();
            break;
        case 'organismo_externo':
            $login = new PracticasController();
            $login->ctrLoginOrganismoReceptor();
            break;
        default:
            echo "Tipo de usuario no reconocido.";
    }
}
