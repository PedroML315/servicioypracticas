<?php
// ── CRIT-001: Guard de autenticación ──────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
// ──────────────────────────────────────────────────────────────────────────

require_once "../../model/forms.models.php";

$users = FormsModel::mdlGetUsers();
echo json_encode($users);