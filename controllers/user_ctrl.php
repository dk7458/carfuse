<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';


header('Content-Type: application/json');

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'delete_user':
                $userId = intval($_POST['user_id']);
                if ($userId === 0 || $userId === $_SESSION['user_id']) {
                    throw new Exception("Invalid user ID.");
                }

                $targetUser = getUserDetails($conn, $userId);
                if ($targetUser['role'] === 'admin' && $_SESSION['user_role'] !== 'super_admin') {
                    throw new Exception("Only Super Admins can delete Admins.");
                }

                if (deleteUser($conn, $userId)) {
                    logAction($conn, $_SESSION['user_id'], 'delete_user', "Deleted user ID: $userId");
                    echo json_encode(['success' => 'User deleted successfully.']);
                } else {
                    throw new Exception("Failed to delete user.");
                }
                break;

            case 'update_role':
                $userId = intval($_POST['user_id']);
                $role = $_POST['role'];
                if (!in_array($role, ['user', 'admin'])) {
                    throw new Exception("Invalid role.");
                }

                if ($role === 'admin' && $_SESSION['user_role'] !== 'super_admin') {
                    throw new Exception("Only Super Admins can assign Admin roles.");
                }

                if (updateUserRole($conn, $userId, $role)) {
                    logAction($conn, $_SESSION['user_id'], 'update_role', "Updated user ID: $userId to role: $role");
                    echo json_encode(['success' => 'User role updated successfully.']);
                } else {
                    throw new Exception("Failed to update user role.");
                }
                break;

            default:
                throw new Exception("Unknown action.");
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
