<?php

class FormsModel
{

    // Users
    static public function mdlRegisterUser($table, $data)
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("INSERT INTO $table (firstname, lastname, email, password, role) VALUES (:firstname, :lastname, :email, :password, :role)");
        $stmt->bindParam(":firstname", $data["firstname"], PDO::PARAM_STR);
        $stmt->bindParam(":lastname", $data["lastname"], PDO::PARAM_STR);
        $stmt->bindParam(":email", $data["email"], PDO::PARAM_STR);
        $stmt->bindParam(":password", $data["password"], PDO::PARAM_STR);
        $stmt->bindParam(":role", $data["role"], PDO::PARAM_STR);

        $response = $stmt->execute();

        // Si es admin y single_event_type está en 'on', asignar el tipo de servicio
        if ($data['role'] === 'admin') {
            if (
                $response &&
                isset($data['role'], $data['single_event_type'], $data['tipo_servicio']) &&
                $data['single_event_type'] === 'on'
            ) {
                $userId = $pdo->lastInsertId();
                $stmt2 = $pdo->prepare("INSERT INTO users_to_tipo_servicio (idUser, idTipoSer) VALUES (:idUser, :idTipoSer)");
                $stmt2->bindParam(":idUser", $userId, PDO::PARAM_INT);
                $stmt2->bindParam(":idTipoSer", $data['tipo_servicio'], PDO::PARAM_INT);
                $stmt2->execute();
                $stmt2->closeCursor();
                $stmt2 = null;
            }
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetUsers()
    {
        $stmt = Conexion::conectar()->prepare("SELECT id, firstname, lastname, email, role, created_at FROM users");
        $stmt->execute();
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetUserById($id)
    {
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.role
                FROM users u 
                WHERE u.id = :id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        if ($response['role'] === 'admin') {
            $tipoServicio = self::mdlGetTipoServicioByUserId($id);
            if ($tipoServicio) {
                $response['idTipoSer'] = $tipoServicio['idTipoSer'];
                $response['nombre'] = $tipoServicio['nombre'];
                $response['type_admin'] = $tipoServicio['type_admin'];
            } else {
                $response['idTipoSer'] = null;
                $response['nombre'] = null;
                $response['type_admin'] = null;
            }
        }
        return $response;
    }

    static public function mdlGetTipoServicioByUserId($idUser)
    {
        $sql = "SELECT ts.idTipoSer, ts.nombre, adt.type_admin
                FROM admin_type adt
                LEFT JOIN users_to_tipo_servicio uts ON uts.idUser = adt.idUser
                LEFT JOIN tipo_servicio ts ON ts.idTipoSer = uts.idTipoSer AND ts.isActive = 1
                WHERE adt.idUser = :idUser";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idUser", $idUser, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlUpdateUser($id, $firstname, $lastname, $email, $role)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE users SET firstname = :firstname, lastname = :lastname, email = :email, role = :role WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":firstname", $firstname, PDO::PARAM_STR);
        $stmt->bindParam(":lastname", $lastname, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":role", $role, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlUpdateUserPassword($id, $password)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":password", $password, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlUpdateUserTypeServices($idUser, $tipoSer, $type_admin)
    {
        $pdo = Conexion::conectar();

        self::mdlDeleteAdminType($idUser);

        $stmtTypeAdmin = $pdo->prepare("INSERT INTO admin_type (idUser, type_admin) VALUES (:idUser, :type_admin)");
        $stmtTypeAdmin->bindParam(":idUser", $idUser, PDO::PARAM_INT);
        $stmtTypeAdmin->bindParam(":type_admin", $type_admin, PDO::PARAM_STR);
        $stmtTypeAdmin->execute();
        $stmtTypeAdmin->closeCursor();
        $stmtTypeAdmin = null;

        // Primero, eliminar las asociaciones existentes
        self::mdlDeleteUserTypeServices($idUser);

        if ($tipoSer) {
            // Luego, insertar las nuevas asociaciones
            $stmtInsert = $pdo->prepare("INSERT INTO users_to_tipo_servicio (idUser, idTipoSer) VALUES (:idUser, :idTipoSer)");
            $stmtInsert->bindParam(":idUser", $idUser, PDO::PARAM_INT);
            $stmtInsert->bindParam(":idTipoSer", $tipoSer, PDO::PARAM_INT);
            $stmtInsert->execute();
            $stmtInsert->closeCursor();
            $stmtInsert = null;
        }

        return "success";
    }

    static public function mdlDeleteAdminType($idUser)
    {
        $pdo = Conexion::conectar();
        // Eliminar las asociaciones existentes
        $stmtDelete = $pdo->prepare("DELETE FROM admin_type WHERE idUser = :idUser");
        $stmtDelete->bindParam(":idUser", $idUser, PDO::PARAM_INT);
        $stmtDelete->execute();
        $stmtDelete->closeCursor();
        $stmtDelete = null;
        return "success";
    }

    static public function mdlDeleteUserTypeServices($idUser)
    {
        $pdo = Conexion::conectar();
        // Eliminar las asociaciones existentes
        $stmtDelete = $pdo->prepare("DELETE FROM users_to_tipo_servicio WHERE idUser = :idUser");
        $stmtDelete->bindParam(":idUser", $idUser, PDO::PARAM_INT);
        $stmtDelete->execute();
        $stmtDelete->closeCursor();
        $stmtDelete = null;

        return "success";
    }
    static public function mdlDeleteUser($id)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlShowUser($table, $item, $value)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM $table u LEFT JOIN areas_users au ON au.idUser = u.id WHERE $item = :$item");
        $stmt->bindParam(":" . $item, $value, PDO::PARAM_STR);
        $stmt->execute();
        $response = $stmt->fetch();
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGradeStudent($data)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE students SET points = points + :points WHERE id = :id");
        $stmt->bindParam(":points", $data["points"], PDO::PARAM_INT);
        $stmt->bindParam(":id", $data["id"], PDO::PARAM_INT);
        $response = $stmt->execute();
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    // Events

    static public function mdlRegisterEvent($data)
    {
        $stmt = Conexion::conectar()->prepare("INSERT INTO events (eventName, lastname, type, points) VALUES (:eventName, :lastname, :type, :points)");
        $stmt->bindParam(":eventName", $data["eventName"], PDO::PARAM_STR);
        $stmt->bindParam(":type", $data["type"], PDO::PARAM_STR);
        $stmt->bindParam(":points", $data["points"], PDO::PARAM_INT);
        $response = $stmt->execute();
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetEvents()
    {
        $sql = "SELECT e.*, et.* FROM events e LEFT JOIN event_types et ON et.idEventType = e.eventTypeId LEFT JOIN courses c ON c.idCourse = e.idCourse";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlApplyEvent($idEvent, $idStudent)
    {
        $stmt = Conexion::conectar()->prepare("INSERT INTO students_events (idEvent, idStudent) VALUES (:idEvent, :idStudent)");
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $response = $stmt->execute();
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlCheckApplicationEvent($idEvent, $idStudent)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM students_events WHERE idEvent = :idEvent AND idStudent = :idStudent");
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlAddEvent($eventTypeId, $eventName, $idUser, $date, $location, $start_time, $end_time, $points, $vacancies_available, $description)
    {
        $stmt = Conexion::conectar()->prepare("
            INSERT INTO events 
            (eventTypeId, eventName, idUser, date, location, start_time, end_time, points, vacancies_available, description, createdAt, idCourse) 
            VALUES 
            (:eventTypeId, :eventName, :idUser, :date, :location, :start_time, :end_time, :points, :vacancies_available, :description, NOW(), (SELECT idCourse FROM courses WHERE active = 1))
        ");

        $stmt->bindParam(":eventTypeId", $eventTypeId, PDO::PARAM_INT);
        $stmt->bindParam(":eventName", $eventName, PDO::PARAM_STR);
        $stmt->bindParam(":idUser", $idUser, PDO::PARAM_INT);
        $stmt->bindParam(":date", $date, PDO::PARAM_STR);
        $stmt->bindParam(":location", $location, PDO::PARAM_STR);
        $stmt->bindParam(":start_time", $start_time, PDO::PARAM_STR);
        $stmt->bindParam(":end_time", $end_time, PDO::PARAM_STR);
        $stmt->bindParam(":points", $points, PDO::PARAM_INT);
        $stmt->bindParam(":vacancies_available", $vacancies_available, PDO::PARAM_INT);
        $stmt->bindParam(":description", $description, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
            $students = self::mdlSearchStudents(null);
            foreach ($students as $student) {
                $points = self::mdlStudentPoints($student['idStudent']);
                if ($points['totalPoints'] < $points['degreeMinPoints'] && $student['status'] == 1) {
                    $event_types = self::mdlSearchEventTypes($eventTypeId);
                    if ($event_types['typeService'] == $student['tipo_servicio']) {
                        // Asunto del correo
                        $subject = "Nuevo evento disponible para Servicio Social | " . $event_types['name'];
                        // Enviar correo
                        sendNewEvent($subject, $student['email'], $eventName, $description);
                    }
                }
            }
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }


    static public function mdlGetEventById($idEvent)
    {
        $stmt = Conexion::conectar()->prepare("SELECT e.idEvent, e.eventTypeId, e.eventName, e.date, e.location, e.start_time, e.end_time, e.points, e.vacancies_available, e.description, et.typeService FROM events e LEFT JOIN event_types et on et.idEventType = e.eventTypeId WHERE idEvent = :idEvent");
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlUpdateEvent($idEvent, $eventTypeId, $eventName, $date, $location, $start_time, $end_time, $points, $vacancies_available, $description)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE events SET eventTypeId = :eventTypeId, eventName = :eventName, date = :date, location = :location, start_time = :start_time, end_time = :end_time, points = :points, vacancies_available = :vacancies_available, description = :description WHERE idEvent = :idEvent");
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->bindParam(":eventTypeId", $eventTypeId, PDO::PARAM_INT);
        $stmt->bindParam(":eventName", $eventName, PDO::PARAM_STR);
        $stmt->bindParam(":date", $date, PDO::PARAM_STR);
        $stmt->bindParam(":location", $location, PDO::PARAM_STR);
        $stmt->bindParam(":start_time", $start_time, PDO::PARAM_STR);
        $stmt->bindParam(":end_time", $end_time, PDO::PARAM_STR);
        $stmt->bindParam(":points", $points, PDO::PARAM_INT);
        $stmt->bindParam(":vacancies_available", $vacancies_available, PDO::PARAM_INT);
        $stmt->bindParam(":description", $description, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDeleteEvent($idEvent)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM events WHERE idEvent = :idEvent");
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetStudentsByServiceType($service_type)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM student WHERE tipo_servicio = :service_type AND status = 1");
        $stmt->bindParam(":service_type", $service_type, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    // Courses
    static public function mdlGetCourses($idCourse)
    {

        if ($idCourse == null) {
            $sql = "SELECT * FROM courses";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute();
            $response = $stmt->fetchAll();
        } else {
            $sql = "SELECT * FROM courses WHERE idCourse = :idCourse";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":idCourse", $idCourse, PDO::PARAM_INT);
            $stmt->execute();
            $response = $stmt->fetch();
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlAddCourse($nameCourse, $startCourse, $endCourse)
    {
        $stmt = Conexion::conectar()->prepare("INSERT INTO courses (nameCourse, startCourse, endCourse) VALUES (:nameCourse, :startCourse, :endCourse)");

        $stmt->bindParam(":nameCourse", $nameCourse, PDO::PARAM_STR);
        $stmt->bindParam(":startCourse", $startCourse, PDO::PARAM_STR);
        $stmt->bindParam(":endCourse", $endCourse, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetCourseById($idCourse)
    {
        $stmt = Conexion::conectar()->prepare("SELECT idCourse, nameCourse, startCourse, endCourse FROM courses WHERE idCourse = :idCourse");
        $stmt->bindParam(":idCourse", $idCourse, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlUpdateCourse($idCourse, $nameCourse, $startCourse, $endCourse)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE courses SET nameCourse = :nameCourse, startCourse = :startCourse, endCourse = :endCourse WHERE idCourse = :idCourse");
        $stmt->bindParam(":idCourse", $idCourse, PDO::PARAM_INT);
        $stmt->bindParam(":nameCourse", $nameCourse, PDO::PARAM_STR);
        $stmt->bindParam(":startCourse", $startCourse, PDO::PARAM_STR);
        $stmt->bindParam(":endCourse", $endCourse, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDeleteCourse($idCourse)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM courses WHERE idCourse = :idCourse");
        $stmt->bindParam(":idCourse", $idCourse, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlActivateCourse($idCourse)
    {
        $sql = "UPDATE courses SET active = 0;";
        $sql .= "UPDATE courses SET active = 1 WHERE idCourse = :idCourse;";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idCourse", $idCourse, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlSearchAreas($idArea)
    {
        if ($idArea == null) {
            $sql = "SELECT * FROM areas";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute();
            $response = $stmt->fetchAll();
        } else {
            $sql = "SELECT * FROM areas WHERE idArea = :idArea";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":idArea", $idArea, PDO::PARAM_INT);
            $stmt->execute();
            $response = $stmt->fetch();
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlEditArea($editArea, $nameArea)
    {
        $sql = "UPDATE areas SET nameArea = :nameArea WHERE idArea = :editArea";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":editArea", $editArea, PDO::PARAM_INT);
        $stmt->bindParam(":nameArea", $nameArea, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDeleteArea($idArea)
    {
        $sql = "DELETE FROM areas WHERE idArea = :idArea";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idArea", $idArea, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlAddArea($nameArea)
    {
        $sql = "INSERT INTO areas (nameArea) VALUES (:nameArea)";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":nameArea", $nameArea, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlSearchEventTypes($idEventType, $typeService = null)
    {
        if ($idEventType == null) {
            $sql = "SELECT et.*, ts.nombre AS nombreTipoServicio, a.* FROM event_types et 
                    LEFT JOIN areas a on a.idArea = et.idArea
                    LEFT JOIN tipo_servicio ts ON ts.idTipoSer = et.typeService
                    " . ($typeService ? "WHERE et.typeService = :typeService" : "") . "
                    ORDER BY et.idEventType DESC";
            $stmt = Conexion::conectar()->prepare($sql);
            if ($typeService) {
                $stmt->bindParam(":typeService", $typeService, PDO::PARAM_INT);
            }
            $stmt->execute();
            $response = $stmt->fetchAll();
        } else {
            $sql = "SELECT * FROM event_types WHERE idEventType = :idEventType";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":idEventType", $idEventType, PDO::PARAM_INT);
            $stmt->execute();
            $response = $stmt->fetch();
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlEditEventTypes($editEventType, $name, $idArea, $pointsPerEvent, $benefitsPerYear, $typeService)
    {
        $sql = "UPDATE event_types SET name = :name, idArea = :idArea, pointsPerEvent = :pointsPerEvent, benefitsPerYear = :benefitsPerYear, typeService = :typeService WHERE idEventType = :editEventType";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":editEventType", $editEventType, PDO::PARAM_INT);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        $stmt->bindParam(":idArea", $idArea, PDO::PARAM_INT);
        $stmt->bindParam(":pointsPerEvent", $pointsPerEvent, PDO::PARAM_INT);
        $stmt->bindParam(":benefitsPerYear", $benefitsPerYear, PDO::PARAM_INT);
        $stmt->bindParam(":typeService", $typeService, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDeleteEventTypes($idEventType)
    {
        $sql = "DELETE FROM event_types WHERE idEventType = :idEventType";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idEventType", $idEventType, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlAddEventTypes($name, $pointsPerEvent, $benefitsPerYear, $idArea, $typeService)
    {
        $sql = "INSERT INTO event_types (name, pointsPerEvent, benefitsPerYear, idArea, typeService) VALUES (:name, :pointsPerEvent, :benefitsPerYear, :idArea, :typeService)";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        $stmt->bindParam(":pointsPerEvent", $pointsPerEvent, PDO::PARAM_INT);
        $stmt->bindParam(":benefitsPerYear", $benefitsPerYear, PDO::PARAM_INT);
        $stmt->bindParam(":idArea", $idArea, PDO::PARAM_INT);
        $stmt->bindParam(":typeService", $typeService, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    public static function mdlRegisterStudent($data)
    {
        try {
            // 1) Abre UNA sola conexión PDO
            $pdo = Conexion::conectar();

            // 2) Prepara la consulta con esa misma conexión
            $sql = "INSERT INTO student (
                    matricula, firstname, lastname, lastnameMom, 
                    idDegree, grado, email, phone, emergenci_phone, 
                    parent, type_lic, street, nInt, nExt, colony, cp,
                    dayBirthday, monthBirthday, yearBirthday, gender, 
                    idCourse, type, accepted, loginOn
                ) VALUES (
                    :matricula, :firstname, :lastname, :lastnameMom, 
                    :idDegree, :grado, :email, :phone, :emergenci_phone, 
                    :parent, :type_lic, :street, :nInt, :nExt, :colony, :cp,
                    :dayBirthday, :monthBirthday, :yearBirthday, :gender, 
                    (SELECT idCourse FROM courses WHERE active = 1),
                    :type, :accepted, :loginOn
                )";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':matricula', $data['matricula'], PDO::PARAM_STR);
            $stmt->bindParam(':firstname', $data['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':lastname', $data['apellidoPaterno'], PDO::PARAM_STR);
            $stmt->bindParam(':lastnameMom', $data['apellidoMaterno'], PDO::PARAM_STR);
            $stmt->bindParam(':idDegree', $data['licenciatura'], PDO::PARAM_INT);
            $stmt->bindParam(':grado', $data['grado'], PDO::PARAM_INT);
            $stmt->bindParam(':email', $data['correoInstitucional'], PDO::PARAM_STR);
            $stmt->bindParam(':phone', $data['telefonoContacto'], PDO::PARAM_STR);
            $stmt->bindParam(':emergenci_phone', $data['telefonoEmergencia'], PDO::PARAM_STR);
            $stmt->bindParam(':parent', $data['parentesco'], PDO::PARAM_STR);
            $stmt->bindParam(':type_lic', $data['tipoLicenciatura'], PDO::PARAM_STR);
            $stmt->bindParam(':street', $data['calle'], PDO::PARAM_STR);
            $stmt->bindParam(':nInt', $data['numeroInterior']);
            $stmt->bindParam(':nExt', $data['numeroExterior']);
            $stmt->bindParam(':colony', $data['colonia'], PDO::PARAM_STR);
            $stmt->bindParam(':cp', $data['codigoPostal'], PDO::PARAM_STR);
            $stmt->bindParam(':dayBirthday', $data['diaNacimiento'], PDO::PARAM_INT);
            $stmt->bindParam(':monthBirthday', $data['mesNacimiento'], PDO::PARAM_INT);
            $stmt->bindParam(':yearBirthday', $data['anioNacimiento'], PDO::PARAM_INT);
            $stmt->bindParam(':gender', $data['genero'], PDO::PARAM_INT);
            $stmt->bindParam(':type', $data['type'], PDO::PARAM_STR);
            $accepted = ($data['type'] === 'universidad') ? 0 : 1;
            $loginOn = ($data['type'] === 'universidad') ? 0 : 1;
            $stmt->bindParam(':accepted', $accepted, PDO::PARAM_INT);
            $stmt->bindParam(':loginOn', $loginOn, PDO::PARAM_INT);

            // 4) Ejecuta
            if (!$stmt->execute()) {
                // Opcional: para depurar
                throw new Exception("Error en execute(): " . implode(" | ", $stmt->errorInfo()));
            }

            // 5) Devuelve el ID recién generado sobre LA MISMA conexión
            if ($data['type'] === 'universidad') {
                return "success";
            }

            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // Aquí imprime el error real
                $errorInfo = $e->errorInfo;
                error_log("SQLSTATE: " . $errorInfo[0] . " - Código: " . $errorInfo[1] . " - Mensaje: " . $errorInfo[2]);
                return "duplicate: " . $errorInfo[2];
            }
            throw $e;
        }
    }


    public static function mdlEditStudent($data)
    {
        try {
            $stmt = Conexion::conectar()->prepare("UPDATE student SET matricula = :matricula, firstname = :firstname, lastname = :lastname, idDegree = :idDegree, grado = :grado, email = :email, phone = :phone, emergenci_phone = :emergenci_phone, parent = :parent, type_lic = :type_lic, street = :street, nInt = :nInt, nExt = :nExt, colony = :colony, cp = :cp, dayBirthday = :dayBirthday, monthBirthday = :monthBirthday, yearBirthday = :yearBirthday, gender = :gender WHERE idStudent = :idStudent");

            $stmt->bindParam(':matricula', $data['matricula'], PDO::PARAM_STR);
            $stmt->bindParam(':firstname', $data['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':lastname', $data['apellidos'], PDO::PARAM_STR);
            $stmt->bindParam(':idDegree', $data['licenciatura'], PDO::PARAM_INT);
            $stmt->bindParam(':grado', $data['grado'], PDO::PARAM_INT);
            $stmt->bindParam(':email', $data['correoInstitucional'], PDO::PARAM_STR);
            $stmt->bindParam(':phone', $data['telefonoContacto'], PDO::PARAM_STR);
            $stmt->bindParam(':emergenci_phone', $data['telefonoEmergencia'], PDO::PARAM_STR);
            $stmt->bindParam(':parent', $data['parentesco'], PDO::PARAM_STR);
            $stmt->bindParam(':type_lic', $data['tipoLicenciatura'], PDO::PARAM_STR);
            $stmt->bindParam(':street', $data['calle'], PDO::PARAM_STR);
            $stmt->bindParam(':nInt', $data['numeroInterior'], PDO::PARAM_STR);
            $stmt->bindParam(':nExt', $data['numeroExterior'], PDO::PARAM_STR);
            $stmt->bindParam(':colony', $data['colonia'], PDO::PARAM_STR);
            $stmt->bindParam(':cp', $data['codigoPostal'], PDO::PARAM_STR);
            $stmt->bindParam(':dayBirthday', $data['diaNacimiento'], PDO::PARAM_INT);
            $stmt->bindParam(':monthBirthday', $data['mesNacimiento'], PDO::PARAM_INT);
            $stmt->bindParam(':yearBirthday', $data['anioNacimiento'], PDO::PARAM_INT);
            $stmt->bindParam(':gender', $data['genero'], PDO::PARAM_INT);
            $stmt->bindParam(':idStudent', $data['idStudent'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                $response = "success";
            } else {
                $response = "error update";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Código de error para entrada duplicada
                $response = "duplicate";
            } else {
                $response = "error" . $e->getCode();
            }
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlSearchStudents($idStudent)
    {
        if ($idStudent == null) {
            $sql = "SELECT * FROM student s LEFT JOIN courses c ON c.idCourse = s.idCourse LEFT JOIN degrees d ON d.idDegree = s.idDegree WHERE s.type = 'universidad'";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute();
            $response = $stmt->fetchAll();
        } else {
            $sql = "SELECT * FROM student s LEFT JOIN courses c ON c.idCourse = s.idCourse LEFT JOIN degrees d ON d.idDegree = s.idDegree WHERE s.idStudent = :idStudent";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_STR);
            $stmt->execute();
            $response = $stmt->fetch();
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlAcceptStudent($idStudent)
    {
        $sql = "UPDATE student SET accepted = 1 WHERE idStudent = :idStudent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDenegateStudent($idStudent)
    {
        $sql = "DELETE FROM student WHERE idStudent = :idStudent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDropStudent($idStudent, $comments)
    {
        $sql = "UPDATE student SET status = 0, comments = :comments, password = '' WHERE idStudent = :idStudent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_STR);
        $stmt->bindParam(":comments", $comments, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    /**
     * Retorna todos los alumnos INTERNOS (type='universidad') activos
     * junto con un resumen JSON de sus documentos en servicio_social_ijumich.
     */
    static public function mdlGetStudentsWithHistory(): array
    {
        $pdo = Conexion::conectar();
        // carta_conclusion_servicio se guarda en cartas_conclusion_servicio (tabla separada),
        // se une via JOIN para incluirla correctamente.
        $sql = "
            SELECT
                s.idStudent, s.matricula, s.firstname, s.lastname, s.lastnameMom,
                s.email, s.phone, s.parent, s.emergenci_phone,
                s.accepted, s.status, s.type, s.grado, s.type_lic,
                d.nameDegree,
                docs_agg.documentos,
                docs_last.ultimo_tipo,
                docs_last.ultimo_status
            FROM student s
            LEFT JOIN degrees d ON d.idDegree = s.idDegree
            LEFT JOIN (
                SELECT student_id,
                    JSON_ARRAYAGG(JSON_OBJECT(
                        'tipo', tipo, 'status', status,
                        'created_at', DATE_FORMAT(created_at,'%d/%m/%Y')
                    )) AS documentos
                FROM (
                    SELECT student_id, tipo, status, created_at FROM servicio_social_ijumich
                    UNION ALL
                    SELECT student_id, 'carta_conclusion_servicio', 'aprobado', created_at FROM cartas_conclusion_servicio
                ) all_docs
                GROUP BY student_id
            ) docs_agg ON docs_agg.student_id = s.idStudent
            LEFT JOIN (
                SELECT all_docs2.student_id,
                    SUBSTRING_INDEX(GROUP_CONCAT(all_docs2.tipo ORDER BY all_docs2.created_at DESC), ',', 1) AS ultimo_tipo,
                    SUBSTRING_INDEX(GROUP_CONCAT(all_docs2.status ORDER BY all_docs2.created_at DESC), ',', 1) AS ultimo_status
                FROM (
                    SELECT student_id, tipo, status, created_at FROM servicio_social_ijumich
                    UNION ALL
                    SELECT student_id, 'carta_conclusion_servicio', 'aprobado', created_at FROM cartas_conclusion_servicio
                ) all_docs2
                GROUP BY all_docs2.student_id
            ) docs_last ON docs_last.student_id = s.idStudent
            WHERE s.type = 'universidad' AND s.status = 1
            ORDER BY s.firstname ASC, s.lastname ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos los alumnos EXTERNOS (type != 'universidad') activos
     * junto con un resumen JSON de sus documentos en servicio_social_ijumich.
     */
    static public function mdlGetExternalStudentsWithHistory(): array
    {
        $pdo = Conexion::conectar();
        $sql = "
            SELECT
                s.idStudent, s.matricula, s.firstname, s.lastname, s.lastnameMom,
                s.email, s.phone, s.parent, s.emergenci_phone,
                s.accepted, s.status, s.type, s.grado, s.type_lic,
                d.nameDegree,
                docs_agg.documentos,
                docs_last.ultimo_tipo,
                docs_last.ultimo_status
            FROM student s
            LEFT JOIN degrees d ON d.idDegree = s.idDegree
            LEFT JOIN (
                SELECT student_id,
                    JSON_ARRAYAGG(JSON_OBJECT(
                        'tipo', tipo, 'status', status,
                        'created_at', DATE_FORMAT(created_at,'%d/%m/%Y')
                    )) AS documentos
                FROM servicio_social_ijumich
                GROUP BY student_id
            ) docs_agg ON docs_agg.student_id = s.idStudent
            LEFT JOIN (
                SELECT ssi_last.student_id,
                    SUBSTRING_INDEX(GROUP_CONCAT(ssi_last.tipo ORDER BY ssi_last.created_at DESC), ',', 1) AS ultimo_tipo,
                    SUBSTRING_INDEX(GROUP_CONCAT(ssi_last.status ORDER BY ssi_last.created_at DESC), ',', 1) AS ultimo_status
                FROM servicio_social_ijumich ssi_last
                GROUP BY ssi_last.student_id
            ) docs_last ON docs_last.student_id = s.idStudent
            WHERE s.type != 'universidad' AND s.status = 1
            ORDER BY s.firstname ASC, s.lastname ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlAddPasswordStudent($cryptPass, $student)
    {
        $sql = "UPDATE student SET password = :password WHERE idStudent = :student";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":password", $cryptPass, PDO::PARAM_STR);
        $stmt->bindParam(":student", $student, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetStudent($email)
    {
        $sql = "SELECT * FROM student WHERE email = :email";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $response = $stmt->fetch();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlAddDegree($data)
    {
        $sql = "INSERT INTO degrees (nameDegree, minPoints) VALUES (:nameDegree, :minPoints);";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":nameDegree", $data["nameDegree"], PDO::PARAM_STR);
        $stmt->bindParam(":minPoints", $data["minPoints"], PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlSearchDegrees($idDegree)
    {
        if ($idDegree == null) {
            $sql = "SELECT * FROM degrees order by nameDegree asc";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute();
            $response = $stmt->fetchAll();
        } else {
            $sql = "SELECT * FROM degrees WHERE idDegree = :idDegree";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":idDegree", $idDegree, PDO::PARAM_INT);
            $stmt->execute();
            $response = $stmt->fetch();
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlSearchEvents($idEvent)
    {
        if ($idEvent == null) {
            $sql = "SELECT * FROM events ORDER BY dateEvent ASC";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute();
            $response = $stmt->fetchAll();
        } else {
            $sql = "SELECT * FROM events WHERE idEvent = :idEvent";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
            $stmt->execute();
            $response = $stmt->fetch();
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlStudentEvents($idEvent)
    {
        $sql = "SELECT SUM(1) AS students FROM students_events WHERE idEvent = :idEvent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlEventsCandidates($idEvent)
    {
        $sql = "SELECT s.idStudent, s.firstname, s.lastname, s.email, s.phone, e.*, se.* FROM students_events se LEFT JOIN student s ON s.idStudent = se.idStudent LEFT JOIN events e ON e.idEvent = se.idEvent WHERE se.idEvent = :idEvent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetchAll();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlUsersToAreas($idArea)
    {
        $sql = "SELECT 
                    u.id AS idUser, 
                    u.firstname, 
                    u.lastname, 
                    CASE 
                        WHEN (SELECT COUNT(*) FROM areas_users au WHERE au.idUser = u.id AND au.idArea = :idArea) > 0 THEN 1 
                        ELSE 0 
                    END AS pertenece
                FROM 
                    users u
                LEFT JOIN 
                    areas_users au ON u.id = au.idUser AND au.idArea = :idArea
                GROUP BY 
                    u.id;
                ";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idArea", $idArea, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetchAll();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlUpdateUsersToAreas($idArea, $idUser)
    {
        $sqlInsert = "INSERT INTO areas_users (idUser, idArea) VALUES (:idUser, :idArea) ON DUPLICATE KEY UPDATE idArea = :idArea";
        $stmtInsert = Conexion::conectar()->prepare($sqlInsert);
        $stmtInsert->bindParam(":idUser", $idUser, PDO::PARAM_INT);
        $stmtInsert->bindParam(":idArea", $idArea, PDO::PARAM_INT);

        if ($stmtInsert->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmtInsert->closeCursor();
        $stmtInsert = null;
        return $response;
    }

    static public function mdlSearchUsers($idUser)
    {
        if ($idUser == null) {
            $sql = "SELECT * FROM users ORDER BY firstname ASC";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute();
            $response = $stmt->fetchAll();
        } else {
            $sql = "SELECT * FROM users WHERE id = :idUser";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":idUser", $idUser, PDO::PARAM_INT);
            $stmt->execute();
            $response = $stmt->fetch();
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlAcceptCandidate($idStudent, $idEvent, $idUser)
    {
        $sql = "UPDATE students_events SET status = 1, idUser = :idUser, statusEvent = 1, points = 0 WHERE idStudent = :idStudent AND idEvent = :idEvent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->bindParam(":idUser", $idUser, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDeclineCandidate($idStudent, $idEvent, $idUser)
    {
        $sql = "UPDATE students_events SET status = 3, idUser = :idUser, statusEvent = 0, points = 0 WHERE idStudent = :idStudent AND idEvent = :idEvent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->bindParam(":idUser", $idUser, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetPointsEvent($idEvent)
    {
        $sql = "SELECT points FROM events WHERE idEvent = :idEvent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlApproveEvent($idStudent, $idEvent, $idUser, $points)
    {
        $sql = "UPDATE students_events 
                SET 
                    status = 2, 
                    idUser = :idUser, 
                    statusEvent = 1, 
                    points = :points, 
                    evaluationDate = DATE_SUB(NOW(), INTERVAL 6 HOUR) 
                WHERE 
                    idStudent = :idStudent 
                    AND idEvent = :idEvent;
                ";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->bindParam(":idUser", $idUser, PDO::PARAM_INT);
        $stmt->bindParam(":points", $points, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDeclineEvent($idStudent, $idEvent, $idUser)
    {
        $sql = "UPDATE students_events SET status = 3, idUser = :idUser, statusEvent = 0, points = 0 WHERE idStudent = :idStudent AND idEvent = :idEvent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->bindParam(":idEvent", $idEvent, PDO::PARAM_INT);
        $stmt->bindParam(":idUser", $idUser, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlStudentEventsPoints($idStudent)
    {
        $sql = "SELECT se.points, se.idEvent, e.eventName, 
                (SELECT d.minPoints FROM degrees d LEFT JOIN student s ON s.idDegree = d.idDegree WHERE s.idStudent = :idStudent ) as minPoints
                    FROM students_events se 
                    LEFT JOIN events e ON e.idEvent = se.idEvent 
                WHERE se.idStudent = :idStudent AND se.statusEvent = 1";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetchAll();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlStudentPoints($idStudent)
    {
        $sql = "SELECT 
                    SUM(se.points) as totalPoints,
                    (SELECT d.minPoints 
                    FROM degrees d 
                    LEFT JOIN student s ON s.idDegree = d.idDegree 
                    WHERE s.idStudent = :idStudent) as degreeMinPoints
                FROM 
                    students_events se 
                LEFT JOIN 
                    events e ON e.idEvent = se.idEvent 
                WHERE 
                    se.idStudent = :idStudent AND se.statusEvent = 1;";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlEditDegree($data)
    {
        $sql = "UPDATE degrees SET nameDegree=:nameDegree,minPoints=:minPoints WHERE idDegree = :idDegree";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":nameDegree", $data['nameDegree'], PDO::PARAM_STR);
        $stmt->bindParam(":minPoints", $data['minPoints'], PDO::PARAM_INT);
        $stmt->bindParam(":idDegree", $data['idDegree'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDeleteDegree($idDegree)
    {
        $sql = "DELETE FROM degrees WHERE idDegree = :idDegree";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idDegree", $idDegree, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetNoAceptedStudents()
    {
        $sql = "SELECT * FROM student WHERE accepted = 0 AND status = 1 AND type = 'universidad' ORDER BY firstname ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $response = $stmt->fetchAll();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetNoAceptedStudentsPractice()
    {
        $sql = "SELECT * FROM students_practicas WHERE isAcepted = 0 AND isActive = 1 ORDER BY nombre_completo ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $response = $stmt->fetchAll();

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlRegisterStudentPracticas($data)
    {
        try {
            $pdo = Conexion::conectar();

            // Verificar si la matrícula ya está registrada
            $checkSql = "SELECT COUNT(*) FROM students_practicas WHERE matricula = :matricula";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(':matricula', $data['matricula'], PDO::PARAM_STR);
            $checkStmt->execute();
            $exists = $checkStmt->fetchColumn();
            $checkStmt->closeCursor();

            if ($exists > 0) {
                return array(
                    'status' => 'duplicate',
                    'message' => 'La matrícula ya está registrada.'
                );
            }

            $sql = "INSERT INTO students_practicas(matricula, grupo, nombre_completo, curp, fecha_nacimiento, genero, email, telefono, programa_academico, periodo, tipo_practica)
        VALUES 
        ( :matricula, :grupo, :nombre_completo, :curp, :fecha_nacimiento, :genero, :email, :telefono, :programa_academico, :periodo, :tipo_practica)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':matricula', $data['matricula'], PDO::PARAM_STR);
            $stmt->bindParam(':grupo', $data['grupo'], PDO::PARAM_STR);
            $stmt->bindParam(':nombre_completo', $data['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':curp', $data['curp'], PDO::PARAM_STR);
            $stmt->bindParam(':fecha_nacimiento', $data['nacimiento'], PDO::PARAM_STR);
            $stmt->bindParam(':genero', $data['genero'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $data['telefono'], PDO::PARAM_STR);
            $stmt->bindParam(':programa_academico', $data['programa'], PDO::PARAM_STR);
            $stmt->bindParam(':periodo', $data['periodo'], PDO::PARAM_STR);
            $stmt->bindParam(':tipo_practica', $data['tipoPractica'], PDO::PARAM_STR);

            if ($stmt->execute()) {
                $response = array(
                    'status' => 'success',
                    'id' => $pdo->lastInsertId(),
                    'message' => 'Registro exitoso, le responderemos su solicitud a la brevedad por su correo electrónico.'
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'Error al registrar.'
                );
            }

            $stmt->closeCursor();
            $stmt = null;
            return $response;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return array(
                    'status' => 'duplicate',
                    'message' => 'La matrícula ya está registrada.'
                );
            }
            return array(
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
    }

    static public function mdlAcceptStudentPractice($id)
    {
        $sql = "UPDATE students_practicas SET isAcepted = 1 WHERE id = :id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDenegateStudentPractice($id)
    {
        $sql = "UPDATE students_practicas SET isAcepted = 2 WHERE id = :id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlAddPasswordStudentPractice($cryptPass, $id)
    {
        $sql = "UPDATE students_practicas SET password = :password WHERE id = :id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":password", $cryptPass, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlGetActiveServiceTypes()
    {
        $sql = "SELECT * FROM tipo_servicio WHERE isActive = 1";
        $stmt = Conexion::conectar()->prepare(query: $sql);
        $stmt->execute();
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlSelectServiceActive($serviceType, $idStudent)
    {
        $sql = "UPDATE student SET tipo_servicio = :serviceType, loginOn = 1 WHERE idStudent = :idStudent";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->bindParam(":serviceType", $serviceType, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $response = "success";
        } else {
            $response = "error";
        }
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }
}
