<?php
require_once "../../model/forms.models.php";

    $idEvent = $_POST['idEvent'];

    $response = FormsModel::mdlGetEventById($idEvent);
    echo json_encode($response);