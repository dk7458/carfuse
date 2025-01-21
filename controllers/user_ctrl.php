<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session_middleware.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Ensure the user has sufficient privileges
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak dostępu.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            http_response_code(400);
            throw new Exception("Nieprawidłowy token CSRF.");
        }

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'delete_user':
                $userId = intval($_POST['user_id']);
                if ($userId === 0 || $userId === $_SESSION['user_id']) {
                    throw new Exception("Nieprawidłowy identyfikator użytkownika.");
                }

                // Only allow "super_admin" to delete other admins
                $targetUser = getUserDetails($conn, $userId);
                if ($targetUser['role'] === 'admin' && $_SESSION['user_role'] !== 'super_admin') {
                    throw new Exception("Tylko Super Administrator może usuwać innych administratorów.");
                }

                if (deleteUser($conn, $userId)) {
                    logAction($_SESSION['user_id'], 'delete_user', "Deleted user ID: $userId");
                    echo json_encode(['success' => 'Użytkownik został usunięty.']);
                } else {
                    throw new Exception("Nie udało się usunąć użytkownika.");
                }
                break;

            case 'update_role':
                $userId = intval($_POST['user_id']);
                $role = $_POST['role'];
                if (!in_array($role, ['user', 'admin'])) {
                    throw new Exception("Nieprawidłowa rola.");
                }

                // Only allow "super_admin" to promote users to admin
                if ($role === 'admin' && $_SESSION['user_role'] !== 'super_admin') {
                    throw new Exception("Tylko Super Administrator może przypisać rolę Administratora.");
                }

                if (updateUserRole($conn, $userId, $role)) {
                    logAction($_SESSION['user_id'], 'update_role', "Updated user ID: $userId to role: $role");
                    echo json_encode(['success' => 'Rola użytkownika została zaktualizowana.']);
                } else {
                    throw new Exception("Nie udało się zaktualizować roli użytkownika.");
                }
                break;

            default:
                throw new Exception("Nieznana akcja.");
        }
    } else {
        throw new Exception("Nieprawidłowa metoda żądania.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
