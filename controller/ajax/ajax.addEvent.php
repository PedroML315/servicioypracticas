<?php
require_once "../../model/forms.models.php";
require_once __DIR__ . '/../emails.php';

$eventTypeId = $_POST['eventTypeId'];
$eventName = $_POST['eventName'];
$eventUser = $_POST['eventUser'];
$date = $_POST['date'];
$location = $_POST['location'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$points = $_POST['points'];
$vacancies_available = $_POST['vacancies_available'];
$description = $_POST['description'];

$response = FormsModel::mdlAddEvent($eventTypeId, $eventName, $eventUser, $date, $location, $start_time, $end_time, $points, $vacancies_available, $description);
echo $response;
