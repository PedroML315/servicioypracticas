<?php
require_once "../../model/forms.models.php";

    $idCourse = $_POST['idCourse'];
    $nameCourse = $_POST['nameCourse'];
    $startCourse = $_POST['startCourse'];
    $endCourse = $_POST['endCourse'];

    $response = FormsModel::mdlUpdateCourse($idCourse, $nameCourse, $startCourse, $endCourse);
    echo $response;
