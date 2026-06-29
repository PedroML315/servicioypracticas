<?php
// ── CRIT-001 / CRIT-003: Guard de autenticación + validación de ID ─────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}
// ──────────────────────────────────────────────────────────────────────────

require_once "../../model/forms.models.php";

$response = FormsModel::mdlDeleteUser($id);
echo $response;
