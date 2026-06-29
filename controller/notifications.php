<?php

header('Content-Type: application/json');

require_once "../model/forms.models.php";
require_once "../model/notifications.php";
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

switch ($_POST['action']) {
    case 'getNotifications':
        $response = Notifications::getNotifications($_SESSION['user']['id'], $_SESSION['user']['role']);
        echo json_encode($response);
        break;
    case 'markAsRead':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $ok = Notifications::markAsRead($id);
            echo json_encode(['success' => (bool) $ok]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
        }
        break;
    case 'deleteNotification':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $ok = Notifications::deleteNotification($id);
            echo json_encode(['success' => (bool) $ok]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
        }
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}