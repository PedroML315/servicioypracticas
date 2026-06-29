<?php
require_once "../../model/forms.models.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idEvent = $_POST['idEvent'];
    $eventTypeId = $_POST['editEventTypeId'];
    $eventName = $_POST['eventName'];
    $date = $_POST['date'];
    $location = $_POST['location'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $points = $_POST['points'];
    $vacancies_available = $_POST['vacancies_available'];
    $description = $_POST['description'];

    $response = FormsModel::mdlUpdateEvent($idEvent, $eventTypeId, $eventName, $date, $location, $start_time, $end_time, $points, $vacancies_available, $description);
    echo $response;
}
