<?php
require_once "../../model/forms.models.php";

    $events = FormsModel::mdlGetEvents();
    echo json_encode($events);
