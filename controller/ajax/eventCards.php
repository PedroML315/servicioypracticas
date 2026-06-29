<?php
require_once "../../model/forms.models.php";
require_once "../forms.controller.php";

$controller = new FormsController();
$response = $controller->ctrGetEvents();

echo json_encode($response);