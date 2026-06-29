<?php
function includeDashboardModal() {
    include 'view/pages/modals/dashboardModal.php';
}

function includeNotifications() {
    require_once 'view/pages/navs/notifications.php';
}

function selectServicesOnLog($role, $type, $tipo_servicio) {
    if ($role === 'student' && $type === 'universidad' && $tipo_servicio === null) {
        include 'view/pages/modals/firstLogStudent.php';
    }
}

switch ($role) {
    case 'student':
        if ($type == 'universidad') {
            echo '<div class="row float-right">';
            require_once 'view/pages/servicio/dashboardEstudianteInterno.php';
            selectServicesOnLog($role, $type, $_SESSION['user']['tipo_servicio']);
            includeDashboardModal();
            echo '</div>';
        } else {
            require_once 'view/pages/servicio/dashboardEstudianteExterno.php';
        }
        break;

    case 'organismo_externo':
        require_once 'view/pages/practicas/dashboardOrganismo.php';
        break;

    case 'alumno_practicas':
        require_once 'view/pages/practicas/dashboardStudent.php';
        break;

    case 'teacher':
        echo '<div class="row float-right">';
        require_once 'view/pages/servicio/dashboardProfesor.php';
        includeDashboardModal();
        echo '</div>';
        break;

    default:
        echo '<div class="row float-right">';
        require_once 'view/pages/servicio/dashboardAdministrador.php';
        includeDashboardModal();
        includeNotifications();
        echo '</div>';
        break;
}
?>