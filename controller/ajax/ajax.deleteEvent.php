<?php
require_once "../../model/forms.models.php";
require_once __DIR__ . '/../emails.php';

    $idEvent = $_POST['idEvent'];

    //Primero obtenemos los datos del evento, luego conseguimos a todos los alumnos que esten en el mismo tipo de servicio
    //y les enviamos el correo notificando que el evento ha sido eliminado
    //Al final se elimina el evento
    $event = FormsModel::mdlGetEventById($idEvent);
    $students = FormsModel::mdlGetStudentsByServiceType($event['typeService']);
    foreach ($students as $student) {
        cancelEvent("Evento Cancelado: " . $event['eventName'], $student['email'], $event['eventName'], $event['description']);
    }

    $response = FormsModel::mdlDeleteEvent($idEvent);
    echo $response;
