<?php
declare(strict_types=1);

// ── CRIT-001 / CRIT-003 / CRIT-004: Guard de autenticación + validación ───
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
$_id_raw = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$_id_raw) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}
$_role_raw = $_POST['role'] ?? null;
$_allowedRoles = ['admin', 'teacher', 'student', 'organismo'];
if ($_role_raw !== null && !in_array($_role_raw, $_allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Rol no permitido']);
    exit;
}
// ──────────────────────────────────────────────────────────────────────────

require_once "../../model/forms.models.php";

// ===== Inputs =====
$id                = $_id_raw;
$firstname         = $_POST['firstname']        ?? null;
$lastname          = $_POST['lastname']         ?? null;
$email             = $_POST['email']            ?? null;
$role              = $_POST['role']             ?? null;
$event_permission_type = $_POST['event_permission_type'] ?? null;
$single_event_type = $_POST['single_event_type']?? null;
$event_type        = $_POST['event_type']       ?? null;
$password          = $_POST['password']         ?? "";

// ===== Update de usuario =====
$response = FormsModel::mdlUpdateUser($id, $firstname, $lastname, $email, $role);

// Si falla la actualización del usuario, cortamos aquí
if ($response !== "success") {
    echo $response;
    exit;
}

// ===== Actualizar contraseña si viene =====
if ($password !== "") {
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $response = FormsModel::mdlUpdateUserPassword($id, $password);
    // si falla la actualización de password, devolvemos de inmediato
    if ($response !== "success") {
        echo $response;
        exit;
    }
}

// ===== Decidir acción sobre tipos de evento =====
$isAdmin       = ($role === "admin");
$hasEventTypes = (isset($single_event_type) && isset($event_type));

if ($isAdmin) {
    $response = FormsModel::mdlUpdateUserTypeServices($id, $event_type, $event_permission_type);
} else {
    $response = FormsModel::mdlDeleteUserTypeServices($id);
}

echo $response;